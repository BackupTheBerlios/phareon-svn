<?php

require_once 'creole/IdGenerator.php';

/**
 * MSSQL IdGenerator implimenation.
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @author    Michael Aichler <aichler@mediacluster.de>
 * @version   $Revision: 1.1 $
 * @package   creole.drivers.mssql
 */
class MSSQLIdGenerator extends IdGenerator 
{
  /** Connection object that instantiated this class */
  var $conn;

  /**
   * Creates a new IdGenerator class, saves passed connection for use
   * later by getId() method.
   * @param Connection $conn
   */
  function MSSQLIdGenerator(/*Connection*/ &$conn)
  {
    if (! is_a($conn, 'Connection')) {
      trigger_error(
        "MSSQLIdGenerator::MSSQLIdGenerator(): parameter 1 not of type 'Connection' !",
        E_USER_ERROR
      );
    }
      
    $this->conn =& $conn;
  }
  
  /**
   * @see IdGenerator::isBeforeInsert()
   */
  function isBeforeInsert()
  {
    return false;
  }    
  
  /**
   * @see IdGenerator::isAfterInsert()
   */
  function isAfterInsert()
  {
    return true;
  }
      
  /**
   * @see IdGenerator::getIdMethod()
   */
  function getIdMethod()
  {
    return IdGenerator::AUTOINCREMENT();
  }
  
  /**
   * @see IdGenerator::getId()
   * @return mixed The id on success, SQLException on failure.
   */
  function getId($unused = null)
  {
    $rs =& $this->conn->executeQuery("select @@identity", ResultSet::FETCHMODE_NUM());
    if (Creole::isError($rs)) { return $rs; }
    $e = $rs->next();
    if (Creole::isError($e)) { return $e; }
    return $rs->getInt(1);        
  }
  
}

