<?php

class MailException extends PnException
{
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
	function MailException($id, $message, $file=null, $line=null)
	{
		parent::PnException($id, $message, $file, $line);
	}
}

?>