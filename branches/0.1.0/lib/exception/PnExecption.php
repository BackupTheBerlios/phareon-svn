<?php

include_once 'lib/exception/ExceptionList.php';

/**
 * Exception for the phareon system
 *
 * @author David Molineus <david at molineus dot de>
 * @version $Revision: 12$
 * @since 0.1
 * @package phareon.lib.exception
*/
class PnException
{
	/**
	 * exception identifer
	 *
	 * @since 0.1
	 * @access protected
	 * @var string
	*/
	var $id;
	
	/**
	 * backrace information
	 *
	 * @since 0.1
	 * @access protected
	 * @var array
	*/
	var $trace;
	
	/**
	 * file where exception was thrown
	 *
	 * @since 0.1
	 * @access protected
	 * @var string
	*/
	var $file;
	
	/**
	 * int where exception was thrown
	 *
	 * @since 0.1
	 * @access protected
	 * @var int
	*/
	var $line;
	
	/**
	 * exception message
	 *
	 * @since 0.1
	 * @access protected
	 * @var string
	*/
	var $message;
	
	/**
	 * constructor
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	 * @param string $id
	 * @param string $message
	 * @param string $file
	 * @param string $line
	*/
	function PnException($id, $message, $file=null, $line=null)
	{
		$this->id = $id;
		$this->message = $message;
		$this->file = $file;
		$this->line = $line;
		$this->trace = debug_backtrace();
	}
	
	/**
	 * get exception id
	 *
	 * @since 0.1
	 * @access public
	 * @return string
	*/
	function getId()
	{
		return $this->id;
	}
	
	/**
	 * get exception message
	 *
	 * @since 0.1
	 * @access public
	 * @return string
	*/
	function getMessage()
	{
		return $this->message;
	}
	
	/**
	 * get file
	 *
	 * @since 0.1
	 * @access public
	 * @return string
	*/
	function getFile()
	{
		return $this->file;
	}
	
	/**
	 * get line
	 *
	 * @since 0.1
	 * @access public
	 * @return int
	*/
	function getLine()
	{
		return $this->line;
	}
	
	/**
	 * get backtrace
	 *
	 * @since 0.1
	 * @access public
	 * @return array
	*/
	function getTrace()
	{
		return $this->trace;
	}
	
	/**
	 * get excpetion formatted as string
	 *
	 * @since 0.1
	 * @access public
	 * @return string
	*/
	function toString()
	{
		$output = '<ul>';
		$output .= '<b>ID:</b> ' . $this->getId();
		$output .= '<b>Message:</b> ' . $this->getMessage();
		$output .= '<b>File:</b> ' . $this->getFile();
		$output .= '<b>Line:</b> ' . $this->getLine();
		$output .= '</ul>' . "\r\n";
		
		return $output;
	}
	
	/**
	 * magic copy method implementation for php5 
	 *
	 * @since 0.1
	 * @access public
	 * @return PnException
	*/
	function __copy()
	{
		return $this;
	}
}

?>