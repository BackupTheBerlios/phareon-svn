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
 * @abstract
*/
abstract class MailReader
{
	/**
	 * no secure connection
	 *
	 * @accss public
	 * @var int
	*/
	const SSL_NONE = 0;
	
	/**
	 * secure connection with certificate validation
	 *
	 * @accss public
	 * @var int
	*/
	const SSL_VALIDATE = 1;
	
	/**
	 * secure connection without certificate validation
	 *
	 * @accss public
	 * @var int
	*/
	const SSL_NOVALIDATE = 2;
	
	/**
	 * secure authentication
	 *
	 * @accss public
	 * @var int
	*/
	const SECURE_AUTH = 4;
	

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
	 * @param string $type connection type
	 * @param string $mailbox mailbox name
	 * @param int $port=110
	 * @param int $secure level
	 * @throws MailException
	*/
	public function connect($host, $user, $password, $type, $mailbox, $port, $secure)
	{
		if((self::SECURE_AUTH & $secure) == self::SECURE_AUTH) {
			$type .= '/secure';
			$secure = self::SECURE_AUTH ^ $secure;
		}
		
		switch($secure) {
			case self::SSL_VALIDATE:
				$type .= '/ssl/validate-cert';
			break;

			case self::SSL_NOVALIDATE:
				$type .= '/ssl/novalidate-cert';
			break;
		}
				
		$dsn = sprintf('{%s:%d/%s}%s', $host, $port, $type, $mailbox);
		
		imap_errors();

		$this->handle = @imap_open($dsn, $user, $password);		
		$errors = imap_errors();
		$pos = array_search('Mailbox is empty', (array) $errors);
		
		if($pos !== false) {
			unset($errors[$pos]);
		}
		
		if(is_array($errors) && count($errors) > 0) {
			throw new MailException(sprintf(
				"Can not connect create imap connection using this dsn '%s'. "
				. "Following imap errors are caught: '%s'",
				$dsn, print_r($errors, true))
			);

			return false;
		}
		
		$this->reset();		
		return true;
	}
	
	/**
	 * disconnect imap connection
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	*/
	public function disconnect()
	{
		imap_expunge($this->handle);
		imap_close($this->handle);
	}
	
	/**
	 * returns number of mails in the mailbox
	 *
	 * @since 0.1
	 * @access public
	 * @return int
	*/	
	public function countMails()
	{
		return $this->count;
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
		$this->current = 0;
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
		$data = imap_header($this->handle, $this->current);
		
		/*
		echo '<pre>(' . $this->current . ')<br/>';
		print_r($data);
		echo '</pre>';
		*/
		
		$header['msgid'] = $data->message_id;
		$header['subject'] = $data->subject;
		$header['date'] = $data->date;
		$header['from'] = $data->fromaddress;
		$header['recent'] = ($data->Recent == '') ? false : true;
		$header['seen'] = ($data->Recent == 'N' || $data->Unseen == 'U') 
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
			case 'quoted-printable':
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
				$content = $body;
				throw new MailException(sprintf(
					"Encoding method '%s' is not supported", $part['encoding']
					)
				);
		}

		echo $part['disposition'] . '<br />';
		
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
	
	/**
	 * parse header of current mail message
	 *
	 * @since 0.1
	 * @access protected
	 * @return void
	 * @param MailMessage $mail
	*/
	protected function _parseHeader(MailMessage $mail)
	{
		$data = imap_header($this->handle, $this->current);
		
		$mail->setMessageId($data->message_id);
		$mail->setSubject($data->subject);
		$mail->setDate($data->date);
		
		$from = $this->_transformAdress($data->from[0]);
		$seen = ($data->Recent == 'N' || $data->Unseen == 'U') ? false : true;

		$mail->setFrom($from[0], $from[1]);
		$mail->setRecent(($data->Recent == '') ? false : true);
		$mail->setSeen($seen);
		
		if(isset($data->to)) {
			foreach($data->to as $adress) {
				$adress = $this->_transformAdress($adress);
				$mail->addTo($adress[0], $adress[1]);
			}
		}

		if(isset($data->cc)) {
			foreach($data->cc as $adress) {
				$adress = $this->_transformAdress($adress);
				$mail->addCc($adress[0], $adress[1]);
			}
		}

		if(isset($data->bcc)) {
			foreach($data->bcc as $adress) {
				$adress = $this->_transformAdress($adress);
				$mail->addBcc($adress[0], $adress[1]);
			}
		}

		if(isset($data->return_path)) {
			foreach($data->return_path as $adress) {
				$adress = $this->_transformAdress($adress);
				$mail->addReplyTo($adress[0], $adress[1]);
			}
		}
	}

	/**
	 * transform a imap mail adress into an array array($adress, $name)
	 *
	 * @since 0.1
	 * @access private
	 * @return array
	 * @final
	 * @param object $adress
	*/
	final private function _transformAdress($adress)
	{
		return array($adress->mailbox . '@' . $adress->host, isset($adress->personal) ? $adress->personal : null);
	}
	
	
	/**
	 * get port number using security ports
	 *
	 * @since 0.1 
	 * @return int
	 * @param int $secure
	 * @param int $default
	*/
	final protected function _getPort($secure, $default)
	{
		if((self::SSL_VALIDATE & $secure) == self::SSL_VALIDATE) {
			return 993;
		}

		if((self::SSL_NOVALIDATE & $secure) == self::SSL_NOVALIDATE) {
			return 995;
		}
		
		return $default;
	}
}

?>
