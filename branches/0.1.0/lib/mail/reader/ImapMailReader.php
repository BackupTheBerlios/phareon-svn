<?php

include_once 'lib/mail/MailReader.php';

/**
 * ImapMailReader allows to connect to a imap mailbox and fetch its messages
 *
 * @author David Molineus <david at molineus dot de>
 * @since 0.1
 * @package phareon.lib.mail.reader
*/
class ImapMailReader extends MailReader
{
	/**
	 * connect to a imap server
	 *
	 * @since 0.1
	 * @access public
	 * @return bool
	 * @param string $host mail server
	 * @param string $user
	 * @param string $password
	 * @param int $secure level
	 * @param int $port=null
	 * @throws MailException
	*/
	public function connect($host, $user, $password, $secure=MailReader::SSL_NONE, $port=null)
	{
		$type = 'service=imap';
		$mailbox = 'INBOX';
		
		if($port === null) {
			$port = $this->_getPort($secure, 143);
		}
		
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
