<?php

include_once '../../../phareon.php';
include_once 'lib/mail/MailReader.php';


$reader = new MailReader();

try {
	$reader->connect('mail.yomb.de', 'web5p2', 'dmbaszas5');
}
catch(MailException $e) {
	$e->toString();
}

?>