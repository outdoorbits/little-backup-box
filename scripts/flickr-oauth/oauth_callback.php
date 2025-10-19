<?php
// /var/www/html/flickr-oauth/oauth_callback.php
session_start();

$consumerKey    = getenv('FLICKR_API_KEY');
$consumerSecret = getenv('FLICKR_API_SECRET');

if (!isset($_GET['oauth_token'], $_GET['oauth_verifier'], $_SESSION['oauth_token_secret'])) {
    http_response_code(400);
    exit('Fehlende Parameter');
}

$oauth = new OAuth($consumerKey, $consumerSecret, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
$oauth->setToken($_GET['oauth_token'], $_SESSION['oauth_token_secret']);

// 3) Access Token tauschen
$acc = $oauth->getAccessToken('https://www.flickr.com/services/oauth/access_token', null, $_GET['oauth_verifier']);
if (!$acc || empty($acc['oauth_token']) || empty($acc['oauth_token_secret'])) {
    http_response_code(500);
    exit('Fehler beim Access Token');
}

// Sicher speichern (z. B. JSON-Datei, nur für www-data lesbar)
$store = [
  'oauth_token'        => $acc['oauth_token'],
  'oauth_token_secret' => $acc['oauth_token_secret'],
  // optional zurückgeliefert: 'user_nsid', 'username', 'fullname'
];
file_put_contents('/media/flickr-auth.json', json_encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
chmod('/media/flickr-auth.json', 0600);

echo "OAuth abgeschlossen. Token gespeichert.";
