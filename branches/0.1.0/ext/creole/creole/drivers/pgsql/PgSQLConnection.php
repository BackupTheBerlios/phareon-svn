<?php
/*
 *  $Id: PgSQLConnection.php,v 1.3 2004/06/14 15:05:55 micha Exp $
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
include_once 'creole/drivers/pgsql/PgSQLResultSet.php';

/**
 * PgSQL implementation of Connection.
 * 
 * @author    Hans Lellelid <hans@xmpl.org> (Creole)
 * @author    Michael Aichler <aichler@mediacluster.de> (Creole)
 * @author    Stig Bakken <ssb@fast.no> (PEAR::DB)
 * @author    Lukas Smith (PEAR::MDB)
 * @version   $Revision: 1.3 $
 * @package   creole.drivers.pgsql
 */ 
class PgSQLConnection extends ConnectionCommon 
{        
  /** 
   * Result of last executed query.
   * Postgres needs this for getUpdateCount()
   * @var resource
   */
  var $result;
  
  /**
   * Connect to a database and log in as the specified user.
   *
   * @param array $dsn The datasource hash.
   * @param $flags Any connection flags.
   * @access public
   * @return mixed TRUE on success, SQLException on failure.
   */
  function connect($dsninfo, $flags = 0)
  {    
    if (!extension_loaded('pgsql')) {
      return new SQLException(CREOLE_ERROR_EXTENSION_NOT_FOUND, 'pgsql extension not loaded');
    }

    $this->dsn = $dsninfo;
    $this->flags = $flags;
    
    $persistent = ($flags & Creole::PERSISTENT() === Creole::PERSISTENT());
            
    $protocol = (isset($dsninfo['protocol'])) ? $dsninfo['protocol'] : 'tcp';
    $connstr = '';

    if ($protocol == 'tcp') {
      if (!empty($dsninfo['hostspec'])) {
        $connstr = 'host=' . $dsninfo['hostspec'];
      }
      if (!empty($dsninfo['port'])) {
        $connstr .= ' port=' . $dsninfo['port'];
      }
    }

    if (isset($dsninfo['database'])) {
      $connstr .= ' dbname=\'' . addslashes($dsninfo['database']) . '\'';
    }
    if (!empty($dsninfo['username'])) {
      $connstr .= ' user=\'' . addslashes($dsninfo['username']) . '\'';
    }
    if (!empty($dsninfo['password'])) {
      $connstr .= ' password=\'' . addslashes($dsninfo['password']) . '\'';
    }
    if (!empty($dsninfo['options'])) {
      $connstr .= ' options=' . $dsninfo['options'];
    }
    if (!empty($dsninfo['tty'])) {
      $connstr .= ' tty=' . $dsninfo['tty'];
    }
    
    if ($persistent) {
      $conn = @pg_pconnect($connstr);
    } else {
      $conn = @pg_connect($connstr);
    }
    
    if (!$conn) {
      return new SQLException(CREOLE_ERROR_CONNECT_FAILED, 'Could not connect', $php_errormsg, $connstr);
    }
    
    $this->dblink = $conn;        
    return true;
  }

  /**
   * @see Connection::disconnect()
   */
  function close()
  {
    $ret = @pg_close($this->dblink);
    $this->dblink = null;
    return $ret;
  }
  
  /**
  * @see Connection::applyLimit()
  */
  function applyLimit(&$sql, $offset, $limit)
  {
    if ( $limit > 0 ) {
      $sql .= " LIMIT " . $limit;
    }
    if ( $offset > 0 ) {
      $sql .= " OFFSET " . $offset;
    }
  }

  /**
   * @see Connection::simpleQuery()
   */
  function & executeQuery($sql, $fetchmode = null)
  {
    $this->result = @pg_query($this->dblink, $sql);
    if (!$this->result) {
      return new SQLException(CREOLE_ERROR, 'Could not execute query', pg_last_error($this->dblink), $sql);
    }
    return new PgSQLResultSet($this, $this->result, $fetchmode);
  }        

  /**
   * @see Connection::simpleUpdate()
   */
  function executeUpdate($sql)
  {            
    if (!$this->autocommit) {
      if ($this->transactionOpcount == 0) {
        $result = @pg_exec($this->dblink, "begin;");
        if (!$result) {
          return new SQLException(CREOLE_ERROR, 'Could not begin transaction', pg_last_error($this->dblink));
        }
      }
      $this->transactionOpcount++;
    }        
    
    $this->result = @pg_query($this->dblink, $sql);
    if (!$this->result) {
        return new SQLException(CREOLE_ERROR, 'Could not execute update', pg_last_error($this->dblink), $sql);
    }        
    
    return (int) @pg_cmdtuples($this->result);     
  }

  /**
   * Commit the current transaction.
   * @return mixed TRUE on success, SQLException on failure.
   */
  function commit()
  {
    if ($this->transactionOpcount > 0) {            
      $result = @pg_query($this->dblink, "end;");
      $this->transactionOpcount = 0;
      if (!$result) {
        return new SQLException('Could not commit transaction', pg_last_error($this->dblink));
      }
    }
    
    return true;
  }

  /**
   * Roll back (undo) the current transaction.
   * @return mixed TRUE on success, SQLException on failure.
   */
  function rollback()
  {
    if ($this->transactionOpcount > 0) {
      $result = @pg_query($this->dblink, "abort;");
      $this->transactionOpcount = 0;
      if (!$result) {
        return new SQLException(CREOLE_ERROR, 'Could not rollback transaction', pg_last_error($this->dblink));
      }
    }     
    
    return true;   
  }

  /**
   * Gets the number of rows affected by the data manipulation
   * query.
   * @see Statement::getUpdateCount()
   * @return int Number of rows affected by the last query.
   */
  function getUpdateCount()
  {
    return (int) @pg_cmdtuples($this->result);
  }    

  
  /**
   * @see Connection::getDatabaseInfo()
   */
  function & getDatabaseInfo()
  {
    require_once 'creole/drivers/pgsql/metadata/PgSQLDatabaseInfo.php';
    return new PgSQLDatabaseInfo($this);
  }
  
  /**
   * @see Connection::getIdGenerator()
   */
  function & getIdGenerator()
  {
    require_once 'creole/drivers/pgsql/PgSQLIdGenerator.php';
    return new PgSQLIdGenerator($this);
  }
  
  /**
   * @see Connection::prepareStatement()
   */
  function & prepareStatement($sql) 
  {
    require_once 'creole/drivers/pgsql/PgSQLPreparedStatement.php';
    return new PgSQLPreparedStatement($this, $sql);
  }
  
  /**
   * @see Connection::prepareCall()
   */
  function prepareCall($sql) {
    return new SQLException(CREOLE_ERROR_UNSUPPORTED, 'PostgreSQL does not support stored procedures.');
  }
  
  /**
   * @see Connection::createStatement()
   */
  function & createStatement()
  {
    require_once 'creole/drivers/pgsql/PgSQLStatement.php';
    return new PgSQLStatement($this);
  }
  
}
