<?php

include_once 'lib/mail/MailReader.php';

/**
 * NntpMailReader allows to connect a news server to get messages from a 
 * newsgroup
 *
 * @author David Molineus <david at molineus dot de>
 * @since 0.1
 * @package phareon.lib.mail.reader
*/
class NntpMailReader extends MailReader
{
	/**
	 * connect to nntp server for a newsgroup
	 *
	 * @since 0.1
	 * @access public
	 * @return bool
	 * @param string $host mail server
	 * @param string $user
	 * @param string $password
	 * @param string $newsgroup name of newsgroup
	 * @param int $secure level
	 * @param int $port=null
	 * @throws MailException
	*/
	public function connect($host, $user, $password, $newsgroup, $secure=MailReader::SSL_NONE, $port=null)
	{
		$type = '/nntp';
		
		if($port === null) {
			$port = $this->_getPort($secure, 119);
		}
		
		try {
			$result = parent::connect(
				$host, $user, $password, $type, $newsgroup, $port, $secure
			);
		}
		catch(MailException $e) {
			throw $e;
		}
	}
}

?>