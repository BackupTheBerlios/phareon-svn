<?php
/*
 *  $Id: PgSQLDatabaseInfo.php,v 1.1 2004/04/28 17:51:31 micha Exp $
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
 
require_once 'creole/metadata/DatabaseInfo.php';

/**
 * PgSQL implementation of DatabaseInfo.
 * 
 * @author    Hans Lellelid <hans@xmpl.org>
 * @author    Michael Aichler <aichler@mediacluster.de>
 * @version   $Revision: 1.1 $
 * @package   creole.drivers.pgsql.metadata
 */ 
class PgSQLDatabaseInfo extends DatabaseInfo 
{
  
  /**
   * @return TRUE on success, SQLException on failure.
   */
  function initTables()
  {
    include_once 'creole/drivers/pgsql/metadata/PgSQLTableInfo.php';
    
    $result = pg_exec($this->dblink, "select tablename from pg_tables where tablename not like 'pg\_%' order by 1");        

    if (!$result) {
      return new SQLException(CREOLE_ERROR, "Could not list tables", pg_last_error($this->dblink));
    }
    
    while ($row = pg_fetch_row($result)) {
      $this->tables[strtoupper($row[0])] =& new PgSQLTableInfo($this, $row[0]);
    }
    
    return true;
  }            
    
  /**
   * PgSQL sequences.
   *
   * @return void 
   * @throws SQLException
   */
  function initSequences()
  {
    return new SQLException(CREOLE_ERROR, "Sequences are currently unsupported.");
  }
        
}
