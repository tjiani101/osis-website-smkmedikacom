<?php
$host = 'sql302.infinityfree.com';
$user = 'if0_38709109';
$pass = '0O2uwnawRf';
$db = 'if0_38709109_osisdb';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

?>