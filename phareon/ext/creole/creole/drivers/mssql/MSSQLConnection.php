<?php

/*
 *  $Id: MSSQLConnection.php,v 1.1 2004/05/02 21:59:24 micha Exp $
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
include_once 'creole/drivers/mssql/MSSQLResultSet.php';

/**
 * MS SQL Server implementation of Connection.
 * 
 * If you have trouble with BLOB / CLOB support
 * --------------------------------------------
 * 
 * You may need to change some PHP ini settings.  In particular, the following settings
 * set the text size to maximum which should get around issues with truncated data:
 * <code>
 *  ini_set('mssql.textsize', 2147483647);
 *  ini_set('mssql.textlimit', 2147483647);
 * </code>
 * We do not set these by default (anymore) because they do not apply to cases where MSSQL
 * is being used w/ FreeTDS.
 * 
 * @author    Hans Lellelid <hans@xmpl.org>
 * @author    Michael Aichler <aichler@mediacluster.de>
 * @author    Stig Bakken <ssb@fast.no> 
 * @author    Lukas Smith
 * @version   $Revision: 1.1 $
 * @package   creole.drivers.mssql
 */ 
class MSSQLConnection extends ConnectionCommon
{        
  /** Current database (used in mssql_select_db()). */
  var $database;
  
  /**
   * @see Connection::connect()
   */
  function connect($dsninfo, $flags = 0)
  {                
    if (!extension_loaded('mssql') && !extension_loaded('sybase') && !extension_loaded('sybase_ct')) {
      return new SQLException(CREOLE_ERROR_EXTENSION_NOT_FOUND, 'mssql extension not loaded');
    }

    $this->dsn = $dsninfo;
    $this->flags = $flags;
            
    $persistent = ($flags & Creole::PERSISTENT() === Creole::PERSISTENT());

    $user = $dsninfo['username'];
    $pw = $dsninfo['password'];
    $dbhost = $dsninfo['hostspec'] ? $dsninfo['hostspec'] : 'localhost';

    $connect_function = $persistent ? 'mssql_pconnect' : 'mssql_connect';

    if ($dbhost && $user && $pw) {
      $conn = @$connect_function($dbhost, $user, $pw);
    } elseif ($dbhost && $user) {
      $conn = @$connect_function($dbhost, $user);
    } else {
      $conn = @$connect_function($dbhost);
    }
    if (!$conn) {
      return new SQLException(CREOLE_ERROR_CONNECT_FAILED, 'connect failed', mssql_get_last_message());
    }
    
    if ($dsninfo['database']) {
      if (!@mssql_select_db($dsninfo['database'], $conn)) {
        return new SQLException(CREOLE_ERROR_NODBSELECTED, 'No database selected');               
      }
      
      $this->database = $dsninfo['database'];
    }
    
    $this->dblink = $conn;        
  }    
  
  /**
  * @see Connection::getDatabaseInfo()
  */
  function & getDatabaseInfo()
  {
    require_once 'creole/drivers/mssql/metadata/MSSQLDatabaseInfo.php';
    return new MSSQLDatabaseInfo($this);
  }
  
  /**
  * @see Connection::getIdGenerator()
  */
  function & getIdGenerator()
  {
    require_once 'creole/drivers/mssql/MSSQLIdGenerator.php';
    return new MSSQLIdGenerator($this);
  }
  
  /**
  * @see Connection::prepareStatement()
  */
  function & prepareStatement($sql) 
  {
    require_once 'creole/drivers/mssql/MSSQLPreparedStatement.php';
    return new MSSQLPreparedStatement($this, $sql);
  }
  
  /**
  * @see Connection::createStatement()
  */
  function & createStatement()
  {
    require_once 'creole/drivers/mssql/MSSQLStatement.php';
    return new MSSQLStatement($this);
  }
  
  /**
  * Returns false since MSSQL doesn't support this method.
  */
  function applyLimit(&$sql, $offset, $limit)
  {
    return false;
  }
  
  /**
   * @see Connection::close()
   */
  function close()
  {
    @mssql_close($this->dblink);
  }
  
  /**
  * @see Connection::executeQuery()
  */
  function & executeQuery($sql, $fetchmode = null)
  {            
    $this->lastQuery = $sql;
    if (!@mssql_select_db($this->database, $this->dblink)) {
      return new SQLException(CREOLE_ERROR_NODBSELECTED, 'No database selected');
    }       
    $result = @mssql_query($sql, $this->dblink);
    if (!$result) {
      return new SQLException(CREOLE_ERROR, 'Could not execute query', mssql_get_last_message());
    }
    return new MSSQLResultSet($this, $result, $fetchmode);
  }

  /**
  * @see Connection::executeUpdate()
  */
  function executeUpdate($sql)
  {    
    $this->lastQuery = $sql;
    if (!mssql_select_db($this->database, $this->dblink)) {
      return new SQLException(CREOLE_ERROR_NODBSELECTED, 'No database selected');
    }
    
    if (!$this->autocommit) {
      if ($this->transactionOpcount === 0) {
        $result = @mssql_query('BEGIN TRAN', $this->dblink);
        if (!$result) {
          return new SQLException(CREOLE_ERROR, 'Could not begin transaction', mssql_get_last_message());
        }
      }
      $this->transactionOpcount++;
    }
    
    $result = @mssql_query($sql, $this->dblink);
    if (!$result) {
      return new SQLException(CREOLE_ERROR, 'Could not execute update', mssql_get_last_message(), $sql);
    }
    
    return $this->getUpdateCount();
  }

  /**
  * Commit the current transaction.
  */
  function commit()
  {
    if ($this->transactionOpcount > 0) {
      if (!@mssql_select_db($this->database, $this->dblink)) {
        return new SQLException(CREOLE_ERROR_NODBSELECTED, 'No database selected');
      }
      $result = @mssql_query('COMMIT TRAN', $this->dblink);
      $this->transactionOpcount = 0;
      if (!$result) {
        return new SQLException(CREOLE_ERROR, 'Could not commit transaction', mssql_get_last_message());
      }
    }
  }

  /**
  * Roll back (undo) the current transaction.
  * @throws SQLException
  * @return void
  */
  function rollback()
  {
    if ($this->transactionOpcount > 0) {
      if (!@mssql_select_db($this->database, $this->dblink)) {            
        return new SQLException(CREOLE_ERROR_NODBSELECTED, 'no database selected');
      }
      $result = @mssql_query('ROLLBACK TRAN', $this->dblink);
      $this->transactionOpcount = 0;
      if (!$result) {
        return new SQLException(CREOLE_ERROR, 'Could not rollback transaction', mssql_get_last_message());
      }
    }
  }

  /**
  * Gets the number of rows affected by the last query.
  * if the last query was a select, returns 0.
  *
  * @return int Number of rows affected by the last query
  * @throws SQLException
  */
  function getUpdateCount()
  {       
    $res = @mssql_query('select @@rowcount', $this->dblink);
    if (!$res) {
      return new SQLException(CREOLE_ERROR, 'Unable to get affected row count', mssql_get_last_message());
    }
    $ar = @mssql_fetch_row($res);
    if (!$ar) {
      $result = 0;
    } else {
      @mssql_free_result($res);
      $result = $ar[0];
    }
    
    return $result;
  }          
  
  
  /**
  * Creates a CallableStatement object for calling database stored procedures.
  * 
  * @param string $sql
  * @return CallableStatement
  * @throws SQLException
  */
  function & prepareCall($sql) 
  {             
    require_once 'creole/drivers/mssql/MSSQLCallableStatement.php';
    $stmt = mssql_init($sql);
    if (!$stmt) {
      return new SQLException(CREOLE_ERROR, 'Unable to prepare statement', mssql_get_last_message(), $sql);
    }
    return new MSSQLCallableStatement($this, $stmt);
  }
  
}
