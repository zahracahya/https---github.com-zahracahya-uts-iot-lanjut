<?php
// Menambahkan header CORS
header("Access-Control-Allow-Origin: *"); // Mengizinkan semua asal
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE"); // Mengizinkan metode HTTP
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Mengizinkan header tertentu

require_once("koneksi.php");

// Memeriksa koneksi database
if ($koneksi->connect_error) {
    die("Koneksi gagal: " . $koneksi->connect_error);
}

// 1. Mengambil nilai suhumax, suhumint, dan suhurata
$queryStats = "SELECT 
    MAX(suhu) AS suhumax, 
    MIN(suhu) AS suhumint, 
    AVG(suhu) AS suhurata 
FROM tb_cuaca";

$resultStats = $koneksi->query($queryStats);
if (!$resultStats) {
    die("Query gagal: " . $koneksi->error); // Menampilkan kesalahan query
}

$stats = $resultStats->fetch_assoc();

// Pastikan bahwa suhumax dan suhumint ada dan tidak NULL
$suhumax = isset($stats['suhumax']) ? (int)$stats['suhumax'] : 0;
$suhumin = isset($stats['suhumin']) ? (int)$stats['suhumin'] : 0;
$suhurata = isset($stats['suhurata']) ? round((float)$stats['suhurata'], 2) : 0;

// 2. Mengambil data nilai_suhu_max dengan suhu tertinggi
$queryMaxValues = "SELECT 
    id, 
    suhu, 
    humid, 
    lux, 
    ts
FROM tb_cuaca 
WHERE suhu = $suhumax";

$resultMaxValues = $koneksi->query($queryMaxValues);
if (!$resultMaxValues) {
    die("Query gagal: " . $koneksi->error); // Menampilkan kesalahan query
}

// Membuat array untuk data nilai_suhu_max
$nilaiSuhuMax = [];
while ($row = $resultMaxValues->fetch_assoc()) {
    $nilaiSuhuMax[] = $row;
}

// 3. Mengambil data mount_years dalam format bulan-tahun dari ts
$queryMountYears = "SELECT DISTINCT DATE_FORMAT(ts, '%c-%Y') AS mount_years 
FROM tb_cuaca 
WHERE suhu = $suhumax";

$resultMountYears = $koneksi->query($queryMountYears);
if (!$resultMountYears) {
    die("Query gagal: " . $koneksi->error); // Menampilkan kesalahan query
}

// Membuat array untuk data mount_year_max
$mountYearMax = [];
while ($row = $resultMountYears->fetch_assoc()) {
    $mountYearMax[] = ["mount_years" => $row['mount_years']];
}

// 4. Menggabungkan semua data ke dalam array respons
$response = [
    "suhumax" => $suhumax,
    "suhumin" => $suhumin,
    "suhurata" => $suhurata,
    "nilai_suhu_max_humid_max" => $nilaiSuhuMax,
    "mount_year_max" => $mountYearMax
];

// Menyusun data respons
$jsonResponse = json_encode($response, JSON_PRETTY_PRINT);

// Mengembalikan JSON dengan format rapi
echo $jsonResponse;

// Menutup koneksi
$koneksi->close();
