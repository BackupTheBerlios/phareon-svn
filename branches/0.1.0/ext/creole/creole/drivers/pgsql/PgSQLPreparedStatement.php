<?php
/*
 *  $Id: PgSQLPreparedStatement.php,v 1.1 2004/04/28 17:49:35 micha Exp $
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
 
require_once 'creole/PreparedStatement.php';
require_once 'creole/common/PreparedStatementCommon.php';

/**
 * PgSQL subclass for prepared statements.
 * 
 * @author Hans Lellelid <hans@xmpl.org>
 * @author Michael Aichler <aichler@mediacluster.de>
 * @version $Revision: 1.1 $
 * @package creole.drivers.pgsql
 */
class PgSQLPreparedStatement extends PreparedStatementCommon
{
  /**
   * Quotes string using native pgsql function (pg_escape_string).
   * @access protected
   * @param string $str
   * @return string
   */
  function escape($str)
  {
    return pg_escape_string($str);
  }
  
  /**
   * Recursive function to turn multi-dim array into str representation.
   * @param array $arr
   * @access private
   * @return string Array in pgsql-friendly string notation: {val1, val2} or {{sub1,sub2}, {sub3, sub4}}
   */
  function arrayToStr($arr)
  {
    $parts = array();
    foreach((array)$arr as $el) {
      if (is_array($el)) {
        $parts[] = $this->arrayToStr($el);
      } else {
        if (is_string($el)) {
          $parts[] = '"' . pg_escape_string($el) . '"';
        } else {
          $parts[] = $el;
        }                
      }
    }        
    return '{' . implode(',', $parts) . '}';
  }
  
  /**
   * Sets an array.
   * Unless a driver-specific method is used, this means simply serializing
   * the passed parameter and storing it as a string.
   * @param int $paramIndex
   * @param array $value
   * @see PreparedStatement::setArray()
   * @return mixed boolean TRUE on success, SQLException if index out of range.
   */
  function setArray($paramIndex, $value) 
  {
    if( $paramIndex > $this->positionsCount || $paramIndex < 1) {
      return new SQLException(CREOLE_ERROR_INVALID, 'Cannot bind to invalid param index: '.$paramIndex);
    }
    
    if ($value === null)
      $this->setNull($paramIndex);
    else
      $this->boundInVars[$paramIndex] = "'" . $this->arrayToStr($value) . "'";        
    
    return true;
  }

  /**
   * For setting value of Postgres BOOLEAN column.
   * @param int $paramIndex
   * @param boolean $value
   * @return mixed boolean TRUE on success, SQLException if index out of range.
   */
  function setBoolean($paramIndex, $value) 
  {
    if( $paramIndex > $this->positionsCount || $paramIndex < 1) {
      return new SQLException(CREOLE_ERROR_INVALID, 'Cannot bind to invalid param index: '.$paramIndex);
    }        
    
    if ($value === null)
      $this->setNull($paramIndex);
    else
      $this->boundInVars[$paramIndex] = ($value ? "'t'" : "'f'");
    
    return true;
  }

  /**
   * Applies sqlite_udf_encode_binary() to ensure that binary contents will be handled correctly by sqlite.
   * @param int $paramIndex
   * @param mixed $blob Blob object or string containing data.
   * @return mixed boolean TRUE on success, SQLException if index out of range.
   */
  function setBlob($paramIndex, $blob) 
  {    
    if ($blob === null) {
      $this->setNull($paramIndex);
    } else {
      // they took magic __toString() out of PHP5.0.0; this sucks
      if (is_object($blob)) {
        $blob =& $blob->__toString();
      }            
      $data = unpack("H*hex", $blob);
      $this->boundInVars[$paramIndex] = "'" . pg_escape_bytea( $blob ) . "'";
    }    

    return true;        
  }
}
