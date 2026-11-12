# Login lewat Super App (SSO) – Perpustakaan SLiMS

Siswa, guru, dan pegawai yang **akunnya aktif di Super App Madrasah** bisa masuk ke perpustakaan
tanpa mengetik password lagi. Login biasa (username / ID Anggota + password) **tetap berfungsi**;
login lewat Super App hanya **alternatif**.

Referensi: <https://github.com/KamaludinZ/super_app_madrasah/tree/main/docs/sso> (bagian 3, SLiMS).

## Cara kerjanya (singkat)

1. Di halaman login SLiMS ada tombol **Masuk lewat Super App**. Tombol ini membuka dashboard Super App.
2. Di dashboard Super App, pengguna klik kartu **Perpustakaan**.
3. Super App membuat **tiket** (berlaku 60 detik, sekali pakai) dan mengarahkan ke
   `https://ALAMAT-PERPUS/index.php?p=superapp_sso&tiket=...`.
4. SLiMS menukar tiket itu ke Super App (dari server, memakai kunci rahasia), lalu:
   * **Pustakawan** (peran Super App `perpustakaan`/`admin` + username/email cocok di tabel `user`)
     masuk ke **halaman admin**;
   * **Anggota** (NISN / NIS / NIP / Peg ID / username cocok dengan **ID Anggota**, atau email cocok)
     masuk ke **Area Anggota**.

Siapa yang ditolak:

| Kondisi | Ditolak oleh |
| --- | --- |
| Akun Super App dinonaktifkan / aksesnya dicabut | Super App |
| Jenis pengguna bukan siswa/guru/pegawai (mis. alumni) | SLiMS (`jenis_diizinkan`) |
| Anggota masih *pending* atau masa berlakunya habis | SLiMS (`anggota_harus_aktif`) |
| Tidak ada akun pustakawan/anggota yang cocok | SLiMS |
| Tiket salah, kedaluwarsa, atau dipakai dua kali | Super App |

Semua percobaan (berhasil/gagal) tercatat di **Sistem → Log Sistem** SLiMS.

## Langkah pemasangan

### 1. Daftarkan aplikasi di Super App (admin Super App)

**Admin → Sistem & Utilitas → Aplikasi Terhubung (SSO) → Tambah aplikasi**

| Isian | Nilai |
| --- | --- |
| Kode aplikasi | `perpustakaan` |
| Nama | Perpustakaan |
| Alamat penerima tiket | `https://ALAMAT-PERPUS/index.php?p=superapp_sso` (wajib https) |
| Peran yang boleh membuka | Semua Siswa, Semua Guru, Semua Pegawai, dan `perpustakaan` |

Salin **kunci rahasia** yang muncul (hanya tampil sekali, diawali `sso_`).

### 2. Buat file konfigurasi di server SLiMS

```bash
cp config/superapp.sample.php config/superapp.php
```

Buka `config/superapp.php`, isi:

```php
'url'   => 'https://superapp.madrasah.sch.id/api/sso',   // alamat API SSO Super App
'kunci' => 'sso_xxxxxxxxxxxxxxxx',                       // kunci rahasia dari langkah 1
```

File ini **tidak ikut git** (sudah diabaikan `.gitignore`), jadi kunci tidak bocor.

> **Pakai Coolify / Docker?** Tidak perlu membuat file. Isi **Environment Variables** di Coolify:
>
> | Nama | Isi |
> | --- | --- |
> | `SUPERAPP_SSO_URL` | `https://superapp.madrasah.sch.id/api/sso` |
> | `SUPERAPP_SSO_KUNCI` | `sso_xxxxxxxxxxxxxxxx` (centang **Is Secret** bila ada) |
> | `SUPERAPP_SSO_URL_TOMBOL` | opsional, alamat yang dibuka tombol (kosong = otomatis `.../dashboard`) |
>
> Lalu **Redeploy**. `entrypoint.sh` membuat `config/superapp.php` otomatis saat container mulai.
> Di Coolify, pastikan **Branch** = `main` (bukan `v9.8.0`, itu nama tag di repo resmi SLiMS).

### 3. Aktifkan plugin

Login SLiMS sebagai admin → **Sistem → Plugin** → aktifkan **Login lewat Super App**.
Setelah itu tombol **Masuk lewat Super App** otomatis muncul di halaman login.
Tombol tidak muncul bila plugin belum aktif atau `url`/`kunci` belum diisi.

### 4. Pastikan ID Anggota cocok dengan data Super App

Anggota dicocokkan lewat **ID Anggota** (`member_id`). Agar siswa/guru/pegawai bisa masuk:

* siswa: ID Anggota = **NISN** (atau NIS);
* guru/pegawai ASN: ID Anggota = **NIP**;
* pegawai non-ASN: ID Anggota = **Peg ID**;
* atau email anggota sama dengan email di Super App (email harus unik).

Untuk pustakawan: username (atau email) akun SLiMS harus sama dengan di Super App, dan di Super App
pengguna itu punya peran `perpustakaan` atau `admin`.

### 5. Uji koneksi dari server SLiMS

```bash
curl -H "Authorization: Bearer sso_xxxxxxxxxxxxxxxx" https://superapp.madrasah.sch.id/api/sso/cek
```

Hasil yang benar: `{"aplikasi":"perpustakaan","nama":"Perpustakaan","umur_tiket_detik":60}`.

## Pengaturan tambahan (`config/superapp.php`)

| Kunci | Bawaan | Fungsi |
| --- | --- | --- |
| `tombol_login` | `true` | Tampilkan tombol di halaman login |
| `url_tombol` | `''` | Alamat yang dibuka tombol. Kosong = otomatis `.../dashboard` dari `url` |
| `peran_pustakawan` | `['perpustakaan','admin']` | Peran Super App yang boleh masuk sebagai pustakawan |
| `pustakawan` / `anggota` | lihat file | Urutan pencocokan data Super App → kolom SLiMS |
| `jenis_diizinkan` | `['siswa','guru','pegawai']` | Jenis pengguna Super App yang boleh masuk |
| `anggota_harus_aktif` | `true` | Tolak anggota *pending* / kedaluwarsa |
| `timeout` | `10` | Batas waktu menghubungi Super App (detik) |

## Berkas yang terlibat

* `plugins/superapp_sso/` – plugin penerima tiket (`masuk.php`, `MetodeSuperApp.php`)
* `config/superapp.sample.php` – contoh konfigurasi
* `lib/contents/login.inc.php` – tombol **Masuk lewat Super App** di halaman login
