<?php

/*
 *  $Id: Record.php,v 1.2 2004/05/22 13:37:20 micha Exp $
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

include_once 'creole/CreoleTypes.php';
 
/**
 * A Record represents a row in the database. It contains a hash of 
 * values which represent the column values for each row.
 *
 * @author    Jon S. Stevens <jon@latchkey.com> (Village)
 * @author    Michael Aichler <aichler@mediacluster.de> (Jargon)
 * @author    Hans Lellelid <hans@xmpl.org> (Jargon)
 * @version   $Revision: 1.2 $
 * @package   jargon
 */
class Record 
{
  // saveType constants
  function ZOMBIE() { return -1; }
  function UNKNOWN() { return 0; }
  function INSERT() { return 1; }
  function UPDATE() { return 2; }
  function DELETE() { return 3; }
  function BEFOREINSERT() { return 4; }
  function AFTERINSERT() { return 5; }
  function BEFOREUPDATE() { return 6; }
  function AFTERUPDATE() { return 7; }
  function BEFOREDELETE() { return 8; }
  function AFTERDELETE() { return 9; }
  
  /** an array of values strings, indexed by column name.*/
  var $values = array();
  
  /** array of modified (dirty) columns */
  var $dirtyCols = array();
  
  /** the parent DataSet for this Record */
  var $ds;
      
  /** this is the state of this record */
  var $saveType = 0;
  
  /**
  * Creates a new Record and sets the parent dataset to the passed in value.
  * The object created with 'new' may be a DataSetException if property $resultSet
  * of the passed in DataSet object is NULL.
  * 
  * If $addRecord is true, then an empty record is created.
  * 
  * @param DataSet $ds The parent / owning dataset.
  * @param boolean $addRecord Whether to create an empty record.
  */
  function Record(/*DataSet*/ &$ds, $addRecord = false)
  {
    /* checks for type DataSet */
    $this->setParentDataSet($ds);

    if (! $addRecord) {
      $rs =& $this->ds->resultSet();
      if (Creole::isError($rs)) {
        $this =& $rs;
      } 
      else {        
        $this->createValues($rs);
      }
    }
  }
  
  /**
  * Performs initialization for this Record.
  * @access private
  */
  function initializeRecord() 
  {
    $this->values = array();
    $this->dirtyCols = array();
    $this->setSaveType(UNKNOWN);                
  }
  
  /**
  * Creates the value objects for this Record. It is 1 based
  * 
  * @access private
  * @return void
  */
  function createValues(/*ResultSet*/ &$rs)
  {
    Creole::typeHint($rs, 'ResultSet', 'DataSet', 'createValues');
    $this->values =& $rs->getRow();
  }

  /**
  * Saves the data in this Record to the database.
  *
  * @return boolean True if the save completed, false otherwise.
  * @return SQL- or DataSetException on failure.
  */
  function save() 
  {
    $returnValue = false;

    if (is_a($this->ds, 'QueryDataSet')) {
      return new DataSetException(0, "You cannot save a QueryDataSet. Please use a TableDataSet instead.");
    }

    if (! $this->needsToBeSaved()) {
      return $returnValue;
    }

    switch($this->saveType) 
    {            
      case INSERT:
        $returnValue = $this->doInsert();
        break;
      case UPDATE:
        $returnValue = $this->doUpdate();
        break;                
      case DELETE:
        $returnValue = $this->doDelete();
        break;
      default:
        return new DataSetException("Invalid or no-action saveType for Record.");
    }

    if (Creole::isError($returnValue)) {
      return $returnValue;
    }
            
    return (boolean) $returnValue;
  }

  /**
  * Performs a DELETE on databse using this Record as criteria.
  *
  * @access private
  * @return mixed int Number of rows affected by delete or SQL - or DataSetException.
  */
  function doDelete() 
  {
    $conn =& $this->ds->connection();
    $table =& $this->ds->tableInfo();
    $sql =& $this->getDeleteSql();

    if (Creole::isError($sql)) {
      return $sql;
    }

    $stmt =& $conn->prepareStatement($sql);
    $kd =& $this->ds->keydef();
    $ps = 1;

    for ($i = 1, $kdsize = $kd->size(); $i <= $kdsize; $i++) {                
      $col =& $kd->getAttrib($i);
      $colInfo =& $table->getColumn($col);
      $val =& $this->getValue($col);                    

      if (Creole::isError($val)) { return $val; } 
      else if (Creole::isError($colInfo)) { return $colIinfo; }

      $setter = 'set' . CreoleTypes::getAffix($colInfo->getType());
      $stmt->$setter($ps++, $val);
    }

    $ret = $stmt->executeUpdate();

    if (Creole::isError($ret)) {
      $stmt->close();
      return $ret;
    }

    // note that the actual removal of the Record objects 
    // from the DataSet is done by the TDS::save() method.
    $this->setSaveType(ZOMBIE);
    
    $stmt->close();
    
    if ($ret > 1) {
      return new SQLException(0, "There were " . $ret . " rows deleted with this records key value.");
    }
        
    return $ret;
  }

  /**
  * Saves the data in this Record to the database with an UPDATE statement.
  *
  * @access private
  * @return SQL UPDATE statement
  * @throws DataSetException, SQLException
  */
  function doUpdate()
  {
    $conn =& $this->ds->connection();
    $table =& $this->ds->tableInfo();
    $sql =& $this->getUpdateSql();

    if (Creole::isError($sql)) {
      return $sql;
    }

    $stmt =& $conn->prepareStatement($sql);
    $ps = 1;

    foreach($this->dirtyColumns() as $col) {
      $colInfo =& $table->getColumn($col);
      $value =& $this->getValue($col);

      if (Creole::isError($value)) { return $value; }
      else if (Creole::isError($colInfo)) { return $colInfo; }

      $setter = 'set' . CreoleTypes::getAffix($colInfo->getType());
      $stmt->$setter($ps++, $value);
    }

    $kd =& $this->ds->keydef();

    for ($i = 1, $kdsize = $kd->size(); $i <= $kdsize; $i++) {
      $attrib = $kd->getAttrib($i);
      $colInfo =& $table->getColumn($attrib);
      $value =& $this->getValue($attrib);

      if (Creole::isError($value)) { return $value; }
      else if (Creole::isError($colInfo)) { return $colInfo; }

      $setter = 'set' . CreoleTypes::getAffix($colInfo->getType());
      $stmt->$setter($ps++, $value);
    }
    
    $ret = $stmt->executeUpdate();

    if (Creole::isError($ret)) {
      $stmt->close();
      return $ret;
    }

    if ($this->ds->refreshOnSave()) {
      if (($e = $this->refresh()) !== true) {
        $stmt->close();
        return $e;
      }
    } else {
      // Marks all of the values clean since they have now been saved
      $this->markRecordClean();
    }

    $this->setSaveType(AFTERUPDATE);

    if ($ret > 1) {
      return new SQLException (0, "There were " . $ret . " rows updated with this records key value.");
    }
            
    return $ret;
  }

  /**
  * Saves the data in this Record to the database with an INSERT statement
  *
  * @access private
  * @return mixed int on success, SQL- or DataSetException on failure.
  */
  function doInsert()
  {
    /* getInsertSql does not throw anything */
    $stmt =& $conn->prepareStatement($this->getInsertSql());
    $ps = 1;

    foreach($this->dirtyColumns() as $col) {
      $colInfo =& $table->getColumn($col);
      $value =& $this->getValue($col);

      if (Creole::isError($value)) { return $value; }
      else if (Creole::isError($colInfo)) { return $colInfo; }

      $setter = 'set' . CreoleTypes::getAffix($colInfo->getType());
      $stmt->$setter($ps++, $value);
    }
    
    $ret = $stmt->executeUpdate();

    if (Creole::isError($ret)) {
      $stmt->close();
      return $ret;
    }

    if ($this->ds->refreshOnSave()) {
      $e = $this->refresh();
      if (Creole::isError($e)) {
        $stmt->close();
        return $e;
      }
    } else {
      // Marks all of the values clean since they have now been saved
      $this->markRecordClean();
    }

    $this->setSaveType(AFTERINSERT);

    if ($ret > 1) {
      $stmt->close();
      // a little late again...
      return new SQLException (0, "There were " . $ret . " rows inserted with this records key value.");
    }

    return $ret;
  }

  /**
  * Builds the SQL UPDATE statement for this Record
  *
  * @access private
  * @return string SQL UPDATE statement on success, SQL- or DataSetException on failure
  */
  function getUpdateSql() 
  {
    $kd =& $this->ds->keydef();

    if ($kd === null || $kd->size() === 0) {
      return new DataSetException(0, "You must specify KeyDef attributes for this TableDataSet in order to create a Record for update.");
    } 
    elseif ($this->recordIsClean()) {
      return new DataSetException (0, "You must Record->setValue() on a column before doing an update.");
    }

    $set_sql = "";
    $where_sql = "";
    
    $comma = false;
    
    foreach($this->dirtyColumns() as $col)  {
      if (!$comma) {
        $set_sql .= $col . " = ?";
        $comma = true;
      } else {
        $set_sql .= ", " . $col . " = ?";
      }            
    }

    $comma = false;               
    for ($i = 1, $kdsize = $kd->size(); $i <= $kdsize; $i++) {        
      $attrib = $kd->getAttrib($i);
      if (! $this->valueIsClean ($attrib)) {
        return new DataSetException (0, "The value for column '" . $attrib . "' is a key value and cannot be updated.");
      }
      if (!$comma) {
        $where_sql .= $attrib . " = ?";
        $comma = true;
      } else {
        $where_sql .= " AND " . $attrib . " = ?";
      }
    }

    return "UPDATE " . $this->ds->tableName() . " SET " . $set_sql . " WHERE " . $where_sql;
  }    
  

  /**
  * Builds the SQL DELETE statement for this Record.
  *
  * @access private
  * @return mixed string SQL DELETE statement on success, SQL- or DataSetException - if no keydef
  */
  function getDeleteSql() 
  {
    $kd =& $this->ds->keydef();
    
    if ($kd === null || $kd->size() === 0) {
      return new DataSetException(0, "You must specify KeyDef attributes for this TableDataSet in order to delete a Record.");
    }

    $where_sql = "";
    $comma = false;
    for ($i = 1, $kdsize = $kd->size(); $i <= $kdsize; $i++) {
      if (!$comma) {
        $where_sql .= $kd->getAttrib($i) . " = ?";               
        $comma = true;
      } else {
        $where_sql .= " AND " . $kd->getAttrib($i) . " = ?";
      }
    }
    
    return "DELETE FROM " . $this->ds->tableName() . " WHERE " . $where_sql;
  }

  /**
  * Builds the SQL INSERT statement for this Record
  *
  * @access private
  * @return string SQL INSERT statement
  */
  function getInsertSql() 
  {
    $fields_sql = "";
    $values_sql = "";                
    $comma = false;

    foreach($this->dirtyColumns() as $col) {
      if (!$comma) {
        $fields_sql .= $col;
        $values_sql .= "?";
        $comma = true;
      } else {
        $fields_sql .= ", " . $col;                    
        $values_sql .= ", ?";
      }
    }

    return "INSERT INTO " . $this->ds->tableName() . " ( " . $fields_sql . " ) VALUES ( " . $values_sql . " )";
  }       

  /**
  * Gets the value for specified column.
  * This function performs no type-conversion.
  *
  * @return string The value object for specified column as string on success, DataSetException on failure.
  */
  function & getValue($col) 
  {
    if (!isset($this->values[$col])) {
      return new DataSetException(0, "Undefined column in Record: " . $col);
    }        
    return $this->values[$col];
  }   

  /**
  * Get the column names for current record.
  * @return array Column names.
  */ 
  function columns()
  {
    return array_keys($this->values);
  }

  /**
  * Get the modified (dirty) columns.
  * Private right now because this is only used internally.  No
  * real reason why this couldn't be public, though ...
  *
  * @access private
  * @return array
  */
  function dirtyColumns()
  {
    return array_keys($this->dirtyCols);
  }

  /**
  * The number of columns in this object.
  * @return the number of columns in this object
  */
  function size()
  {
    return count($this->values);
  }
  
  /**
  * Whether or not this Record is to be saved with an SQL insert statement
  * @return boolean True if saved with insert
  */
  function toBeSavedWithInsert()
  {
    return ($this->saveType === INSERT);
  }
  
  /**
  * Whether or not this Record is to be saved with an SQL update statement
  * @return boolean True if saved with update
  */
  function toBeSavedWithUpdate()
  {
    return ($this->saveType === UPDATE);
  }
  
  /**
  * Whether or not this Record is to be saved with an SQL delete statement
  * @return boolean True if saved with delete
  */
  function toBeSavedWithDelete()
  {
    return ($this->saveType === DELETE);
  }

  /**
  * Marks all the values in this record as clean.
  * @return void
  */
  function markRecordClean()
  {
    $this->dirtyCols = array();
  }

  /**
  * Marks this record to be inserted when a save is executed.
  * @return mixed boolean TRUE on success, DataSetException - if DataSet is not TableDataSet
  */
  function markForInsert() 
  {
    if (is_a($this->ds, 'QueryDataSet')) {
      return new DataSetException (0, "You cannot mark a record in a QueryDataSet for insert");
    }
    $this->setSaveType(INSERT);
    return true;
  }
  
  /**
  * Marks this record to be updated when a save is executed.
  *
  * @return mixed boolean TRUE on success, DataSetException - if DataSet is not TableDataSet
  */
  function markForUpdate()
  {
    if (is_a($this->ds, 'QueryDataSet')) {
      return new DataSetException (0, "You cannot mark a record in a QueryDataSet for update");
    }
    $this->setSaveType(UPDATE);
    return true;
  }

  /**
  * Marks this record to be deleted when a save is executed.
  * @return mixed boolean TRUE on success, DataSetException - if DataSet is not TableDataSet
  */
  function markToBeDeleted()
  {
    if (is_a($this->ds, 'QueryDataSet')) {
      return new DataSetException (0, "You cannot mark a record in a QueryDataSet for deletion");
    }
    $this->setSaveType(DELETE);
    return true;
  }

  /**
  * Unmarks a record that has been marked for deletion.
  * <P>
  * WARNING: You must reset the save type before trying to save this record again.
  *
  * @see markForUpdate()
  * @see markForInsert()
  * @see markToBeDeleted()
  * @return mixed boolean TRUE on success, DataSetException - if record has already been deleted
  */
  function unmarkToBeDeleted() 
  {
    if ($this->saveType === ZOMBIE) {
      return new DataSetException (0, "This record has already been deleted!");
    }   
    $this->setSaveType(UNKNOWN);
    return true;
  }
  
  /**
  * Marks a value with a given column name as clean (unmodified).
  * @param string $col
  * @return void
  */
  function markValueClean($col)
  {
    unset($this->dirtyCols[$col]);
  }

  /**
  * Marks a value with a given column as "dirty" (modified).
  * @param string $col
  * @return void
  */
  function markValueDirty($col)
  {
    $this->dirtyCols[$col] = true;
  }
 
  /**
  * Sets the internal save type as one of the defined privates (ie: ZOMBIE)
  * @param int $type
  * @return void
  */
  function setSaveType($type)
  {
    $this->saveType = $type;
  }

  /**
  * Gets the internal save type as one of the defined privates (ie: ZOMBIE)
  * @return int
  */
  function getSaveType()
  {
    return $this->saveType;    
  }
  
  /**
  * Sets the value of col.
  * @return Record this object.
  */
  function & setValue ($col, &$value) 
  {
    $this->values[$col] =& $value;
    $this->markValueDirty($col);
    return $this;
  }

  /**
  * Determines if this record is a Zombie. A Zombie is a record that has been deleted from the
  * database, but not yet removed from the DataSet.
  *
  * @return boolean
  */
  function isAZombie()
  {
    return ($this->saveType === ZOMBIE);
  }

  /**
  * If the record is not clean, needs to be saved with an Update, Delete or Insert, it returns true.
  * @return boolean
  */
  function needsToBeSaved()
  {
    return (!$this->isAZombie() || !$this->recordIsClean() || $this->toBeSavedWithUpdate() ||
            $this->toBeSavedWithDelete() || $this->toBeSavedWithInsert());
  }

  /**
  * Determines whether or not a value stored in the record is clean.
  * @return mixed boolean TRUE if clean, DataSetException if column is undefined
  */
  function valueIsClean($column) 
  {
    if (!isset($this->values[$column])) {
      return new DataSetException(0, "Undefined column: ".$column);
    }
    return !isset($this->dirtyCols[$column]);
  }

  /**
  * Goes through all the values in the record to determine if it is clean or not.
  * @return true if clean
  */
  function recordIsClean()
  {
    return empty($this->dirtyCols);
  }
      
  /**
  * This method refreshes this Record's Value's. It can only be performed on
  * a Record that has not been modified and has been created with a TableDataSet
  * and corresponding KeyDef.
  *
  * @return mixed boolean TRUE on success, SQL- or DataSetException on failure.
  */
  function refresh()
  {
    $conn =& $this->ds->connection();
    
    if ($this->toBeSavedWithDelete()) {
      return;
    } elseif ($this->toBeSavedWithInsert()) {
      return new DataSetException(0, "There is no way to refresh a record which has been created with addRecord().");
    } elseif (is_a($this->ds, 'QueryDataSet')) {
      return new DataSetException(0, "You can only perform a refresh on Records created with a TableDataSet.");
    }

    $sql =& $this->getRefreshSql();
    if (Creole::isError($sql)) {
      return $sql;
    }

    $stmt &= $conn->prepareStatement ();
    $kd =& $this->ds->keydef();
    $ps = 1;

    for ($i = 1, $kdsize = $kd->size(); $i <= $kdsize; $i++)
    {
      $colInfo =& $table->getColumn($col);
      $val =& $this->getValue($kd->getAttrib($i));

      if (Creole::isError($val)) { return $val; } 
      else if (Creole::isError($colInfo)) { return $colInfo; }

      if ($val == null) {
        return new DataSetException(0, "You cannot execute an update with a null value for a KeyDef.");
      }                    

      $setter = 'set' . CreoleTypes::getAffix($colInfo->getType());
      $stmt->$setter($ps++, $val);
    }

    $rs = $stmt->executeQuery();

    if (Creole::isError($rs)) {
      $stmt->close();
      return $rs;
    }

    if (($e = $rs->next()) !== true) {
      $stmt->close();
      $rs->close();
      return $e;
    }
            
    $this->initializeRecord();
    $this->createValues($rs);
    return true;
  }

  /**
  * This builds the SELECT statement in order to refresh the contents of
  * this Record. It depends on a valid KeyDef to exist and it must have been
  * created with a TableDataSet.
  *
  * @return string The SELECT SQL on success, DataSetException on failure.
  */
  function & getRefreshSql()
  {
    $kd =& $this->ds->keydef();

    if ($kd === null || $kd->size() === 0) {
      return new DataSetException(0, "You can only perform a getRefreshQueryString on a TableDataSet that was created with a KeyDef.");            
    } elseif (is_a($this->ds, 'QueryDataSet')) {
      return new DataSetException(0, "You can only perform a getRefreshQueryString on Records created with a TableDataSet.");
    }
    
    $sql1 = "";
    $sql2 = "";
    $comma = false;

    foreach($this->columns() as $col) {
      if (!$comma) {                
        $attribs_sql .= $col;
        $comma = true;
      } else {
        $attribs_sql .= ", " . $col;
      }
    }

    $comma = false;

    for ($i = 1, $kdsize = $kd->size(); $i <= $kdsize; $i++) {
      $attrib = $kd->getAttrib($i);

      if (!$this->valueIsClean($attrib)) {
        return new DataSetException (0, 
                "You cannot do a refresh from the database if the value " .
                "for a KeyDef column has been changed with a Record.setValue().");
      }
      
      if (!$comma) {
        $where_sql .= $attrib . " = ?";
        $comma = true;
      } else {
        $where_sql .= " AND " . $attrib . " = ?";
      }
    }
    
    return "SELECT " . $attribs_sql . " FROM " . $this->ds->tableName() . " WHERE " . $where_sql;
  }    

  /**
  * Gets the DataSet for this Record.
  *
  * @return DataSet
  */
  function & dataset()
  {
    return $this->ds;
  }

  /**
  * Sets the parent DataSet for this record.
  * @param DataSet $ds
  */
  function setParentDataSet(/*DataSet*/ &$ds)
  {
    Creole::typeHint($ds, 'DataSet', 'Record', 'setParentDataSet');
    $this->ds =& $ds;
  }
  
  /**
  * This returns a representation of this Record.
  * @return string
  */
  function __toString()
  {
    $sb = "{";
    foreach($this->columns() as $col) {
      $sb .= "'" . $this->getValue($col) . "',";
    }
    $sb = substr($sb, 0, -1);
    $sb .= "}";
    return $sb;
  }
  
}
