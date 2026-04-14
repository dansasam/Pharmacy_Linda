<?php
require_once __DIR__ . '/common.php';

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;
$scope = urlencode('openid email profile');
$redirect = urlencode(GOOGLE_REDIRECT_URI);
$authUrl = "https://accounts.google.com/o/oauth2/v2/auth?response_type=code&client_id=" . urlencode(GOOGLE_CLIENT_ID) . "&redirect_uri={$redirect}&scope={$scope}&state={$state}&access_type=offline&prompt=select_account";
header('Location: ' . $authUrl);
exit;
