<?php
/*
 *  $Id: CreoleTypes.php,v 1.3 2004/05/09 21:19:23 micha Exp $
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

/**
 * Generic Creole types modeled on JDBC types.
 *
 * @author    David Giffin <david@giffin.org>
 * @author    Hans Lellelid <hans@xmpl.org>
 * @version   $Revision: 1.3 $
 * @package   creole
 */
class CreoleTypes
{
  function BOOLEAN()       { return (1); }
  function BIGINT()        { return (2); }
  function SMALLINT()      { return (3); }
  function TINYINT()       { return (4); }
  function INTEGER()       { return (5); }
  function CHAR()          { return (6); }
  function VARCHAR()       { return (7); }
  function TEXT()          { return (17); }
  function FLOAT()         { return (8); }
  function DOUBLE()        { return (9); }
  function DATE()          { return (10); }
  function TIME()          { return (11); }
  function TIMESTAMP()     { return (12); }
  function VARBINARY()     { return (13); }
  function NUMERIC()       { return (14); }
  function BLOB()          { return (15); }
  function CLOB()          { return (16); }
  function LONGVARCHAR()   { return (17); }
  function DECIMAL()       { return (18); }
  function REAL()          { return (19); }
  function BINARY()        { return (20); }
  function LONGVARBINARY() { return (21); }
  function YEAR()          { return (22); }
  /** this is "ARRAY" from JDBC types */
  function ARR()           { return (23); }
  function OTHER()         { return (-1); }

  /** Map of Creole type integers to the setter/getter affix. */
  var $affixMap = null;
  /** Map of Creole type integers to their textual name. */
  var $creoleTypeMap = null;

  /**
  * This method returns the generic Creole (JDBC-like) type
  * when given the native db type.
  * @param string $nativeType DB native type (e.g. 'TEXT', 'byetea', etc.).
  * @return int Creole native type (e.g. Types::LONGVARCHAR, Types::BINARY, etc.).
  */
  function getType($nativeType)
  {
    trigger_error(
      "CreoleTypes: abstract function getType() has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * This method will return a native type that corresponds to the specified
   * Creole (JDBC-like) type.
   * If there is more than one matching native type, then the LAST defined
   * native type will be returned.
   * @return string Native type string.
   */
  function getNativeType($creoleType)
  {
    trigger_error(
      "CreoleTypes: abstract function getNativeType() has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
  * Gets the "affix" to use for ResultSet::get*() and PreparedStatement::set*() methods.
  * <code>
  * $setter = 'set' . CreoleTypes::getAffix(CreoleTypes::INTEGER);
  * $stmt->$setter(1, $intval);
  * // or
  * $getter = 'get' . CreoleTypes::getAffix(CreoleTypes::TIMESTAMP);
  * $timestamp = $rs->$getter();
  * </code>
  * @param int $creoleType The Creole types.
  * @return mixed The default affix for getting/setting cols of this type on success,
  * SQLException if $creoleType does not correspond to an affix
  */
  function getAffix($creoleType)
  {
    $self =& CreoleTypes::getInstance();

    if (! isset($self->affixMap[$creoleType])) {
      $e = new SQLException(CREOLE_ERROR, "Unable to return 'affix' for unknown CreoleType: " . $creoleType);
      print $e;
      return $e;
    }
    return $self->affixMap[$creoleType];
  }

  /**
  * Given a PHP variable, returns the correct affix (for getter/setter) to use based
  * on the PHP type of the variable.
  * @param mixed The PHP value for which to get affix.
  * @return string
  */
  function getAffixForValue($value)
  {
  }

  /**
  * Given the integer type, this method will return the corresponding type name.
  * @param int $creoleType the integer Creole type.
  * @return string The name of the Creole type (e.g. 'VARCHAR').
  */
  function getCreoleName($creoleType)
  {
    $self =& CreoleTypes::getInstance();

    if (! isset($self->creoleTypeMap[$creoleType])) {
      return null;
    }
    return $self->creoleTypeMap[$creoleType];
  }

  /**
  * Given the name of a type (e.g. 'VARCHAR') this method will return the corresponding integer.
  * @param string $creoleTypeName The case-sensisive (must be uppercase) name of the Creole type (e.g. 'VARCHAR').
  * @return int the Creole type.
  */
  function getCreoleCode($creoleTypeName)
  {
    $self =& CreoleTypes::getInstance();
    $type = array_search($creoleTypeName, $self->creoleTypeMap);

    if ($type === false) {
      return null;
    }

    return $type;
  }

  /*
  * @private
  */
  function & getInstance()
  {
    static $instance;

    if ($instance === null)
    {
      $instance = new CreoleTypes();
      $instance->affixMap = array
        (
          CreoleTypes::BOOLEAN() => 'Boolean',
          CreoleTypes::BIGINT()  => 'Int',
          CreoleTypes::CHAR() => 'String',
          CreoleTypes::DATE() => 'Date',
          CreoleTypes::DOUBLE() => 'Float',
          CreoleTypes::FLOAT()  => 'Float',
          CreoleTypes::INTEGER()  => 'Int',
          CreoleTypes::SMALLINT() => 'Int',
          CreoleTypes::TINYINT()  => 'Int',
          CreoleTypes::TIME() => 'Time',
          CreoleTypes::TIMESTAMP() => 'Timestamp',
          CreoleTypes::VARCHAR() => 'String',
          CreoleTypes::VARBINARY() => 'Blob',
          CreoleTypes::NUMERIC() => 'Float',
          CreoleTypes::BLOB() => 'Blob',
          CreoleTypes::CLOB() => 'Clob',
          CreoleTypes::LONGVARCHAR() => 'String',
          CreoleTypes::DECIMAL() => 'Float',
          CreoleTypes::REAL() => 'Float',
          CreoleTypes::BINARY() => 'Blob',
          CreoleTypes::LONGVARBINARY() => 'Blob',
          CreoleTypes::YEAR() => 'Int',
          CreoleTypes::ARR() => 'Array',
          CreoleTypes::OTHER() => '', // get() and set() for unknown
        );

      $instance->creoleTypeMap = array
        (
          CreoleTypes::BOOLEAN()  => 'BOOLEAN',
          CreoleTypes::BIGINT()   => 'BIGINT',
          CreoleTypes::SMALLINT() => 'SMALLINT',
          CreoleTypes::TINYINT()  => 'TINYINT',
          CreoleTypes::INTEGER()  => 'INTEGER',
          CreoleTypes::NUMERIC()  => 'NUMERIC',
          CreoleTypes::DECIMAL()  => 'DECIMAL',
          CreoleTypes::REAL()          => 'REAL',
          CreoleTypes::FLOAT()         => 'FLOAT',
          CreoleTypes::DOUBLE()        => 'DOUBLE',
          CreoleTypes::CHAR()          => 'CHAR',
          CreoleTypes::VARCHAR()       => 'VARCHAR',
          CreoleTypes::TEXT()          => 'TEXT',
          CreoleTypes::TIME()          => 'TIME',
          CreoleTypes::TIMESTAMP()     => 'TIMESTAMP',
          CreoleTypes::DATE()          => 'DATE',
          CreoleTypes::YEAR()          => 'YEAR',
          CreoleTypes::VARBINARY()     => 'VARBINARY',
          CreoleTypes::BLOB()          => 'BLOB',
          CreoleTypes::CLOB()          => 'CLOB',
          CreoleTypes::LONGVARCHAR()   => 'LONGVARCHAR',
          CreoleTypes::BINARY()        => 'BINARY',
          CreoleTypes::LONGVARBINARY() => 'LONGVARBINARY',
          CreoleTypes::ARR()           => 'ARR',
          CreoleTypes::OTHER()         => 'OTHER', // string is "raw" return
        );
    }

    return $instance;
  }
}
