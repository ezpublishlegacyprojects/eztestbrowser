<?php

function eZUpdateDebugSettings(){}

include('autoload.php');

$browser = new ezpWebBrowser(array(), 'ezpAdapter');
$browser->get('/plain_site/user/login');

unset($browser);

unset($_COOKIE);
unset($_SESSION);
unset($_SERVER);
unset($GLOBALS);
unset($_ENV);

$browser = new ezpWebBrowser(array(), 'ezpAdapter');
$browser->get('/plain_site_admin/user/login');

echo $browser->getResponseText();


?>
