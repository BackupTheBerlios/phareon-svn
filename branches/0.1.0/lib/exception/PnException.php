<?php

//include_once 'lib/exception/ExceptionList.php';

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
        
        echo '<div style="display: block; border: 1px solid #ff0000;';
        echo 'padding:5px; background-color: #f5f5f5; margin: 5px;">' . $nl;
        echo '<b>Exception "' . get_class($this) . '" was thrown:</b>' . $nl;
        echo '<ul>' . $nl;
        echo '<li><b>Message:</b> ' . $this->message . '</li>' . $nl;
        echo '<li><b>File:</b> ' . $this->file . '</li>' . $nl;
        echo '<li><b>Line:</b> ' . $this->line . ' </li>' . $nl;
        echo '<li><b>Debug backtrace:</b> <a href="#top" onclick="';
        echo 'document.getElementById(\''.$id.'\').style.display =';
        echo '((document.getElementById(\''.$id.'\').style.display == \'none\')';
        echo ' ? \'block\' : \'none\');">show / hide</a>' . $nl;
        echo '<div id="' .$id. '" style="display:none;"><pre>' . $nl;
        print_r($this->getTrace());
        echo '</pre></div></li>' . $nl;
        echo '</ul></div>' . $nl;
	}
}

?>