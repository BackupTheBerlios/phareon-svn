<?php

include_once 'lib/database/DatabaseException.php';
include_once 'lib/database/Statement.php';
include_once 'lib/database/Record.php';
include_once 'lib/database/RecordSet.php';

/**
 * Database is a basic class for connect to a database and execute queries
 *
 * @author André Schmidt <schmidt at softwarecreator dot de>
 * @author David Molineus <david at molineus dot de>
 * @package phareon.lib.database
*/
class Database
{
    /**
     * Use FETCH_ASSOC for fetching result as an associate array
     *
     * @var int
    */
    const FETCH_ASSOC = MYSQL_ASSOC;
    
    /**
     * Use FETCH_NUM for fetching result as a numeric array
     *
     * @var int
    */
    const FETCH_NUM = MYSQL_NUM;
    
    /**
     * Use FETCH_BOTH for fetching result as an associative and a numeric array
     *
     * @var int
    */
    const FETCH_BOTH = MYSQL_BOTH;
    
    /**
     * Used for get a Record as result of Database::query()
     *
     * @var int
    */
    const Record = 1024;
    
    /**
     * Used for get a RecordSet as result of Database::query()
     *
     * @var int
    */
    const RecordSet = 2048;
    
    /**
     * FORCE_RESULT forces a record or recordset as result of query
     *
     * Example:
     * $db->query($sql, Database::RecordSet | Database::FORCE_RESULT)
     *
     * @var int
    */
    const FORCE_RESULT = 4096;
    
    
    /**
     * Database connection resource
     *
     * @var resource
    */
    private $connection;
    
    /**
     * destructor
     *
     * disconnect database connection
     *
     * @return void
    */
    function __destruct()
    {
        $this->disconnect();
    }

    /**
     * connect to database
     *
     * @return bool
     * @param string $host
     * @param string $host
     * @param string $password
     * @param string $database database name
     * @throws DatabaseException if connection failed
    */
    public function connect($host, $user, $password, $database = null)
    {
        //connect to the database
		$conn = mysql_connect($host,$user,$password); //connect to the host
		if(!$conn)
		{
			throw new DatabaseException(sprintf(
                "Could not connect to database '%s', username '%s'"
                . "and using password '%s'", $host, $user,
                ($password !== null ? 'yes' : 'no'))
            );
            
			return false;
		}
		
        $this->connection = $conn; //handle of the connection
			
		if($database === null) {
		    return true;
		}
		
		try {
		    $this->selectDatabase($database);
		}
		catch(DatabaseException $e) {
		    throw $e;
		}
		
		return false;	
    }
    
    /**
     * select database
     *
     * @return bool
     * @param string $database database name
    */
    public function selectDatabase($database)
    {
        $result = mysql_select_db($database, $this->getConnection());
        if($result) {
            return true;
        }
        
        throw new DatabaseException(sprintf(
            "Select database '%s' failed. Following error is occured: '%s'",
            $database, mysql_error($this->getConnection())
            )
        );
        
        return false;
    }

    /**
     * disconnect to database
     *
     * @return bool
    */
    public function disconnect()
    {
        if(is_resource($this->connection)) {
            return mysql_close($this->getConnection());
        }
        
        return false;
    }

    /**
     * prepare a statement
     *
     * @return Statement
     * @param string $sql
    */
    public function prepareStatement($sql)
    {
        return new Statement($this, $sql);
    }

    /**
     * get connection identifer
     *
     * @return resource
    */
    public function getConnection()
    {
        return $this->connection;
    }
    
    /**
     * execute sql statement
     *
     * The return of this method depends on the sql type and result:
     * <code><ul>
     * <li>SELECT statement and one row: @see Result</li>
     * <li>SELECT statement and multiple rows: @see ResultSet</li>
     * <li>otherwise: int (Number of affected rows)</li>
     * </ul></code>
     *
     * @return mixed
     * @param string $sql
     * @param int $resultType Database::RecordSet or Database::Record
     * @param int $mode
     * throws DatabaseException if mysql_query return an error
    */
    public function query($sql, $resultType=Database::RecordSet, $mode=Database::FETCH_ASSOC)
    {
        $result = mysql_query($sql, $this->getConnection());

		if($result === false)
		{
			//throw new DatabaseException('...');
            throw new DatabaseException(sprintf(
                "Error occured while executing sql statement '%s' "
                ." with following error message: '%s'.", $sql,
                mysql_error($this->getConnection()))
            );
			return false;
		}
		
		$force = ($resultType & self::FORCE_RESULT) === self::FORCE_RESULT;

		if((stripos($sql,'select') !== false) || $force)
		{
			if($resultType === self::Record)
			{
				return new Record($result, $mode);
			}
			else
			{
				return new RecordSet($result, $mode);
			}
		}
		
		return mysql_affected_rows($this->getConnection());
    }
}

?>
