<?php
// api/index.php - Serverless Router for Vercel Deployment

// Pastikan environment Vercel terdeteksi
putenv('VERCEL=1');
$_ENV['VERCEL'] = '1';

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = trim($uri, '/');

// Routing berdasarkan path request
if ($path === 'admin.php' || $path === 'admin') {
    require __DIR__ . '/../admin.php';
} elseif ($path === 'login.php' || $path === 'login') {
    require __DIR__ . '/../login.php';
} elseif ($path === 'api.php') {
    require __DIR__ . '/../api.php';
} elseif ($path === 'auth.php') {
    require __DIR__ . '/../auth.php';
} else {
    // Default: Halaman Beranda Publik
    require __DIR__ . '/../index.php';
}
