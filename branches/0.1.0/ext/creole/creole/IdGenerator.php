<?php
/*
 *  $Id: IdGenerator.php,v 1.1 2004/03/25 22:59:39 micha Exp $
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
 * Interface for classes that provide functionality to get SEQUENCE or AUTO-INCREMENT ids from the database.
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @version   $Revision: 1.1 $
 * @package   creole
 */
class IdGenerator
{
  /** SEQUENCE id generator type */
  function SEQUENCE()      { return(1); }
  /** AUTO INCREMENT id generator type */
  function AUTOINCREMENT() { return(2); }

  /**
   * Convenience method that returns TRUE if id is generated
   * before an INSERT statement.  This is the same as checking
   * whether the generator type is SEQUENCE.
   * @return boolean TRUE if gen id method is SEQUENCE
   * @see getIdMethod()
   */
  function isBeforeInsert()
  {
    trigger_error (
      "IdGenerator::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * Convenience method that returns TRUE if id is generated
   * after an INSERT statement.  This is the same as checking
   * whether the generator type is AUTOINCREMENT.
   * @return boolean TRUE if gen id method is AUTOINCREMENT
   * @see getIdMethod()
   */
  function isAfterInsert()
  {
    trigger_error (
      "IdGenerator::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * Get the preferred type / style for generating ids for RDBMS.
   * @return int SEQUENCE or AUTOINCREMENT
   */
  function getIdMethod()
  {
    trigger_error (
      "IdGenerator::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

  /**
   * Get the autoincrement or sequence id given the current connection
   * and any additional needed info (e.g. sequence name for sequences).
   * <p>
   * Note: if you take advantage of the fact that $keyInfo may not be specified
   * you should make sure that your code is setup in such a way that it will
   * be portable if you change from an RDBMS that uses AUTOINCREMENT to one that
   * uses SEQUENCE (i.e. in which case you would need to specify sequence name).
   *
   * @param mixed $keyInfo Any additional information (e.g. sequence name) needed to fetch the id.
   * @return int The last id / next id.
   */
  function getId($keyInfo = null)
  {
    trigger_error (
      "IdGenerator::(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }

}

