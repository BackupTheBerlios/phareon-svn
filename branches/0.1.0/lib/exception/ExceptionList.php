<?php

/**
 * Exception class is an internal class handling exceptions which are thrown
 * and catched. You should not use this class directly. To use exception 
 * handling use this example:
 *
 * try(); {
 *     throw(new FooException('FooClass.Invalid', 'msg', __FILE__, __LINE__));
 *     throw(new MailException('MailClass.Invalid', 'msg', __FILE__, __LINE__));
 * }
 * if(catch('FooException', $e)) {
 *    echo $e->toString();
 * }
 * if(catch('MailException', $e)) {
 *    echo $e->toString();
 * }
 *
 * @author David Molineus <david at molineus dot de>
 * @package phareon.lib.exception
 * @since 0.1
*/
class ExceptionList
{
	/**
	 * array of all handled exceptions
	 *
	 * @since 0.1
	 * @access protected
	 * @var array
	*/
	var $exceptions = array();
	
	/**
	 * singleton method
	 *
	 * @since 0.1
	 * @access public
	 * @return ExceptionList
	*/
	function &getInstance()
	{
		static $instance;
		
		if(!isset($instance)) {
			$instance = new ExceptionList();			
		}
		
		return $instance;
	}
	
	/**
	 * check if list has an exception of $class
	 *
	 * @since 0.1
	 * @access public
	 * @return bool
	 * @param string $class class name of exception
	*/	
	function has($class) {
		return (bool) isset($this->exceptions[$class]);
	}
	
	/**
	 * remove exception from list
	 *
	 * @since 0.1
	 * @access public
	 * @return bool
	 * @param string $class class name of exception
	*/
	function remove($class) {
		if(isset($this->exceptions[$class])) {
			unset($this->exceptions[$class]);
			return true;
		}
		
		return false;
	}
	
	/**
	 * reset exception list
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	*/
	function reset()
	{
		$this->exceptions = array();
	}
	
	/**
	 * throw new exception
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	 * @param Pnxception $e
	*/
	function throw($e) {
		$class = get_class($e);
		
		if($this->has($class)) {
			$msg = 'Unable to throw exception while another exception ';
			$msg .= 'of this exception type is not caught.';
			die($msg);
		}
		
		$this->exceptions[$class] = $e;
	}
	
	/**
	 * catch exception
	 *
	 * @since 0.1
	 * @access public
	 * @return bool
	 * @param string $class class name of exception
	 * @param PnException &$exception
	*/
	function catch($class, &$exception)
	{
		if($this->has($class)) {
			$exception = $this->exceptions[$class];
			$this->remove($class);
			return true;
		}
		
		return false;
	}
	
	/**
	 * start try block
	 *
	 * @since 0.1
	 * @access public
	 * @return void
	*/
	function try()
	{
		if(count($this->exceptions) > 0) {
			echo 'Unable to start new try part until all exceptions are not ';
			echo 'caught:<br />';
			
			$keys = array_keys($this->exceptions);
			foreach($keys as $key) {
				echo $this->exceptions[$key]->toString();
			}
			
			die();			
		}
		
		$this->reset();
	}	
}

/**
 * wrapper function for ExceptionList::try()
 *
 * @since 0.1
 * @access public
 * @return void
 * @see ExceptionList::try()
*/
function try()
{
	$list =& ExceptionList::getInstance();
	$list->try();
}

/**
 * wrapper function for ExceptionList::throw()
 *
 * @since 0.1
 * @access public
 * @return void
 * @see ExceptionList::throw()
 * @param PnException $e
*/
function throw($e)
{
	$list =& ExceptionList::getInstance();
	$list->throw($e);
}

/**
 * wrapper function for ExceptionList::catch()
 *
 * @since 0.1
 * @access public
 * @return bool
 * @see ExceptionList::catch()
 * @param string $class classs name of exception
 * @param PnException &$e
*/
function catch($class, &$e)
{
	$list =& ExceptionList::getInstance();
	return $list->catch($class, $e);
}

?>
