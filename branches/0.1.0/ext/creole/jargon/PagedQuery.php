<?php

/*
 *  $Id: PagedQuery.php,v 1.2 2004/06/20 17:10:07 micha Exp $
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

require_once 'jargon/Query.php';

/**
 * Class for representing a SQL query for retrieving paged results from a database.
 * 
 * Note that this class is for retrieving results and not performing updates.
 * 
 * 
 * @author    Hans Lellelid <hans@xmpl.org>
 * @author    Michael Aichler <aichler@mediacluster.de>
 * @version   $Revision: 1.2 $
 * @package   jargon 
 */
class PagedQuery extends Query 
{
  /** @var int Current page number (1-based). */
  var $page;
  
  /**
   * Create a new Query.
   * @param Connection $conn
   * @param string $sql
   */
  function PagedQuery(/*Connection*/ &$conn, $sql = null, $page = 1, $rowsPerPage = 25)
  {
    /* parent checks connection type */
    parent::Query($conn, $sql);
    $this->setRowsPerPage($rowsPerPage);
    $this->setPage($page);
  }
  
  /**
   * Set the current page number (First page is 1).
   * @param int $page
   * @return void
   */
  function setPage($page)
  {
    $this->page = $page;
    // (re-)calculate start rec
    $this->calculateStart();
  }
  
  /**
   * Get current page.
   * @return int
   */
  function getPage()
  {
    return $this->page;
  }
  
  /**
   * Set the number of rows per page.
   * @param int $r
   */
  function setRowsPerPage($r)
  {
    $this->max = $r;
    // (re-)calculate start rec
    $this->calculateStart();
  }
  
  /**
   * Get number of rows per page.
   * @return int
   */
  function getRowsPerPage()
  {
    return $this->max;
  }
  
  /**
   * Calculate startrow / max rows based on current page and rows-per-page.
   * @access private
   * @return void
   */
  function calculateStart()
  {
    $this->start = ( ($this->page - 1) * $this->max );
  }
  
  /**
   * Gets the total number (un-LIMITed) of records.
   * 
   * This method will perform a query that executes un-LIMITed query.  This
   * method is not performance-conscious, so don't call this repeatedly for
   * the same query.
   * 
   * @return mixed int Total number of records - disregarding page, maxrows, etc. on success,
   *               SQLException on failure
   */
  function getTotalRecordCount()
  {
    $stmt =& $this->conn->createStatement();
    $rs =& $stmt->executeQuery($this->sql);

    if (Creole::isError($rs)) {
      return $rs;
    }

    return $rs->getRecordCount();
  }
      
} 

