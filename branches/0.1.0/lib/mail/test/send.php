<?php

include_once '../../../phareon.php';
include_once 'lib/exception/PnException.php';
include_once 'lib/mail/transport/SMTPTransport.php';
include_once 'lib/mail/MailMessage.php';

$transport = new SMTPTransport();
try(); {
	$transport->connect('server.moli.net');
}
if(catch('PnException', $e)) {
	die($e->toString());
}

try(); {
	$transport->authenticate('dm', 'mail');
}
if(catch('PnException', $e)) {
	die($e->toString());
}

$mail = new MailMessage();
$mail->setFrom('david@molineus.de', 'David Molineus');
$mail->addTo('david_molineus@web.de');
$mail->addCC('schmidt@softwarecreator.de');
$mail->addBcc('molineus@softwarecreator.de');
$mail->setSubject('Test');
$mail->setHtmlBody('<b>Test HTML Body</b>');
$mail->setTextBody('Dies ist der Text Body der Testmail');

try(); {
	$mail->send($transport);
	$transport->disconnect();
}
if(catch('PnException', $e)) {
	die($e->toString());
}

?>
