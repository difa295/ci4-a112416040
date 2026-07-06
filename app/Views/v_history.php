<?= $this->extend('layout') ?>
<?= $this->section('content') ?>
History Transaksi Pembelian <strong><?= $username ?></strong>
<hr>
<div class="table-responsive">
    <table class="table datatable">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">ID Pembelian</th>
                <th scope="col">Waktu Pembelian</th>
                <th scope="col">Total Bayar</th>
                <th scope="col">Alamat</th>
                <th scope="col">Status</th>
                <th scope="col"></th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (!empty($transactions)) :
                foreach ($transactions as $index => $item) :
            ?>
                    <tr>
                        <th scope="row"><?= $index + 1 ?></th>
                        <td><?= $item['id'] ?></td>
                        <td><?= $item['created_at'] ?></td>
                        <td><?= number_to_currency($item['total_harga'] ?? 0, 'IDR') ?></td>
                        <td><?= $item['alamat'] ?></td>
                        <td>
                            <?= ($item['status'] == "1")
                                ? '<span class="badge bg-success">Sudah Selesai</span>'
                                : '<span class="badge bg-warning">Belum Selesai</span>' ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#detailModal-<?= $item['id'] ?>">
                                Detail
                            </button>
                        </td>
                    </tr> 
            <?php
                endforeach;
            endif;
            ?>
        </tbody>
    </table>
</div>

<?php if (!empty($transactions)) : ?>
    <?php foreach ($transactions as $item) : ?>
        <div class="modal fade" id="detailModal-<?= $item['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Detail Transaksi #<?= $item['id'] ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body"> 
                        <?php if (!empty($products[$item['id']])) : ?>
                            <?php foreach ($products[$item['id']] as $index2 => $item2) : ?>
                                <?= $index2 + 1 . ")" ?>
                                
                                <?php
                                $imagePath = FCPATH . 'img/' . $item2['foto'];
                                if (!empty($item2['foto']) && file_exists($imagePath)) :
                                ?>
                                    <div class="my-2">
                                        <img src="<?= base_url('img/' . $item2['foto']) ?>" width="100" class="img-thumbnail">
                                    </div>
                                <?php endif; ?>

                                <strong><?= $item2['nama'] ?></strong>
                                <?= number_to_currency($item2['harga'] ?? 0, 'IDR') ?>
                                <br>
                                <?= "(" . $item2['jumlah'] . " pcs)" ?><br>
                                <?= number_to_currency($item2['subtotal_harga'] ?? 0, 'IDR') ?>
                                <hr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Ongkir:</span>
                                <span><?= number_to_currency($item['ongkir'] ?? 0, 'IDR') ?></span>
                            </div>

                            <?php if (!empty($item['diskon_voucher']) && $item['diskon_voucher'] > 0) : ?>
                                <div class="d-flex justify-content-between mb-1 text-danger">
                                    <span>Diskon Voucher (<?= $item['voucher_code'] ?>):</span>
                                    <span>-<?= number_to_currency($item['diskon_voucher'], 'IDR') ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex justify-content-between mb-1">
                                <span>PPN (11%):</span>
                                <span><?= number_to_currency($item['ppn'] ?? 0, 'IDR') ?></span>
                            </div>

                            <div class="d-flex justify-content-between mb-1">
                                <span>Biaya Admin:</span>
                                <span><?= number_to_currency($item['biaya_admin'] ?? 0, 'IDR') ?></span>
                            </div>
                            
                            <hr class="my-2">
                            
                            <div class="d-flex justify-content-between fw-bold text-success fs-5">
                                <span>Total Bayar:</span>
                                <span><?= number_to_currency($item['total_harga'] ?? 0, 'IDR') ?></span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<?= $this->endSection() ?>