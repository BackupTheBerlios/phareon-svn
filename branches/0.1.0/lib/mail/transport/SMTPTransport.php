re<?php

include_once 'Net/SMTP.php';

include_once 'lib/mail/MailTransport.php';
include_once 'lib/mail/MailException.php';

/**
 * SMTPTransport allows to send mail with smtp protocoll
 *
 * Usage example:
 * $mail = new MailMessage();
 * //set Mail attributes here
 *
 * $transport = new SMTPTransport();
 * $transport->connect('smtp.server.de');
 * $transport->authenticate('user', 'pass');
 * 
 * $mail->send($transport);
 * $transport->disconnect();
 *
 * @author David Molineus <david at molineus dot de>
 * @version $Revision: 1.0$
 * @since 0.1
 * @package phareon.lib.mail.transport
*/
class SMTPTransport extends MailTransport
{
	/**
	 * instance of pear's NET_SMTP class
	 * 
	 * @since 0.1
	 * @access protected
	 * @var NET_SMTP
	*/
	var $smtp = null;
	
	/**
	 * connect to host
	 *
	 * @since 0.1
	 * @access public
	 * @return bool
	 * @param string $host smtp host
	 * @throws MailException MailTransport.ConnectionFailed
	*/
	function connect($host)
	{
		if($this->smtp !== null) {
			$this->smtp->disconnect();
			$this->smtp = null;
		}
		
		$this->smtp = new NET_SMTP($host);
		
		if(!is_a($this->smtp, 'NET_SMTP')) {
			throw(new MailException('MailTransport.ConnectionFailed',
				sprintf("Could not connect to host '%s'", $host),
				__FILE__, __LINE__)
			);
			
			return false;
		}
		
		$result = $this->smtp->connect();
		if(PEAR::isError($result)) {
			throw(new MailException('MailTransport.ConnectionFailed',
				sprintf("Could not connect to host '%s'", $host),
				__FILE__, __LINE__)
			);
			
			return false;
		}
		
		return true;
	}
	
	/**
	 * try to authenticate
	 *
	 * @since 0.1 
	 * @access public
	 * @return bool
	 * @param string $username
	 * @param string $password=null
	 * @throws MailException MailTransport.AuthenticationFailed
	*/
	function authenticate($username, $password=null)
	{
		$result = $this->smtp->auth($username, $password);
		
		if(!PEAR::isError($result)) {
			return true;
		}
		
		throw(new MailException('MailTransport.AuthenticationFailed',
			sprintf('Could not authenticate smtp host with user (%s)'
				.'and password (%s)', $username, $password), __FILE__, __LINE__)
		);
		return false;
	}
	
	/**
	 * send mail using smtp protocoll
	 *
	 * @since 0.1
	 * @access public
	 * @return bool
	 * @param MailMessage &$mail mail object
	 * @throws MailException MailTransport.InvalidFrom
	 * @throws MailException MailTransport.InvalidRecipient
	 * @throws MailException MailTransport.SendFailed
	*/
	function send(&$mail)
	{
		try(); {
			$built= $this->build($mail);
		}
		if(catch('MailException', $e)) {
			throw($e);
			return false;
		}
		
		$result = $this->smtp->mailFrom($built['from']);
		if(PEAR::isError($result)) {
			throw(new MailException('MailTransport.InvalidFrom',
				sprintf("Could not set mail's from '%s'", $recipient),
				__FILE__, __LINE__)
			);
			return false;
		}
		
		foreach($built['recipients'] as $recipient) {
			$result = $this->smtp->rcptFrom($recipient);			
			if(!PEAR::isError($result)) {
				continue;
			}
			
			throw(new MailException('MailTransport.InvalidRecipient',
				sprintf("Could not add an invalid recipient '%s'", $recipient),
				__FILE__, __LINE__)
			);			
			return false;
		}
		
		$result = $this->smtp->data($data['data']);		
		if(!PEAR::isError($result)) {
			return true;
		}
		
		throw(new MailException('MailTransport.SendFailed',
			sprintf("Send failed with error message: '%s'", $result->getMessage()),
			__FILE__, __LINE__)
		);
		return false;
	}
	
	/**
	 * disconnect smtp connection
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	*/
	function disconnect()
	{
		$this->smtp->disconnect();
		$this->smtp = null;		
	}
}

?>