<?php

/**
 * Statement is a tool for creating simple sql query with storing data in it. To
 * use this class you should use Database's prepareStatement($sql) method. Here
 * is an example for using it:
 *
 * <code>
 * $statement = $db->prepareStatement('UPDATE table SET name = ? WHERE id = ?');
 * $statement->setString(1, 'Test');
 * $statement->setInteger(2, 40053);
 * try {
 *     $result = $statement->query();
 * }
 * catch(DatabaseException $e) {
 *     echo 'Can not execute query. SQL Statment is invalid';
 * }
 * echo 'Affected rows: ' . $result;
 * </code>
 *
 * @author André Schmidt <schmidt at softwarecreator dot de>
 * @author David Molineus <david at molineus dot de>
 * @since 0.1.0
 * @package phareon.lib.database
*/
class Statement
{
    /**
     * Params which are set for the statement
     *
     * @var array
    */
	protected $params = array();
	
	/**
     * Sql statement
     *
     * @var string
    */
	protected $sql;
	
	/**
     * Reference to database object
     *
     * @var Database
    */
	protected $database;
	
	
	/**
     * constructor
     *
     * @return void
     * @param Database
     * @param string
    */	
	function __construct(Database $database, $sql)
	{
	    $this->sql = $sql;
	    $this->database = $database;
	}
	
	/**
     * delete all set params
     *
     * @return void
    */
	public function deleteParams()
	{
		$this->param = array();
	}
	
	/**
	 * escape value
	 *
	 * @return string
	*/
	public function escape($value)
	{
	    return mysql_escape_string($value);
	}
	
	/**
     * set a param as an array
     *
     * @return bool
     * @param int $nr
     * @param array $value
    */
	public function setArray($nr,$value)
	{
		if (is_int($nr) && is_array($value))
		{
		    $this->params[$nr] = $this->escape(serialize($value));
		    return true;
		}
		
		return false;
	}
	
	/**
     * set a param as a boolean value
     *
     * @return bool
     * @param int $nr
     * @param bool $value
    */
	public function setBoolean($nr,$value)
	{
		if (is_int($nr))
		{
			$this->param[$nr] = ($value === true ? 1 : 0 );
			return true;
		}
		
		return false;
	}
	
	/**
     * set a param as a float
     *
     * @return bool
     * @param int $nr
     * @param float $value
    */
	public function setFloat($nr,$value)
	{
		if (is_int($nr))
		{
			$this->params[$nr] = floatVal($value);
			return true;
		}
		
		return false;
	}
	
	/**
     * set a param as an integer
     *
     * @return bool
     * @param int $nr
     * @param int $value
    */
	public function setInteger($nr, $value)
	{
		if (is_int($nr))
		{
			$this->params[$nr] = intVal($value);
			return true;
		}
		
		return false;
	}
	
	/**
     * set a param as a null value
     *
     * @return bool
     * @param int $nr
    */
	public function setNull($nr)
	{
		$this->params[$nr] = 'NULL';
		return true;
	}
	
    /**
     * set a param as a string
     *
     * @return bool
     * @param int $nr
     * @param string $value
    */
	public function setString($nr,$value)
	{
		if(is_int($nr))
		{
		    $this->params[$nr] = "'" . $this->escape(strVal($value)) . "'";
		    return true;
		}
		
		return false;
	}
	
	/**
     * prepare sql string
     *
     * @return string
     * @throws DatabaseException if number of params and gaps does not agree
    */
	public function prepareSql()
	{
	    if (count($this->params) != substr_count($this->sql, '?'))
		{
		    throw new DatabaseException(sprintf(
                "The number of set params '%d' does not agree with the number "
                ."of gaps (?) '%d'  in the statement",
                count($this->params), substr_count($this->sql, '?')
                )
            );
		    return false;
		}
		
		if (substr_count($this->sql,'?') != 0)
		{
			$p = explode("?", trim($this->sql));
			$sql = '';
			for ($i = 0; $i < count($p)-1; $i++)
			{
				$sql = $sql . $p[$i] . $this->params[$i+1];
			}
		}
		else {
		    $sql = $this->sql;
		}

		return $sql;
	}
	
	/**
     * execute statement
     *
     * @see Database::query()
     * @return mixed
     * @param int $type
    */
	public function query($resultType=Database::RecordSet, $mode=Database::FETCH_ASSOC)
	{
	    try {
	        $sql = $this->prepareSql();
	    }
	    catch(DatabaseException $e) {
	        throw $e;
	        return false;
        }
        
	    return $this->database->query($sql, $resultType, $mode);
	}
}

?>
