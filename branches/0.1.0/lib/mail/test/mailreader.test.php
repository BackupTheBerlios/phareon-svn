<?php

error_reporting(E_ALL);

echo '<p><b>Datei wurde aufgerufen</b></p>';

include_once '../../../phareon.php';
include_once 'lib/mail/MailReader.php';

$reader = new MailReader();

try {
	print_r($reader->connect('server.moli.net', 'dm', 'mail'));
}
catch(MailException $e) {
	echo $e->toString();
}

echo '<pre>';

while($reader->next()) {
	echo htmlentities(var_export($reader->getHeader(), 1));	
}

$reader->disconnect();

?>