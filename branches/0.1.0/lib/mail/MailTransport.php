<?php

abstract class MailTransport
{
	public abstract function connect($host);
	public abstract function authenticate($userername, $password=null);
	public abstract function send();
	public abstract function disconnect();
	
	/**
	 * build parts of mime mail message
	 *
	 * @since 0.1
	 * @access public
	 * @return array
	 * @throws MailException MailTransport.InvalidFrom
	 * @throws MailException MailTransport.InvalidRecipient
	 * @throws MailException MailTransport.InvalidHeader
	 * @throws MailException MailTransport.InvalidBody
	*/
	public function build(MailMessage $mail)
	{
		$built = array();
		
		try {
			$built['recipients'] = $this->_buildRecipients($mail);
		}
		catch(MailException $e){
			throw($e);
		}
			
		try {
			$built['from'] = $built['from'] = $this->_buildFrom($mail);
		}
		catch(MailException $e) {
			throw($e);
		}
		
		try {
			$built['data'] = $this->_buildHeader($mail);
		}
		catch(MailException $e) {
			throw($e);
		}
		
		try {
			$built['data'] .= $this->_buildBody($mail);
		}
		catch(MailException $e) {
			throw($e);
		}
		
		return $built;
	}
}

?>