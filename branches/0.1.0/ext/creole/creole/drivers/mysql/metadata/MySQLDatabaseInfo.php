<?php
/*
 *  $Id: MySQLDatabaseInfo.php,v 1.3 2004/03/31 16:30:22 micha Exp $
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

//
// STATUS:
// - ported:   y
// - compiled: y
// - tested:   n
//

require_once 'creole/metadata/DatabaseInfo.php';

/**
 * MySQL implementation of DatabaseInfo.
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @version   $Revision: 1.3 $
 * @package   creole.drivers.mysql.metadata
 */
class MySQLDatabaseInfo extends DatabaseInfo
{
  /**
  * @return TRUE on success, SQLException on error.
  */
  function initTables()
  {
    include_once 'creole/drivers/mysql/metadata/MySQLTableInfo.php';

    $result = mysql_list_tables($this->dbname, $this->dblink);

    if (!$result) {
      return new SQLException(CREOLE_ERROR, "Could not list tables", mysql_error($this->dblink));
    }

    while ($row = mysql_fetch_row($result)) {
      $this->tables[strtoupper($row[0])] =& new MySQLTableInfo($this, $row[0]);
    }

    return true;
  }

  /**
  * MySQL does not support sequences.
  *
  * @return void
  * @throws SQLException
  */
  function initSequences()
  {
    // return throw (new SQLException("MySQL does not support sequences natively."));
  }

}
