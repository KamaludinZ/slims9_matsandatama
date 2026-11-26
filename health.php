<?php
/**
 * Health check SLiMS untuk Docker / Coolify / pemantau uptime.
 *
 *   GET /health.php  -> 200 {"status":"ok"}     aplikasi & database sehat
 *                    -> 503 {"status":"error"}  database tidak bisa dihubungi
 *
 * Sengaja ringan: TIDAK memuat sysconfig, sesi, atau template SLiMS, hanya PHP + 1 query
 * "SELECT 1" ke database (batas waktu 3 detik). Tidak menampilkan detail apa pun
 * (versi, nama host, pesan error); penyebab gagal hanya ditulis ke log server.
 */

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'GET' && $method !== 'HEAD') {
    http_response_code(405);
    header('Allow: GET, HEAD');
    exit('{"status":"error"}');
}

$sehat = false;
try {
    $configDb = __DIR__ . '/config/database.php';
    if (!is_file($configDb) || !class_exists('mysqli')) {
        throw new RuntimeException('config/database.php tidak ada atau ekstensi mysqli tidak aktif');
    }
    defined('SB') or define('SB', __DIR__ . DIRECTORY_SEPARATOR);
    $db = require $configDb;
    $node = $db['nodes'][$db['default_profile'] ?? 'SLiMS'] ?? null;
    if (!is_array($node)) throw new RuntimeException('profil database tidak ditemukan');

    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = mysqli_init();
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
    if (!@$conn->real_connect($node['host'], $node['username'], $node['password'], $node['database'], (int) ($node['port'] ?? 3306))) {
        throw new RuntimeException('koneksi database gagal: ' . mysqli_connect_error());
    }
    $res = $conn->query('SELECT 1');
    $sehat = $res !== false && (int) $res->fetch_row()[0] === 1;
    $conn->close();
    if (!$sehat) throw new RuntimeException('query SELECT 1 gagal');
} catch (\Throwable $e) {
    error_log('[SLiMS health] ' . $e->getMessage());
    $sehat = false;
}

http_response_code($sehat ? 200 : 503);
echo $sehat ? '{"status":"ok"}' : '{"status":"error"}';
