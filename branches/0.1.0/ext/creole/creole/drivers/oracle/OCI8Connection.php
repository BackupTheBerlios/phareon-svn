<?php
/*
 *  $Id: OCI8Connection.php,v 1.1 2004/05/09 21:33:45 micha Exp $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://creole.phpdb.org>.
 */
 
require_once 'creole/Connection.php';
require_once 'creole/common/ConnectionCommon.php';

/**
 * Oracle implementation of Connection.
 * 
 * @author    David Giffin <david@giffin.org>
 * @author    Hans Lellelid <hans@xmpl.org>
 * @author    Michael Aichler <aichler@mediacluster.de>
 * @author    Stig Bakken <ssb@fast.no> 
 * @author    Lukas Smith
 * @version   $Revision: 1.1 $
 * @package   creole.drivers.oracle
 */ 
class OCI8Connection extends ConnectionCommon 
{        
  var $lastStmt = null;    

  /**
   * Connect to a database and log in as the specified user.
   *
   * @param array $dsn The data source hash.
   * @param int $flags Any connection flags.
   * @access public
   * @return mixed boolean TRUE on success, SQLException on failure.
   */
  function connect($dsninfo, $flags = 0)
  {
    if (!extension_loaded('oci8')) {
      return new SQLException(CREOLE_ERROR_EXTENSION_NOT_FOUND, 'oci8 extension not loaded');
    }

    $this->dsn = $dsninfo;
    $this->flags = $flags;
    
    $persistent = ($flags & Creole::PERSISTENT() === Creole::PERSISTENT());
    
    $user     = $dsninfo['username'];
    $pw       = $dsninfo['password'];
    $hostspec = $dsninfo['hostspec'];

    $connect_function = $persistent ? 'OCIPLogon' : 'OCILogon';

    @ini_set('track_errors', true);
    if ($hostspec && $user && $pw) {
      $conn = @$connect_function($user, $pw, $hostspec);
    } elseif ($user || $pw) {
      $conn = @$connect_function($user, $pw);
    } else {
      $conn = false;
    }
    @ini_restore('track_errors');
    
    if ($conn == false) {
      $error = @OCIError();
      $error = (is_array($error)) ? $error['message'] : null;
      return new SQLException(CREOLE_ERROR_CONNECT_FAILED, "connect failed", $err);
    }

    $this->dblink = $conn;
    return true;
  }


  /**
   * @see Connection::disconnect()
   */
  function close()
  {
    @OCILogOff($this->dblink);
  }        
  
  /**
   * @see Connection::executeQuery()
   */
  function & executeQuery($sql, $fetchmode = null)
  {
    $this->lastQuery = $sql;
    
    $result = @OCIParse($this->dblink, $sql);

    if (!$result) {
      return new SQLException(CREOLE_ERROR, "Unable to prepare query", $this->nativeError(), $sql);
    }

    $success = @OCIExecute($result,OCI_DEFAULT);
    if (!$success) {
      return new SQLException(CREOLE_ERROR, "Unable to execute query", $this->nativeError($result), $sql);
    }
    
    return new OCI8ResultSet($this, $result, $fetchmode);
  }

  
  /**
  * @see Connection::simpleUpdate()
  */
  function executeUpdate($sql)
  {    
    $this->lastQuery = $sql;

    $statement = @OCIParse($this->dblink, $sql);
    if (!$statement) {
      return new SQLException(CREOLE_ERROR, "Unable to prepare update", $this->nativeError(), $sql);            
    }
            
    if ($this->autocommit) {
        $success = @OCIExecute($statement, OCI_COMMIT_ON_SUCCESS);
    } else {
        $success = @OCIExecute($statement, OCI_DEFAULT);
        $this->transactionOpcount++;
    }

    if (!$success) {
      return new SQLException(CREOLE_ERROR, "Unable to execute update", $this->nativeError($statement), $sql);
    }

    $this->lastStmt = $statement;

    return @OCIRowCount($statement);
  }

  /**
  * Commit the current transaction.
  * @return TRUE if either successfully committed or no transactions to commit, SQLException on failure.
  */
  function commit()
  {
    if ($this->transactionOpcount > 0) {
      $result = @OCICommit($this->dblink);
      $this->transactionOpcount = 0;
      if (!$result) {
        return new SQLException(CREOLE_ERROR, "Unable to commit transaction", $this->nativeError());
      }
    }
    return true;
  }

  
  /**
  * Roll back (undo) the current transaction.
  * @return TRUE if either successfully rolled back or no transactions to rollback, SQLException on failure.
  */
  function rollback()
  {
    if ($this->transactionOpcount > 0) {
      $result = @OCIRollback($this->dblink);
      if (!$result) {
        return new SQLException(CREOLE_ERROR, "Unable to rollback transaction", $this->nativeError());
      }
    }
    return true;
  }

  
  /**
  * Gets the number of rows affected by the data manipulation
  * query.
  *
  * @return int Number of rows affected by the last query.
  * @todo -cOCI8Connection Figure out whether getUpdateCount() should throw exception on error or just return 0.
  */
  function getUpdateCount()
  {
    if (!$this->lastStmt) {
      return 0;
    }
    $result = @OCIRowCount($this->lastStmt);
    if ($result === false) {
      return new SQLException(CREOLE_ERROR, "Update count failed", $this->nativeError($this->lastStmt));
    }
    return $result;
  }


 /**
  * Build Oracle-style query with limit or offset.
  * If the original SQL is in variable: query then the requlting
  * SQL looks like this:
  * <pre>
  * SELECT B.* FROM (
  *          SELECT A.*, rownum as TORQUE$ROWNUM FROM (
  *                  query
  *          ) A
  *     ) B WHERE B.TORQUE$ROWNUM > offset AND B.TORQUE$ROWNUM
  *     <= offset + limit
  * </pre>
  *
  * @param string &$sql the query
  * @param int $offset
  * @param int $limit
  * @return void ($sql parameter is currently manipulated directly)
  */
  function applyLimit(&$sql, $offset, $limit)
  {
    $sql = 'SELECT B.* FROM ( ' . 
           'SELECT A.*, rownum AS CREOLE$ROWNUM FROM ( ' . $sql . ' ) A ' .
           ') B WHERE ';
    if ($offset > 0) {
      $sql .= ' B.CREOLE$ROWNUM > ' . $offset;            
      if ($limit > 0) {
        $sql .= ' AND B.CREOLE$ROWNUM <= ' . ($offset + $limit);
      }
    } else {
        $sql .= ' B.CREOLE$ROWNUM <= ' . $limit;
    }
  } 

  /**
  * Get the native Oracle Error Message as a string.
  *
  * @param string $msg The Internal Error Message
  * @param mixed $errno The Oracle Error resource
  */
  function nativeError($result = null)
  {
    if ($result !== null) {
       $error = OCIError($result);
    } else {
       $error = OCIError($this->dblink);
    }         
    return $error['code'] . ": " . $error['message'];
  }
  
  
  /**
  * @see Connection::getDatabaseInfo()
  */
  function & getDatabaseInfo()
  {
    require_once 'creole/drivers/oracle/metadata/OCI8DatabaseInfo.php';
    return new OCI8DatabaseInfo($this);
  }
  
  /**
  * @see Connection::getIdGenerator()
  */
  function & getIdGenerator()
  {
     require_once 'creole/drivers/oracle/OCI8IdGenerator.php';
     return new OCI8IdGenerator($this);
  }
  
  /**
  * Oracle supports native prepared statements, but the OCIParse call
  * is actually called by the OCI8PreparedStatement class because
  * some additional SQL processing may be necessary (e.g. to apply limit).
  * @see OCI8PreparedStatement::executeQuery()
  * @see OCI8PreparedStatement::executeUpdate()
  * @see Connection::prepareStatement()
  */
  function & prepareStatement($sql) 
  {
    require_once 'creole/drivers/oracle/OCI8PreparedStatement.php';
    return new OCI8PreparedStatement($this, $sql);
  }
  
  /**
  * @see Connection::prepareCall()
  */
  function prepareCall($sql) 
  {
    return new SQLException(CREOLE_ERROR, 'Oracle driver does not yet support stored procedures using CallableStatement.');
  }
  
  /**
  * @see Connection::createStatement()
  */
  function & createStatement()
  {
    require_once 'creole/drivers/oracle/OCI8Statement.php';
    return new OCI8Statement($this);
  }
}
