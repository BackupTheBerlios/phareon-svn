<?php
/*
 *  $Id: MySQLTableInfo.php,v 1.5 2004/05/02 21:05:29 micha Exp $
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

require_once 'creole/metadata/TableInfo.php';

/**
 * MySQL implementation of TableInfo.
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @author    Michael Aichler <aichler@mediacluster.de>
 * @version   $Revision: 1.5 $
 * @package   creole.drivers.mysql.metadata
 */
class MySQLTableInfo extends TableInfo
{
  /**
  * Loads the columns for this table.
  * @return TRUE on success, SQLException on error.
  */
  function initColumns()
  {
    include_once 'creole/metadata/ColumnInfo.php';
    include_once 'creole/drivers/mysql/MySQLTypes.php';

    if (!@mysql_select_db($this->dbname, $this->dblink)) {
      return new SQLException(CREOLE_ERROR_NODBSELECTED, 'No database selected');
    }

    // To get all of the attributes we need, we use
    // the MySQL "SHOW COLUMNS FROM $tablename" SQL.  We cannot
    // use the API functions (e.g. mysql_list_fields() because they
    // do not return complete information -- e.g. precision / scale, default
    // values).

    $res = mysql_query("SHOW COLUMNS FROM " . $this->name, $this->dblink);

    $defaults = array();
    $nativeTypes = array();
    $precisions = array();

    while($row = mysql_fetch_assoc($res)) 
    {
      $name = $row['Field'];
      $default = $row['Default'];
      $is_nullable = ($row['Null'] == 'YES');
      
      $size = null;
      $precision = null;   
      
      if (preg_match('/^(\w+)[\(]?([\d,]*)[\)]?( |$)/', $row['Type'], $matches)) {                
        //            colname[1]   size/precision[2]    
        $nativeType = $matches[1];                                                     
        if ($matches[2]) {
          if ( ($cpos = strpos($matches[2], ',')) !== false) {
            $size = (int) substr($matches[2], 0, $cpos);
            $precision = (int) substr($matches[2], $cpos + 1);
          } else {
            $size = (int) $matches[2];
          }
        }            
      } elseif (preg_match('/^(\w+)\(/', $row['Type'], $matches)) {
        $nativeType = $matches[1];                
      } else {
        $nativeType = $row['Type'];
      }
      
      $this->columns[$name] = new ColumnInfo($this, $name, MySQLTypes::getType($nativeType), $nativeType, $size, $precision, $is_nullable, $default);
    }
    
    $this->colsLoaded = true;
    return true;
  }

  /**
  * Loads the primary key information for this table.
  *
  * @return TRUE on success, SQLException on error.
  */
  function initPrimaryKey()
  {
    include_once 'creole/metadata/PrimaryKeyInfo.php';

    // columns have to be loaded first
    if (!$this->colsLoaded) {
      if (($e = $this->initColumns()) !== true) {
        return $e;
      }
    }

    if (!@mysql_select_db($this->dbname, $this->dblink)) {
      return new SQLException(CREOLE_ERROR_NODBSELECTED, 'No database selected');
    }

    // Primary Keys
    $res = mysql_query("SHOW KEYS FROM " . $this->name, $this->dblink);

    // Loop through the returned results, grouping the same key_name together
    // adding each column for that key.

    while($row = mysql_fetch_assoc($res)) {
      $name = $row["Column_name"];
      if (!isset($this->primaryKey)) {
          $this->primaryKey =& new PrimaryKeyInfo($name);
      }
      $this->primaryKey->addColumn($this->columns[ $name ]);
    }


    $this->pkLoaded = true;
    return true;
  }

  /**
  * Loads the indexes for this table.
  *
  * @return TRUE on success, SQLException on error.
  */
  function initIndexes()
  {
    include_once 'creole/metadata/IndexInfo.php';

    // columns have to be loaded first
    if (!$this->colsLoaded) $this->initColumns();

    if (!@mysql_select_db($this->dbname, $this->dblink)) {
      return new SQLException(CREOLE_ERROR_NODBSELECTED, 'No database selected');
    }

    // Indexes
    $res = mysql_query("SHOW INDEX FROM " . $this->name, $this->dblink);

    // Loop through the returned results, grouping the same key_name together
    // adding each column for that key.

    while($row = mysql_fetch_assoc($res)) {
      $name = $row["Column_name"];
      if (!isset($this->indexes[$name])) {
          $this->indexes[$name] = new IndexInfo($name);
      }
      $this->indexes[$name]->addColumn($this->columns[ $name ]);
    }

    $this->indexesLoaded = true;
    return true;
  }

  /** Load foreign keys (unsupported in MySQL). */
  function initForeignKeys()
  {
    // columns have to be loaded first
    if (!$this->colsLoaded) {
      if (($e = $this->initColumns()) !== true) {
        return $e;
      }
    }

    // Foreign keys are not supported in mysql.

    $this->fksLoaded = true;
    return true;
  }

}
