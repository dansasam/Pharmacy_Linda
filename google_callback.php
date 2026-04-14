<?php
require_once __DIR__ . '/common.php';

if (!isset($_GET['code']) || !isset($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
    echo 'Invalid Google authentication request.';
    exit;
}

$code = $_GET['code'];

$tokenUrl = 'https://oauth2.googleapis.com/token';
$postFields = http_build_query([
    'code' => $code,
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code',
]);

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

if (!$response) {
    echo 'Unable to exchange Google code.';
    exit;
}

tokenResponse:
$tokenData = json_decode($response, true);
if (empty($tokenData['id_token'])) {
    echo 'Missing id_token from Google.';
    exit;
}

$idToken = $tokenData['id_token'];
$jwtParts = explode('.', $idToken);
if (count($jwtParts) < 2) {
    echo 'Invalid ID token.';
    exit;
}

$payload = json_decode(base64_decode(strtr($jwtParts[1], '-_', '+/')), true);
if (!$payload || empty($payload['email'])) {
    echo 'Unable to read Google profile.';
    exit;
}

$email = $payload['email'];
$fullName = $payload['name'] ?? '';
$googleId = $payload['sub'] ?? '';

$stmt = $pdo->prepare('SELECT id, role FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    $_SESSION['user_id'] = $user['id'];
    header('Location: dashboard_' . strtolower(str_replace(' ', '_', $user['role'])) . '.php');
    exit;
}

// New Google user - keep data in session and ask for role selection.
$_SESSION['google_pending'] = [
    'email' => $email,
    'full_name' => $fullName,
    'google_id' => $googleId,
];
header('Location: google_role.php');
exit;
