<?php

// Router untuk PHP built-in server (dipakai di Railway via deploy/railway-start.sh).
// File statis di public/ disajikan apa adanya; selain itu diarahkan ke index.php
// supaya semua route Laravel (web & api) berfungsi.

$publicPath = __DIR__;

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH)
);

if ($uri !== '/' && file_exists($publicPath.$uri)) {
    return false; // biarkan built-in server menyajikan file statis
}

require_once $publicPath.'/index.php';
