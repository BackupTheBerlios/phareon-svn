<?php

include_once 'lib/mail/MailPart.php';

/**
 * MailMessage is base class for an multi part mime mail and is used as a 
 * container for saving mail to database, sending and receiving them
 * 
 * @author David Molineus <david at molineus dot de>
 * @version $Revision: 1.0$
 * @since 0.1.0
 * @package phareon.lib.mail
*/
class MailMessage
{
	/**
	 * high priority state
	 *
	 * @since 0.1
	 * @access public
	 * @var int
	*/
	const PRIORITY_HIGH = 1;

	/**
	 * normal priority state
	 *
	 * @since 0.1
	 * @access public
	 * @var int
	*/
	const PRIORITY_NORMAL = 3;
	
	/**
	 * low priority state
	 *
	 * @since 0.1
	 * @access public
	 * @var int
	*/
	const PRIORITY_LOW = 5;
	
	/**
	 * attachments of the e-mail 
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	*/
	protected $attachments = array();
	
	/**
	 * bcc contact data
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	*/
	protected $bcc = array();
	
	/**
	 * cc contact data
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	*/
	protected $cc = array();
	
	/**
	 * date
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var string
	*/
	protected $date;		
	
	/**
	 * embededFiles 
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	*/
	protected $embededFiles = array();
	
	/**
	 * from data
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	*/
	protected $from = array();
	
	/**
	 * email headers
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	*/
	protected $headers = array();
	
	/**
	 * server hostname
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var string
	*/
	protected $hostName;
	
	/**
	 * html body
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var string
	*/
	protected $htmlBody = null;
	
	/**
	 * mail type
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var string
	*/
	protected $mailType = null;
	
	/**
	 * reply to contact data
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	*/
	protected $replyTo = array();
	
	/**
	 * priority of email
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var int
	*/
	protected $priority;

	/**
	 * mail subject 
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var string
	*/
	protected $subject;
	
	/**
	 * plain text body 
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var string
	*/
	protected $textBody;
	
	/**
	 * to contact data
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	*/
	protected $to = array();
	
	/**
	 * xMailer
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var string
	*/
	protected $xMailer;
	
	/**
	 * is message already seen
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var bool
	*/
	protected $seen = false;

	/**
	 * is message still recent
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var bool
	*/
	protected $recent = true;

	/**
	 * message id
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var bool
	*/
	protected $msgid;


	/**
	 * Constructor
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	*/
	function __construct()
	{
		$sName = getenv('SERVER_NAME');
		
		$this->hostName = ($sName != '') ? $sName : 'localhost';		
		$this->date = time();
		$this->xMailer = 'Phareon ' . PHAREON_VERSION;
		$this->priority = self::PRIORITY_NORMAL;
	}
	
	/**
	 * add an attachment to mail
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 * @param string $content file content or file path
	 * @param string $name filename
	 * @param string $encoding encoding type
	 * @param string $cType content type
	*/
	public function addAttachment($content, $name=null, $encoding='base4', $cType='application/octet-stream')
	{		
		$part = $this->_createMailPart($content, $name, $encoding, $cType);
		$part->setDisposition('attachment');
		$this->attachments[] =& $part;
		
		return $part;
	}
	
	/**
	 * add an bcc contact to email
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 * @param string email adress
	 * @param string contact name
	*/
	public function addBcc($email, $name=null)
	{
		$this->bcc[] = array($email, $name);
	}
	
	/**
	 * add an cc contact to email
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 * @param string email adress
	 * @param string contact name
	*/
	public function addCc($email, $name=null)
	{
		$this->cc[] = array($email, $name);
	}
	
	/**
	 * add an embedded file to mail
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 * @param string $content file content or file path
	 * @param string $name filename
	 * @param string $encoding encoding type
	 * @param string $cType content type
	*/
	public function addEmbeddedFile($content, $name=null, $encoding='base4', $cType='application/octet-stream')
	{		
		$part =& $this->_createMailPart($content, $name, $encoding, $cType);
		$part->setDisposition('inline');
		$this->embeddedFiles[] =& $part;
		
		return $part;
	}
	
	/**
	 * add header to mail
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 * @param string name
	 * @param string value
	*/
	public function addHeader($name, $value)
	{
		$this->headers[$name] = $value;
	}
	
	/**
	 * add an replyto contact to email
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 * @param string email adress
	 * @param string contact name
	*/
	public function addReplyTo($email, $name=null)
	{
		$this->replyTo[] = array($email, $name);
	}
	
	/**
	 * add an to contact to email
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 * @param string email adress
	 * @param string contact name
	*/
	public function addTo($email, $name=null)
	{
		$this->to[] = array($email, $name);
	}
	
	/**
	 * build mail message for sending
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	*/
	public function build()
	{
		$this->_buildFrom();
		$this->_buildRecipients();
		$this->_buildHeaders();
		$this->_buildBody();
	}
	
	/**
	 * get attachments
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array
	*/
	public function getAttachments()
	{
		return $this->attachments;
	}
	
	/**
	 * get bcc
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array
	*/
	public function getBcc()
	{
		return $this->bcc;
	}
	
	/**
	 * get cc
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array
	*/
	public function getCc()
	{
		return $this->Cc;
	}
	
	/**
	 * get embedded files
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array
	*/
	public function getEmbeddedFiles()
	{
		return $this->embeddedFiles;
	}
	
	/**
	 * get date
	 *
	 * @since 0.1.0
	 * @access public
	 * @param string $format=null date format for php's date function
	 * @return string
	*/
	public function getDate($format=null)
	{
		if($format !== null) {
			$date = date($format, $this->date);
		}
		
		return $date;
	}
	
	/**
	 * get from
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array
	*/
	public function getFrom()
	{
		return $this->from;
	}
	
	/**
	 * get headers
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array
	*/
	public function getHeaders()
	{
		return $this->headers;
	}
	
	/**
	 * get hostname
	 *
	 * @since 0.1.0
	 * @access public
	 * @return string
	*/
	public function getHostname()
	{
		return $this->hostname;
	}
	
	/**
	 * get html body
	 *
	 * @since 0.1.0
	 * @access public
	 * @return string
	*/
	public function getHtmlBody()
	{
		return $this->htmlBody;
	}
	
	/**
	 * get reply to
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array
	*/
	public function getReplyTo()
	{
		return $this->replyTo;
	}
	
	/**
	 * get priority
	 *
	 * @since 0.1.0
	 * @access public
	 * @return int
	*/
	public function getPriority()
	{
		return $this->priority;
	}
	
	/**
	 * get subject
	 *
	 * @since 0.1.0
	 * @access public
	 * @return string
	*/
	public function getSubject()
	{
		return $this->subject;
	}
	
	/**
	 * get text body
	 *
	 * @since 0.1.0
	 * @access public
	 * @return string
	*/
	public function getTextBody()
	{
		return $this->textBody;
	}
	
	/**
	 * get to contact data
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array
	*/
	public function getTo()
	{
		return $this->to;
	}
	
	/**
	 * get xmailer
	 *
	 * @since 0.1.0
	 * @access public
	 * @return string
	*/
	public function getXMailer()
	{
		return $this->xMailer;
	}
	
	/**
	 * send mail
	 *
	 * @since 0.1.0
	 * @access public
	 * @return bool
	 * @throws @see MailTransport::send();
	*/
	public function send(&$transport)
	{		
		try {
			$transport->send($this);
		}
		catch(MailException $e) {
			throw($e);
			return false;
		}
		
		return true;
	}
	
	/**
	 * set date
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 * @param string date
	*/
	public function setDate($date)
	{
		$this->date = $date;
	}
	
	/**
	 * set from contact to email
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 * @param string email adress
	 * @param string contact name
	*/
	public function setFrom($email, $name)
	{
		$this->from = array($email, $name);
	}
	
	/**
	 * set hostname
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 * @param string hostname
	*/
	public function setHostname($hostname)
	{
		$this->hostname = $hostname;
	}
	
	/**
	 * set text body
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 * @param string body
	*/
	public function setHtmlBody($body)
	{
		$this->htmlBody = $body;
		$this->setContentType('text/html');
	}
	
	/**
	 * set priority
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 * @param int priority
	*/
	public function setPriority($priority)
	{
		$this->priority = $priority;
	}
	
	/**
	 * set subject
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 * @param string subject
	*/
	public function setSubject($subject)
	{
		$this->subject = $subject;
	}
	
	/**
	 * set text body
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 * @param string body
	*/
	public function setTextBody($body)
	{
		$this->textBody = $body;
	}
	
	/**
	 * set Xmailer
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 * @param string x mailer
	*/
	public function setXMailer($mailer)
	{
		$this->xMailer = $mailer;
	}
	
	/**
	 * set seen state
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 * @param bool $value
	*/
	public function setSeen($value)
	{
		$this->seen = (bool) $value;
	}
	
	/**
	 * set recent state
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 * @param bool $value
	*/
	public function setRecent($value)
	{
		$this->recent = (bool) $value;
	}

	/**
	 * set message id
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 * @param bool $value
	*/
	public function setMessageId($value)
	{
		$this->msgid = $value;
	}
	
	/**
	 * get seen state
	 *
	 * @since 0.1.0.0
	 * @access public
	 * @return bool
	*/
	public function isSeen()
	{
		return $this->seen;
	}
	
	/**
	 * get recent state
	 *
	 * @since 0.1.0
	 * @access public
	 * @return bool
	*/
	public function isRecent()
	{
		return $this->recent;
	}
	
	/**
	 * create mail part for attachment or embedded file
	 *
	 * @since 0.1.0
	 * @access protected
	 * @return void
	 * @param string $content file content or file path
	 * @param string $name filename
	 * @param string $encoding encoding type
	 * @param string $cType content type
	*/
	protected function _createMailPart($content, $name=null, $encoding='base4', $cType='application/octet-stream')
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
