<?php
require_once 'configuratie.include.php';

require_once "Google/autoload.php";

$google_redirect_uri = CSR_ROOT . '/googlecallback';

//setup new google client
$client = new Google_Client();
$client -> setApplicationName('Stek');
$client -> setClientId(GOOGLE_CLIENT_ID);
$client -> setClientSecret(GOOGLE_CLIENT_SECRET);
$client -> setRedirectUri($google_redirect_uri);
$client -> setAccessType('online');
$client -> setScopes('https://www.google.com/m8/feeds');

$googleImportUrl = $client->createAuthUrl();

//google response with contact. We set a session and redirect back
if (isset($_GET['code'])) {
    $auth_code = $_GET["code"];
    $_SESSION['google_token'] = $auth_code;
    header("Location: " . CSR_ROOT . "/profiel/" . $_GET['state'] . "/addToGoogleContacts");
}