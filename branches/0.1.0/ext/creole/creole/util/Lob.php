<?php
/*
 *  $Id: Lob.php,v 1.2 2004/03/29 18:46:46 micha Exp $
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
 * An abstract class for handling LOB (Locator Object) columns.
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @version   $Revision: 1.2 $
 * @package   creole.util
 */
class Lob {

  /**
   * The contents of the Lob.
     * DO NOT SET DIRECTLY (or you will disrupt the
     * ability of isModified() to give accurate results).
   * @var string
   */
  var /*protected*/  $data;

  /**
   * File that blob should be written out to.
   * @var string
   */
  var /*protected*/  $outFile;

  /**
   * File that blob should be read in from
   * @var string
   */
  var /*protected*/  $inFile;

    /**
     * This is a 3-state value indicating whether column has been
     * modified.
     * Initially it is NULL.  Once first call to setContents() is made
     * it is FALSE, because this will be initial state of Lob.  Once
     * a subsequent call to setContents() is made it is TRUE.
     * @var boolean
     */
    var /*private*/ $modified = null;

    /**
     * Construct a new Lob.
     * @param sttring $data The data contents of the Lob.
     * @see setContents()
     */
//    public function __construct($data = null)
    function Lob($data = null)
    {
        if ($data !== null) {
            $this->setContents($data);
        }
    }

  /**
   * Get the contents of the LOB.
   * @return string The characters in this LOB.
   * @throws Exception
   */
  function getContents()
  {
    if ($this->data === null && $this->isFromFile()) {
        $this->readFromFile();
    }
    return $this->data;
  }

  /**
   * Set the contents of this LOB.
     * Sets the modified flag to FALSE if this is the first call
     * to setContents() for this object.  Sets the bit to TRUE if
     * this any subsequent call to setContents().
   * @param string $bytes
   */
  function setContents($data)
  {
    $this->data = $data;

        if ($this->modified === null) {
             // if modified bit hasn't been set yet,
            // then it should now be set to FALSE, since
            // we just did inital population
            $this->modified = false;
        } elseif ($this->modified === false) {
            // if it was already FALSE, then it should
            // now be set to TRUE, since this is a subsequent
            // modfiication.
            $this->modified = true;
        }
  }

  /**
   * Dump the contents of the file to stdout.
   * Must be implemented by subclasses so that binary status is handled
   * correctly. (i.e. ignored for Clob, handled for Blob)
   * @return void
   * @throws Exception if no file or contents.
   */
  function dump()
  {
    trigger_error("Lob::dump(): abstract function has to be reimplemented !", E_USER_ERROR);
  }

  /**
   * Specify the file that we want this LOB read from.
   * @param string $filePath The location of the file.
   * @return void
   */
  function setInputFile($filePath)
  {
    $this->inFile = $filePath;
  }

  /**
   * Get the file that we want this LOB read from.
   * @return string The location of the file.
   */
  function & getInputFile()
  {
    return $this->inFile;
  }

  /**
   * Specify the file that we want this LOB saved to.
   * @param string $filePath The location of the file.
   * @return void
   */
  function setOutputFile($filePath)
  {
    $this->outFile = $filePath;
  }

  /**
   * Get the file that we want this LOB saved to.
   * @return string $filePath The location of the file.
   */
  function & getOutputFile()
  {
    return $this->outFile;
  }

  /**
   * Returns whether this Lob is loaded from file.
   * This is useful for bypassing need to read in the contents of the Lob.
   * @return boolean Whether this LOB is to be read from a file.
   */
  function isFromFile()
  {
    return ($this->inFile !== null);
  }

  /**
   * Read LOB data from file (binary safe).
   * (Implementation may need to be moved into Clob / Blob subclasses, but
   * since file_get_contents() is binary-safe, it hasn't been necessary so far.)
     * @param string $file Filename may also be specified here (if not specified using setInputFile()).
   * @return void
   * @throws Exception - if no file specified or error on read.
     * @see setInputFile()
   */
  function & readFromFile($file = null)
  {
        if ($file !== null) {
            $this->setInputFile($file);
        }
    if (!$this->inFile) {
      return new Exception(CREOLE_ERROR, 'No file specified for read.');
    }
    $data = @file_get_contents($this->inFile);
    if ($data === false) {
      return new Exception(CREOLE_ERROR, 'Unable to read from file: '.$this->inFile);
    }
    $this->setContents($data);
  }


  /**
   * Write LOB data to file (binary safe).
   * (Impl may need to move into subclasses, but so far not necessary.)
     * @param string $file Filename may also be specified here (if not set using setOutputFile()).
   * @throws Exception - if no file specified, no contents to write, or error on write.
     * @see setOutputFile()
   */
  function writeToFile($file = null)
  {
    if ($file !== null) {
      $this->setOutputFile($file);
    }
    if (!$this->outFile) {
      return new Exception(CREOLE_ERROR, 'No file specified for write');
    }
    if ($this->data === null) {
      return new Exception(CREOLE_ERROR, 'No data to write to file');
    }
    if (false === @file_put_contents($this->outFile, $this->data)) {
      return new Exception(CREOLE_ERROR, 'Unable to write to file: '.$this->outFile);
    }
  }

    /**
     * Convenience method to get contents of LOB as string.
     * @return string
     */
    function & __toString()
    {
        return $this->getContents();
    }

    /**
     * Set whether LOB contents have been modified after initial setting.
     * @param boolean $b
     */
    function setModified($b)
    {
        $this->modified = $b;
    }

    /**
     * Whether LOB contents have been modified after initial setting.
     * @return boolean TRUE if the contents have been modified after initial setting.
     *                  FALSE if contents have not been modified or if no contents have bene set.
     */
    function isModified()
    {
        // cast it so that NULL will also eval to false
        return (boolean) $this->modified;
    }
}
