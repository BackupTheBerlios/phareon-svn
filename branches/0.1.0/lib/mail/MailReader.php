<?php

include_once 'lib/mail/MailMessage.php';

/**
 * MailReader fetch all messages from a mail box and transform the message into
 * a mail object
 *
 * This class uses PHP's imap_* functions.
 *
 * @author David Molineus <david at molineus dot de>
 * @since 0.1
 * @package phareon.lib.mail
*/
class MailReader
{
	/**
	 * mail box handle
	 * 
	 * @since 0.1
	 * @access protected
	 * @var resource
	*/
	var $handle;
	
	/**
	 * current mail number
	 *
	 * @since 0.1
	 * @access protected
	 * @var int
	*/
	var $current = 0;
	
	/**
	 * number of messages in mailbox
	 *
	 * @since 0.1
	 * @access protected
	 * @var int
	*/
	var $count = 0;
	
	
	/**
	 * connect to mailbox 
	 * 
	 * @since 0.1
	 * @access public
	 * @return bool
	 * @param string $host mail server
	 * @param string $user
	 * @param string $password
	 * @param int $port=110
	 * @throws MailException
	*/
	function connect($host, $user, $password, $port=110)
	{
		$dsn = sprintf('{%s:%d}INBOX', $host, $port);
		$this->handle = @imap_open($dsn, $user, $password);
		
		if(!is_resource($this->handle)) {
			throw new MailException(
				sprintf("Can not connect to ('%s' : '%d'), user '%s'",
					$host, $port, $user
				)				
			);
			
			return false;
		}
		
		$this->reset();		
		return true;
	}
	
	/**
	 * try to go to next mail
	 *
	 * @since 0.1
	 * @access public
	 * @return bool
	*/
	function next()
	{
		if($this->current < $this->count) {
			$this->current++;
			return true;
		}
		
		return false;
	}
	
	/**
	 * reset mail reader's iterator
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	*/
	function reset()
	{
		$this->count = imap_num_msg($this->handle);
		$this->current = 1;
	}
	
	/**
	 * delete current mail
	 *
	 * Errors can not be handled, because imap does not support it
	 * 
	 * @since 0.1
	 * @access public
	 * @return void
	*/
	function delete()
	{
		imap_delete($this->handle, $this->current);
	}
	
	
	/**
	 * get an array of header information of the current mail
	 *
	 * At the moment following header information are supported:
	 * - msgid
	 * - subject
	 * - date
	 * - from
	 * - recent (boolean, new or not new)
	 * - seen
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	*/
	function getHeader()
	{
		$header = array();
		$data = imap_header($this->header, $this->current);
		
		$header['msgid'] = $data->message_id;
		$header['subject'] = $data->subject;
		$header['date'] = $data->date;
		$header['from'] = $data->fromadress;
		$header['recent'] = ($data->recent == '') ? false : true;
		$header['seen'] = ($data->recent == 'N' || $data->unseen == 'U') 
						? false : true;
		
		return $header;		
	}
	
	
	/**
	 * tranform whole mail message into a MailMessage object
	 *
	 * @since 0.1
	 * @access public
	 * @return MailMessage
	*/
	function getMail()
	{
		$mail = new MailMessage();
		
	}
		
}


?>