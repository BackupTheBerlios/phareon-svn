<?php
/*
 *  $Id: ResultSetCommon.php,v 1.5 2004/06/16 20:56:22 hlellelid Exp $
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

require_once 'creole/ResultSet.php';

/**
 * This class implements many shared or common methods needed by resultset drivers.
 *
 * A new instance of this class will be returned by the DB implementation
 * after processing a query that returns data.
 *
 * The get*() methods in this class will format values before returning them. Note
 * that if they will return <code>null</code> if the database returned <code>NULL</code>
 * which makes these functions easier to use than simply typecasting the values from the
 * db. If the requested column does not exist than an exception (SQLException) will be thrown.
 *
 * <code>
 * $rs = $conn->executeQuery("SELECT MAX(stamp) FROM event", ResultSet::FETCHMODE_NUM);
 * $rs->next();
 *
 * $max_stamp = $rs->getTimestamp(1, "d/m/Y H:i:s");
 * // $max_stamp will be date string or null if no MAX(stamp) was found
 *
 * $max_stamp = $rs->getTimestamp("max(stamp)", "d/m/Y H:i:s");
 * // will THROW EXCEPTION, because the resultset was fetched using numeric indexing
 * // SQLException: Invalid resultset column: max(stamp)
 * </code>
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @version   $Revision: 1.5 $
 * @package   creole.common
 */
class ResultSetCommon extends ResultSet
{
  /**
   * The fetchmode for this recordset.
   * @var int
   */
  var $fetchmode;

  /**
   * DB connection.
   * @var Connection
   */
  var $conn;

  /**
   * Resource identifier used for native result set handling.
   * @var resource
   */
  var $result;

  /**
   * The current cursor position (row number). First row is 0.
   * @var int
   */
  var $cursorPos = 0;

  /**
   * The current unprocessed record/row from the db.
   * @var array
   */
  var $fields;

  /**
   * Whether to convert assoc col case.
   */
  var $ignoreAssocCase = false;

  /**
  * Constructor
  *
  * @see ResultSet::isBeforeFirst()
  */
  function ResultSetCommon(/*Connection*/ &$conn, &$result, $fetchmode = null)
  {
    if (! is_a($conn, 'Connection')) {
      trigger_error (
        "ResultSetCommon::ResultSetCommon(): parameter 1 not of type 'Connection' !",
        E_USER_ERROR
      );
    }

    $this->conn =& $conn;
    $this->result =& $result;
    
    if ($fetchmode !== null) {
      $this->fetchmode = $fetchmode;
    } else {
      $this->fetchmode = ResultSet::FETCHMODE_ASSOC(); // default
    }
    
    $this->ignoreAssocCase = (($conn->getFlags() & Creole::NO_ASSOC_LOWER()) === Creole::NO_ASSOC_LOWER());
    
    register_shutdown_function(array($this, '__ResultSetCommon'));
  }

  /**
  * Destructor
  *
  * Free db result resource.
  */
  function __ResultSetCommon()
  {
    $this->close();
  }
  
  /**
  * @see ResultSet::getIterator()
  */
  function & getIterator()
  {
      require_once 'creole/ResultSetIterator.php';
      return new ResultSetIterator($this);
  }

  /**
   * @see ResultSet::getResource()
   */
  function & getResource()
  {
      return $this->result;
  }

  /**
  * @see ResultSet::isIgnoreAssocCase()
  */
  function isIgnoreAssocCase()
  {
      return $this->ignoreAssocCase;
  }

  /**
  * @see ResultSet::isBeforeFirst()
  */
  function setFetchmode($mode)
  {
      $this->fetchmode = $mode;
  }

  /**
  * @see ResultSet::isBeforeFirst()
  */
  function getFetchmode()
  {
      return $this->fetchmode;
  }

  /**
  * @see ResultSet::isBeforeFirst()
  */
  function & previous()
  {
    // Go back 2 spaces so that we can then advance 1 space.
    $ok = $this->seek($this->cursorPos - 2);
    if ($ok === false) {
        $this->beforeFirst();
        return false;
    }
    return $this->next();
  }

  /**
   * @see ResultSet::isBeforeFirst()
   */
  function relative($offset)
  {
      // which absolute row number are we seeking
      $pos = $this->cursorPos + ($offset - 1);
      $ok = $this->seek($pos);

      if ($ok === false) {
          if ($pos < 0) {
              $this->beforeFirst();
          } else {
              $this->afterLast();
          }
      } else {
          $ok = $this->next();
      }

      return $ok;
  }

  /**
  * @see ResultSet::isBeforeFirst()
  */
  function absolute($pos)
  {
      $ok = $this->seek( $pos - 1 ); // compensate for next() factor
      if ($ok === false) {
          if ($pos - 1 < 0) {
              $this->beforeFirst();
          } else {
              $this->afterLast();
          }
      } else {
          $ok = $this->next();
      }
      return $ok;
  }

  /**
   * @see ResultSet::isBeforeFirst()
   */
  function & first()
  {
      if($this->cursorPos !== 0) { $this->seek(0); }
      return $this->next();
  }

  /**
   * @see ResultSet::isBeforeFirst()
   */
  function & last()
  {
    $last = $this->getRecordCount();
    if (Creole::isError($last)) {
      return $last;
    }

    if($this->cursorPos !==  ($last = $last - 1)) {
      $this->seek( $last );
    }

    return $this->next();
  }

  /**
   * @see ResultSet::isBeforeFirst()
   */
  function beforeFirst()
  {
      $this->cursorPos = 0;
  }


  /**
   * @see ResultSet::isBeforeFirst()
   */
  function afterLast()
  {
    $rc = $this->getRecordCount();
    if (Creole::isError($rc)) {
      return $rc;
    }

    $this->cursorPos = $rc + 1;
    return true;
  }


  /**
   * @see ResultSet::isBeforeFirst()
   */
  function isAfterLast()
  {
      return ($this->cursorPos === $this->getRecordCount() + 1);
  }

  /**
   * @see ResultSet::isBeforeFirst()
   */
  function isBeforeFirst()
  {
      return ($this->cursorPos === 0);
  }

  /**
   * @see ResultSet::getCursorPos()
   */
  function getCursorPos()
  {
      return $this->cursorPos;
  }

  /**
   * @see ResultSet::getRow()
   */
  function & getRow()
  {
      return $this->fields;
  }

  /**
   * @see ResultSet::get()
   */
  function get($column)
  {
      $idx = (is_int($column) ? $column - 1 : $column);
      if (!array_key_exists($idx, $this->fields)) {
        return new SQLException(CREOLE_ERROR_INVALID, "Invalid resultset column: " . $column);
      }
      return $this->fields[$idx];
  }

  /**
   * @see ResultSet::getArray()
   */
  function getArray($column)
  {
      $idx = (is_int($column) ? $column - 1 : $column);
      if (!array_key_exists($idx, $this->fields)) {
        return new SQLException(CREOLE_ERROR_INVALID, "Invalid resultset column: " . $column);
      }
      if ($this->fields[$idx] === null) { return null; }
      return (array) unserialize($this->fields[$idx]);
  }

  /**
   * @see ResultSet::getBoolean()
   */
  function getBoolean($column)
  {
      $idx = (is_int($column) ? $column - 1 : $column);
      if (!array_key_exists($idx, $this->fields)) {
        return new SQLException(CREOLE_ERROR_INVALID, "Invalid resultset column: " . $column);
      }
      if ($this->fields[$idx] === null) { return null; }
      return (boolean) $this->fields[$idx];
  }

  /**
   * @see ResultSet::getBlob()
   */
  function getBlob($column)
  {
    $idx = (is_int($column) ? $column - 1 : $column);
    if (!array_key_exists($idx, $this->fields)) {
      return new SQLException(CREOLE_ERROR_INVALID, "Invalid resultset column: " . $column);
    }

    if ($this->fields[$idx] === null) { return null; }
    require_once 'creole/util/Blob.php';
    $b = new Blob();
    $b->setContents($this->fields[$idx]);
    return $b;
  }

  /**
   * @see ResultSet::getClob()
   */
  function getClob($column)
  {
    $idx = (is_int($column) ? $column - 1 : $column);
    if (!array_key_exists($idx, $this->fields)) {
      return new SQLException(CREOLE_ERROR_INVALID, "Invalid resultset column: " . $column);
    }

    if ($this->fields[$idx] === null) { return null; }
    require_once 'creole/util/Clob.php';
    $c = new Clob();
    $c->setContents($this->fields[$idx]);
    return $c;
  }

  /**
   * @see ResultSet::getDate()
   */
  function getDate($column, $format = '%x')
  {
    $idx = (is_int($column) ? $column - 1 : $column);
    if (!array_key_exists($idx, $this->fields)) {
      return new SQLException(CREOLE_ERROR_INVALID, "Invalid resultset column: " . $column);
    }

    if ($this->fields[$idx] === null) { return null; }
    $ts = strtotime($this->fields[$idx]);
    if ($ts === -1) {
      return new SQLException(CREOLE_ERROR_INVALID, "Unable to convert value at column " . $column . " to timestamp: " . $this->fields[$idx]);
    }
    if ($format === null) {
      return $ts;
    }
    if (strpos($format, '%') !== false) {
        return strftime($format, $ts);
    } else {
        return date($format, $ts);
    }
  }

  /**
   * @see ResultSet::getFloat()
   */
  function getFloat($column)
  {
    $idx = (is_int($column) ? $column - 1 : $column);
    if (!array_key_exists($idx, $this->fields)) {
      return new SQLException(CREOLE_ERROR_INVALID, "Invalid resultset column: " . $column);
    }

    if ($this->fields[$idx] === null) { return null; }
    return (float) $this->fields[$idx];
  }

  /**
   * @see ResultSet::getInt()
   */
  function getInt($column)
  {
    $idx = (is_int($column) ? $column - 1 : $column);
    if (!array_key_exists($idx, $this->fields)) {
      return new SQLException(CREOLE_ERROR_INVALID, "Invalid resultset column: " . $column);
    }

    if ($this->fields[$idx] === null) { return null; }
    return (int) $this->fields[$idx];
  }

  /**
   * @see ResultSet::getString()
   */
  function getString($column)
  {
    $idx = (is_int($column) ? $column - 1 : $column);
    if (!array_key_exists($idx, $this->fields)) {
      return new SQLException(CREOLE_ERROR_INVALID, "Invalid resultset column: " . $column);
    }

    if ($this->fields[$idx] === null) { return null; }
    return rtrim((string) $this->fields[$idx]);
  }

  /**
   * @see ResultSet::getTime()
   */
  function getTime($column, $format = '%X')
  {
    $idx = (is_int($column) ? $column - 1 : $column);
    if (!array_key_exists($idx, $this->fields)) {
      return new SQLException(CREOLE_ERROR_INVALID, "Invalid resultset column: " . $column);
    }

    if ($this->fields[$idx] === null) { return null; }

    $ts = strtotime($this->fields[$idx]);

    if ($ts === -1) {
      return new SQLException(CREOLE_ERROR_INVALID, "Unable to convert value at column " . (is_int($column) ? $column + 1 : $column) . " to timestamp: " . $this->fields[$idx]);
    }

    if ($format === null) {
      return $ts;
    }

    if (strpos($format, '%') !== false) {
        return strftime($format, $ts);
    } else {
        return date($format, $ts);
    }
  }

  /**
   * @see ResultSet::getTimestamp()
   */
  function getTimestamp($column, $format = 'Y-m-d H:i:s')
  {
    $idx = (is_int($column) ? $column - 1 : $column);
    if (!array_key_exists($idx, $this->fields)) {
      return new SQLException(CREOLE_ERROR_INVALID, "Invalid resultset column: " . $column);
    }

    if ($this->fields[$idx] === null) { return null; }

    $ts = strtotime($this->fields[$idx]);
    if ($ts === -1) {
      return new SQLException(CREOLE_ERROR_INVALID, "Unable to convert value at column " . $column . " to timestamp: " . $this->fields[$idx]);
    }
    if ($format === null) {
      return $ts;
    }
    if (strpos($format, '%') !== false) {
        return strftime($format, $ts);
    } else {
        return date($format, $ts);
    }
  }

}

