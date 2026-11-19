<?php
// Cegah akses langsung melalui browser
if (
    (count(get_included_files()) <= 1 && php_sapi_name() !== 'cli') ||
    (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) ||
    (isset($_SERVER['PHP_SELF']) && basename(__FILE__) === basename($_SERVER['PHP_SELF']))
) {
    http_response_code(403);
    exit('Access Denied: Direct access is prohibited.');
}

require_once __DIR__ . '/../db.php';
