<?php

include_once 'lib/mail/MailReader.php';

/**
 * Pop3MailReader allows to connect to a pop3 mailbox and fetch its messages
 *
 * @author David Molineus <david at molineus dot de>
 * @since 0.1
 * @package phareon.lib.mail.reader
*/
class Pop3MailReader extends MailReader
{
	/**
	 * connect to a pop3 server
	 *
	 * @since 0.1
	 * @access public
	 * @return bool
	 * @param string $host mail server
	 * @param string $user
	 * @param string $password
	 * @param int $secure level
	 * @throws MailException
	*/
	public function connect($host, $user, $password, $secure=MailReader::SSL_NONE)
	{
		$type = '/pop3';
		$mailbox = 'INBOX';
		$port = 110;
		
		try {
			$result = parent::connect(
				$host, $user, $password, $type, $mailbox, $port, $secure
			);
		}
		catch(MailException $e) {
			throw $e;
		}
	}
}

?>