<?php
/**
 * Tata letak halaman error (403, 404, 500, 503, dll.) yang serasi dengan tema "default" SLiMS:
 * header bergambar (slide1.jpg + lapisan gelap), kartu putih yang menumpang di header seperti
 * kotak pencarian OPAC, tombol Bootstrap, dan footer abu gelap (#3d4852).
 *
 * Sengaja TIDAK bergantung pada sysconfig/database, supaya tetap tampil walau aplikasi sedang error.
 * Dipakai oleh:
 *   - template/httperror.php      (ErrorDocument Apache di .htaccess)
 *   - template/errorproduction.php (error PHP saat mode production)
 */

if (!function_exists('slims_error_info')) {
    /**
     * Judul, pesan, dan ikon bawaan per kode HTTP.
     */
    function slims_error_info(int $code): array
    {
        $daftar = [
            400 => ['Permintaan Tidak Valid', 'Permintaan yang dikirim ke server tidak dapat diproses. Periksa kembali alamat atau isian Anda.', 'fa-exclamation-circle'],
            401 => ['Perlu Masuk Terlebih Dahulu', 'Halaman ini hanya bisa dibuka setelah Anda masuk (login).', 'fa-lock'],
            403 => ['Akses Ditolak', 'Anda tidak memiliki izin untuk membuka halaman ini. Jika menurut Anda ini keliru, silakan hubungi petugas perpustakaan.', 'fa-lock'],
            404 => ['Halaman Tidak Ditemukan', 'Halaman yang Anda cari mungkin sudah dipindahkan, dihapus, atau alamatnya salah ketik.', 'fa-map-signs'],
            405 => ['Metode Tidak Diizinkan', 'Cara permintaan ini tidak didukung oleh halaman yang dituju.', 'fa-ban'],
            408 => ['Waktu Permintaan Habis', 'Server menunggu terlalu lama. Silakan muat ulang halaman.', 'fa-hourglass-end'],
            429 => ['Terlalu Banyak Permintaan', 'Anda mengirim terlalu banyak permintaan dalam waktu singkat. Tunggu sebentar lalu coba lagi.', 'fa-tachometer-alt'],
            500 => ['Terjadi Gangguan pada Sistem', 'Maaf, sistem perpustakaan sedang mengalami gangguan. Silakan coba beberapa saat lagi. Jika masalah berlanjut, hubungi petugas perpustakaan.', 'fa-exclamation-triangle'],
            502 => ['Server Tidak Merespons', 'Server perpustakaan sedang tidak dapat dihubungi. Silakan coba beberapa saat lagi.', 'fa-plug'],
            503 => ['Sedang Dalam Pemeliharaan', 'Layanan perpustakaan sedang dalam pemeliharaan atau sangat sibuk. Silakan kembali beberapa saat lagi.', 'fa-wrench'],
            504 => ['Server Terlalu Lama Merespons', 'Server membutuhkan waktu terlalu lama untuk merespons. Silakan coba lagi.', 'fa-hourglass-half'],
        ];
        if (isset($daftar[$code])) return $daftar[$code];
        return $code >= 500 ? $daftar[500] : $daftar[400];
    }
}

if (!function_exists('slims_error_page')) {
    /**
     * Buat HTML halaman error lengkap.
     *
     * @param array $o kunci:
     *   code (int)        kode HTTP, bawaan 500
     *   base (string)     URL dasar SLiMS, diakhiri "/", mis. "/" atau "/slims/"
     *   root (string)     path folder SLiMS di disk (untuk mencari logo)
     *   library (string)  nama perpustakaan (bawaan "Perpustakaan")
     *   title, message    menimpa judul/pesan bawaan
     *   show_search (bool) tampilkan kotak cari katalog (bawaan: hanya 404)
     *   ref (string)      kode rujukan kecil untuk dilaporkan ke petugas (opsional)
     *   fragment (bool)   true = hanya isi kartu tanpa <html> (untuk permintaan AJAX)
     */
    function slims_error_page(array $o): string
    {
        $code = (int) ($o['code'] ?? 500);
        [$judul, $pesan, $ikon] = slims_error_info($code);
        $judul = $o['title'] ?? $judul;
        $pesan = $o['message'] ?? $pesan;
        $base = (string) ($o['base'] ?? '/');
        if ($base === '' || substr($base, -1) !== '/') $base .= '/';
        $root = rtrim((string) ($o['root'] ?? dirname(__DIR__)), '/\\') . DIRECTORY_SEPARATOR;
        $library = trim((string) ($o['library'] ?? '')) ?: 'Perpustakaan';
        $showSearch = $o['show_search'] ?? ($code === 404);
        if ($code === 404 && !isset($o['message'])) {
            $pesan .= $showSearch ? ' Coba cari koleksi melalui kotak pencarian di bawah ini.' : ' Gunakan kotak pencarian untuk menemukan koleksi yang Anda cari.';
        }
        $ref = (string) ($o['ref'] ?? '');

        $e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $asset = $base . 'template/default/assets/';

        // logo perpustakaan (images/default/logo.*), sama seperti navbar tema default
        $logo = '';
        foreach (['png', 'jpg', 'jpeg', 'svg', 'webp', 'gif'] as $ext) {
            if (is_file($root . 'images' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'logo.' . $ext)) {
                $logo = $base . 'images/default/logo.' . $ext;
                break;
            }
        }
        $logoHtml = $logo
            ? '<img src="' . $e($logo) . '" alt="" class="se-logo">'
            : '<span class="se-logo se-logo-icon"><i class="fas fa-book"></i></span>';

        ob_start(); ?>
<div class="se-card" role="alert">
    <div class="se-icon"><i class="fas <?= $e($ikon) ?>"></i></div>
    <div class="se-code"><?= $e($code) ?></div>
    <h1 class="se-title"><?= $e($judul) ?></h1>
    <p class="se-message"><?= $e($pesan) ?></p>
    <?php if ($showSearch): ?>
    <form class="se-search" action="<?= $e($base) ?>index.php" method="get" role="search">
        <input type="hidden" name="search" value="search">
        <input type="text" name="keywords" class="form-control" placeholder="Cari judul, pengarang, atau subjek…" aria-label="Kata kunci pencarian">
        <button type="submit" class="btn btn-primary" aria-label="Cari"><i class="fas fa-search"></i></button>
    </form>
    <?php endif; ?>
    <div class="se-actions">
        <a href="<?= $e($base) ?>index.php" class="btn btn-primary"><i class="fas fa-home mr-2"></i>Kembali ke Beranda</a>
        <a href="javascript:history.back()" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-2"></i>Halaman Sebelumnya</a>
    </div>
    <?php if ($ref !== ''): ?>
    <div class="se-ref">Kode rujukan: <code><?= $e($ref) ?></code> &middot; sebutkan kode ini saat menghubungi petugas.</div>
    <?php endif; ?>
</div>
<?php
        $kartu = ob_get_clean();
        if (!empty($o['fragment'])) {
            return '<style>' . slims_error_css('', false) . '</style><div class="se-fragment">' . $kartu . '</div>';
        }

        ob_start(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $e($code . ' - ' . $judul . ' | ' . $library) ?></title>
    <link rel="icon" href="<?= $e($base) ?>webicon.ico">
    <link rel="stylesheet" href="<?= $e($asset) ?>css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $e($asset) ?>plugin/font-awesome/css/fontawesome-all.min.css">
    <style><?= slims_error_css($asset) ?></style>
</head>
<body>
    <header class="se-header">
        <div class="se-mask"></div>
        <nav class="se-nav container">
            <a class="se-brand" href="<?= $e($base) ?>index.php"><?= $logoHtml ?><span><?= $e($library) ?></span></a>
            <a class="se-navlink" href="<?= $e($base) ?>index.php"><i class="fas fa-home mr-1"></i> Beranda</a>
        </nav>
    </header>
    <main class="container se-main"><?= $kartu ?></main>
    <footer class="se-footer">
        <div class="container">&copy; <?= date('Y') ?> <?= $e($library) ?> &middot; Didukung oleh SLiMS</div>
    </footer>
</body>
</html>
<?php
        return ob_get_clean();
    }

    /**
     * CSS halaman error. $fullPage = false hanya mengembalikan gaya kartu (aman disisipkan ke halaman lain,
     * tidak menyentuh html/body).
     */
    function slims_error_css(string $asset, bool $fullPage = true): string
    {
        $bg = $asset !== '' ? "background: #3d4852 url('{$asset}images/slide1.jpg') 50% 90% / cover no-repeat;" : '';
        $page = $fullPage ? "html, body { height: 100%; }\nbody { display: flex; flex-direction: column; background: #f8fafc; color: #3d4852; margin: 0; }\n" : '';
        return $page . <<<CSS
.se-header { position: relative; min-height: 220px; {$bg} }
.se-mask { position: absolute; inset: 0; background: rgba(0, 0, 0, .45); }
.se-nav { position: relative; display: flex; align-items: center; justify-content: space-between; padding-top: 14px; padding-bottom: 14px; }
.se-brand, .se-navlink { color: #fff; text-decoration: none; }
.se-brand:hover, .se-navlink:hover { color: #fff; text-decoration: none; opacity: .85; }
.se-brand { display: inline-flex; align-items: center; font-size: 1.125rem; font-weight: 500; }
.se-logo { width: 32px; height: 32px; margin-right: 10px; object-fit: contain; }
.se-logo-icon { display: inline-flex; align-items: center; justify-content: center; border: 1px solid rgba(255,255,255,.6); border-radius: 4px; font-size: 16px; }
.se-navlink { font-size: .95rem; color: rgba(255,255,255,.8); }
.se-main { flex: 1 0 auto; margin-top: -120px; position: relative; padding-bottom: 48px; }
.se-card { background: #fff; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,.12); max-width: 640px; margin: 0 auto; padding: 40px 32px 32px; text-align: center; }
.se-icon { width: 64px; height: 64px; margin: 0 auto 8px; border-radius: 50%; background: #e8f1ff; color: #007bff; display: flex; align-items: center; justify-content: center; font-size: 26px; }
.se-code { font-size: 64px; line-height: 1; font-weight: 700; color: #dae1e7; letter-spacing: 4px; margin-bottom: 8px; }
.se-title { font-size: 1.6rem; font-weight: 600; color: #3d4852; margin-bottom: 10px; }
.se-message { color: #606f7b; max-width: 480px; margin: 0 auto 24px; }
.se-search { display: flex; max-width: 440px; margin: 0 auto 24px; box-shadow: 0 2px 8px rgba(0,0,0,.08); border-radius: 6px; }
.se-search .form-control { border-top-right-radius: 0; border-bottom-right-radius: 0; height: 46px; }
.se-search .btn { border-top-left-radius: 0; border-bottom-left-radius: 0; padding: 0 18px; }
.se-actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
.se-ref { margin-top: 22px; font-size: .8rem; color: #8795a1; }
.se-footer { flex-shrink: 0; background: #3d4852; color: #f1f5f8; font-size: .85rem; padding: 18px 0; text-align: center; }
.se-fragment { padding: 24px 0; }
.se-fragment .se-card { box-shadow: none; border: 1px solid #dae1e7; }
@media (max-width: 575.98px) {
  .se-card { padding: 32px 18px 24px; }
  .se-code { font-size: 48px; }
  .se-title { font-size: 1.3rem; }
  .se-actions .btn { width: 100%; }
  .se-navlink { display: none; }
}
CSS;
    }
}
