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

// PayMongo Configuration (for online payments)
// Get your API keys from: https://dashboard.paymongo.com/developers
// Use test keys for development, live keys for production
define('PAYMONGO_SECRET_KEY', 'sk_test_xxxxxxxxxxxxxxxxxxxxxxxx'); // Replace with your secret key
define('PAYMONGO_PUBLIC_KEY', 'pk_test_xxxxxxxxxxxxxxxxxxxxxxxx'); // Replace with your public key

if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
