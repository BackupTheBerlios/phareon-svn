<?php

/**
 * Result is used by Database to provide a layer for handle one result row
 *
 * @author André Schmidt <schmidt at softwarecreator dot de>
 * @author David Molineus <david at molineus dot de>
 * @since 0.1.0
 * @package phareon.lib.database
*/
class Result
{
    /**
     * current result row
     *
     * @since 0.1.0
     * @access protected
     * @var array
    */
	protected $row;
	
	/**
     * fetched result
     *
     * @since 0.1.0
     * @access protected
     * @var array
    */
	protected $result;
	
	
	/**
	 * constructor
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 * @param resource
	 * @param int $mode fetch mode
	*/
	function __construct($result, $mode = Database::FETCH_ASSOC)
	{
		$this->result = $result;
		$this->row = mysql_fetch_array($this->result, $this->mode);
	}
	
	/**
	 * count all rows
	 *
	 * @since 0.1.0
	 * @access public
	 * @return int
	*/
	function count()
	{
		return mysql_num_rows($this->result);
	}
	
	/**
	 * get value as integer
	 *
	 * @since 0.1.0
	 * @access public
	 * @return int
	 * @param mixed string or integer
	 * @throws DatabaseException if index does not exists
	*/
	public function getInteger($index)
	{
	    $this->_checkIndex($index);
	
        if(isset($this->row[$index]))
        {
	        return intval($this->row[$index]);
	    }
	    
	    throw($this->_throwInvalidIndex($index));
	}

    /**
	 * get value as string
	 *
	 * @since 0.1.0
	 * @access public
	 * @return string
	 * @param mixed string or integer
	 * @throws DatabaseException if index does not exists
	*/
	public function getString($index)
	{
	    $this->_checkIndex($index);
	    
	    if(isset($this->row[$index]))
        {
	        return strval($this->row[$index]);
	    }
	
	    throw($this->_throwInvalidIndex($index));
	}
	
	/**
	 * get value as float
	 *
	 * @since 0.1.0
	 * @access public
	 * @return float
	 * @param mixed string or integer
	 * @throws DatabaseException if index does not exists
	*/
	public function getFloat($index)
	{
	    $this->_checkIndex($index);
	    
	    if(isset($this->row[$index]))
        {
	        return floatval($this->row[$index]);
	    }
	
	    throw($this->_throwInvalidIndex($index));
	}

    /**
	 * get value as array
	 *
	 * @since 0.1.0
	 * @access public
	 * @return array
	 * @param mixed string or integer
	 * @throws DatabaseException if index does not exists
	*/
    public function getArray($index)
	{
	    $this->_checkIndex($index);
	    
	    if(isset($this->row[$index]))
        {
	        return unserialize($this->row[$index]);
	    }
	
	    throw($this->_throwInvalidIndex($index));
	}

	/**
	 * get value as boolean type
	 *
	 * @since 0.1.0
	 * @access public
	 * @return bool
	 * @param mixed string or integer
	 * @throws DatabaseException if index does not exists
	*/
	public function getBoolean($index)
	{
	    $this->_checkIndex($index);
	    
	    if(isset($this->row[$index]))
        {
	        return strval($this->row[$index]);
	    }
	
	    throw($this->_throwInvalidIndex($index));
	    
		if ($this->row[$index] === 1)
		{
			return true;
		}
		return false;
	}
	
	/**
	 * check if an index is an int. if it's true the value will be decrease,
	 * because PHP's array starts at 0 not as 1 like used in this system
	 *
	 * @since 0.1.0
	 * @access protected
	 * @return void
	 * @param mixed
	*/
	protected function _checkIndex(&$index)
    {
        if(is_int($index)) {
            $index--;
        }
	}
	
	/**
	 * create an exception for throwing if an index does not exists
	 *
	 * @since 0.1.0
	 * @access protected
	 * @return DatabaseException
	*/
	protected function _throwInvalidIndex($index)
	{
	    return new DatabaseException(
            sprintf("Invalid index '%d'. Index does not exists", $index)
        );
	}
}

?>
