<?php
/*
 *  $Id: MSSQLCallableStatement.php,v 1.3 2004/06/14 15:05:55 micha Exp $
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

require_once 'creole/drivers/mssql/MSSQLPreparedStatement.php';
include_once 'creole/CreoleTypes.php';

/**
 * MS SQL Server class to handle stored procedure execution.
 * 
 * Developer note: 
 *
 *    There is no CallableStatement superclass.  Unlike JDBC, Creole
 *    uses abstract parent classes rather than interfaces -- in order
 *    to minimize code duplication.  Since PHP doesn't support multiple
 *    inheritance, the DRIVERCallableStatement class cannot extend both
 *    the DRIVERPreparedStatement class and the would-be abstract
 *    CallableStatement class.
 *
 * @author Hans Lellelid <hans@xmpl.org>
 * @author Michael Aichler <aichler@mediacluster.de>
 * @version $Revision: 1.3 $
 * @package creole
 */
class MSSQLCallableStatement extends MSSQLPreparedStatement
{
  /** Output variables */
  var $boundOutVars = array();
  
  /**
   * Match Creole types to SQL Server types
   * @var array
   */
  /*var $typeMap = array(); */    
  /**
   * Statement created by mssql_init()
   * @var resource
   */
  var $stmt;


  /**
   * The result resource.
   * @var resource
   */
  var $result;
    
  /**
   * Construct new MSSQLCallableStatement.
   * 
   * @param Connection $conn
   * @param resource $stmt
   */
  function MSSQLCallableStatement(/*Connection*/ &$conn, $stmt)
  {
    if (! is_a($table, 'Connection')) {
      trigger_error(
        "MSSQLCallableStatement::MSSQLCallableStatement(): parameter 1 not of type 'Connection' !",
        E_USER_ERROR
      );
    }

    $this->conn =& $conn;
    $this->stmt = $stmt;
  }   
    
  /**
  * @see CallableStatement::getResource()
  */
  function getResource()
  {
    return $this->stmt;
  }
      
  /**
  * @see CallableStatement::close()
  */
  function close()
  {
    @mssql_free_statement($this->stmt);
    $this->rsFetchCount = 0;
  }
  
  /**
   * @see CallableStatement::executeQuery()
   */
  function & executeQuery($p1 = null, $fetchmode = null)
  {
    $params = null;
    if ($fetchmode !== null) {
      $params = $p1;
    } elseif ($p1 !== null) {
      if (is_array($p1)) $params = $p1;
      else $fetchmode = $p1;
    }
    
    if ($params) {
      for($i=0,$cnt=count($params); $i < $cnt; $i++) {
        $this->set($i+1, $params[$i]);
      }
    }
    
    $this->result = mssql_execute($this->stmt);
    if (!$this->result) {
      return new SQLException(CREOLE_ERROR, 'unable to execute callable statement', mssql_get_last_message());
    }
    
    return new MSSQLResultSet($this->conn, $this->result, $fetchmode, $this->offset, $this->limit);
  }
  
  /**
   * @see CallableStatement::getMoreResults()
   */
  function getMoreResults()
  {
    $this->rsFetchCount++; // we track this because 
    $hasMore = mssql_next_result($this->result);
    if ($this->resultSet) $this->resultSet->close();                    
    if ($hasMore) {
      $clazz = $this->resultClass;
      $this->resultSet =& new $clazz($this, $this->result);
    } else {
      $this->resultSet = null;
    }
    return $hasMore;
  }

  /**
  * @see CallableStatement::registerOutParameter()
  */
  function registerOutParameter($paramIndex, $sqlType)
  {
    /* $typeMap seems to be only used here, so keep it simple */
    static $typeMap;
    
    if ($typeMap === null) 
    {
      $typeMap = array
      (
        CreoleTypes::BOOLEAN() => SQLBIT,
        CreoleTypes::BIGINT() => SQLINT4,
        CreoleTypes::SMALLINT() => SQLINT2,
        CreoleTypes::TINYINT() => SQLINT2,
        CreoleTypes::INTEGER() => SQLINT4,
        CreoleTypes::CHAR() => SQLCHAR,
        CreoleTypes::VARCHAR() => SQLVARCHAR,
        CreoleTypes::TEXT() => SQLTEXT,
        CreoleTypes::FLOAT() => SQLFLT8,
        CreoleTypes::DOUBLE() => SQLFLT8,
        CreoleTypes::DATE() => SQLVARCHAR,
        CreoleTypes::TIME() => SQLVARCHAR,
        CreoleTypes::TIMESTAMP() => SQLVARCHAR,
        CreoleTypes::VARBINARY() => SQLVARCHAR,
        CreoleTypes::NUMERIC() => SQLINT4,
        CreoleTypes::DECIMAL() => SQLFLT8                                        
      );
    }

    mssql_bind($this->stmt, $paramIndex, $this->boundOutVars[$paramIndex], $typeMap[$sqlType], true);
  }
  
  /**
  * @see CallableStatement::setArray()
  */
  function setArray($paramIndex, &$value, $out = false) 
  {
    if ($out) $this->boundOutVars[$paramIndex] = &$value; // reference means that changes to value, will be reflected
    if ($value === null) {
      $this->setNull($paramIndex);
    } else {
      $value = serialize($value);
      mssql_bind($this->stmt, $paramIndex, $value, SQLTEXT, $out);
    }
  }

  /**
  * @see CallableStatement::setBoolean()
  */
  function setBoolean($paramIndex, &$value, $out = false) 
  {
    if ($out) $this->boundOutVars[$paramIndex] = &$value; // reference means that changes to value, will be reflected        
    if ($value === null) {
      $this->setNull($paramIndex);
    } else {
      $value = ($value) ? 1 : 0;
      mssql_bind($this->stmt, $paramIndex, $value, SQLBIT, $out);
    }
  }
  

  /**
   * @see CallableStatement::setBlob()
   */
  function setBlob($paramIndex, &$blob, $out = false) 
  {
    if ($blob === null) {
      $this->setNull($paramIndex);
    } else {
      if (is_object($blob)) {
        $blob = $blob->__toString();
      }
      if ($out) $this->boundOutVars[$paramIndex] = &$blob; // reference means that changes to value, will be reflected        
      $data = unpack("H*hex", $blob);
      mssql_bind($this->stmt, $paramIndex, $data, SQLTEXT, $out);
    }
  } 
  
  /**
  * @see CallableStatement::setClob()
  */
  function setClob($paramIndex, &$clob, $out = false) 
  {
    if ($clob === null) {
      $this->setNull($paramIndex);
    } else {
      if (is_object($clob)) {
        $clob = $clob->__toString();
      }
      if ($out) $this->boundOutVars[$paramIndex] = &$clob; // reference means that changes to value, will be reflected
      mssql_bind($this->stmt, $paramIndex, $clob, SQLTEXT, $out);
    }
  }

  /**
  * @see CallableStatement::setDate()
  */
  function setDate($paramIndex, &$value, $out = false) 
  {
    if ($out) $this->boundOutVars[$paramIndex] = &$value; // reference means that changes to value, will be reflected
    if ($value === null) {
      $this->setNull($paramIndex);
    } else {
      if (is_numeric($value)) $value = date("Y-m-d", $value);
      mssql_bind($this->stmt, $paramIndex, $value, SQLVARCHAR, $out);
    }
  } 
      
  /**
   * @see CallableStatement::setFloat()
   */
  function setFloat($paramIndex, &$value, $out = false) 
  {
    if ($out) $this->boundOutVars[$paramIndex] = &$value; // reference means that changes to value, will be reflected
    if ($value === null) {
      $this->setNull($paramIndex);
    } else {
      $value = (float) $value;
      mssql_bind($this->stmt, $paramIndex, $value, SQLFLT8, $out);
    }
  }
  
  /**
  * @see CallableStatement::setInt()
  */
  function setInt($paramIndex, &$value, $out = false) 
  {
    if ($out) $this->boundOutVars[$paramIndex] = &$value; // reference means that changes to value, will be reflected
    if ($value === null) {
      $this->setNull($paramIndex);
    } else {
      $value = (int) $value;
      mssql_bind($this->stmt, $paramIndex, $value, SQLINT4, $out);
    }
  }    
  
  /**
  * @see CallableStatement::setNull()
  */
  function setNull($paramIndex) 
  {
    // hopefully type isn't essential here :)
    $value = null; // wants a var to pass by reference
    mssql_bind($this->stmt, $paramIndex, $value, $type=null, $out=false, $is_null=true);
  }

  /**
  * @see CallableStatement::setString()
  */
  function setString($paramIndex, &$value, $out = false) 
  {    
    if ($out) $this->boundOutVars[$paramIndex] = &$value; // reference means that changes to value, will be reflected
    if ($value === null) {
      $this->setNull($paramIndex);
    } else {
      $value = (string) $value;            
      mssql_bind($this->stmt, $paramIndex, $value, SQLVARCHAR, $out);
    }
  } 
  
  /**
  * @see CallableStatement::setTime()
  */
  function setTime($paramIndex, &$value, $out = false) 
  {    
    if ($out) $this->boundOutVars[$paramIndex] = &$value; // reference means that changes to value, will be reflected
    if ($value === null) {
      $this->setNull($paramIndex);
    } else {
      if (is_numeric($value)) $value = date("H:i:s", $value);
      mssql_bind($this->stmt, $paramIndex, $value, SQLVARCHAR, $out);
    }
  }
  
  /**
  * @see CallableStatement::setTimestamp()
  */
  function setTimestamp($paramIndex, &$value, $out = false) 
  {
    if ($out) $this->boundOutVars[$paramIndex] = &$value; // reference means that changes to value, will be reflected
    if ($value === null) {
      $this->setNull($paramIndex);
    } else {
      if (is_numeric($value)) $value = date('Y-m-d H:i:s', $value);
      mssql_bind($this->stmt, $paramIndex, $value, SQLVARCHAR, $out);
    }
  }            
      
  /**
  * @see CallableStatement::getArray()
  * @return mixed array on success, SQLException, if requested variable not bound to output var.
  */
  function getArray($paramIndex) 
  {
    if (!array_key_exists($paramIndex, $this->boundOutVars)) {
      return new SQLException(CREOLE_ERROR_INVALID, 'Requesting variable not bound to output var: '.$paramIndex);
    }
    if ($this->boundOutVars[$paramIndex] === null) { return null; }
    return (array) unserialize($this->boundOutVars[$paramIndex]);
  } 

  /**
  * @see CallableStatement::getBoolean()
  * @return mixed boolean on success, SQLException, if requested variable not bound to output var.
  */
  function getBoolean($paramIndex) 
  {
    if (!array_key_exists($paramIndex, $this->boundOutVars)) {
      return new SQLException(CREOLE_ERROR_INVALID, 'Requesting variable not bound to output var: '.$paramIndex);
    }
    if ($this->boundOutVars[$paramIndex] === null) { return null; }
    return (boolean) $this->boundOutVars[$paramIndex];
  }
          
  /**
  * @see CallableStatement::getBlob()
  * @return mixed BLob on success, SQLException, if requested variable not bound to output var.
  */
  function & getBlob($paramIndex) 
  {
    if (!array_key_exists($paramIndex, $this->boundOutVars)) {
      return new SQLException(CREOLE_ERROR_INVALID, 'Requesting variable not bound to output var: '.$paramIndex);
    }
    if ($this->boundOutVars[$paramIndex] === null) { return null; }
    require_once 'creole/util/Blob.php';
    $b =& new Blob();
    $b->setContents($this->boundOutVars[$paramIndex]);
    return $b;
  }     

  /**
  * @see CallableStatement::getClob()
  * @return mixed CLob on success, SQLException, if requested variable not bound to output var.
  */
  function & getClob($paramIndex) 
  {
    if (!array_key_exists($paramIndex, $this->boundOutVars)) {
      return new SQLException(CREOLE_ERROR_INVALID, 'Requesting variable not bound to output var: '.$paramIndex);
    }
    if ($this->boundOutVars[$paramIndex] === null) { return null; }
    require_once 'creole/util/Clob.php';
    $c =& new Clob();
    $c->setContents($this->boundOutVars[$paramIndex]);
    return $c;
  } 
  
  /**
  * @see CallableStatement::getDate()
  * @return mixed string on success, SQLException, if requested variable not bound to output var.
  */
  function getDate($paramIndex, $fmt = '%Y-%m-%d') 
  {
    if (!array_key_exists($paramIndex, $this->boundOutVars)) {
      return new SQLException(CREOLE_ERROR_INVALID, 'Requesting variable not bound to output var: '.$paramIndex);
    }
    if ($this->boundOutVars[$paramIndex] === null) { return null; }
    
    $ts = strtotime($this->boundOutVars[$paramIndex]);        
    if ($ts === -1) {
      return new SQLException(CREOLE_ERROR_INVALID, "Unable to convert value at column " . $paramIndex . " to timestamp: " . $this->boundOutVars[$paramIndex]);
    }        
    if (strpos($format, '%') !== false) {
      return strftime($format, $ts);
    } else {
      return date($format, $ts);
    }
    
    return $this->boundOutVars[$paramIndex];
  } 

  /**
  * @param mixed $paramIndex Column name (string) or index (int).
  * @return mixed float on success, SQLException, if requested variable not bound to output var.
  */
  function getFloat($paramIndex) 
  {
    if (!array_key_exists($paramIndex, $this->boundOutVars)) {
      return new SQLException(CREOLE_ERROR_INVALID, 'Requesting variable not bound to output var: '.$paramIndex);
    }
    if ($this->boundOutVars[$paramIndex] === null) { return null; }
    return (float) $this->boundOutVars[$paramIndex];
  }

  /**
  * @see CallableStatement::getInt()
  * @return mixed int on success, SQLException, if requested variable not bound to output var.
  */
  function getInt($paramIndex) 
  {
    if (!array_key_exists($paramIndex, $this->boundOutVars)) {
      return new SQLException(CREOLE_ERROR_INVALID, 'Requesting variable not bound to output var: '.$paramIndex);
    }
    if ($this->boundOutVars[$paramIndex] === null) { return null; }
    return (int) $this->boundOutVars[$paramIndex];
  }            

  /**
  * @see CallableStatement::getString()
  * @return mixed string on success, SQLException, if requested variable not bound to output var.
  */
  function getString($paramIndex) 
  {
    if (!array_key_exists($paramIndex, $this->boundOutVars)) {
      return new SQLException(CREOLE_ERROR_INVALID, 'Requesting variable not bound to output var: '.$paramIndex);
    }
    if ($this->boundOutVars[$paramIndex] === null) { return null; }
    return (string) $this->boundOutVars[$paramIndex];
  } 

  /**
  * @see CallableStatement::getTime()
  * @return mixed string on success, SQLException, if requested variable not bound to output var.
  */
  function getTime($paramIndex, $format='%X') 
  {
    if (!array_key_exists($paramIndex, $this->boundOutVars)) {
      return new SQLException(CREOLE_ERROR_INVALID, 'Requesting variable not bound to output var: '.$paramIndex);
    }
    if ($this->boundOutVars[$paramIndex] === null) { return null; }
    
    $ts = strtotime($this->boundOutVars[$paramIndex]);        
    if ($ts === -1) {
      return new SQLException(CREOLE_ERROR_INVALID, "Unable to convert value at column " . $paramIndex . " to timestamp: " . $this->boundOutVars[$paramIndex]);
    }        
    if (strpos($format, '%') !== false) {
      return strftime($format, $ts);
    } else {
      return date($format, $ts);
    }
  }

  /**
  * @see CallableStatement::getTimestamp()
  * @return mixed string on success, SQLException, if requested variable not bound to output var.
  */
  function getTimestamp($paramIndex, $format = 'Y-m-d H:i:s') 
  {
    if (!array_key_exists($paramIndex, $this->boundOutVars)) {
      return new SQLException(CREOLE_ERROR_INVALID, 'Requesting variable not bound to output var: '.$paramIndex);
    }
    if ($this->boundOutVars[$paramIndex] === null) { return null; }
            
    $ts = strtotime($this->boundOutVars[$paramIndex]);        
    if ($ts === -1) {
      return new SQLException(CREOLE_ERROR_INVALID, "Unable to convert value at column " . $paramIndex . " to timestamp: " . $this->boundOutVars[$paramIndex]);
    }        
    if (strpos($format, '%') !== false) {
      return strftime($format, $ts);
    } else {
      return date($format, $ts);
    }
  }    

}
