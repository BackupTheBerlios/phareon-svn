<?php

/*
 *  $Id: DatabaseInfo.php,v 1.5 2004/06/14 15:21:13 micha Exp $
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

/**
 * "Info" metadata class for a database.
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @author    Michael Aichler <aichler@mediacluster.de>
 * @version   $Revision: 1.5 $
 * @package   creole.metadata
 */
class DatabaseInfo
{
  var $tables = array();

  var $sequences = array();

  /** have tables been loaded */
  var $tablesLoaded = false;

  /** have sequences been loaded */
  var $seqsLoaded = false;

  /**
   * The database Connection.
   * @var Connection
   */
  var $conn;

  /** Database name. */
  var $dbname;

  /**
   * Database link
   * @var resource
   */
  var $dblink;

  /**
   * @param Connection $conn
   */
  function DatabaseInfo(/*Connection*/ &$conn)
  {
    if (! is_a($conn, 'Connection')) {
      trigger_error(
        "DatabaseInfo::DatabaseInfo(): parameter 1 not of type 'Connection' !",
        E_USER_ERROR
      );
    }
  
    $this->conn =& $conn;
    $this->dblink =& $conn->getResource();
    $dsn = $conn->getDSN();
    $this->dbname = $dsn['database'];
  }

  /**
   * Get name of database.
   * @return string
   */
  function getName()
  {
    return $this->dbname;
  }

  /**
   * This method is invoked upon serialize().
   * Because the Info class hierarchy is recursive, we must handle
   * the serialization and unserialization of this object.
   * @return array The class variables that should be serialized (all must be public!).
   */
  function __sleep()
  {
    return array('tables','conn');
  }

  /**
   * This method is invoked upon unserialize().
   * This method re-hydrates the object and restores the recursive hierarchy.
   */
  function __wakeup()
  {
    // Re-init vars from serialized connection
    $this->dbname =& $conn->database;
    $this->dblink =& $conn->connection;

    // restore chaining
    for($i=0, $j=count($this->tables); $i < $j; $i++) {
      $tbl =& $this->tables[$i];
      $tbl->database =& $this;
      $tbl->dbname = $this->dbname;
      $tbl->dblink = $this->dblink;
      $tbl->schema = $this->schema;
    }
  }

  /**
   * Returns Connection being used.
   * @return Connection
   */
  function & getConnection()
  {
    return $this->conn;
  }

  /**
   * Get the TableInfo object for specified table name.
   * @param string $name The name of the table to retrieve.
   * @return mixed TableInfo on success, SQLException - if table does not exist in this db.
   */
  function & getTable($name)
  {
    if(!$this->tablesLoaded) {
      if (($e = $this->initTables()) !== true) {
        return $e;
      }
    }
  
    if (!isset($this->tables[strtoupper($name)])) {
      return new SQLException(CREOLE_ERROR_NOSUCHTABLE, "Database `".$this->name."` has no table `".$name."`");
    }
    
    return $this->tables[ strtoupper($name) ];
  }

  /**
   * Gets array of TableInfo objects.
   * @return mixed array TableInfo[] on success, SQLException on failure.
   */
  function & getTables()
  {
    if(!$this->tablesLoaded) {
      if (($e = $this->initTables()) !== true) {
        return $e;
      }
    }
    
    return array_values($this->tables); //re-key [numerically]
  }

  /**
   * Adds a table to this db.
   * Table name is case-insensitive.
   * @param TableInfo $table
   */
  function addTable(/*TableInfo*/ &$table)
  {
    if (! is_a($table, 'TableInfo')) {
      trigger_error(
        "DatabaseInfo::addTable(): parameter 1 not of type 'TableInfo' !",
        E_USER_ERROR
      );
    }
    $this->tables[strtoupper($table->getName())] =& $table;
  }

  /**
   * @return void
   * @throws SQLException
   */
  function initTables()
  {
    trigger_error (
      "DatabaseInfo::initTables(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  // FIXME
  // Figure out sequences.  What are they exactly?  Simply columns?
  // Should this logic really be at the db level (yes & no, i think).  Maybe
  // also a Column::isSequence() method ?  PosgreSQL supports sequences obviously,
  // but currently this part of dbinfo classes is not being used.

  /**
   * @return void
   * @throws SQLException
   */
  function initSequences()
  {
    trigger_error (
      "DatabaseInfo::initSequences(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * @return boolean
   * @throws SQLException
   */
  function isSequence($key)
  {
    if(!$this->seqsLoaded) {
      if (($e = $this->initSequences()) !== true) {
        return $e;
      }
    }
    
    return isset($this->sequences[ strtoupper($key) ]);
  }

  /**
   * Gets array of ? objects.
   * @return array ?[]
   */
  function getSequences()
  {
    if(!$this->seqsLoaded) {
      if (($e = $this->initSequences()) !== true) {
        return $e;
      }
    }
    
    return array_values($this->sequences); //re-key [numerically]
  }

}

