<?php
/**
 * Login lewat Super App (SSO tiket) untuk SLiMS 9.
 * Salin file ini menjadi config/superapp.php (nama file TANPA garis bawah: SLiMS mengabaikan config/*_*.php),
 * lalu isi 'url' dan 'kunci'. File config/superapp.php tidak ikut git (sudah di-.gitignore).
 * Dibaca plugin plugins/superapp_sso dan tombol di halaman login lewat config('superapp.sso....').
 * Panduan lengkap: docs/SSO_SUPERAPP.md
 */
return [
    'sso' => [
        // Alamat API SSO Super App (lihat Admin > Aplikasi Terhubung di Super App).
        'url' => 'https://superapp.madrasah.sch.id/api/sso',

        // Kunci rahasia aplikasi "perpustakaan" (diawali "sso_"), ditampilkan sekali saat aplikasi didaftarkan.
        // Jangan simpan file ini di git publik.
        'kunci' => '',

        // Tombol "Masuk lewat Super App" di halaman login SLiMS (index.php?p=login).
        // Login biasa (username/ID anggota + password) tetap bisa dipakai; tombol ini hanya alternatif.
        'tombol_login' => true,

        // Alamat yang dibuka tombol tersebut: dashboard Super App, tempat pengguna mengklik kartu "Perpustakaan".
        // Kosongkan ('') agar dibuat otomatis dari 'url' (mis. https://superapp.../api/sso -> https://superapp.../dashboard).
        'url_tombol' => '',

        // Pencocokan akun PUSTAKAWAN (tabel `user`): data Super App (kiri) => kolom tabel user (kanan), dicoba berurutan.
        'pustakawan' => [
            'username' => 'username',
            'email' => 'email',
        ],

        // Hanya pengguna Super App dengan salah satu peran ini yang boleh masuk sebagai PUSTAKAWAN (ke halaman admin).
        // Kosongkan ([]) agar siapa pun yang punya akun pustakawan yang cocok bisa masuk sebagai pustakawan.
        'peran_pustakawan' => ['perpustakaan', 'admin'],

        // Pencocokan ANGGOTA (tabel `member`): data Super App (kiri) => kolom tabel member (kanan), dicoba berurutan.
        // Umumnya ID anggota (member_id) siswa = NISN/NIS, guru = NIP, pegawai non-ASN = Peg ID.
        'anggota' => [
            'nisn' => 'member_id',
            'nis' => 'member_id',
            'nip' => 'member_id',
            'peg_id' => 'member_id',
            'username' => 'member_id',
            'email' => 'member_email',
        ],

        // Jenis pengguna Super App yang boleh masuk (siswa | guru | pegawai | alumni). Pengaman kedua; hak akses utama
        // tetap diatur admin Super App.
        'jenis_diizinkan' => ['siswa', 'guru', 'pegawai'],

        // Hanya ANGGOTA yang keanggotaannya aktif yang boleh masuk lewat Super App:
        // tidak berstatus "pending" dan masa berlakunya belum habis. Ubah ke false bila anggota yang
        // kedaluwarsa tetap boleh masuk (mis. untuk melihat riwayat pinjaman).
        // Catatan: akun Super App yang dinonaktifkan selalu ditolak oleh Super App sendiri.
        'anggota_harus_aktif' => true,

        // Batas waktu menghubungi Super App (detik).
        'timeout' => 10,
    ],
];
