<?php

/**
 * Exception for the phareon system
 *
 * @author David Molineus <david at molineus dot de>
 * @version $Revision: 12$
 * @since 0.1
 * @package phareon.lib.exception
*/
class PnException extends Exception
{	
	/**
	 * constructor
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	 * @param string $message
	*/
	function __constrcut($message)
	{
		parent::__construct($message);
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
		$id = md5(uniqid(time()));
        $nl = "\r\n";
        
        $out = '<div style="display: block; border: 1px solid #ff0000;';
        $out .= 'padding:5px; background-color: #f5f5f5; margin: 5px;">' . $nl;
        $out .= '<b>Exception "' . get_class($this) . '" was thrown:</b>' . $nl;
        $out .= '<ul>' . $nl;
        $out .= '<li><b>Message:</b> ' . $this->message . '</li>' . $nl;
        $out .= '<li><b>File:</b> ' . $this->file . '</li>' . $nl;
        $out .= '<li><b>Line:</b> ' . $this->line . ' </li>' . $nl;
        $out .= '<li><b>Debug backtrace:</b> <a href="#" onclick="';
        $out .= 'document.getElementById(\''.$id.'\').style.display =';
        $out .= '((document.getElementById(\''.$id.'\').style.display == \'none\')';
        $out .= ' ? \'block\' : \'none\');">show / hide</a>' . $nl;
        $out .= '<div id="' .$id. '" style="display:none;"><pre>' . $nl;
        $out .= print_r($this->getTrace(), true);
        $out .= '</pre></div></li>' . $nl;
        $out .= '</ul></div>' . $nl;
		
		return $out;
	}
	
	
	/**
	 *
	 *
	 *
	 *
	 *
	*/
	function __toString()
	{
		return $this->toString();
	}
}

?>
