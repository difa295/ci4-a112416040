<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\RajaOngkirService;
use App\Models\TransactionModel;
use App\Models\TransactionDetailModel;

class TransaksiController extends BaseController
{
    protected $cart;
    protected $transactionModel;
    protected $transactionDetailModel;

    public function __construct()
    {
        // MODIFIKASI UAS: Memuat 'transaksi' helper yang telah dibuat di langkah 2
        helper(['number', 'form', 'transaksi']);
        $this->cart = service('cart');
        $this->transactionModel = new TransactionModel();
        $this->transactionDetailModel = new TransactionDetailModel(); 
    }

    public function index()
    {  
        $data = [
            'items' => $this->cart->contents(),
            'total' => $this->cart->total()
        ];

        return view('v_keranjang', $data);
    }

    public function cart_add()
    {
        $this->cart->insert([
            'id'      => $this->request->getPost('id'),
            'qty'     => 1,
            'price'   => $this->request->getPost('harga'),
            'name'    => $this->request->getPost('nama'),
            'options' => [
                'foto' => $this->request->getPost('foto')
            ]
        ]);
        
        session()->setFlashdata(
            'success',
            'Produk berhasil ditambahkan ke keranjang. 
            <a href="' . base_url('keranjang') . '">Lihat</a>'
        );
        
        return redirect()->to(base_url('/'));
    } 

    public function cart_edit()
    {
        $i = 1;
        foreach ($this->cart->contents() as $item) {
            $qty = $this->request->getPost('qty' . $i++);

            $this->cart->update([
                'rowid' => $item['rowid'],
                'qty'   => $qty
            ]);
        }

        session()->setFlashdata(
            'success',
            'Keranjang berhasil diperbarui'
        );

        return redirect()->to(base_url('keranjang'));
    }

    public function cart_delete($rowid)
    {
        $this->cart->remove($rowid);

        session()->setFlashdata(
            'success',
            'Produk berhasil dihapus dari keranjang'
        );

        return redirect()->to(base_url('keranjang'));
    }

    public function cart_clear()
    {
        $this->cart->destroy();

        session()->setFlashdata(
            'success',
            'Keranjang berhasil dikosongkan'
        );

        return redirect()->to(base_url('keranjang'));
    }

    public function checkout()
    {  
        $service = new RajaOngkirService();
        $response = $service->getDestination('semarang');
        $response2 = $service->getCost('64999','65042','1000','jne');
        $data = [
            'items'     => $this->cart->contents(),
            'total'     => $this->cart->total(),
        ];

        return view('v_checkout', $data);
    }

    public function destinations()
    {
        $search = $this->request->getGet('q'); 

        $service = new RajaOngkirService();
        $response = $service->getDestination($search);

        $results = [];
        $data = $response['data'] ?? [];

        foreach ($data as $item) {
            $results[] = [
                'id'   => $item['id'],
                'text' => $item['label']
            ];
        }
        return $this->response->setJSON([
            'results' => $results
        ]);
    }

    public function costs()
    {
        $origin = '64999';
        $destination = $this->request->getGet('destination');
        $weight = '1000';
        $courier = 'jne'; 

        $service = new RajaOngkirService();
        $response = $service->getCost($origin, $destination, $weight, $courier);

        $results = [];
        $data = $response['data'] ?? [];

        foreach ($data as $item) {
            $results[] = [
                'service'     => $item['service'],
                'description' => $item['description'],
                'cost'        => $item['cost'],
                'etd'         => $item['etd']
            ];
        }

        return $this->response->setJSON($results);
    }

    public function buy()
    { 
        // PANGGIL HELPER DI SINI AGAR TIDAK UNDEFINED FUNCTION
        helper(['transaksi']);

        $cartItems = $this->cart->contents();

        if (empty($cartItems)) {
            return redirect()->back();
        }
        
        // ... sisa kode ke bawah tetap sama ...

        $db = \Config\Database::connect();
        $db->transStart(); 

        // Total harga dihitung dari perkalian qty * price tiap item (sebelum pajak & admin)
        $subtotal = 0;
        foreach ($cartItems as $item) {
            $subtotal += $item['qty'] * $item['price'];
        }

        $ongkir = (int) $this->request->getPost('ongkir');

        // --- MODIFIKASI PERHITUNGAN BARU UAS ---
        // 1. Mengambil input kode voucher dari form checkout
        $voucher_code = $this->request->getPost('voucher_code');

        // 2. Kalkulasi komponen menggunakan fungsi dari TransaksiHelper
        $diskon_voucher = hitung_diskon_voucher($subtotal, $voucher_code);
        $ppn            = hitung_ppn($subtotal);
        $biaya_admin    = hitung_biaya_admin($subtotal);

        // 3. Menghitung Grand Total sesuai rumus lembar soal:
        // Grand Total = Subtotal - Diskon Voucher + PPN + Biaya Admin + Ongkir
        $grand_total = $subtotal - $diskon_voucher + $ppn + $biaya_admin + $ongkir;

        $transaction = [
            'username'       => $this->request->getPost('username'),
            'alamat'         => $this->request->getPost('alamat'),
            'ongkir'         => $ongkir,
            'status'         => 0, 
            // --- UPDATE FIELD BARU DI DATABASE ---
            'total_harga'    => $grand_total, // grand total akhir
            'ppn'            => $ppn,
            'biaya_admin'    => $biaya_admin,
            'voucher_code'   => !empty($voucher_code) ? strtoupper(trim($voucher_code)) : null,
            'diskon_voucher' => $diskon_voucher,
        ];

        if (!$this->transactionModel->insert($transaction)) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Gagal membuat transaksi');
        }

        $transactionId = $this->transactionModel->getInsertID();

        foreach ($cartItems as $item) {
            $this->transactionDetailModel->insert([
                'transaction_id' => $transactionId,
                'product_id'     => $item['id'],
                'jumlah'         => $item['qty'],
                'diskon'         => 0,
                'subtotal_harga' => $item['qty'] * $item['price'] 
            ]);
        }

        $db->transComplete();

        if (!$db->transStatus()) {
            return redirect()->back()->with('error', 'Gagal membuat transaksi');
        }

        $this->cart->destroy();
        return redirect()->to(base_url());
    }

    public function history()
    {
        $username = session()->get('username');

        if (!$username) {
            return redirect()->to(base_url('login'))->with('error', 'Silakan login terlebih dahulu');
        }

        $transaksi = $this->transactionModel->where('username', $username)->findAll();

        $transactionIds = [];
        foreach ($transaksi as $t) {
            $transactionIds[] = $t['id']; 
        }

        $detailTransaksi = [];
        if (!empty($transactionIds)) {
            $detailTransaksi = $this->transactionDetailModel->getProductsByTransactionIds($transactionIds);
        }

        $data = [
            'username'     => $username,
            'transactions' => $transaksi,
            'products'     => $detailTransaksi
        ];

        return view('v_history', $data);
    }
}