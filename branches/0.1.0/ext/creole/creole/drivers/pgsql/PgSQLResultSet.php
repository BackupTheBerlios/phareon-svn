<?php
/*
 *  $Id: PgSQLResultSet.php,v 1.2 2004/05/06 19:39:16 micha Exp $
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
require_once 'creole/common/ResultSetCommon.php';

/**
 * PostgreSQL implementation of ResultSet.
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @author    Michael Aichler <aichler@mediacluster.de> (Creole)
 * @version   $Revision: 1.2 $
 * @package   creole.drivers.pgsql
 */
class PgSQLResultSet extends ResultSetCommon
{
  /**
   * Postgres doesn't actually move the db pointer.  The specific row
   * is fetched by call to pg_fetch_array() rather than by a seek and
   * then an unspecified pg_fetch_array() call.
   * 
   * The only side-effect of this situation is that we don't really know 
   * if the seek will fail or succeed until we have called next().  This
   * behavior is acceptible - and explicitly documented in 
   * ResultSet::seek() phpdoc.
   *
   * @access public 
   * @see ResultSet::seek()
   * @return bool
   */ 
  function seek($rownum)
  {
    if ($rownum < 0) {
        return false;
    }
    
    // PostgreSQL rows start w/ 0, but this works, because we are
    // looking to move the position _before_ the next desired position
    $this->cursorPos = $rownum;
    return true;
  }
  
  /**
   * @access public
   * @see ResultSet::next()
   * @return mixed boolean TRUE on success, SQLException on failure.
   */ 
  function next()
  {
    // must suppress errors here because we are jumping to rownum that may not exist w/ fetch_array command
    $this->fields = @pg_fetch_array($this->result, $this->cursorPos, $this->fetchmode);

    if (!$this->fields) {
      $err = @pg_result_error($this->result);
      if (!$err) {
        // We've advanced beyond end of recordset.
        $this->afterLast();
        return false;
      } else {
        return new SQLException(CREOLE_ERROR, "Error fetching result", $err);                
      }
    }
    
    // Advance cursor position
    $this->cursorPos++;    
    return true;
  }

  /**
   * @access public
   * @see ResultSet::getRecordCount()
   * @return mixed number of rows on success, SQLException on failure.
   */
  function getRecordCount()
  {
    $rows = @pg_num_rows($this->result);
    if ($rows === null) {
      return new SQLException(CREOLE_ERROR, "Error fetching num rows", pg_result_error($this->result));
    }
    return (int) $rows;
  }

  /**
   * @access public
   * @see ResultSet::close()
   * @return void
   */ 
  function close()
  {
    $this->fields = array();
    @pg_free_result($this->result);
  }
  
  /**
   * Convert Postgres string representation of array into native PHP array.
   * @param string $str Postgres string array rep: {1223, 2343} or {{"welcome", "home"}, {"test2", ""}}
   * @access private
   * @return array
   */
  function strToArray($str)
  {
    $str = substr($str, 1, -1); // remove { }
    $res = array();
    
    $subarr = array();
    $in_subarr = 0;
    
    $toks = explode(',', $str);
    foreach($toks as $tok) {                    
      if ($in_subarr > 0) { // already in sub-array?
        $subarr[$in_subarr][] = $tok;
        if ('}' === substr($tok, -1, 1)) { // check to see if we just added last component                    
          $res[] = $this->strToArray(implode(',', $subarr[$in_subarr]));
          $in_subarr--;
        }
      } elseif ($tok{0} === '{') { // we're inside a new sub-array                               
        if ('}' !== substr($tok, -1, 1)) {
          $in_subarr++;
          // if sub-array has more than one element
          $subarr[$in_subarr] = array();
          $subarr[$in_subarr][] = $tok;                    
        } else {
          $res[] = $this->strToArray($tok);
        }
      } else { // not sub-array
        $val = trim($tok, '"'); // remove " (surrounding strings)
        // perform type castng here?
        $res[] = $val;
      }
    }
    
    return $res;
  }

  /**
   * Reads a column as an array.
   * The value of the column is unserialized & returned as an array.
   * @param mixed $column Column name (string) or index (int) starting with 1.
   * @access public
   * @return mixed array on success, SQLException - If the column specified is not a valid key in current field array.
   */
  function & getArray($column) 
  {
    if (is_int($column)) { $column--; } // because Java convention is to start at 1 
    if (!array_key_exists($column, $this->fields)) { 
      return new SQLException(CREOLE_ERROR_INVALID, "Invalid resultset column: " . (is_int($column) ? $column + 1 : $column)); 
    }
    if ($this->fields[$column] === null) { return null; }
    return $this->strToArray($this->fields[$column]);
  } 
  
  /**
   * Returns Blob with contents of column value.
   * 
   * @param mixed $column Column name (string) or index (int) starting with 1 (if ResultSet::FETCHMODE_NUM was used).
   * @access public
   * @return mixed New Blob with data from column on success, SQLException - If the column specified is not a valid key in current field array.
   */
  function & getBlob($column) 
  {
    if (is_int($column)) { $column--; } // because Java convention is to start at 1 
    if (!array_key_exists($column, $this->fields)) { 
      return new SQLException(CREOLE_ERROR_INVALID, "Invalid resultset column: " . (is_int($column) ? $column + 1 : $column)); 
    }
    if ($this->fields[$column] === null) { return null; }
    require_once 'creole/util/Blob.php';
    $b =& new Blob();
    $b->setContents(pg_unescape_bytea($this->fields[$column]));
    return $b;
  }     

  /**
   * @param mixed $column Column name (string) or index (int) starting with 1.
   * @access public
   * @return mixed boolean on success, SQLException - If the column specified is not a valid key in current field array.
   */
  function getBoolean($column) 
  {
      if (is_int($column)) { $column--; } // because Java convention is to start at 1 
      if (!array_key_exists($column, $this->fields)) { 
        return new SQLException(CREOLE_ERROR_INVALID, "Invalid resultset column: " . (is_int($column) ? $column + 1 : $column)); 
      }
      if ($this->fields[$column] === null) { return null; }
      return ($this->fields[$column] === 't');
  }
          
}
