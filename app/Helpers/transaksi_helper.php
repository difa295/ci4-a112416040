<?php

/**
 * Helper untuk perhitungan transaksi UAS Pemrograman Web Lanjut
 * Path: app/Helpers/TransaksiHelper.php
 */

if (!function_exists('hitung_ppn')) {
    /**
     * 1. Menghitung PPN 11% dari total harga pembelian
     */
    function hitung_ppn($total_harga) {
        return 0.11 * $total_harga;
    }
}

if (!function_exists('hitung_biaya_admin')) {
    /**
     * 2. Menghitung Biaya Admin berjenjang berdasarkan total harga
     */
    function hitung_biaya_admin($total_harga) {
        if ($total_harga <= 20000000) {
            return 0.006 * $total_harga; // <= 20 Juta: 0.6%
        } elseif ($total_harga <= 40000000) {
            return 0.008 * $total_harga; // 20 Juta - 40 Juta: 0.8%
        } else {
            return 0.010 * $total_harga; // > 40 Juta: 1.0%
        }
    }
}

if (!function_exists('hitung_diskon_voucher')) {
    /**
     * 3. Menghitung Diskon Voucher berdasarkan kode voucher yang valid
     */
    function hitung_diskon_voucher($total_harga, $voucher_code) {
        // Mengubah ke huruf kapital dan menghapus spasi tak sengaja
        $code = strtoupper(trim($voucher_code));
        
        switch ($code) {
            case 'FLASH10':
                return 0.10 * $total_harga; // Diskon 10%
            case 'FLASH15':
                return 0.15 * $total_harga; // Diskon 15%
            case 'MEMBER20':
                return 0.20 * $total_harga; // Diskon 20%
            default:
                return 0; // Jika kode tidak valid, diskon = 0
        }
    }
}