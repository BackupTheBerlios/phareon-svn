<?php
/*
 *  $Id: CallableStatement.php,v 1.1 2004/03/25 22:59:39 micha Exp $
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

include_once 'creole/PreparedStatement.php';

/**
 * Interface for callable statements.
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @version   $Revision: 1.1 $
 * @package   creole
 */
class CallableStatement extends PreparedStatement
{

  /**
   * Register a parameter as an output param.
   * @param string $paramIndex The stored procedure param name (e.g. @val1).
   * @param int $sqlType The type of the parameter (e.g. Type::BIT) .
   */
  function registerOutParameter($paramIndex, $sqlType)
  {
    trigger_error (
      "CallableStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   *
   * @param mixed $paramIndex Parameter name (e.g. "@var1").
   * @return array
   * @throws SQLException if $paramIndex was not bound as output variable.
   */
  function getArray($paramIndex)
  {
    trigger_error (
      "CallableStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   *
   * @param mixed $paramIndex Parameter name (e.g. "@var1").
   * @return boolean
   * @throws SQLException if $paramIndex was not bound as output variable.
   */
  function getBoolean($paramIndex)
  {
    trigger_error (
      "CallableStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   *
   * @param mixed $paramIndex Parameter name (e.g. "@var1").
   * @return Blob blob object
   * @throws SQLException if $paramIndex was not bound as output variable.
   */
  function getBlob($paramIndex)
  {
    trigger_error (
      "CallableStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * @param mixed $paramIndex Column name (string) or index (int).
   * @return Clob clob object.
   */
  function getClob($paramIndex)
  {
    trigger_error (
      "CallableStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

    /**
     * Return a formatted date.
     *
     * The default format for dates returned is preferred (in your locale, as specified using setlocale())
     * format w/o time (i.e. strftime("%x", $val)).  Override this by specifying a format second parameter.  You
     * can also specify a date()-style formatter; if you do, make sure there are no "%" symbols in your format string.
     *
     * @param mixed $column Column name (string) or index (int) starting with 1 (if ResultSet::FETCHMODE_NUM was used).
     * @param string $format Date formatter for use w/ strftime() or date() (it will choose based on examination of format string)
     * @return string Date.
     * @throws SQLException - If the column specified is not a valid key in current field array.
     */
    function getDate($column, $format = '%x')
  {
    trigger_error (
      "CallableStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * @param mixed $paramIndex Column name (string) or index (int).
   * @return float
   */
  function getFloat($paramIndex)
  {
    trigger_error (
      "CallableStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * @param mixed $paramIndex Column name (string) or index (int).
   * @return int
   */
  function getInt($paramIndex)
  {
    trigger_error (
      "CallableStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * @param mixed $paramIndex Column name (string) or index (int).
   * @return string
   */
  function getString($paramIndex)
  {
    trigger_error (
      "CallableStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

    /**
     * Return a formatted time.
     *
     * The default format for times returned is preferred (in your locale, as specified using setlocale())
     * format w/o date (i.e. strftime("%X", $val)).  Override this by specifying a format second parameter.  You
     * can also specify a date()-style formatter; if you do, make sure there are no "%" symbols in your format string.
     *
     * @param mixed $column Column name (string) or index (int) starting with 1 (if ResultSet::FETCHMODE_NUM was used).
     * @param string $format Date formatter for use w/ strftime() or date() (it will choose based on examination of format string)
     * @return string Formatted time.
     * @throws SQLException - If the column specified is not a valid key in current field array.
     */
    function getTime($column, $format = '%X')
  {
    trigger_error (
      "CallableStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

    /**
     * Return a formatted timestamp.
     *
     * The default format for timestamp is ISO standard YYYY-MM-DD HH:MM:SS (i.e. date('Y-m-d H:i:s', $val).
     * Override this by specifying a format second parameter.  You can also specify a strftime()-style formatter.
     *
     * Hint: if you want to get the unix timestamp use the "U" formatter string.
     *
     * @param mixed $column Column name (string) or index (int) starting with 1 (if ResultSet::FETCHMODE_NUM was used).
     * @param string $format Date formatter for use w/ strftime() or date() (it will choose based on examination of format string)
     * @return string Timestamp
     * @throws SQLException - If the column specified is not a valid key in current field array.
     */
    function getTimestamp($column, $format = 'Y-m-d H:i:s')
  {
    trigger_error (
      "CallableStatement::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

}
