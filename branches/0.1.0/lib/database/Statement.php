<?php

/**
 *
 *
 *
*/
class Statement
{
    /**
     *
     *
     *
    */
	private $params = array();
	
	/**
     *
     *
     *
    */
	private $sql;
	
	/**
     *
     *
     *
    */
	private $database;
	
	/**
     *
     *
     *
    */	
	function __construct(Database $database, $sql)
	{
	    $this->sql = $sql;
	    $this->database = $database;
	}
	
	/**
     *
     *
     *
    */
	public function setInteger($nr, $value)
	{
		if (is_int($nr))
		{
			return $this->params[$nr] = intVal($value);
		}
	}
	
	/**
     *
     *
     *
    */
	public function setString($nr,$value)
	{
		if(is_int($nr))
		{
		    return $this->params[$nr] = mysql_escape_string(strVal($value));
		}
	}
	
	/**
     *
     *
     *
    */
	public function setFloat($nr,$value)
	{
		if (is_int($nr))
		{
			return $this->params[$nr] = floatVal($value);
		}
	}
	
	/**
     *
     *
     *
    */
	public function setArray($nr,$value)
	{//serialize
		if (is_int($nr) && is_array($value))
		{
		    return $this->setString($nr, serialize($value));
		}
	}
	
	/**
     *
     *
     *
    */
	public function setBoolean($nr,$value)
	{
		if (is_int($nr))
		{
			return $this->param[$nr] = ($value === true ? 1 : 0 );
		}
	}
	
	/**
     *
     *
     *
    */
	public function deleteParams()
	{
		$this->param = array();
	}
	
	/**
     *
     *
     *
    */
	public function query($type=Database::FETCH_ASSOC)
	{
	    try {
	        $sql = $this->prepare();
	    }
	    catch(DatabaseException $e) {
	        throw $e;
	        return false;
        }
        
	    return $this->database->query($sql, $type);
	}
	
	/**
     *
     *
     *
    */
	public function prepare()
	{
	    if (count($this->params) != substr_count($this->sql, '?'))
		{
		    throw new DatabaseException(sprintf(
                "The number of set params '%d' does not agree with the number "
                ."of gaps (?) '%d'  in the statement",
                count($this->params), substr_count($this->sql, '?')
            );
		    return false;
		}
		
		if (substr_count($this->sql,'?') != 0)
		{
			$p = explode("?", trim($this->sql));
			$sql = '';
			for ($i = 0; $i < count($p)-1; $i++)
			{
				$sql = $sql . $p[$i] . "'" . $this->params[$i+1] . "'";
			}
		}

		return $sql;
	}
}

?>
