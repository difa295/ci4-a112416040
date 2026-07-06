<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPpnBiayaAdminVoucherToTransactionTable extends Migration
{
    public function up()
    {
        $fields = [
            'ppn' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'biaya_admin' => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'voucher_code' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'null'       => true,
            ],
            'diskon_voucher' => [
                'type' => 'DOUBLE',
                'null'       => true,
            ],
        ];

        // Menambahkan kolom baru ke tabel transaction
        $this->forge->addColumn('transaction', $fields);
    }

    public function down()
    {
        // Untuk rollback jika ingin membatalkan perubahan
        $this->forge->dropColumn('transaction', ['ppn', 'biaya_admin', 'voucher_code', 'diskon_voucher']);
    }
}