<?php

error_reporting(E_ALL);

include_once '../../../phareon.php';
include_once 'lib/mail/MailReader.php';


$reader = new MailReader();

try {
	$reader->connect('server.moli.net', 'dm', 'mail');
}
catch(MailException $e) {
	$e->toString();
}


echo '<pre>';

while($reader->next()) {
	print_r($reader->getHeader());	
}

$reader->disconnect();

?>