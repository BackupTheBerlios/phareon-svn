<?php
/*
 *  $Id: OCI8ResultSet.php,v 1.1 2004/05/09 21:36:46 micha Exp $
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
 
require_once 'creole/ResultSet.php';
require_once 'creole/common/ResultSet.php';

/**
 * Oracle (OCI8) implementation of ResultSet class.
 *
 * @author    David Giffin <david@giffin.org>
 * @author    Hans Lellelid <hans@xmpl.org>
 * @author    Michael Aichler <aichler@mediacluster.de>
 * @version   $Revision: 1.1 $
 * @package   creole.drivers.oracle
 */
class OCI8ResultSet extends ResultSetCommon
{
  /**
   * @see ResultSet::seek()
   */ 
  function seek($rownum)
  {
    if ($rownum < $this->cursorPos) {
      // this will effectively disable previous(), first() and some calls to relative() or absolute()
      return new SQLException(CREOLE_ERROR, "Oracle ResultSet is FORWARD-ONLY");
    }
    
    // Oracle has no seek function imulate it here
    while ($this->cursorPos < $rownum) {
      $this->next();
    }
    $this->cursorPos = $rownum;
    return true;
  }

  
  /**
   * @see ResultSet::next()
   */ 
  function next()
  {   
    $moredata = OCIFetchInto($this->result, $arr, $this->fetchmode + OCI_RETURN_NULLS + OCI_RETURN_LOBS);
    $this->fields = $arr; // intentional, because we know $array started from empty, whereas ->$fields already had contents
    if (!$this->ignoreAssocCase) {
      $this->fields = array_change_key_case($arr, CASE_LOWER);
    }
    
    if (!$moredata) {
      // Check for an Error Here??            
      // We've advanced beyond end of recordset.
      $this->afterLast();
      return false;
    }
    
    // Advance cursor position
    $this->cursorPos++;
    return true;
  }


  /**
  * @see ResultSet::getRecordCount()
  */
  function getRecordCount()
  {
    $rows = @ocirowcount($this->result);
    if ($rows === null) {
      return new SQLException(CREOLE_ERROR, "Error fetching num rows");
    }
    return (int) $rows;
  }


  /**
  * @see ResultSet::close()
  */ 
  function close()
  {
    $ret = @OCIFreeStatement($this->result);
    parent::close();
  }

}
