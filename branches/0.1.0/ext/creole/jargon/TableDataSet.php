<?php

/*
 *  $Id: TableDataSet.php,v 1.3 2004/05/22 12:56:30 micha Exp $
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
 * 
 * This product includes software based on the Village framework,  
 * http://share.whichever.com/index.php?SCREEN=village.
 */

require_once 'jargon/DataSet.php';
require_once 'jargon/KeyDef.php';

/**
 * This class is used for doing select/insert/delete/update on the database.
 * A TableDataSet cannot be used to join multiple tables for an update, if you
 * need join functionality on a select, you should use a QueryDataSet.
 * 
 * @author    Jon S. Stevens <jon@latchkey.com> (Village)
 * @author    Michael Aichler <aichler@mediacluster.de> (Jargon)
 * @author    Hans Lellelid <hans@xmpl.org> (Jargon)
 * @version   $Revision: 1.3 $
 * @package   jargon
 */
class TableDataSet extends DataSet 
{
  /** Name of current table */
  var $tableName;
  
  /** TableInfo (metadata) object */
  var $tableInfo;
  
  /** the optimistic locking column value */
  var $optimisticLockingCol;
  
  /** the value for the sql where clause */
  var $where;
  
  /** the value for the sql order by clause */
  var $order;
  
  /** the value for the sql other clause */
  var $other;
  
  /** Whether to reload record values when save() is performed. */
  var $refreshOnSave = false;
  
  /**
  * Construct new TableDataSet instance.
  * 
  * Supports a few signatures:
  *  - new TableDataSet($conn, "mytable", "col1, col2")
  *  - new TableDataSet($conn, "mytable", new KeyDef(...))
  *  - new TableDataSet($conn, "mytable", "col1, col2", new KeyDef(...))
  * 
  * @param Connection $conn
  * @param string $tableName
  * @param mixed $p3 KeyDef or column list (string)
  * @param mixed $p4 KeyDef or column list (string)
  */    
  function TableDataSet(/*Connection*/ &$conn, $tableName, $p3 = null, $p4 = null)
  {
    if (! is_a($conn, 'Connection')) {
      trigger_error (
        "TableDataSet::TableDataSet(): parameter 1 not of type 'Connection' !",
        E_USER_ERROR
      );
    }

    $this->conn =& $conn;
    $this->columns = "*";
    $this->tableName = $tableName;
    
    if ($p4 !== null) {
      $this->columns = $p3;
      $this->keyDef = $p4;
    } 
    else if ($p3 !== null) {
      if (is_a($p3, 'KeyDef')) {
        $this->keyDef =& $p3;
      } else { // it's a string (column list)
        $this->columns = $p3;
      }
    }
  }

  /**
  * Gets the tableName defined in the schema
  * @return string
  */
  function tableName() 
  {
    return $this->tableName;
  }
      
  /**
   * Load the TableInfo (metadata) object for this table.
   * @return TableInfo on success, SQLException if current conn doesn't know about $this->tableName
   */
  function & tableInfo()
  {
    if ($this->tableInfo === null) {
      $dbInfo =& $this->conn->getDatabaseInfo();
      $tblInfo =& $dbInfo->getTable($this->tableName);

      if (Creole::isError($tblInfo)) {
        return $tblInfo;
      }
      $this->tableInfo =& $tblInfo;
    }
    return $this->tableInfo;
  }
           
  /**
  * Fetch start to max records. start is at Record 0
  *
  * @param  int $start
  * @param  int $max
  * @return TableDataSet This object on success, SQL- or DataSetException on failure.
  */
  function & fetchRecords($p1 = 0, $p2 = null)
  {
    $this->buildSelectString();
    return parent::fetchRecords($p1, $p2);
  }

  /**
  * Creates a new Record within this DataSet
  *
  * @return Record The added record on success, SQL - or DataSetException on failure.
  */
  function & addRecord()
  {
    $rec =& new Record($this, true);

    if (Creole::isError($rec)) {
      return $rec;
    }
    /* 
    * no need to check return value here as markForInsert() only returns an
    * exception, if $rec->ds is not a TableDataSet
    */
    $rec->markForInsert();
    $this->records[] =& $rec;
    return $rec;
  }

  /**
  * Saves all the records in the DataSet.
  * @return int Total number of records updated/inserted/deleted on success, SQL- or DataSetException on failure.
  */
  function save() 
  {
    $i = 0;
    for($j=count($this->records); $i < $j; $i++) {
      $rec =& $this->records[$i];
      if (($e = $rec->save()) !== true) {
        return $e;
      }
    }        
    // now go through and remove any records
    // that were previously marked as a zombie by the 
    // delete process
    $this->removeDeletedRecords();
    return $i;
  }

  /**
  * Removes any records that are marked as a zombie.
  * @return void
  */
  function removeDeletedRecords() 
  {
    // this algorythm should be a fair bit
    // faster than calling DataSet::removeRecord()
    $new_recs = array();
    for($i=0,$j=count($this->records); $i < $j; $i++) {
      $rec =& $this->records[$i];
      if (!$rec->isAZombie()) {
        $new_recs[] =& $rec;
      }
    }
    $this->records =& $new_recs;
  }

  /**
  * Sets the table column used for optomistic locking.
  * @param string $olc
  */
  function setOptimisticLockingColumn($olc)
  {
    $this->optimisticLockingCol = $olc;
  }

  /**
  * Gets the table column used for optomistic locking.
  * @returns string
  */
  function optimisticLockingCol()
  {
    return $this->optimisticLockingCol;
  }

  /**
  * Sets the value for the SQL portion of the WHERE statement.
  * @return instance of self on success, DataSetException on failure.
  */
  function & where($where) 
  {
    if ($where == null) {
      return new DataSetException(0, "null not allowed for where clause");
    }            
    $this->where =& $where;
    return $this;
  }

  /**
  * Gets the value of the SQL portion of WHERE.
  * @returns string
  */
  function & getWhere()
  {
    return $this->where;
  }

  /**
  * Sets the value for the SQL portion of the ORDER statement
  *
  * @returns TableDataSet instance of self on success, DataSetException on failure.
  */
  function & order($order) 
  {
    if ($order === null) {
      return new DataSetException(0, "null not allowed for order clause");
    }
    $this->order =& $order;
    return $this;
  }

  /**
  * Gets the value of the SQL portion of ORDER.
  * @returns string
  */
  function & getOrder()
  {
    return $this->order;
  }

  /**
  * Sets the value for the SQL portion of the OTHER statement
  * @param string $other
  * @return TableDataSet instance of self
  * @throws DataSetException
  */
  function & other($other) 
  {
    if ($other === null) {
      return new DataSetException(0, "null not allowed for other clause");
    }            
    $this->other =& $other;
    return $this;
  }

  /**
  * Gets the value of the SQL portion of OTHER.
  * @returns string
  */
  function & getOther()
  {
    return $this->other;
  }

  /**
  * This method refreshes all of the Records stored in this TableDataSet.
  *
  * @return mixed boolean TRUE on success, SQL- or DataSetException on failure.
  */
  function refresh()
  {
    for($i=0,$j=count($this->records); $i < $j; $i++) {
      $rec =& $this->records[$i];
      if (($e = $rec->refresh()) !== true) {
        return $e;
      }
    }
    return true;
  }

  /**
  * Setting this causes each Record to refresh itself when a save() is performed on it.
  * <p>
  * Default value is false.
  * @param boolean $v
  * @returns true if it is on; false otherwise
  */
  function setRefreshOnSave($v)
  {
    $this->refreshOnSave = $v;
  }

  /**
  * Setting this causes each Record to refresh itself when a save() is performed on it.
  * 
  * Default value is false.
  * 
  * @return boolean True if it is on; false otherwise
  */
  function refreshOnSave()
  {
    return $this->refreshOnSave;
  }  

  /**
  * Builds the select string that was used to populate this TableDataSet.
  * @returns SQL select string
  */
  function getSelectSql()
  {
    return $this->selectSql;     
  }

  /**
  * Used by getSelectSql() to build the select string that was used to
  * populate this TableDataSet.
  *
  * @access private
  * @see getSelectSql()
  * @return void
  */
  function buildSelectString () 
  {
    $this->selectSql = "SELECT ";
    $this->selectSql .= $this->columns;
    $this->selectSql .= " FROM " . $this->tableName;
    if ($this->where !== null && $this->where !== "") {
      $this->selectSql .= " WHERE " . $this->where;
    }
    if ($this->order !== null && $this->order !== "") {
      $this->selectSql .= " ORDER BY " . $this->order;
    }
    if ($this->other !== null && $this->other !== "") {
      $this->selectSql .= $this->other;
    }
  }
}
