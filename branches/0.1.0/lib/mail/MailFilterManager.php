<?php

include_once 'lib/util/ErrorList.php';

/**
 * MailFilterManager manages filters which can manipulate a MailMessage
 *
 * Usage example:
 * <code>$manager = new MailFilterManager(MailFilterManager::ERRORLIST_ONE);
 *
 * $filter = new MaxAttachmentMailFilter(array('max' => 2));
 * $manager->addFilter($filter);
 * unset($filter);
 *
 * $filter = new NoHtmlMailFilter();
 * $manager->addFilter($filter);
 * unset($filter);
 *
 * $reader = new Pop3MailReader();
 * $reader->connect('host', 'user', 'pw');
 *
 * while($reader->next()) {
 * 	   $mail = $reader->getMail();
 *	   try {
 *		  $manager->execute($mail);
 *	   }
 *	   catch(MailException $e) {
 *	       //error handling;
 *		   continue;
 *	   }
 *	   //mail in datenbank speichern
 * }</code>
 *
 * @author David Molineus <david at molineus dot de>
 * @since 0.1.0
 * @package phareon.lib.mail
*/
class MailFilterManager
{
	/** 
	 * constant is used to create one error list in the life of a 
	 * MailFilterManager instance
	 *
	 * @since 0.1.0
	 * @access public
	 * @var int
	*/
	const ERRORLIST_ONE = 0;
	
	/** 
	 * constant is used to create a new ErrorList every time execute is called
	 *
	 * @since 0.1.0
	 * @access public
	 * @var int
	*/
	const ERRORLIST_NEW = 1;
	
	/** 
	 * array of registered filters
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var array
	*/
	protected $filters = array();
	
	/** 
	 * ErrorList instance
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var ErrorList
	*/
	protected $errorList;
	
	/** 
	 * selected error handling methode
	 *
	 * @since 0.1.0
	 * @access protected
	 * @var int
	*/
	protected $errorMode;
	
	
	
	public function __construct($errorMode = self::ERRORLIST_ONE)
	{
		$this->errorMode = $errorMode;
		
		if($this->errorMode === self::ERRORLIST_ONE) {
			$this->errorList = new ErrorList();
		}
	}
	
	public function addFilter(MailFilter $filter)
	{
		$this->filters[] = $filter;
	}
	
	public function getErrorList() 
	{
		return $this->errorList;
	}
	
	public function execute(MailMessage $mail)
	{
		if($this->errorMode === self::ERRORLIST_NEW) {
			unset($this->errorList);
			$this->errorList = new ErrorList();
		}
		
		$keys = array_keys($this->filters);
		
		foreach($keys as $key) {
			try {
				$this->filters[$key]->execute($mail);
			}
			catch(ErrorException $e) {
				$this->errorList->addError($e->getMessage());
			}
		}
		
		return ($this->errorList->count() > 0) ? false : true;
	}
}

?>