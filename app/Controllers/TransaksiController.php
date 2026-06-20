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
        helper(['number', 'form']);
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
        $cartItems = $this->cart->contents();

        if (empty($cartItems)) {
            return redirect()->back();
        }

        $db = \Config\Database::connect();
        $db->transStart(); 

        $subtotal = 0;
        foreach ($cartItems as $item) {
            $subtotal += $item['qty'] * $item['price'];
        }

        $ongkir = (int) $this->request->getPost('ongkir');

        $transaction = [
            'username'    => $this->request->getPost('username'),
            'alamat'      => $this->request->getPost('alamat'),
            'ongkir'      => $ongkir,
            'total_harga' => $subtotal + $ongkir,
            'status'      => 0, 
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

    /**
     * FUNGSI BARU SESUAI INSTRUKSI DOSEN
     * Menampilkan riwayat transaksi milik user yang sedang login
     */
  public function history()
{
    // 1. Ambil variable username dari session yang tersimpan
    $username = session()->get('username');

    // Jika user belum login, tendang balik ke halaman login atau home
    if (!$username) {
        return redirect()->to(base_url('login'))->with('error', 'Silakan login terlebih dahulu');
    }

    // 2. Ambil semua data transaksi berdasarkan username dari transactionModel
    $transaksi = $this->transactionModel->where('username', $username)->findAll();

    // 3. Tampung semua id transaksinya pada sebuah variable array
    $transactionIds = [];
    foreach ($transaksi as $t) {
        $transactionIds[] = $t['id']; 
    }

    // 4. Ambil semua data detail transaksi melalui fungsi model (jika ada transaksi)
    $detailTransaksi = [];
    if (!empty($transactionIds)) {
        $detailTransaksi = $this->transactionDetailModel->getProductsByTransactionIds($transactionIds);
    }

    // 5. SINKRONISASI 100%: Mengubah key array agar pas dengan variabel di v_history.php
    $data = [
        'username'     => $username,
        'transactions' => $transaksi,       // Mengikuti nama di view ($transactions)
        'products'     => $detailTransaksi  // Mengikuti nama di view ($products)
    ];

    return view('v_history', $data);
}
}