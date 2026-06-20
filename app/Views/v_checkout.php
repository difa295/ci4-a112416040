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

        <div class="col-12">
            <?= form_label('Ongkir', 'ongkir', ['class' => 'form-label']) ?>
            <?= form_input([
                'name'     => 'ongkir',
                'id'       => 'ongkir',
                'class'    => 'form-control',
                'value'    => '0',
                'readonly' => true
            ]) ?>
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
                <tr>
                    <td colspan="2"></td>
                    <td>Subtotal</td>
                    <td><span id="subtotal-val" data-subtotal="<?= $total ?>"><?= number_to_currency($total, 'IDR') ?></span></td>
                </tr>
                <tr>
                    <td colspan="2"></td>
                    <td>Total</td>
                    <td><span id="total"><?= number_to_currency($total, 'IDR') ?></span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
// Deklarasi variabel global agar bisa dibaca langsung oleh fungsi hitungTotal() tanpa parameter tambahan
var subtotal = parseInt($('#subtotal-val').data('subtotal')) || 0;
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

    // ==========================================
    // SAMA PERSIS: Event On Change Kelurahan
    // ==========================================
    $("#kelurahan").on('change', function () {
        let id_kelurahan = $(this).val();

        $("#layanan").empty().append('<option value="">- Pilih Layanan -</option>');
        ongkir = 0;
        $('#ongkir').val(ongkir);
        hitungTotal(); 

        console.log(id_kelurahan);

        // AJAX Request ke rute costs untuk mendapatkan data layanan kurir
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

    // ==========================================
    // SAMA PERSIS: Event On Change Layanan
    // ==========================================
    $("#layanan").on('change', function() {
        ongkir = parseInt($(this).val()) || 0;
        $('#ongkir').val(ongkir);
        hitungTotal();
    });

    // ==========================================
    // SAMA PERSIS: Fungsi hitungTotal()
    // ==========================================
    function hitungTotal() {
        let grandTotal = subtotal + ongkir;
        $('#total_harga').val(grandTotal);

        let formatRupiah = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(grandTotal);

        $('#total').text(formatRupiah);
    }
});
</script>
<?= $this->endSection() ?>