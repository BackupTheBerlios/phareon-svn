<?php

/*
 *  $Id: TableInfo.php,v 1.3 2004/04/27 19:18:16 micha Exp $
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
 * Represents a table.
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @author    Michael Aichler <aichler@mediacluster.de>
 * @version   $Revision: 1.3 $
 * @package   creole.metadata
 */
class TableInfo
{
   // FIXME
   //  - Currently all member attributes are public.  This should be fixed
   // when PHP's magic __sleep() and __wakeup() functions & serialization support
   // handles protected/private members. (if ever)

  var $name;
  var $columns = array();
  var $foreignKeys = array();
  var $indexes = array();
  var $primaryKey;

  var $pkLoaded = false;
  var $fksLoaded = false;
  var $indexesLoaded = false;
  var $colsLoaded = false;

  /**
   * Database Connection.
   * @var Connection
   */
  var $conn;

  /**
   * The parent DatabaseInfo object.
   * @var DatabaseInfo
   */
  var $database;

  /** Shortcut to db resource link id (needed by drivers for queries). */
  var $dblink;

  /** Shortcut to db name (needed by many drivers for queries). */
  var $dbname;

  /**
   * @param string $table The table name.
   * @param string $database The database name.
   * @param resource $dblink The db connection resource.
   */
  function TableInfo(/*DatabaseInfo*/ &$database, $name) 
  {
    if (! is_a($database, 'DatabaseInfo')) {
      trigger_error(
        "TableInfo::TableInfo(): parameter 1 not of type 'DatabaseInfo' !",
        E_USER_ERROR
      );
    }
    $this->database =& $database;
    $this->name = $name;
    $this->conn =& $database->getConnection(); // shortcut because all drivers need this for the queries
    $this->dblink =& $this->conn->getResource();
    $this->dbname = $database->getName();
  }

  /**
   * This "magic" method is invoked upon serialize().
   * Because the Info class hierarchy is recursive, we must handle
   * the serialization and unserialization of this object.
   * @return array The class variables that should be serialized (all must be public!).
   */
  function __sleep()
  {
    return array('name', 'columns', 'foreignKeys', 'indexes', 'primaryKey');
  }

  /**
   * This "magic" method is invoked upon unserialize().
   * This method re-hydrates the object and restores the recursive hierarchy.
   */
  function __wakeup()
  {
    // restore chaining
    for($i=0,$j=count($this->columns); $i < $j; $i++) {
      $col =& $this->columns[$i];
      $col->table =& $this;
    }
  }

  /**
   * Loads the columns.
   * @return void
   */
  function initColumns()
  {
    trigger_error (
      "TableInfo::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * Loads the primary key information for this table.
   * @return void
   */
  function initPrimaryKey()
  {
    trigger_error (
      "TableInfo::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * Loads the foreign keys for this table.
   * @return void
   */
  function initForeignKeys()
  {
    trigger_error (
      "TableInfo::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * Loads the indexes information for this table.
   * @return void
   */
  function initIndexes()
  {
    trigger_error (
      "TableInfo::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }


  /**
   * Get parimary key in this table.
   * @return mixed array ForeignKeyInfo[] on success, SQLException - if foreign keys are unsupported by DB.
   */
  function & getPrimaryKey()
  {
    if(!$this->pkLoaded) {
      if (($e = $this->initPrimaryKey()) !== true) {
        return $e;
      }
    }
    return $this->primaryKey;
  }

  /**
   * Get the ColumnInfo object for specified column.
   * @param string $name The column name.
   * @return mixed ColumnInfo on success, SQLException if column does not exist for this table.
   */
  function & getColumn($name)
  {
    if(!$this->colsLoaded) {
      if (($e = $this->initColumns()) !== true) {
        return $e;
      }
    }
    
    if (!isset($this->columns[$name])) {
      return new SQLException(CREOLE_ERROR_NOSUCHFIELD, "Table `".$this->name."` has no column `".$name."`");
    }
    
    return $this->columns[$name];
  }

  /**
   * Get array of columns for this table.
   * @return mixed array ColumnInfo[] on success, SQLException on failure.
   */
  function & getColumns()
  {
    if(!$this->colsLoaded) {
      if (($e = $this->initColumns()) !== true) {
        return $e;
      }
    }
    return array_values($this->columns); // re-key numerically
  }

  /**
   * Get specified fk for this table.
   * @param string $name The foreign key name to retrieve.
   * @return mixed ForeignKeyInfo on success or SQLException - if fkey does not exist for this table.
   */
  function & getForeignKey($name)
  {
    if(!$this->fksLoaded) {
      if (($e = $this->initForeignKeys()) !== true) {
        return $e;
      }
    }
    if (!isset($this->foreignKeys[$name])) {
      return new SQLException(CREOLE_ERROR_NOSUCHFIELD, "Table `".$this->name."` has no foreign key `".$name."`");
    }
    return $this->foreignKeys[$name];
  }

  /**
   * Get all foreign keys.
   * @return mixed array ForeignKeyInfo[] on success, SQLException on failure.
   */
  function & getForeignKeys()
  {
    if(!$this->fksLoaded) {
      if (($e = $this->initForeignKeys()) !== true) {
        return $e;
      }
    }
    
    return array_values($this->foreignKeys);
  }

  /**
   * Gets the IndexInfo object for a specified index.
   * @param string $name The index name to retrieve.
   * @return mixed IndexInfo on success, SQLException - if index does not exist for this table.
   */
  function & getIndex($name)
  {
    if(!$this->indexesLoaded) {
      if (($e = $this->initIndexes()) !== true) {
        return $e;
      }
    }
    if (!isset($this->indexes[$name])) {
      return new SQLException(CREOLE_ERROR_NOSUCHFIELD, "Table `".$this->name."` has no index `".$name."`");
    }
    return @$this->indexes[$name];
  }

  /**
   * Get array of IndexInfo objects for this table.
   * @return mixed array IndexInfo[] on success, SQLException on failure.
   */
  function & getIndexes()
  {
    if(!$this->indexesLoaded) {
      if (($e = $this->initIndexes()) !== true) {
        return $e;
      }
    }
    return array_values($this->indexes);
  }

  /**
   * Alias for getIndexes() method.
   * @return array
   */
  function & getIndices()
  {
    return $this->getIndexes();
  }

/**
 * Get table name.
 * @return string
 */
  function getName()
  {
    return $this->name;
  }

  /**
   * @return string
   */
  function toString()
  {
    return $this->name;
  }

  /** Have foreign keys been loaded? */
  function foreignKeysLoaded()
  {
    return $this->fksLoaded;
  }

  /** Has primary key info been loaded? */
  function primaryKeyLoaded()
  {
    return $this->pkLoaded;
  }

  /** Have columns been loaded? */
  function columnsLoaded()
  {
    return $this->colsLoaded;
  }

  /** Has index information been loaded? */
  function indexesLoaded()
  {
    return $this->indexesLoaded;
  }

  /** Adds a column to this table. */
  function addColumn(/*ColumnInfo*/ &$column)
  {
      if (! is_a($column, 'ColumnInfo')) {
        trigger_error(
          "TableInfo::addColumn(): parameter 1 not of type 'ColumnInfo' !",
          E_USER_ERROR
        );
      }
    $this->columns[$column->getName()] =& $column;
  }

  /** Get the parent DatabaseInfo object. */
  function & getDatabase()
  {
    return $this->database;
  }
}
