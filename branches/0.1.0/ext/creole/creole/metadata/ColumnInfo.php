<?php

/*
 *  $Id: ColumnInfo.php,v 1.2 2004/05/09 21:20:23 micha Exp $
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

/*
 * Represents a Column.
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @author    Michael Aichler <aichler@mediacluster.de
 * @package   creole.metadata
 * @version   $Revision: 1.2 $
 */
class ColumnInfo
{
  /** Column name */
  var $name;

  /** Column Creole type. */
  var $type;

  /** Column native type */
  var $nativeType;

  /** Column length */
  var $size;

  /** Column scale (number of digits after decimal ) */
  var $scale;

  /** Is nullable? */
  var $isNullable;

  /** Default value */
  var $defaultValue;

  /** Table */
  var $table;

  /**
  * Construct a new ColumnInfo object.
  *
  * @param TableInfo $table The table that owns this column.
  * @param string $name Column name.
  * @param int $type Creole type.
  * @param string $nativeType Native type name.
  * @param int $size Column length.
  * @param int $scale Column scale (number of digits after decimal).
  * @param boolean $is_nullable Whether col is nullable.
  * @param mixed $default Default value.
  */
  function ColumnInfo(/*TableInfo*/ &$table, $name, $type = null, $nativeType = null, $size = null, $scale = null, $is_nullable = null, $default = null)
  {
    if (! is_a($table, 'TableInfo')) {
      trigger_error(
        "ColumnInfo::ColumnInfo(): parameter 1 not of type 'TableInfo' !",
        E_USER_ERROR
      );
    }

    $this->table =& $table;
    $this->name = $name;
    $this->type = $type;
    $this->nativeType = $nativeType;
    $this->size = $size;
    $this->scale = $scale;
    $this->isNullable = $is_nullable;
    $this->defaultValue = $default;
  }

  /**
  * This "magic" method is invoked upon serialize().
  * Because the Info class hierarchy is recursive, we must handle
  * the serialization and unserialization of this object.
  * @return array The class variables that should be serialized (all must be public!).
  */
  function __sleep()
  {
    return array('name', 'type', 'nativeType', 'size', 'precision', 'isNullable', 'defaultValue');
  }

  /**
  * Get column name.
  * @return string
  */
  function getName()
  {
    return $this->name;
  }

  /**
  * Get column type.
  * @return int
  */
  function getType()
  {
    return $this->type;
  }

  /**
  * Gets the native type name.
  * @return string
  */
  function getNativeType()
  {
    return $this->nativeType;
  }

  /**
  * Get column size.
  * @return int
  */
  function getSize()
  {
    return $this->size;
  }

  /**
  * Get column scale.
  * Scale refers to number of digits after the decimal.  Sometimes this is referred
  * to as precision, but precision is the total number of digits (i.e. length).
  * @return int
  */
  function getScale()
  {
    return $this->scale;
  }

  /**
  * Get the default value.
  * @return mixed
  */
  function getDefaultValue()
  {
    return $this->defaultValue;
  }

  /**
  * Is column nullable?
  * @return boolean
  */
  function isNullable()
  {
    return $this->isNullable;
  }

  /**
  * @return string
  */
  function toString()
  {
    return $this->name;
  }

  /**
  * Get parent table.
  * @return TableInfo
  */
  function & getTable()
  {
    return $this->table;
  }

}
