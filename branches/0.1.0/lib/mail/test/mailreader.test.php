<?php

error_reporting(E_ALL);

echo '<p><b>Datei wurde aufgerufen</b></p>';

include_once '../../../phareon.php';
include_once 'lib/mail/reader/Pop3MailReader.php';

$reader = new Pop3MailReader();

try {
	print_r($reader->connect('mail.yomb.de', 'web5p2', 'dmbszas5'));
}
catch(MailException $e) {
	echo $e->toString();
	$reader->disconnect();
	die();
}

echo '<pre>';

while($reader->next()) {
	//echo htmlentities(var_export($reader->getHeader(), 1));
	//continue;

	$header = $reader->getHeader();

	if($header['msgid'] == '<200410191139.19202.schmidt@softwarecreator.de>') {
		echo '<pre>';
		echo htmlentities(print_r($reader->getMail(), 1));
		break;
	}
}

$reader->disconnect();

?>
