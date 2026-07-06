<?= $this->extend('layout') ?>
<?= $this->section('content') ?>

<div class="row">
    <div class="col-lg-6">
        <?= form_open('buy', 'class="row g-3"') ?>

        <?= form_hidden('username', session()->get('username')) ?>
        <?= form_hidden(['name' => 'total_harga', 'value' => (string)$total, 'id' => 'total_harga']) ?>

        <div class="col-12">
            <?= form_label('Nama', 'nama', ['class' => 'form-label']) ?>
            <?= form_input([
                'name'     => 'nama',
                'id'       => 'nama',
                'class'    => 'form-control',
                'value'    => session()->get('username'),
                'readonly' => true
            ]) ?>
        </div>
        
        <div class="col-12">
            <?= form_label('Alamat', 'alamat', ['class' => 'form-label']) ?>
            <?= form_input([
                'name'  => 'alamat',
                'id'    => 'alamat',
                'class' => 'form-control'
            ]) ?>
        </div> 

        <div class="col-12 mb-3"> 
            <?= form_label('Kelurahan', 'kelurahan', ['class' => 'form-label']) ?>
            <select name="kelurahan" id="kelurahan" class="form-control" style="width: 100%;">
                <option value="">Cari daerah tujuan</option>
            </select>
        </div>

        <div class="col-12 mb-3"> 
            <?= form_label('Layanan', 'layanan', ['class' => 'form-label']) ?> 
            <select name="layanan" id="layanan" class="form-control" style="width: 100%;">
                <option value="">- Pilih Layanan -</option>
            </select>
        </div>

        <div class="col-12 mb-3">
            <?= form_label('Ongkir', 'ongkir', ['class' => 'form-label']) ?>
            <?= form_input([
                'name'     => 'ongkir',
                'id'       => 'ongkir',
                'class'    => 'form-control',
                'value'    => '0',
                'readonly' => true
            ]) ?>
        </div>

        <div class="col-12 mb-3">
            <?= form_label('Kode Voucher', 'voucher_code', ['class' => 'form-label fw-bold']) ?>
            <?= form_input([
                'name'        => 'voucher_code',
                'id'          => 'voucher_code',
                'class'       => 'form-control',
                'placeholder' => 'Contoh: FLASH10'
            ]) ?>
            <small class="text-muted d-block mt-1">Tersedia: FLASH10, FLASH15, MEMBER20</small>
        </div>
        
        <div class="col-12">
            <?= form_submit(
                'submit',
                'Buat Pesanan',
                ['class' => 'btn btn-primary']
            ) ?>
        </div>

        <?= form_close() ?> 
    </div>
    
    <div class="col-lg-6">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">Nama</th>
                    <th scope="col">Harga</th>
                    <th scope="col">Jumlah</th>
                    <th scope="col">Sub Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($items)) : foreach ($items as $index => $item) : ?>
                    <tr>
                        <td><?= $item['name'] ?></td>
                        <td><?= number_to_currency($item['price'], 'IDR') ?></td>
                        <td><?= $item['qty'] ?></td>
                        <td><?= number_to_currency($item['price'] * $item['qty'], 'IDR') ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                
                <tr class="border-top">
                    <td colspan="2"></td>
                    <td class="text-secondary">Subtotal</td>
                    <td id="txt-subtotal"><?= number_to_currency($total, 'IDR') ?></td>
                </tr>
                <tr class="text-danger" id="row-voucher">
                    <td colspan="2"></td>
                    <td>Diskon Voucher</td>
                    <td>
                        <span id="txt-voucher">IDR 0</span><br>
                        <small class="text-muted" id="pct-container">(<span id="pct-voucher">0%</span>)</small>
                    </td>
                </tr>
                <tr>
                    <td colspan="2"></td>
                    <td>PPN (11%)</td>
                    <td id="txt-ppn">IDR 0</td>
                </tr>
                <tr>
                    <td colspan="2"></td>
                    <td>Biaya Admin</td>
                    <td id="txt-admin">IDR 0</td>
                </tr>
                <tr class="fw-bold text-success">
                    <td colspan="2"></td>
                    <td>Subtotal (+PPN+Admin-Voucher)</td>
                    <td id="txt-subtotal-final">IDR 0</td>
                </tr>
                <tr class="fw-bold fs-5 table-light">
                    <td colspan="2"></td>
                    <td>Grand Total (incl. Ongkir)</td>
                    <td><span id="total"><?= number_to_currency($total, 'IDR') ?></span></td>
                </tr>
            </tbody>
        </table>
        
        <span id="subtotal-val" data-subtotal="<?= $total ?>" style="display:none;"></span>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
// Ambil angka mentah asli dari server PHP, bukan parsing teks HTML
var subtotal = parseInt('<?= $total ?>') || 0;
var ongkir = 0;

$(document).ready(function() {

    // Inisialisasi Select2 Kelurahan
    $('#kelurahan').select2({
        placeholder: 'Cari daerah tujuan',
        minimumInputLength: 3, 
        width: '100%',
        ajax: {
            url: '<?= site_url('ajax/destinations') ?>',
            dataType: 'json',
            delay: 300,
            data: function(params) {
                return { q: params.term };
            },
            processResults: function(data) {
                return data;
            },
            cache: true
        }
    });

    // Inisialisasi Select2 Layanan
    $('#layanan').select2({
        placeholder: '- Pilih Layanan -',
        width: '100%'
    });

    // Event On Change Kelurahan
    $("#kelurahan").on('change', function () {
        let id_kelurahan = $(this).val();

        $("#layanan").empty().append('<option value="">- Pilih Layanan -</option>');
        ongkir = 0;
        $('#ongkir').val(ongkir);
        hitungTotal(); 

        if (id_kelurahan) {
            $.ajax({
                url: 'http://localhost:8080/ajax/costs',
                type: 'GET',
                data: { destination: id_kelurahan },
                dataType: 'json',
                success: function (response) {
                    $.each(response, function (index, item) {
                        $('#layanan').append(
                            '<option value="' + item.cost + '">' + 
                            item.service + ' (' + item.description + ') - Rp ' + 
                            new Intl.NumberFormat('id-ID').format(item.cost) + ' (' + item.etd + ' hari)' +
                            '</option>'
                        );
                    });
                }
            });
        }
    });

    // Event On Change Layanan
    $("#layanan").on('change', function() {
        ongkir = parseInt($(this).val()) || 0;
        $('#ongkir').val(ongkir);
        hitungTotal();
    });

    // PERBAIKAN: Ikat semua event input utama agar langsung kalkulasi instan
    $("#voucher_code").on('input keyup change paste propertychange', function() {
        hitungTotal();
    });

    // Formatter Rupiah yang membersihkan spasi aneh bawaan browser
    function formatKeRupiah(angka, denganMinus = false) {
        let formatted = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(Math.abs(angka));
        
        // samakan format dengan "IDR 32,697,000" tanpa spasi ganda
        formatted = formatted.replace('Rp', 'IDR ').replace(/\u00A0/g, ' ').replace(/\s+/g, ' ').trim();
        return denganMinus ? '-IDR ' + formatted.replace('IDR ', '') : formatted;
    }

    function hitungTotal() {
        let voucherCode = $("#voucher_code").val().trim().toUpperCase();

        // 1. Hitung Diskon Voucher berdasarkan input
        let persenDiskon = 0;
        if (voucherCode === 'FLASH10') {
            persenDiskon = 0.10;
        } else if (voucherCode === 'FLASH15') {
            persenDiskon = 0.15;
        } else if (voucherCode === 'MEMBER20') {
            persenDiskon = 0.20;
        }
        let diskonVoucher = persenDiskon * subtotal;

        // 2. Hitung Pajak PPN (11%)
        let ppn = 0.11 * subtotal;

        // 3. Hitung Biaya Admin Berjenjang
        let biayaAdmin = 0;
        if (subtotal <= 20000000) {
            biayaAdmin = 0.006 * subtotal;
        } else if (subtotal <= 40000000) {
            biayaAdmin = 0.008 * subtotal;
        } else {
            biayaAdmin = 0.010 * subtotal;
        }

        // 4. Kalkulasi Subtotal setelah PPN, Admin, dan Voucher
        let subtotalFinal = subtotal - diskonVoucher + ppn + biayaAdmin;
        
        // 5. Kalkulasi Grand Total Akhir (ditambah Ongkir)
        let grandTotal = subtotalFinal + ongkir;

        // Update value hidden input untuk dikirim ke backend database via Form POST
        $('#total_harga').val(grandTotal);

        // 6. Update UI Ringkasan Pesanan agar persis seperti luaran dosen
        $('#pct-voucher').text((persenDiskon * 100) + '%');
        
        if (diskonVoucher > 0) {
            $('#txt-voucher').text(formatKeRupiah(diskonVoucher, true)).addClass('text-danger');
            $('#pct-container').show();
        } else {
            $('#txt-voucher').text('IDR 0').removeClass('text-danger');
            $('#pct-container').hide();
        }
        
        $('#txt-ppn').text(formatKeRupiah(ppn));
        $('#txt-admin').text(formatKeRupiah(biayaAdmin));
        $('#txt-subtotal-final').text(formatKeRupiah(subtotalFinal));
        $('#total').text(formatKeRupiah(grandTotal));
    }

    // Jalankan kalkulasi otomatis satu kali saat halaman pertama kali dibuka
    hitungTotal();
});
</script>
<?= $this->endSection() ?>