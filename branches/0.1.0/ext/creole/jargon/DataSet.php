<?php
/*
 *  $Id: DataSet.php,v 1.2 2004/05/22 13:38:13 micha Exp $
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

require_once 'jargon/Record.php';
include_once 'jargon/DataSetException.php';

/**
 * The DataSet represents the results of a query.
 * 
 * It contains a collection of records and implements the IteratorAggregate
 * interface so that you can use the dataset in a foreach() {} loop.
 * 
 * <code>
 *  $ds = new TableDataSet($conn, "mytable");
 *  $ds->fetchRecords();
 *  foreach($ds as $record) {
 *        $record->setValue("col1", "new value");
 *         $record->save();
 *  }
 * </code>
 * 
 * This class is extended by QueryDataSet and TableDataSet and should not be used directly. 
 * 
 * NOTE: Iterating over a dataset in the php4 version has to be done in a for-loop:
 *
 * <code>
 *  $ds = new TableDataSet($conn, "mytable");
 *  $ds->fetchRecords();
 *
 *  for ($it =& $ds->getIterator(); $it->valid(); $it->next()) {
 *    $record =& $it->current();
 *    $record->setValue("col1", "new value");
 *    $record->save();
 *  }
 * </code>
 * @author    Jon S. Stevens <jon@latchkey.com> (Village)
 * @author    Michael Aichler <aichler@mediacluster.de> (Jargon)
 * @author    Hans Lellelid <hans@xmpl.org> (Jargon)
 * @version   $Revision: 1.2 $
 * @package   jargon
 */
class DataSet /*implements IteratorAggregate*/ 
{
  /** indicates that all records should be retrieved during a fetch */
  function ALL_RECORDS() { return 0; }

  /** this DataSet's collection of Record objects */
  var $records;

  /** this DataSet's connection object */
  var $conn;

  /** have all records been retrieved with the fetchRecords? */
  var $allRecordsRetrieved = false;

  /** number of records retrieved */
  var $recordRetrievedCount = 0;

  /** number of records that were last fetched */
  var $lastFetchSize = 0;

  /** number of records total that have been fetched */
  var $totalFetchCount = 0;

  /** the columns in the SELECT statement for this DataSet */
  var $columns;

  /** the select string that was used to build this DataSet */
  var $selectSql;

  /** the KeyDef for this DataSet */
  var $keyDef;

  /** the result set for this DataSet */
  var $resultSet;

  /** the Statement for this DataSet */
  var $stmt;    

  /**
   * Return iterator (for IteratorAggregate interface).
   * This allows this class to be used in a foreach() loop.
   * <code>
   *     foreach($dataset as $record) {
   *         print "col1 = " . $record->getValue("col1") . "\n";
   *  }
   * </code>
   * @return DataSetIterator
   */
  function & getIterator()
  {
    $it =& new DataSetIterator($this);
    return $it;
  }
  
  /**
   * Gets the ResultSet for this DataSet
   *
   * @return mixed ResultSet The result set for this DataSet on success, DataSetException - if resultset is null
   */
  function &resultSet()
  {
    if ($this->resultSet === null) {
      return new DataSetException (0, "ResultSet is null.");
    }            
    return $this->resultSet;
  }

  /**
   * Check if all the records have been retrieve
   *
   * @return boolean True if all records have been retrieved
   */
  function allRecordsRetrieved()
  {
    return $this->allRecordsRetrieved;
  }

  /**
    * Set all records retrieved
    * @param boolean $set
    * @return void
    */
  function setAllRecordsRetrieved($set)
  {
    $this->allRecordsRetrieved = $set;
  }

  /**
    * Remove a record from the DataSet's internal storage
    *
    * @param  Record $rec
    * //@return mixed Record The record removed on success, DataSetException on failure.
    * @return mixed Record The record removed on success, FALSE if not found.
    */
  function &removeRecord(/*Record*/ &$rec)
  {
    Creole::typeHint($rec, 'Record', 'DataSet', 'removeRecord');
    $loc = array_search($rec, $this->records, true);

    if ($loc === false) {
      return false;
    }

    return array_splice($this->records, $loc, 1);                 
  }

  /**
  *  Remove all records from the DataSet and nulls those records out
  *  and close() the DataSet.
  *
  * @return     an instance of myself
  */
  function &clearRecords()
  {
    $this->records = null;
    return $this;
  }

  /**
  * Removes the records from the DataSet, but does not null the records out
  *
  * @return     an instance of myself
  */
  function &releaseRecords()
  {
    $this->records = null;
    $this->recordRetrievedCount = 0;
    $this->lastFetchSize = 0;
    $this->setAllRecordsRetrieved(false);
    return $this;
  }

  /**
  * Releases the records, closes the ResultSet and the Statement, and
  * nulls the Schema and Connection references.
  *
  * @return void
  */
  function close()
  {
    $this->releaseRecords();
    $this->schema = null;

    if ($this->resultSet !== null && !is_a($this,'QueryDataSet')) {
      /* no check for return value as it returns void */
      $this->resultSet->close();
    }
        
    $this->resultSet = null;
    
    if ( $this->stmt !== null ) {
      $this->stmt->close();
    }

    $this->conn = null;
  }

  /**
  * Essentially the same as releaseRecords, but it won't work on a QueryDataSet that
  * has been created with a ResultSet
  *
  * @return mixed DataSet This object on success, DataSetException on failure.
  */
  function &reset()
  {
    if (! ($this->resultSet !== null && is_a($this, 'QueryDataSet'))) {
      return $this->releaseRecords();
    } else {
      return new DataSetException(0, "You cannot call reset() on a QueryDataSet.");
    }
  }

  /**
  * Gets the current database connection
  *
  * @return Connection A database connection.
  */
  function &connection()
  {
    return $this->conn;
  }

  /**
  * Gets the Schema for this DataSet
  *
  * @return  Schema The Schema for this DataSet
  */
  function &schema()
  {
    return $this->schema;
  }

  /**
  * Get Record at 0 based index position
  *
  * NOTE: Behaviour of this function is slightly different compared to the php5 version.
  *       Instead of throwing (returning) an exception, this function simply returnes
  *       FALSE, if the record at position $pos cannot be found.
  *
  * @param int $pos
  * @return mixed Record An instance of the found Record or FALSE if not found.
  */
  function & getRecord($pos)
  {
    if ($this->containsRecord($pos)) {
      $rec =& $this->records[$pos];
      if (is_a($this, 'TableDataSet')) {
        /* only throws an exception if not TableDataSet */
        $rec->markForUpdate();
      }
      $this->recordRetrievedCount++;
      return $rec;
    }

    return false;
  }

  /**
  * Find Record at 0 based index position. This is an internal alternative 
  * to getRecord which tries to be smart about the type of record it is.
  *
  * NOTE: Behaviour of this function is slightly different compared to the php5 version.
  *       Instead of throwing (returning) an exception, this function simply returnes
  *       FALSE, if the record at position $pos cannot be found.
  *
  * @param int $pos
  * @return mixed Record an instance of the found Record on success, otherwise FALSE.
  */
  function & findRecord($pos) 
  {
    if (! $this->containsRecord($pos)) {
      return false;
    }

    return $this->records[$pos];
  }

  /**
  * Check to see if the DataSet contains a Record at 0 based position
  *
  * @param pos
  * @return TRUE if record exists otherwise FALSE.
  */
  function containsRecord($pos)
  {
    return (isset($this->records[$pos]));            
  }   

  /**
  * Causes the DataSet to hit the database and fetch max records,
  * starting at start. Record count begins at 0.
  *
  * This method supports two signatures:
  *     - fetchRecords(10); // LIMIT = 10
  *     - fetchRecords(5, 10); // OFFSET = 5, LIMIT = 10
  * 
  * @param int $p1 max - or start if $p2 is set
  * @param int $p2 start
  * @return DataSet This class on success.
  * @return SQL- or DataSetException on failure.
  */
  function fetchRecords($p1 = 0, $p2 = null)
  {
    if ($p2 !== null) {
      $start = $p1;
      $max = $p2;
    } else {
      $start = 0;
      $max = $p1;
    }
    
    if ($this->lastFetchSize() > 0 && $this->records !== null) {
      return new DataSetException("You must call DataSet::clearRecords() before executing DataSet::fetchRecords() again!");
    }

    if ($this->stmt === null && $this->resultSet === null) {
      $this->stmt =& $this->conn->createStatement();
      $this->stmt->setOffset($start);
      $this->stmt->setLimit($max);
      // reset, since native limit applied
      $start = 0;
      $max = 0;
      $rs =& $this->stmt->executeQuery($this->selectSql);

      if (Creole::isError($rs)) {
        return $rs;
      }

      $this->resultSet =& $rs;
    }

    if ($this->resultSet !== null) 
    {
      $this->records = array();

      $startCounter = 0;
      $fetchCount = 0;

      while (! $this->allRecordsRetrieved() ) 
      {
        $e = null;

        if (($e = $this->resultSet->next()) === true) 
        {
          if ($startCounter >= $start) {
            $this->records[] =& new Record($this);
            $fetchCount++;
            if ($fetchCount === $max) { // check after because we must fetch at least 1
              break;
            }
          } else {
            $startCounter++;
          }
        }
        else if (Creole::isError($e)) {
          $this->stmt->close();
          return $e;
        } 
        else {
          $this->setAllRecordsRetrieved(true);
          break;
        }
      }                
      $this->lastFetchSize = $fetchCount;
    }

    return $this;
  }

  /**
  * The number of records that were fetched with the last fetchRecords.
  *
  * @return int
  */
  function lastFetchSize()
  {
    return $this->lastFetchSize;
  }

  /**
  * gets the KeyDef object for this DataSet
  *
  * @return KeyDef The keydef for this DataSet, this value can be null
  */
  function & keydef()
  {
    return $this->keyDef;
  }

  /**
  * This returns a represention of this DataSet
  */
  function __toString()
  {
    $sb = "";        
    for ($i = 0, $size = $this->size(); $i < $size; $i++) {
      $sb .= $this->getRecord($i);
    }
    return $sb;
  }       

  /**
  * Classes extending this class must implement this method.
  *
  * @return string The SELECT SQL.
  * @throws DataSetException;
  */
  function getSelectSql()
  {
    trigger_error (
      "DataSet::getSelectSql(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
  * Returns the columns attribute for the DataSet
  *
  * @return     the columns attribute for the DataSet
  */
  function & getColumns()
  {
    return $this->columns;
  }

  /**
  * Gets the number of Records in this DataSet. It is 0 based. 
  *
  * @return int Number of Records in this DataSet
  */
  function size()
  {
    if ( $this->records === null ) {
      return 0;
    }
    return count($this->records);
  }
}


/**
 * The Iterator returned by DataSet::getIterator() that loops through the records.
 * 
 * Thanks to PHP5 SPL this allows you to foreach() over a DataSet:
 * <code>
 *   $ds = new QueryDataSet($conn, "select * from author");
 *   $ds->fetchRecords();
 *   foreach($ds as $rec) {
 *     print $rec->getValue("mycol");
 *   }
 * </code>
 * 
 * @see DataSet::getIterator()
 */
class DataSetIterator /*implements Iterator*/ 
{
  var $ds;
  var $size;
  var $pos;
  
  function DataSetIterator(/*DataSet*/ &$ds) 
  {
    if (! is_a($rec, 'DataSet')) {
      trigger_error (
        "DataSetIterator::DataSetIterator(): parameter 1 not of type 'DataSet' !",
        E_USER_ERROR
      );
    }

    $this->ds =& $ds;
    $this->size = $ds->size();
  }
  
  function rewind() 
  {
    $this->pos = 0;
  }
  
  function valid() 
  {
    return $this->pos < $this->size;
  }
  
  function key() 
  {
    return $this->pos;
  }
  
  function & current() 
  {
    return $this->ds->getRecord($this->pos);
  }
  
  function next() 
  {
    $this->pos++;
  }
}