<?php

include_once 'lib/mail/MailMessage.php';
include_once 'lib/mail/MailException.php';

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
	protected $handle;
	
	/**
	 * current mail number
	 *
	 * @since 0.1
	 * @access protected
	 * @var int
	*/
	protected $current = 0;
	
	/**
	 * number of messages in mailbox
	 *
	 * @since 0.1
	 * @access protected
	 * @var int
	*/
	protected $count = 0;
	
	
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
	public function connect($host, $user, $password, $port=110)
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
	public function next()
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
	public function reset()
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
	public function delete()
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
	public function getHeader()
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
	public function getMail()
	{
		$mail = new MailMessage();		
		$this->_parseHeader($mail);
		$this->_parseStructure($mail);
		return $mail;		
	}
	
	/**
	 * parse structure of mime mail
	 *
	 * @since 0.1
	 * @access protected
	 * @return void
	 * @param MailMessage $mail
	*/
	protected function _parseStructure(MailMessage $mail)
	{
		$structure = imap_fetchstructure($this->handle, $this->current);
		
		if(isset($structure->parts) && (is_array($structure->parts))) {
			$this->_parseParts($mail, $structure);
		}
		else {
			$body = imap_body($this->handle, $this->current);
			$mail->setTextBody($body);
		}
	}
	
	/**
	 * parse parts of mail
	 *
	 * @since 0.1
	 * @access protected
	 * @return void
	 * @param MailMessage $mail
	 * @param object $structure
	*/
	protected function _parseParts(MailMessage $mail, $structure, $parentNr = 1)
	{	
		$partNr = 0;
		
		foreach($structure->parts as $part) {			
			$partNr++;
			$nr = $parentNr . '.' . $partNr;
			
			/*
			$cType = $this->_getTypeName($structure->type);
			$cType .= '/' . strtolower($structure->subtypr);
			
			$partObj = $this->_parsePart($mail, $part, $nr);			
			$partObj->setContentType($cType);
			*/
			
			if(isset($part->parts)) {
				$this->_parseParts($mail, $part, $nr);
			}
		}
	}
		
	/**
	 * do transformation for one part
	 *
	 * @since 0.1
	 * @access protected
	 * @return MailPart
	 * @throws MailException
	*/
	protected function _parsePart(MailMessage $mail, $part, $nr)
	{
		$data = array();
		
		if(empty($part['disposition'])) {
			$data['disposition'] = 'attachment';
		}
		else {
			$data['disposition'] = strtolower($part->disposition);
		}
		
		$data['cType'] = $this->_getTypeName($part->type);
		$data['cType'] .= '/' . strtolower($part->subtype);
		$data['encoding'] = $this->_getContentEncoding($part->encoding);
		$data['id']  = $part->id;
		$data['filename'] = '';
		
		if($part->ifdparameters) {
			foreach($part->dparameters as $param) {
				if(eregi('filename', $param->name)) {
					$data['filename'] = $param->value;
					break;
				}
			}
		}
		
		if(($data['filename'] == '') && $part->ifparameters) {
			foreach($part->parameters as $param) {
				if(eregi('name', $param->name)) {
					$data['filename'] = $param->value;
					break;
				}
			}
		}	
		
		$body = imap_fetchbody($this->handle, $this->current, $nr);
		
		switch ($part['encoding'])
		{
			case 'quoted->printable':
				$content = quoted_printable_decode(
					preg_replace("/=\?[^\?]+\?Q\?([^\s]*)\?=/i","$1", $body)
				);
				break;
			
			case 'base64':
				$content = imap_base64($body);
				break;
			
			case 'binary':
				$content = imap_binary($body);
				break;		
				
			default:
				throw new MailException(sprintf(
					"Can not decode body whith encoding '%s'", $part['encoding']
					)
				);
		}
		
		if($part['disposition'] == 'inline') {
			$this->addEmbeddedFile(
				$content, $part['filename'], $part['encoding'], $part['cType']
			);
		}
		elseif($part['disposition'] == 'attachment') {
			$this->addAttachment(
				$content, $part['filename'], $part['encoding'], $part['cType']
			);			
		}
	}
}


?>