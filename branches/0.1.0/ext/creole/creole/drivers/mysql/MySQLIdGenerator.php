<?php
/*
 * $Id: MySQLIdGenerator.php,v 1.2 2004/06/14 15:14:48 micha Exp $
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

require_once 'creole/drivers/mysql/MySQLConnection.php';
require_once 'creole/IdGenerator.php';

/**
 * MySQL IdGenerator implimenation.
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @author    Michael Aichler <aichler@mediacluster.de>
 * @version   $Revision: 1.2 $
 * @package   creole.drivers.mysql
 */
class MySQLIdGenerator extends IdGenerator
{
  /** Connection object that instantiated this class */
  var $conn;

  /**
   * Creates a new IdGenerator class, saves passed connection for use
   * later by getId() method.
   * @param Connection $conn
   */
  function MySQLIdGenerator(/*Connection*/ &$conn)
  {
    if (! is_a($conn, 'Connection')) {
      trigger_error(
        "MySQLIdGenerator::MySQLIdGenerator(): parameter 1 not of type 'Connection' !",
        E_USER_ERROR
      );
    }

    $this->conn =& $conn;
  }

  /**
   * @see IdGenerator::isBeforeInsert()
   */
  function isBeforeInsert()
  {
      return false;
  }

  /**
   * @see IdGenerator::isAfterInsert()
   */
  function isAfterInsert()
  {
      return true;
  }

  /**
   * @see IdGenerator::getIdMethod()
   */
  function getIdMethod()
  {
      return IdGenerator::AUTOINCREMENT();
  }

  /**
   * Returns last-generated auto-increment ID.
   * 
   * Note that for very large values (2,147,483,648 to 9,223,372,036,854,775,807) a string
   * will be returned, because these numbers are larger than supported by PHP's native
   * numeric datatypes.
   * 
   * @see IdGenerator::getId()
   */
  function getId($unused = null)
  {
    $insert_id = mysql_insert_id($this->conn->getResource());
    if ( $insert_id < 0 ) {
      $insert_id = null;
      $result = mysql_query('SELECT LAST_INSERT_ID()', $this->conn->getResource());
      if ( $result ) {
        $row = mysql_fetch_row($result);
        $insert_id = $row ? $row[0] : null;
      }
    }
    return $insert_id;
  }

}

