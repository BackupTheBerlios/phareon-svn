<?php
/*
 *  $Id: IndexInfo.php,v 1.2 2004/05/02 21:12:23 micha Exp $
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
 * Represents an index.
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @author    Michael Aichler <aichler@mediacluster.de>
 * @version   $Revision: 1.2 $
 * @package   creole.metadata
 */
class IndexInfo
{
  /** name of the index */
  var $name;

  /** columns in this index */
  var $columns = array();

  function IndexInfo($name)
  {
    $this->name = $name;
  }

  function getName()
  {
    return $this->name;
  }

  /**
  * @param ColumnInfo $column
  * @return void
  */
  function addColumn(&$column)
  {
    if (! is_a($column, 'ColumnInfo')) {
      trigger_error(
        "IndexInfo::addColumn(): parameter 1 not of type 'ColumnInfo' !",
        E_USER_ERROR
      );
    }

    $this->columns[] =& $column;
  }

  function & getColumns()
  {
    return $this->columns;
  }

  function toString()
  {
    return $this->name;
  }
}
