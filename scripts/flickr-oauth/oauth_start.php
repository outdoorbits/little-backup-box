 
<?php
// /var/www/html/flickr-oauth/oauth_start.php
session_start();

$consumerKey    = getenv('FLICKR_API_KEY');
$consumerSecret = getenv('FLICKR_API_SECRET');
$callbackUrl    = '/flickr-oauth/oauth_callback.php';

$oauth = new OAuth($consumerKey, $consumerSecret, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
$oauth->enableDebug(false);

// 1) Request Token holen
$req = $oauth->getRequestToken('https://www.flickr.com/services/oauth/request_token', $callbackUrl);
if (!$req || empty($req['oauth_token']) || empty($req['oauth_token_secret'])) {
    http_response_code(500);
    exit('Fehler beim Request Token');
}

// Temporär speichern
$_SESSION['oauth_token_secret'] = $req['oauth_token_secret'];

// 2) User zu Flickr-Authorize leiten
$perms = 'write'; // 'read' | 'write' | 'delete'
$authUrl = 'https://www.flickr.com/services/oauth/authorize?oauth_token=' . urlencode($req['oauth_token']) . '&perms=' . $perms;
header('Location: ' . $authUrl);
exit;
