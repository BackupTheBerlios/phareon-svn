<?php
/*
 *  $Id: MSSQLDatabaseInfo.php,v 1.2 2004/06/28 11:51:11 micha Exp $
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
 * MSSQL impementation of DatabaseInfo.
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @author    Michael Aichler <aichler@mediacluster.de>
 * @version   $Revision: 1.2 $
 * @package   creole.drivers.mssql.metadata
 */ 
class MSSQLDatabaseInfo extends DatabaseInfo 
{
  /**
   * @access protected
   * @return mixed TRUE on success, SQLException on failure.
   */
  function initTables()
  {
    include_once 'creole/drivers/mssql/metadata/MSSQLTableInfo.php';
    
    $dsn = $this->conn->getDSN();
    
    
    if (!@mssql_select_db($this->dbname, $this->dblink)) {
      return new SQLException(CREOLE_ERROR_NODBSELECTED, 'No database selected');
    }
         
    $result = mssql_query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_NAME <> 'dtproperties'", $this->dblink);

    if (!$result) {
      return new SQLException(CREOLE_ERROR, "Could not list tables", mssql_get_last_message());            
    }
    
    while ($row = mssql_fetch_row($result)) {
      $this->tables[strtoupper($row[0])] =& new MSSQLTableInfo($this, $row[0]);            
    }
    
    return true;
  }            
  
  /**
  * @access protected
  * @return void 
  */
  function initSequences()
  {
    // there are no sequences -- afaik -- in MSSQL.
  }
      
}
