<?php
/*
 *  $Id: PreparedStatement.php,v 1.1 2004/03/25 22:59:39 micha Exp $
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

/**
 * Interface for a pre-compiled SQL statement.
 *
 * Many drivers do not take advantage of pre-compiling SQL statements; for these
 * cases the precompilation is emulated.  This emulation comes with slight penalty involved
 * in parsing the queries, but provides other benefits such as a cleaner object model and ability
 * to work with BLOB and CLOB values w/o needing special LOB-specific routines.
 *
 * This class is abstract because there are driver-specific implementations in [clearly] how queries
 * are executed, and how parameters are bound.
 *
 * This class is not as abstract as the JDBC version.  For exmple, if you are using a driver
 * that uses name-based query param substitution, then you'd better bind your variables to
 * names rather than index numbers.  e.g. in Oracle
 * <code>
 *            $stmt = $conn->prepareStatement("INSERT INTO users (name, passwd) VALUES (:name, :pass)");
 *            $stmt->setString(":name", $name);
 *            $stmt->executeUpdate();
 * </code>
 *
 * Developer note:  In many ways this interface is an extension of the Statement interface.  However, due
 * to limitations in PHP5's interface extension model (specifically that you cannot change signatures on
 * methods defined in parent interface), we cannot extend the Statement interface.
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @version   $Revision: 1.1 $
 * @package   creole
 */
class PreparedStatement
{
  /**
   * Gets the db Connection that created this statement.
   * @return Connection
   */
  function & getConnection()
  {
    trigger_error (
      "PreparedStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * Get the PHP native resource for the statement (if supported).
   * @return resource
   */
  function & getResource()
  {
    trigger_error (
      "PreparedStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * Free resources associated with this statement.
   * Some drivers will need to implement this method to free
   * database result resources.
   *
   * @return void
   */
  function close()
  {
    trigger_error (
      "PreparedStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * Get result set.
   * This assumes that the last thing done was an executeQuery() or an execute()
   * with SELECT-type query.
   *
   * @return RestultSet Last ResultSet or <code>null</code> if not applicable.
   */
  function & getResultSet()
  {
    trigger_error (
      "PreparedStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * Gets next result set (if this behavior is supported by driver).
   * Some drivers (e.g. MSSQL) support returning multiple result sets -- e.g.
   * from stored procedures.
   *
   * This function also closes any current restult set.
   *
   * Default behavior is for this function to return false.  Driver-specific
   * implementations of this class can override this method if they actually
   * support multiple result sets.
   *
   * @return boolean True if there is another result set, otherwise false.
   */
  function getMoreResults()
  {
    trigger_error (
      "PreparedStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * Get update count.
   *
   * @return int Number of records affected, or <code>null</code> if not applicable.
   */
  function getUpdateCount()
  {
    trigger_error (
      "PreparedStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * Sets the maximum number of rows to return from db.
   * This will affect the SQL if the RDBMS supports native LIMIT; if not,
   * it will be emulated.  Limit only applies to queries (not update sql).
   * @param int $v Maximum number of rows or 0 for all rows.
   * @return void
   */
  function setLimit($v)
  {
    trigger_error (
      "PreparedStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * Returns the maximum number of rows to return or 0 for all.
   * @return int
   */
  function getLimit()
  {
    trigger_error (
      "PreparedStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * Sets the start row.
   * This will affect the SQL if the RDBMS supports native OFFSET; if not,
   * it will be emulated. Offset only applies to queries (not update) and
   * only is evaluated when LIMIT is set!
   * @param int $v
   * @return void
   */
  function setOffset($v)
  {
    trigger_error (
      "PreparedStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * Returns the start row.
   * Offset only applies when Limit is set!
   * @return int
   */
  function getOffset()
  {
    trigger_error (
      "PreparedStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * Executes the SQL query in this PreparedStatement object and returns the resultset generated by the query.
   * We support two signatures for this method:
   * - $stmt->executeQuery(ResultSet::FETCHMODE_NUM);
   * - $stmt->executeQuery(array($param1, $param2), ResultSet::FETCHMODE_NUM);
   * @param mixed $p1 Either (array) Parameters that will be set using PreparedStatement::set() before query is executed or (int) fetchmode.
   * @param int $fetchmode The mode to use when fetching the results (e.g. ResultSet::FETCHMODE_NUM, ResultSet::FETCHMODE_ASSOC).
   * @return ResultSet
   * @throws SQLException if a database access error occurs.
   */
  function & executeQuery($p1 = null, $fetchmode = null)
  {
    trigger_error (
      "PreparedStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * Executes the SQL INSERT, UPDATE, or DELETE statement in this PreparedStatement object.
   *
   * @param array $params Parameters that will be set using PreparedStatement::set() before query is executed.
   * @return int Number of affected rows (or 0 for drivers that return nothing).
   * @throws SQLException if a database access error occurs.
   */
  function executeUpdate($params = null)
  {
    trigger_error (
      "PreparedStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * A generic set method.
   *
   * You can use this if you don't want to concern yourself with the details.  It involves
   * slightly more overhead than the specific settesr, since it grabs the PHP type to determine
   * which method makes most sense.
   *
   * @param int $paramIndex
   * @param mixed $value
   * @return void
   * @throws SQLException
   */
  function set($paramIndex, $value)
  {
    trigger_error (
      "PreparedStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * Sets an array.
   * Unless a driver-specific method is used, this means simply serializing
   * the passed parameter and storing it as a string.
   * @param int $paramIndex
   * @param array $value
   * @return void
   */
  function setArray($paramIndex, $value)
  {
    trigger_error (
      "PreparedStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * Sets a boolean value.
   * Default behavior is true = 1, false = 0.
   * @param int $paramIndex
   * @param boolean $value
   * @return void
   */
  function setBoolean($paramIndex, $value)
  {
    trigger_error (
      "PreparedStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }


  /**
   * @param int $paramIndex
   * @param mixed $blob Blob object or string containing data.
   * @return void
   */
  function setBlob($paramIndex, $blob)
  {
    trigger_error (
      "PreparedStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * @param int $paramIndex
   * @param mixed $clob Clob object  or string containing data.
   * @return void
   */
  function setClob($paramIndex, $clob)
  {
    trigger_error (
      "PreparedStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * @param int $paramIndex
   * @param string $value
   * @return void
   */
  function setDate($paramIndex, $value)
  {
    trigger_error (
      "PreparedStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * @param int $paramIndex
   * @param float $value
   * @return void
   */
  function setFloat($paramIndex, $value)
  {
    trigger_error (
      "PreparedStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * @param int $paramIndex
   * @param int $value
   * @return void
   */
  function setInt($paramIndex, $value)
  {
    trigger_error (
      "PreparedStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * @param int $paramIndex
   * @return void
   */
  function setNull($paramIndex)
  {
    trigger_error (
      "PreparedStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * @param int $paramIndex
   * @param string $value
   * @return void
   */
  function setString($paramIndex, $value)
  {
    trigger_error (
      "PreparedStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * @param int $paramIndex
   * @param string $value
   * @return void
   */
  function setTime($paramIndex, $value)
  {
    trigger_error (
      "PreparedStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * @param int $paramIndex
   * @param string $value
   * @return void
   */
  function setTimestamp($paramIndex, $value)
  {
    trigger_error (
      "PreparedStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

}
