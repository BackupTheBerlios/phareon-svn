<?php
/*
 *  $Id: PgSQLIdGenerator.php,v 1.1 2004/04/28 17:12:57 micha Exp $
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
 
require_once 'creole/IdGenerator.php';

/**
 * PostgreSQL IdGenerator implemenation.
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @author    Michael Aichler <aichler@mediacluster.de> (Creole)
 * @version   $Revision: 1.1 $
 * @package   creole.drivers.pgsql
 */
class PgSQLIdGenerator extends IdGenerator 
{
  /** Connection object that instantiated this class */
  var $conn;

  /**
   * Creates a new IdGenerator class, saves passed connection for use
   * later by getId() method.
   * @param Connection $conn
   */
  function PgSQLIdGenerator(/*Connection*/ &$conn)
  {
    if (! is_a($conn, 'Connection')) {
      trigger_error(
        "PgSQLIdGenerator::PgSQLIdGenerator(): parameter 1 not of type 'Connection' !",
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
    return true;
  }    
  
  /**
   * @see IdGenerator::isAfterInsert()
   */
  function isAfterInsert()
  {
    return false;
  }
      
  /**
   * @see IdGenerator::getIdMethod()
   */
  function getIdMethod()
  {
    return IdGenerator::SEQUENCE();
  }
  
  /**
   * @see IdGenerator::getId()
   */
  function getId($name = null)
  {
    if ($name === null) {
      return new SQLException(CREOLE_ERROR, "You must specify the sequence name when calling getId() method.");
    }
    $rs =& $this->conn->executeQuery("select nextval('" . $name . "')", ResultSet::FETCHMODE_NUM());
    if (Creole::isError($rs)) { return $rs; }
    $e = $rs->next();
    if (Creole::isError($e)) { return $e; }
    return $rs->getInt(1);
  }
  
}

