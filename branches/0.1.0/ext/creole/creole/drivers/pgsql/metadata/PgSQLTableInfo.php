<?php
/*
 *  $Id: PgSQLTableInfo.php,v 1.1 2004/04/28 17:51:31 micha Exp $
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
 
// ported

require_once 'creole/metadata/TableInfo.php';

/**
 * PgSQL implementation of TableInfo.
 * 
 * See this Python code by David M. Cook for some good reference on Pgsql metadata
 * functions:
 * @link http://www.sandpyt.org/pipermail/sandpyt/2003-March/000008.html
 * 
 * Here's some more information from postgresql:
 * @link http://developer.postgresql.org/docs/pgsql/src/backend/catalog/information_schema.sql
 * 
 * @todo -c Eventually move to supporting only Postgres >= 7.4, which has the information_schema
 * 
 * @author    Hans Lellelid <hans@xmpl.org>
 * @author    Michael Aichler <aichler@mediacluster.de>
 * @version   $Revision: 1.1 $
 * @package   creole.drivers.pgsql.metadata
 */
class PgSQLTableInfo extends TableInfo 
{        
  /** 
  * Load the columns for this table 
  * @return TRUE on success, SQLException on failure.
  */
  function initColumns() 
  {
    include_once 'creole/metadata/ColumnInfo.php';
    include_once 'creole/drivers/pgsql/PgSQLTypes.php';
    
    // Get any default values for columns        
    $result = pg_query($this->dblink, "SELECT d.adnum as num, d.adsrc as def from pg_attrdef d, pg_class c where d.adrelid=c.oid and c.relname='".$this->name."' order by d.adnum");
    
    if (!$result) {
      return new SQLException(CREOLE_ERROR, "Could not get defaults for columns in table: " . $this->name, pg_last_error($this->dblink));
    }
    
    $defaults = array();
    while($row = pg_fetch_assoc($result)) {
      // [HL] for now I am going to not add default
      // values that are nextval(...) sequence values.
      // We need to resolve on a larger level whether these should
      // be returned.  Maybe instead indicating that these columns are 
      // sequences would be appropriate...
      if (!preg_match('/^nextval\(/', $row['def'])) {
        $defaults[ $row['num'] ] = $row['def'];
      }
    }
      
    // Get the columns, types, etc.
    // based on SQL from ADOdb
    $result = pg_query($this->dblink, "SELECT    a.attname,
                                t.typname,
                                a.attlen,
                                a.atttypmod,
                                a.attnotnull,
                                a.atthasdef,
                                a.attnum,
                                CAST(
                                     CASE WHEN t.typtype = 'd' THEN
                                       CASE WHEN t.typbasetype IN (21, 23, 20) THEN 0
                                            WHEN t.typbasetype IN (1700) THEN (t.typtypmod - 4) & 65535
                                            ELSE null END
                                     ELSE
                                       CASE WHEN a.atttypid IN (21, 23, 20) THEN 0
                                            WHEN a.atttypid IN (1700) THEN (a.atttypmod - 4) & 65535
                                            ELSE null END
                                     END
                                     AS int) AS numeric_scale
                        FROM     pg_class c, 
                                pg_attribute a,
                                pg_type t 
                        WHERE    relkind = 'r' AND 
                                c.relname='".$this->name."' AND 
                                a.attnum > 0 AND 
                                a.atttypid = t.oid AND 
                                a.attrelid = c.oid 
                        ORDER BY a.attnum");
      
    if (!$result) {
      return new SQLException(CREOLE_ERROR, "Could not list fields for table: " . $this->name, pg_last_error($this->dblink));
    }
        
    while($row = pg_fetch_assoc($result)) {
      $name = $row['attname'];
      $type = $row['typname'];
      $size = $row['attlen'];
      $scale = $row['numeric_scale'];
      if ($size <= 0) {
        // maxlen for varchar is 4 larger than actual max length
        $size = $row['atttypmod'] - 4; 
        if ($size <= 0) {
          $size = null;
        }
      }
                  
      $is_nullable = ($row['attnotnull'] == 't' ? true : false);
      $default = ($row['atthasdef'] == 't' && isset( $defaults[ $row['attnum'] ]) ? $defaults[ $row['attnum'] ] : null);
      $this->columns[$name] =& new ColumnInfo($this, $name, PgSQLTypes::getType($type), $type, $size, $scale, $is_nullable, $default);
    }
    
    $this->colsLoaded = true;
    return true;
  }
    
  /** 
  * Load foreign keys for this table. 
  * @return TRUE on success, SQLException on failure.
  */
  function initForeignKeys()
  {
    include_once 'creole/metadata/ForeignKeyInfo.php';
            
    $result = pg_query($this->dblink, "SELECT  tgargs
                        FROM    pg_trigger
                        WHERE   tgrelid = (select oid from pg_class where relname='".$this->name."')
                                AND tgfoid = (select oid from pg_proc where proname='RI_FKey_check_ins')");
    if (!$result) {
      return new SQLException(CREOLE_ERROR, "Could not list foreign keys for table: " . $this->name, pg_last_error($this->dblink));
    }
    
    // tgargs ($row[0]) looks like this:
    // _fk_1\000book\000publisher\000UNSPECIFIED\000publisher_id\000publisher_id\000
    //  name?   table     references     ?           key              fkey                                
    //    0       1           2          3            4                 5
    
    while($row = pg_fetch_row($result)) 
    {        
      $parts = explode('\000', $row[0]);
      $name = $parts[0];
      $local_table = $parts[1];
      $foreign_table = $parts[2];
      $local_column = $parts[4];
      $foreign_column = $parts[5];
      
      $foreignTable =& $this->database->getTable($foreign_table);
      if (Creole::isError($foreignTable)) return $foreignTable;
      
      $foreignColumn =& $foreignTable->getColumn($foreign_column);
      if (Creole::isError($foreignColumn)) return $foreignColumn;

      $localTable =& $this->database->getTable($local_table);    
      if (Creole::isError($localTable)) return $localTable;
      
      $localColumn =& $localTable->getColumn($local_column);
      if (Creole::isError($localColumn)) return $localColumn;
      
      if (!isset($this->foreignKeys[$name])) {
        $this->foreignKeys[$name] =& new ForeignKeyInfo($name);
      }
      $this->foreignKeys[$name]->addReference($localColumn, $foreignColumn);
    }
            
    $this->fksLoaded = true;
    return true;
  }
    
  /** 
  * Load indexes for this table 
  * @return TRUE on success, SQLException on failure.
  */
  function initIndexes()
  {
    include_once 'creole/metadata/IndexInfo.php';

    // columns have to be loaded first
    if (!$this->colsLoaded) {
      if (($e = $this->initColumns()) !== true) {
        return $e;
      }
    }
    
    // FIXME -- try this out!
    // then figure out if we need to add any information
    // to our index object to accommodate more complex backends
    
    $result = pg_query($this->dblink, "SELECT c.relname as tablename, c.oid, c2.relname as indexname,

                        i.indisprimary, i.indisunique, pg_catalog.pg_get_indexdef(i.indexrelid) FROM

                        pg_catalog.pg_class c,

                        pg_catalog.pg_class c2, pg_catalog.pg_index i WHERE c.oid = i.indrelid AND

                        i.indexrelid = c2.oid AND c.relname = ".$this->name." ORDER BY i.indisprimary DESC, i.indisunique DESC,

                        c2.relname");
      
    if (!$result) {
      return new SQLException(CREOLE_ERROR, "Could not list indexes keys for table: " . $this->name, pg_last_error($this->dblink));
    }
    
    while($row = pg_fetch_assoc($result)) {
      $name = $row["indexname"];
      if (!isset($this->indexes[$name])) {
        $this->indexes[$name] =& new IndexInfo($name);
      }
      $this->indexes[$name]->addColumn($this->columns[ $name ]);            
    }
    
    $this->indexesLoaded = true;
    return true;
  }
    
  /** 
  * Loads the primary keys for this table.
  * @return TRUE on success, SQLException on failure. 
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
    
    // Primary Keys
    $result = pg_query($this->dblink, "SELECT ta.attname, ia.attnum
                                        FROM pg_attribute ta, pg_attribute ia, pg_class c, pg_index i
                                        WHERE c.relname = '".$this->name."_pkey'
                                            AND c.oid = i.indexrelid
                                            AND ia.attrelid = i.indexrelid
                                            AND ta.attrelid = i.indrelid
                                            AND ta.attnum = i.indkey[ia.attnum-1]
                                        ORDER BY ia.attnum");
    
    if (!$result) {
      return new SQLException(CREOLE_ERROR, "Could not list primary keys for table: " . $this->name, pg_last_error($this->dblink));
    }
    
    // Loop through the returned results, grouping the same key_name together
    // adding each column for that key.
                
    while($row = pg_fetch_assoc($result)) {
      $name = $row["attname"];
      if (!isset($this->primaryKey)) {
        $this->primaryKey =& new PrimaryKeyInfo($name);
      }
      $this->primaryKey->addColumn($this->columns[ $name ]);
    }            
    
    $this->pkLoaded = true;
    return true;
  }
  
}
