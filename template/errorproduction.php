<?php
/**
 * Halaman error PHP saat mode "production" (dipanggil oleh SLiMS\Error::send()).
 * Detail teknis TIDAK ditampilkan ke pengunjung; detail ditulis ke log error PHP
 * dengan kode rujukan yang sama seperti yang tampil di layar, supaya admin bisa melacaknya.
 */
require_once __DIR__ . '/error_layout.php';

$inSimbioRequest = $this->withSimbioAJAXRequest(outputWithHeader: false);

defined('SB') or define('SB', dirname(__DIR__) . DIRECTORY_SEPARATOR);

// kode rujukan + catat detail ke log server (bukan ke layar)
$ref = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
error_log(sprintf(
    '[SLiMS error ref %s] %s in %s:%s | URL: %s',
    $ref,
    $this->info['message'] ?? '-',
    $this->info['path'] ?? '-',
    $this->info['line'] ?? '-',
    ($_SERVER['REQUEST_URI'] ?? '-')
));

if (!headers_sent()) {
    http_response_code(500);
    header('Cache-Control: no-store');
}

echo slims_error_page([
    'code' => 500,
    'base' => defined('SWB') ? SWB : '/',
    'root' => SB,
    'library' => is_array($sysconf ?? null) ? (string) ($sysconf['library_name'] ?? '') : '',
    'ref' => $ref,
    'fragment' => $inSimbioRequest !== false,
]);
