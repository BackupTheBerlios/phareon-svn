<?php

/**
 * MailFilter is a abstract class uses for every filter class in
 * phareon.lib.mail.filter
 *
 * @author David Molineus <david at molineus dot de>
 * @since 0.1.0
 * @package phareon.lib.mail
*/
abstract class MailFilter
{
	protected $error;
	
	public abstract function execute(MailMessage $mail);
}

?>