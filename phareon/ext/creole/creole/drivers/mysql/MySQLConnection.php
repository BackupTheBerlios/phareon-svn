<?php
/*
 *  $Id: MySQLConnection.php,v 1.3 2004/06/14 15:05:55 micha Exp $
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

//
// STATUS:
// - ported:   y
// - exceptions: y
// - compiled: y
// - tested:   n
//

require_once 'creole/common/ConnectionCommon.php';
include_once 'creole/drivers/mysql/MySQLResultSet.php';

/**
 * MySQL implementation of Connection.
 *
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @author    Stig Bakken <ssb@fast.no>
 * @author    Lukas Smith
 * @version   $Revision: 1.3 $
 * @package   creole.drivers.mysql
 */
class MySQLConnection extends ConnectionCommon
{
  /** Current database (used in mysql_select_db()). */
  var $database;

  /**
  * Connect to a database and log in as the specified user.
  *
  * @param $dsn the data source name (see DB::parseDSN for syntax)
  * @param $flags Any conneciton flags.
  * @access public
  * @return TRUE on success, SQLException on error
  */
  function connect($dsninfo, $flags = 0)
  {
    if (! extension_loaded('mysql')) {
      return new SQLException(CREOLE_ERROR_EXTENSION_NOT_FOUND, 'mysql extension not loaded');
    }

    $this->dsn = $dsninfo;
    $this->flags = $flags;

    $persistent = ($flags & Creole::PERSISTENT()) === Creole::PERSISTENT();

    if (isset($dsninfo['protocol']) && $dsninfo['protocol'] == 'unix') {
      $dbhost = ':' . $dsninfo['socket'];
    }
    else {
      $dbhost = $dsninfo['hostspec'] ? $dsninfo['hostspec'] : 'localhost';
      if (!empty($dsninfo['port'])) {
        $dbhost .= ':' . $dsninfo['port'];
      }
    }

    $user = $dsninfo['username'];
    $pw = $dsninfo['password'];

    $connect_function = $persistent ? 'mysql_pconnect' : 'mysql_connect';

    @ini_set('track_errors', true);
    if ($dbhost && $user && $pw) {
      $conn = @$connect_function($dbhost, $user, $pw);
    } elseif ($dbhost && $user) {
      $conn = @$connect_function($dbhost, $user);
    } elseif ($dbhost) {
      $conn = @$connect_function($dbhost);
    } else {
      $conn = false;
    }

    @ini_restore('track_errors');
    if (empty($conn))
    {
      if (($err = @mysql_error()) != '') {
        return new SQLException(CREOLE_ERROR_CONNECT_FAILED, "connect failed", $err);
      } elseif (empty($php_errormsg)) {
        return new SQLException(CREOLE_ERROR_CONNECT_FAILED, "connect failed");
      } else {
        return new SQLException(CREOLE_ERROR_CONNECT_FAILED, "connect failed", $php_errormsg);
      }
    }

    if ($dsninfo['database'])
    {
      if (! @mysql_select_db($dsninfo['database'], $conn))
      {
        switch(mysql_errno($conn))
        {
          case 1049:
            $exc = new SQLException(CREOLE_ERROR_NOSUCHDB, "no such database", mysql_error($conn));
            break;
          case 1044:
            $exc = new SQLException(CREOLE_ERROR_ACCESS_VIOLATION, "access violation", mysql_error($conn));
            break;
          default:
             $exc = new SQLException(CREOLE_ERROR, "cannot select database", mysql_error($conn));
        }

        return $exc;
      }

      // fix to allow calls to different databases in the same script
      $this->database = $dsninfo['database'];
    }

    $this->dblink = $conn;
    return true;
  }

  /**
  * @see Connection::getDatabaseInfo()
  */
  function & getDatabaseInfo()
  {
    require_once 'creole/drivers/mysql/metadata/MySQLDatabaseInfo.php';
    return new MySQLDatabaseInfo($this);
  }

  /**
  * @see Connection::getIdGenerator()
  */
  function & getIdGenerator()
  {
    require_once 'creole/drivers/mysql/MySQLIdGenerator.php';
    return new MySQLIdGenerator($this);
  }

  /**
  * @see Connection::prepareStatement()
  */
  function & prepareStatement(&$sql)
  {
    require_once 'creole/drivers/mysql/MySQLPreparedStatement.php';
    return new MySQLPreparedStatement($this, $sql);
  }

  /**
  * @see Connection::prepareCall()
  */
  function & prepareCall(&$sql)
  {
    return new SQLException(CREOLE_ERROR_UNSUPPORTED, 'MySQL does not support stored procedures.');
  }

  /**
   * @see Connection::createStatement()
   */
  function & createStatement()
  {
    require_once 'creole/drivers/mysql/MySQLStatement.php';
    return new MySQLStatement($this);
  }

  /**
  * @see Connection::disconnect()
  */
  function close()
  {
    $ret = mysql_close($this->dblink);
    $this->dblink = null;
    return $ret;
  }

  /**
  * @see Connection::applyLimit()
  */
  function applyLimit(&$sql, $offset, $limit)
  {
    if ( $limit > 0 ) {
      $sql .= " LIMIT " . ($offset > 0 ? $offset . ", " : "") . $limit;
    } else if ( $offset > 0 ) {
      $sql .= " LIMIT " . $offset . ", 18446744073709551615";
    }
  }

  /**
  * @see Connection::executeQuery()
  */
  function & executeQuery($sql, $fetchmode = null)
  {
    $this->lastQuery = $sql;
    if ($this->database) {
      if (! @mysql_select_db($this->database, $this->dblink)) {
        return new SQLException(CREOLE_ERROR_NODBSELECTED, 'No database selected', mysql_error($this->dblink));
      }
    }

    $result = @mysql_query($sql, $this->dblink);

    if ($result === false) {
      return new SQLException(CREOLE_ERROR, 'Could not execute query', mysql_error($this->dblink), $sql);
    }

    return new MySQLResultSet($this, $result, $fetchmode);
  }

  /**
  * @see Connection::executeUpdate()
  */
  function & executeUpdate($sql)
  {
    $this->lastQuery = $sql;

    if ($this->database) {
      if (!@mysql_select_db($this->database, $this->dblink)) {
        return new SQLException(CREOLE_ERROR_NODBSELECTED, 'No database selected', mysql_error($this->dblink));
      }
    }

    if (!$this->autocommit)
    {
      if ($this->transactionOpcount == 0)
      {
        $result = @mysql_query('SET AUTOCOMMIT=0', $this->dblink);
        $result = @mysql_query('BEGIN', $this->dblink);
        if ($result === false) {
          return new SQLException(CREOLE_ERROR, 'Could not begin transaction', mysql_error($this->dblink));
        }
      }

      $this->transactionOpcount++;
    }

    $result = @mysql_query($sql, $this->dblink);

    if ($result === false) {
      return new SQLException(CREOLE_ERROR, 'Could not execute update', mysql_error($this->dblink), $sql);
    }

    return (int) mysql_affected_rows($this->dblink);
  }

  /**
   * Commit the current transaction.
   *
   * @return TRUE on success, SQLException on error.
   * @see Connection::commit()
   */
  function commit()
  {
    if ($this->transactionOpcount > 0)
    {
      if ($this->database) {
        if (!@mysql_select_db($this->database, $this->dblink)) {
          return new SQLException(CREOLE_ERROR_NODBSELECTED, 'No database selected', mysql_error($this->dblink));
        }
      }

      $result = @mysql_query('COMMIT', $this->dblink);
      $result = @mysql_query('SET AUTOCOMMIT=1', $this->dblink);
      $this->transactionOpcount = 0;

      if ($result === false) {
        return new SQLException(CREOLE_ERROR, 'Can not commit transaction', mysql_error($this->dblink));
      }
    }

    return true;
  }

  /**
  * Roll back (undo) the current transaction.
  * @return TRUE on success, SQLException on error.
  */
  function rollback()
  {
    if ($this->transactionOpcount > 0)
    {
      if ($this->database) {
        if (!@mysql_select_db($this->database, $this->dblink)) {
          return new SQLException(CREOLE_ERROR_NODBSELECTED, 'No database selected', mysql_error($this->dblink));
        }
      }

      $result = @mysql_query('ROLLBACK', $this->dblink);
      $result = @mysql_query('SET AUTOCOMMIT=1', $this->dblink);
      $this->transactionOpcount = 0;

      if ($result === false) {
        return new SQLException(CREOLE_ERROR, 'Could not rollback transaction', mysql_error($this->dblink));
      }
    }

    return true;
  }

  /**
  * Gets the number of rows affected by the data manipulation
  * query.
  *
  * @return int Number of rows affected by the last query.
  */
  function getUpdateCount()
  {
    return (int) @mysql_affected_rows($this->dblink);
  }

}
