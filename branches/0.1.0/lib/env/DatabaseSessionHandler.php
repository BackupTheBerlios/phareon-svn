<?php

/**
 * DatabaseSessionHandler is a Session Handler class using phareons databse
 * abstraction and database tables
 *
 * This class should not use manuelly, but use session_set_save_handler();
 * <code>$shandler = new DatabaseSessionHandler($database);
 * session_set_save_handler(
 *		array($shandler, 'open'),
 *		array($shandler, 'close'),
 *		array($shandler, 'read'),
 *		array($shandler, 'write'),
 *		array($shandler, 'destroy'),
 *		array($shandler, 'gc')
 * );</code>
 *
 * Use now your session as usual with $_SESSION ...
 *
 * @author David Molineus <david at molineus dot de>
 * @since 0.1
 * @package phareon.lib.env
*/
class DatabaseSessionHandler
{
	/**
	 * reference to database connection 
	 *
	 * @since 0.1.0
	 * @access private
	 * @var Database
	*/
	private $db;
	
	/**
	 * constructor
	 *
	 * @since 0.1.0
	 * @access public
	 * @return void
	 * @param Database
	*/
	function __construct(Database $db)
	{
		$this->db = $db;
	}
	
	/**
	 * open implementation
	 *
	 * @since 0.1.0
	 * @access public
	 * @return bool
	 * @param string $path
	 * @param string $name
	*/
	public function open($path, $name)
	{
		return (bool) is_resource($this->db->getConnection());
	}
	
	/**
	 * close implementation
	 *
	 * @since 0.1.0
	 * @access public
	 * @return bool
	*/
	public function close()
	{
		return true;
	}
	
	/**
	 * read session data from database
	 *
	 * @since 0.1.0
	 * @access public
	 * @return bool
	 * @param string $id
	*/
	public function read($id)
	{
		$sql = 'SELECT session.data FROM session WHERE session.id = ?';
		
		$statement = $this->db->prepareStatement($sql);
		$statement->setInteger(1, $id);
		
		try {
			$result = $statement->query();
		}
		catch(DatabaseException $e) {
			return false;
		}
		
		if($result instanceof Result) {
			return $result->getString('data');
		}
		
		return false;
	}
	
	/**
	 * store session data into database
	 *
	 * @since 0.1.0
	 * @access public
	 * @return bool
	 * @param string $id
	*/
	public function write($id, $data)
	{
		$sql = 'SELECT count(*) AS number FROM session WHERE session.id = ?';
		
		$statement = $this->db->prepareStatement($sql);
		$statement->setString(1, $id);
		
		try {
			$result = $statement->query();
		}
		catch(DatabaseException $e) {
			return false;
		}
		
		if($result->getInteger('number') === 1) {
			$sql = 'UPDATE session SET session.data = ? WHERE session.id = ?';
		}
		else {
			$sql = 'INSERT INTO session (data, id) VALUES (?, ?)';		
		}
		
		$statement = $this->db->prepareStatement($sql);
		$statement->setString(1, $data);
		$statement->setString(2, $id);
		
		try {
			$statement->query();
		}
		catch(DatabaseException $e) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * delete session data from database
	 *
	 * @since 0.1.0
	 * @access public
	 * @return bool
	 * @param string $id
	*/
	public function destroy($id)
	{
		$sql = 'DELETE FROM session WHERE session.id = ?';
		
		$statement = $this->db->prepareStatement($sql);
		$statement->setString(1, $id);
		
		try {
			$statement->query();
		}
		catch(DatabaseException $e) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * do carbage collection
	 *
	 * @since 0.1.0
	 * @access public
	 * @return bool
	 * @param int $lifetime
	*/
	public function gc($lifetime)
	{
		$sql = 'DELETE FROM session WHERE session.timestamp < ?';
		
		$statement = $this->db->prepareStatement($sql);
		$statement->setString(1, (time() - $lifetime));
		
		try {
			$statement->query();
		}
		catch(DatabaseException $e) {
			return false;
		}
		
		return true;
	}
}

?>