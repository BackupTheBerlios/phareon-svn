<?php

/**
 * MailPart represents a mime part of a mime mail. It can be an attachment, an
 * embedded file or a plain text or html part
 *
 * @author David Molineus <david at molineus dot de>
 * @since 0.1
 * @package phareon.lib.mail
*/
class MailPart
{
	/**
	 * charset
	 *
	 * @since 0.1
	 * @access protected
	 * @var string
	*/
	protected $charset = 'iso-8859-1';
	
	/**
	 * Content of MailPart 
	 *
	 * @since 0.1
	 * @access protected
	 * @var string
	*/
	protected $content;
	
	/**
	 * Content id
	 *
	 * @since 0.1
	 * @access protected
	 * @var string
	*/
	protected $contentId;
	
	/**
	 * Content disposition
	 *
	 * @since 0.1
	 * @access protected
	 * @var string
	*/
	protected $contentDisposition = null;
	
	/**
	 * Content type
	 *
	 * @since 0.1
	 * @access protected
	 * @var string
	*/
	protected $contentType = 'text/plain';
	
	/**
	 * Content encoding
	 *
	 * @since 0.1
	 * @access protected
	 * @var string
	*/
	protected $contentEncoding = '8bit';
	
	/**
	 * Boundary id of mail part
	 *
	 * @since 0.1
	 * @access protected
	 * @var string
	*/
	protected $boundaryId;
	
	/**
	 * filename
	 *
	 * @since 0.1
	 * @access protected
	 * @var string
	*/
	protected $filename;
	
	
	/**
	 * Constructor
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	*/
	function __construct()
	{
		$this->contentId = md5(uniqid(time()));
	}
	
	/**
	 * get charset
	 *
	 * @since 0.1
	 * @access public
	 * @return string
	*/
	public function getCharset()
	{
		return $this->charset;
	}
	
	/**
	 * get content
	 *
	 * @since 0.1
	 * @access public
	 * @return string
	*/
	public function getContent()
	{
		return $this->content;
	}
	
	/**
	 * get content disposition
	 *
	 * @since 0.1
	 * @access public
	 * @return string
	*/
	public function getContentDisposition()
	{
		return $this->contentDisposition;
	}
	
	/**
	 * get content encoding
	 *
	 * @since 0.1
	 * @access public
	 * @return string
	*/
	public function getContentEncoding()
	{
		return $this->contentEncoding;
	}
	
	/**
	 * get content id
	 *
	 * @since 0.1
	 * @access public
	 * @return string
	*/
	public function getContentId()
	{
		return $this->contentId;
	}
	
	/**
	 * get content type
	 *
	 * @since 0.1
	 * @access public
	 * @return string
	*/
	public function getContentType()
	{
		return $this->charset;
	}
	
	/**
	 * get bounary id
	 *
	 * @since 0.1
	 * @access public
	 * @return string
	*/
	public function getBoundaryId()
	{
		return $this->boundaryId;
	}
	
	/**
	 * get filename
	 *
	 * @since 0.1
	 * @access public
	 * @return string
	*/
	public function getFilename()
	{
		return $this->filename;
	}
	
	/**
	 * set charset
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	 * @param string $charset
	*/
	public function setCharset($charset)
	{
		$this->charset = $charset;
	}
	
	/**
	 * set content
	 *
	 * @since 0.1
	 * @access public
	 * @return bool
	 * @param string $content content of filename
	 * @param boolean $isFile=false if is true load content from file $content
	*/
	public function setContent($content, $isFile=false)
	{
		if(!$isFile) {
			$this->content = $content;
			return true;
		}
		
		$this->content = file_get_contents($content);		
		if($this->content !== false) {
			return true;			
		}
		
		throw new MailException(sprintf(
			"Could not read content from '%s'.", $content)
		);
		
		return false;
	}
	
	/**
	 * set content encoding
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	 * @param string $encoding
	*/
	function setContentEncoding($encoding)
	{
		$this->contentEncoding = $encoding;
	}
	
	/**
	 * set content disposition
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	 * @param string $disposition
	*/
	function setContentDisposition($disposition)
	{
		 $this->$disposition = $disposition;
	}
	
	/**
	 * set content type
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	 * @param string $type
	*/
	function setContentType($type)
	{
		$this->contentType = $type;
	}
	
	/**
	 * set boundary id
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	 * @param string $id
	*/
	function setBoundaryId($id)
	{
		$this->boundaryId = $id;
	}
	
	/**
	 * set filename
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	 * @param string $name
	*/
	function setFilename($name)
	{
		$this->filename = $name;
	}
}

?>