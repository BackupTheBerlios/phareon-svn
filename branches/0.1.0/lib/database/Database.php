<?php

include_once 'lib/database/DatabaseException.php';
include_once 'lib/database/Statement.php';
include_once 'lib/database/Result.php';
include_once 'lib/database/ResultSet.php';

/**
 * Database is a basic class for connect to a database and execute queries
 *
 * @author André Schmidt <schmidt at softwarecreator dot de>
 * @author David Molineus <david at molineus dot de>
 * @package phareon.lib.database
 * @since 0.1.0
*/
class Database
{
    /**
     * Use FETCH_ASSOC for fetching result as an associate array
     *
     * @since 0.1.0
     * @var int
    */
    const FETCH_ASSOC = MYSQL_ASSOC;
    
    /**
     * Use FETCH_NUM for fetching result as a numeric array
     *
     * @since 0.1.0
     * @var int
    */
    const FETCH_NUM = MYSQL_NUM;
    
    /**
     * Use FETCH_BOTH for fetching result as an associative and a numeric array
     *
     * @since 0.1.0
     * @var int
    */
    const FETCH_BOTH = MYSQL_BOTH;
    
    /**
     * Database connection resource
     *
     * @since 0.1.0
     * @var resource
    */
    private $connection;
    
    /**
     * destructor
     *
     * disconnect database connection
     *
     * @since 0.1.0
     * @access public
     * @return void
    */
    function __destruct()
    {
        $this->disconnect();
    }

    /**
     * connect to database
     *
     * @since 0.1.0
     * @access public
     * @return bool
     * @param string $host
     * @param string $host
     * @param string $password
     * @throws DatabaseException if connection failed
    */
    public function connect($host, $user, $password)
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
		else
		{
			mysql_select_db($database,$conn); //select the database
			return $this->connection = $conn; //handle of the connection
		}
    }

    /**
     * disconnect to database
     *
     * @since 0.1.0
     * @access public
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
     * @since 0.1.0
     * @access public
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
     * @since 0.1.0
     * @access public
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
     * @since 0.1.0
     * @access public
     * @return mixed
     * @param string $sql
     * @param int $type
     * throws DatabaseException if mysql_query return an error
    */
    public function query($sql, $type=Database::FETCH_ASSOC)
    {
        $result = mysql_query($sql, $this->getConnection(), $type);

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

		if(stripos($sql,'select') !== false)
		{
			if(mysql_num_rows($result) === 1)
			{
				return new Result($this, $result);
			}
			else
			{
				return new ResultSet($this, $result);
			}
		}
		
		return mysql_affected_rows($this->getConnection());
    }
}

?>
