<?php

/**
 * SMTPTransport allows to send mail with smtp protocoll
 *
 * @author David Molineus <david at molineus dot de>
 * @version $Revision: 1.0$
 * @since 0.1
 * @package phareon.lib.mail.transport
*/
class SMTPTransport
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
	 * @throws MailTransport.ConnectionFailed
	*/
	function connect($host)
	{
		if($this->smtp !== null) {
			$this->smtp->disconnect();
			$this->smtp = null;
		}
		
		$this->smtp = new NET_SMTP($host);
		
		if(!is_a($this->smtp, 'NET_SMTP')) {
			throw(new PnException('MailTransport.ConnectionFailed',
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
	 * @throws MailTransport.AuthenticationFailed
	*/
	function authenticate($username, $password=null)
	{
		$result = $this->smtp->auth($username, $password);
		
		if(!PEAR::isError($result)) {
			return true;
		}
		
		throw(new PnException('MailTransport.AuthenticationFailed',
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
	 * @param string $from
	 * @param array $recipients
	 * @param array $headers
	 * @param string body
	 * @throws MailTransport.InvalidFrom
	 * @throws MailTransport.InvalidRecipient
	 * @throws MailTransport.SendFailed
	*/
	function send($from, $recipients, $headers, $body)
	{
		$result = $this->smtp->mailFrom($from);
		if(PEAR::isError($result)) {
			throw(new PnException('MailTransport.InvalidFrom',
				sprintf("Could not set mail's from '%s'", $recipient),
				__FILE__, __LINE__)
			);
			return false;
		}
		
		foreach($recipients as $recipient) {
			$result = $this->smtp->rcptFrom($recipient);			
			if(!PEAR::isError($result)) {
				continue;
			}
			
			throw(new PnException('MailTransport.InvalidRecipient',
				sprintf("Could not add an invalid recipient '%s'", $recipient),
				__FILE__, __LINE__)
			);			
			return false;
		}
		
		$data = $this->_buildHeaders($headers) . $body;
		$result = $this->smtp->data($data);		
		if(!PEAR::isError($result)) {
			return true;
		}
		
		throw(new PnException('MailTransport.SendFailed',
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