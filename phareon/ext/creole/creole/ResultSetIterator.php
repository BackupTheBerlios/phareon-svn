<?php
/*
 *  $Id: ResultSetIterator.php,v 1.1 2004/03/25 22:59:39 micha Exp $
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
 * Basic ResultSet Iterator.
 *
 * This can be returned by your class's getIterator() method, but of course
 * you can also implement your own (e.g. to get better performance, by using direct
 * driver calls and avoiding other side-effects inherent in ResultSet scrolling
 * functions -- e.g. beforeFirst() / afterLast(), etc.).
 *
 * Important: ResultSet iteration does rewind the resultset if it is not at the
 * start.  Not all drivers support reverse scrolling, so this may result in an
 * exception in some cases (Oracle).
 *
 * Developer note:
 * The implementation of this class is a little weird because it fetches the
 * array _early_ in order to answer hasMore() w/o needing to know total num
 * of fields.  Remember the way iterators work:
 * <code>
 * $it = $obj->getIterator();
 * for($it->rewind(); $it->hasMore(); $it->next()) {
 *  $key = $it->current();
 *  $val = $it->key();
 *  echo "$key = $val\n";
 * }
 * unset($it);
 * </code>
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @version   $Revision: 1.1 $
 * @package   creole
 */
class ResultSetIterator /* implements Iterator */
{

  var $rs;

  /**
   * Construct the iterator.
   * @param ResultSet $rs
   */
  function ResultSetIterator(/*ResultSet*/ &$rs)
  {
    if (! is_a($rs, 'ResultSet')) {
      trigger_error(
        "ResultSetIterator::ResultSetIterator(): parameter 1 not of type 'ResultSet' !",
        E_USER_ERROR
      );
    }

    $this->rs =& $rs;
  }

  /**
   * If not at start of resultset, this method will call seek(0).
   * @see ResultSet::seek()
   */
  function rewind()
  {
      if (!$this->rs->isBeforeFirst()) {
          $this->rs->seek(0);
      }
  }

  /**
   * This method checks to see whether there are more results
   * by advancing the cursor position.
   * @see ResultSet::next()
   */
  function & hasMore()
  {
      return $this->rs->next();
  }

  /**
   * Returns the cursor position.
   * @return int
   */
  function key()
  {
      return $this->rs->getCursorPos();
  }

  /**
   * Returns the row (assoc array) at current cursor pos.
   * @return array
   */
  function & current()
  {
     return $this->rs->getRow();
  }

  /**
   * This method does not actually do anything since we have already advanced
   * the cursor pos in hasMore().
   * @see hasMore()
   */
  function next()
  {
  }

}
