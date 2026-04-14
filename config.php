<?php
// config.php

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'pharmacy_internship');
define('DB_USER', 'root');
define('DB_PASS', '');

define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI', 'http://localhost/Pharmacy intership system/google_callback.php');

define('UPLOAD_DIR', __DIR__ . '/uploads/');

if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
