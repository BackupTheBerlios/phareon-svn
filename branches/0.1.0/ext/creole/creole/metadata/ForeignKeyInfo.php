<?php

/*
 *  $Id: ForeignKeyInfo.php,v 1.1 2004/03/25 22:59:41 micha Exp $
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
 * Represents a foreign key.
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @version   $Revision: 1.1 $
 * @package   creole.metadata
 */
class ForeignKeyInfo
{

    var $name;
  var $references = array();

    /**
   * @param string $name The name of the foreign key.
   */
  function ForeignKeyInfo($name)
    {
        $this->name = $name;
    }

  /**
   * Get foreign key name.
   * @return string
   */
    function getName()
    {
        return $this->name;
    }

  /**
   * Adds a foreign-local mapping.
   * @param ColumnInfo $local
   * @param ColumnInfo $foreign
   */
  function addReference(/*ColumnInfo*/ &$local, /*ColumnInfo*/ &$foreign)
  {
    if (! is_a($local, 'ColumnInfo')) {
      trigger_error(
        "ForeignKeyInfo::addReference(): parameter 1 not of type 'ColumnInfo' !",
        E_USER_ERROR
      );
    }
    else if (! is_a($foreign, 'ColumnInfo')) {
      trigger_error(
        "ForeignKeyInfo::addReference(): parameter 2 not of type 'ColumnInfo' !",
        E_USER_ERROR
      );
    }
    $ref[] =& $local;
    $ref[] =& $foreign;
    $this->references[] =& $ref;
  }

  /**
   * Gets the local-foreign column mapping.
   * @return array array( [0] => array([0] => local ColumnInfo object, [1] => foreign ColumnInfo object) )
   */
  function & getReferences()
  {
    return $this->references;
  }

    /**
   * @return string
   */
  function toString()
    {
        return $this->name;
    }

}
