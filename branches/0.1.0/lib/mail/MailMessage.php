<?php

include_once 'lib/mail/MailPart.php';

/**
 * MailMessage is base class for an multi part mime mail and is used as a 
 * container for saving mail to database, sending and receiving them
 * 
 * @author David Molineus <david at molineus dot de>
 * @version $Revision: 1.0$
 * @since 0.1
 * @package phareon.lib.mail
*/
class MailMessage
{
	/**
	 * attachments of the e-mail 
	 *
	 * @since 0.1
	 * @access protected
	 * @var array
	*/
	var $attachments = array();
	
	/**
	 * bcc contact data
	 *
	 * @since 0.1
	 * @access protected
	 * @var array
	*/
	var $bcc = array();
	
	/**
	 * cc contact data
	 *
	 * @since 0.1
	 * @access protected
	 * @var array
	*/
	var $cc = array();
	
	/**
	 * date
	 *
	 * @since 0.1
	 * @access protected
	 * @var string
	*/
	var $date;		
	
	/**
	 * embededFiles 
	 *
	 * @since 0.1
	 * @access protected
	 * @var array
	*/
	var $embededFiles = array();
	
	/**
	 * from data
	 *
	 * @since 0.1
	 * @access protected
	 * @var array
	*/
	var $from = array();
	
	/**
	 * email headers
	 *
	 * @since 0.1
	 * @access protected
	 * @var array
	*/
	var $headers = array();
	
	/**
	 * server hostname
	 *
	 * @since 0.1
	 * @access protected
	 * @var string
	*/
	var $hostName;
	
	/**
	 * html body
	 *
	 * @since 0.1
	 * @access protected
	 * @var string
	*/
	var $htmlBody = null;
	
	/**
	 * mail type
	 *
	 * @since 0.1
	 * @access protected
	 * @var string
	*/
	var $mailType = null;
	
	/**
	 * reply to contact data
	 *
	 * @since 0.1
	 * @access protected
	 * @var array
	*/
	var $replyTo = array();
	
	/**
	 * priority of email
	 *
	 * @since 0.1
	 * @access protected
	 * @var int
	*/
	var $priority = 3;
	
	/**
	 * mail subject 
	 *
	 * @since 0.1
	 * @access protected
	 * @var string
	*/
	var $subject;
	
	/**
	 * plain text body 
	 *
	 * @since 0.1
	 * @access protected
	 * @var string
	*/
	var $textBody;
	
	/**
	 * to contact data
	 *
	 * @since 0.1
	 * @access protected
	 * @var array
	*/
	var $to = array();
	
	/**
	 * xMailer
	 *
	 * @since 0.1
	 * @access protected
	 * @var string
	*/
	var $xMailer;
	
	
	/**
	 * Constructor
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	*/
	function MailMessage()
	{
		$sName = getenv('SERVER_NAME');
		
		$this->hostName = ($sName != '') ? $sName : 'localhost';		
		$this->date = time();
		$this->xMailer = 'Phareon ' . PHAREON_VERSION;
	}
	
	/**
	 * add an attachment to mail
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	 * @param string $content file content or file path
	 * @param string $name filename
	 * @param string $encoding encoding type
	 * @param string $cType content type
	*/
	function addAttachment($content, $name=null, $encoding='base4', $cType='application/octet-stream')
	{		
		$part =& $this->_createMailPart($content, $name, $encoding, $cType);
		$this->attachments[] =& $part;
	}
	
	/**
	 * add an bcc contact to email
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	 * @param string email adress
	 * @param string contact name
	*/
	function addBcc($email, $name=null)
	{
		$this->bcc[] = array($email, $name);
	}
	
	/**
	 * add an cc contact to email
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	 * @param string email adress
	 * @param string contact name
	*/
	function addCc($email, $name=null)
	{
		$this->cc[] = array($email, $name);
	}
	
	/**
	 * add an embedded file to mail
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	 * @param string $content file content or file path
	 * @param string $name filename
	 * @param string $encoding encoding type
	 * @param string $cType content type
	*/
	function addEmbeddedFile($content, $name=null, $encoding='base4', $cType='application/octet-stream')
	{		
		$part =& $this->_createMailPart($content, $name, $encoding, $cType);
		$this->embeddedFiles[] =& $part;
	}
	
	/**
	 * add header to mail
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	 * @param string name
	 * @param string value
	*/
	function addHeader($name, $value)
	{
		$this->headers[$name] = $value;
	}
	
	/**
	 * add an replyto contact to email
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	 * @param string email adress
	 * @param string contact name
	*/
	function addReplyTo($email, $name=null)
	{
		$this->replyTo[] = array($email, $name);
	}
	
	/**
	 * add an to contact to email
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	 * @param string email adress
	 * @param string contact name
	*/
	function addTo($email, $name=null)
	{
		$this->to[] = array($email, $name);
	}
	
	/**
	 * build mail message for sending
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	*/
	function build()
	{
		$this->_buildFrom();
		$this->_buildRecipients();
		$this->_buildHeaders();
		$this->_buildBody();
	}
	
	/**
	 * get attachments
	 *
	 * @since 0.1
	 * @access public
	 * @return array
	*/
	function getAttachments()
	{
		return $this->attachments;
	}
	
	/**
	 * get bcc
	 *
	 * @since 0.1
	 * @access public
	 * @return array
	*/
	function getBcc()
	{
		return $this->bcc;
	}
	
	/**
	 * get cc
	 *
	 * @since 0.1
	 * @access public
	 * @return array
	*/
	function getCc()
	{
		return $this->Cc;
	}
	
	/**
	 * get embedded files
	 *
	 * @since 0.1
	 * @access public
	 * @return array
	*/
	function getEmbeddedFiles()
	{
		return $this->embeddedFiles;
	}
	
	/**
	 * get date
	 *
	 * @since 0.1
	 * @access public
	 * @param string $format=null date format for php's date function
	 * @return string
	*/
	function getDate($format=null)
	{
		if($format !== null) {
			$date = date($format, $this->date);
		}
		
		return $date;
	}
	
	/**
	 * get from
	 *
	 * @since 0.1
	 * @access public
	 * @return array
	*/
	function getFrom()
	{
		return $this->from;
	}
	
	/**
	 * get headers
	 *
	 * @since 0.1
	 * @access public
	 * @return array
	*/
	function getHeaders()
	{
		return $this->headers;
	}
	
	/**
	 * get hostname
	 *
	 * @since 0.1
	 * @access public
	 * @return string
	*/
	function getHostname()
	{
		return $this->hostname;
	}
	
	/**
	 * get html body
	 *
	 * @since 0.1
	 * @access public
	 * @return string
	*/
	function getHtmlBody()
	{
		return $this->htmlBody;
	}
	
	/**
	 * get reply to
	 *
	 * @since 0.1
	 * @access public
	 * @return array
	*/
	function getReplyTo()
	{
		return $this->replyTo;
	}
	
	/**
	 * get priority
	 *
	 * @since 0.1
	 * @access public
	 * @return int
	*/
	function getPriority()
	{
		return $this->priority;
	}
	
	/**
	 * get subject
	 *
	 * @since 0.1
	 * @access public
	 * @return string
	*/
	function getSubject()
	{
		return $this->subject;
	}
	
	/**
	 * get text body
	 *
	 * @since 0.1
	 * @access public
	 * @return string
	*/
	function getTextBody()
	{
		return $this->textBody;
	}
	
	/**
	 * get to contact data
	 *
	 * @since 0.1
	 * @access public
	 * @return array
	*/
	function getTo()
	{
		return $this->to;
	}
	
	/**
	 * get xmailer
	 *
	 * @since 0.1
	 * @access public
	 * @return string
	*/
	function getXMailer()
	{
		return $this->xMailer;
	}
	
	/**
	 * send mail
	 *
	 * @since 0.1
	 * @access public
	 * @return bool
	 * @throws @see MailTransport::send();
	*/
	function send(&$transport)
	{		
		try(); {
			$transport->send($this);
		}
		if(catch('MailException', $e)) {
			throw($e);
			return false;
		}
		
		return true;
	}
	
	/**
	 * set date
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	 * @param string date
	*/
	function setDate($date)
	{
		$this->date = $date;
	}
	
	/**
	 * set from contact to email
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	 * @param string email adress
	 * @param string contact name
	*/
	function setFrom($email, $name)
	{
		$this->from = array($email, $name);
	}
	
	/**
	 * set hostname
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	 * @param string hostname
	*/
	function setHostname($hostname)
	{
		$this->hostname = $hostname;
	}
	
	/**
	 * set text body
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	 * @param string body
	*/
	function setHtmlBody($body)
	{
		$this->htmlBody = $body;
		$this->setContentType('text/html');
	}
	
	/**
	 * set priority
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	 * @param int priority
	*/
	function setPriority($priority)
	{
		$this->priority = $priority;
	}
	
	/**
	 * set subject
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	 * @param string subject
	*/
	function setSubject($subject)
	{
		$this->subject = $subject;
	}
	
	/**
	 * set text body
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	 * @param string body
	*/
	function setTextBody($body)
	{
		$this->textBody = $body;
	}
	
	/**
	 * set Xmailer
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	 * @param string x mailer
	*/
	function setXMailer($mailer)
	{
		$this->xMailer = $mailer;
	}
	
	
	/**
	 * create mail part for attachment or embedded file
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	 * @param string $content file content or file path
	 * @param string $name filename
	 * @param string $encoding encoding type
	 * @param string $cType content type
	*/
	function &_createMailPart($content, $name=null, $encoding='base4', $cType='application/octet-stream')
	{
		if(is_readable($content)) {
			$content = file_get_contents($content);			
			$name = ($name === null) ? basename($content) : $name;
		}
		else {
			$name = ($name === null) ? '' : $name;
		}
		
		$part = new MailPart();
		$part->setContentType($cType);
		$part->setContentEncoding($enciding);
		$part->setContent($content);
		$part->setName($name);
		
		return $part;
	}
	
}


?>