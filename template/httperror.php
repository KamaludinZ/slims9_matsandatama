<?php
/**
 * Halaman error HTTP (ErrorDocument Apache, lihat .htaccess di folder utama SLiMS).
 * Contoh: ErrorDocument 404 /template/httperror.php
 *
 * Tidak memuat sysconfig SLiMS supaya tetap tampil walau aplikasi sedang bermasalah.
 * Nama perpustakaan diambil langsung dari database (batas waktu 2 detik) khusus untuk error
 * selain 5xx; bila gagal, dipakai teks "Perpustakaan".
 */

require __DIR__ . '/error_layout.php';

// kode error: dari Apache (REDIRECT_STATUS); dibuka langsung = 404
$code = (int) ($_SERVER['REDIRECT_STATUS'] ?? 0);
if ($code < 400 || $code > 599) $code = 404;
http_response_code($code);
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

// URL dasar SLiMS = folder di atas /template/
$base = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/template/httperror.php')));
$base = rtrim($base, '/') . '/';

// nama perpustakaan dari tabel setting (opsional)
$library = '';
if ($code < 500 && is_file(dirname(__DIR__) . '/config/database.php') && class_exists('mysqli')) {
    try {
        defined('SB') or define('SB', dirname(__DIR__) . DIRECTORY_SEPARATOR);
        $db = require dirname(__DIR__) . '/config/database.php';
        $node = $db['nodes'][$db['default_profile'] ?? 'SLiMS'] ?? null;
        if (is_array($node)) {
            mysqli_report(MYSQLI_REPORT_OFF);
            $conn = mysqli_init();
            $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2);
            if (@$conn->real_connect($node['host'], $node['username'], $node['password'], $node['database'], (int) ($node['port'] ?? 3306))) {
                $res = $conn->query("SELECT setting_value FROM setting WHERE setting_name = 'library_name' LIMIT 1");
                if ($res && ($row = $res->fetch_row())) {
                    $value = @unserialize(stripslashes($row[0]));
                    if (is_string($value)) $library = $value;
                }
                $conn->close();
            }
        }
    } catch (\Throwable $e) {
        $library = '';
    }
}

echo slims_error_page([
    'code' => $code,
    'base' => $base,
    'root' => dirname(__DIR__),
    'library' => $library,
]);
