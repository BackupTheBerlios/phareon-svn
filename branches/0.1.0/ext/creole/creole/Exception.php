<?php
/*
 *  $Id: Exception.php,v 1.4 2004/07/06 15:06:50 micha Exp $
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
 * @class Exception exception.h
 * @brief The Exception class is the base exception class for creole.
 * 
 * @author  Michael Aichler <aichler@mediacluster.de>
 * @author  alex black, enigma@turingstudio.com
 * @author  manuel holtgrewe <purestorm at teforge dot org>
 * @version $Revision: 1.4 $
 * @ingroup creole_exception
 */
class Exception
{
  var $code;
  var $message;
  var $context;

  var $class;
  var $file;
  var $line;
  var $method;

  var $backtrace;

  /**
  * @param int $code The error code.
  * @param string $message The error message.
  * @param array $backtrace 
  */
  function Exception($code, $message, $backtrace = null)
  {
      $this->code = $code;
      $this->message = $message;
      $this->backtrace = debug_backtrace();

      $bc = $this->backtrace[count($this->backtrace)-1];
      $this->file = @$bc['file'];
      $this->line = @$bc['line'];
      $this->class = @$bc['class'];
      $this->method= @$bc['function'];

      trigger_error($this->toString(), E_USER_NOTICE);
  }

  /**
  * Retrieve the error message for this exception.
  *
  * @return string
  */
  function getMessage()
  {
    return $this->message;
  }

  /**
  * Retrieve the error code for this exception.
  *
  * @return int
  */
  function getCode()
  {
    return $this->code;
  }

  /**
  * Returns a string representation fitting for debug output.
  *
  * @return  string  String representation of the exception
  * @author  manuel holtgrewe <purestorm at teforge dot org>
  * @access  public
  */
  function toString()
  {
    $result = "[Exception, message=\"" . $this->message . "\", " .
              "code=\"" . $this->code. "\", " .
              "file=\"" . $this->file . "\", " .
              "line=\"" . $this->line . "\"]";
    return $result;
  }

  /**
  * Returns the file name where the exception occured.
  *
  * @return string
  */
  function getFile()
  {
    return $this->file;
  }

  /**
  * Returns the line number of the file where the exception occured.
  *
  * @return int
  */
  function getLine()
  {
    return $this->line;
  }

  function getContext($html = false)
  {
    $str = ($html ? "<h3>[Context]</h3>\n" : "[Context]\n");

    if (! file_exists($this->file)) {
      $str .= "Context cannot be shown - ($this->file) does not exist\n";
      return $str;
    }
    if ((! is_int($this->line)) || ($this->line <= 0)) {
      $str .= "Context cannot be shown - ($this->line) is an invalid line number";
      return $str;
    }

    $lines = file($this->file);
    //  get the source ## core dump in windows, scrap colour highlighting :-(
    //  $source = highlight_file($this->file, true);
    //  $this->lines = split("<br />", $source);
    //  get line numbers
    $start = $this->line - 6; // -1 including error line
    $finish = $this->line + 5;
    //  get lines
    if ($start < 0) {
        $start = 0;
    }
    if ($start >= count($lines)) {
        $start = count($lines) -1;
    }
    for ($i = $start; $i < $finish; $i++) {
        //  highlight line in question
        if ($i == ($this->line -1)) {
            $context_lines[] = '<font color="red"><b>' . ($i + 1) .
                "\t" . strip_tags($lines[$this->line -1]) . '</b></font>';
        } else {
            $context_lines[] = '<font color="black"><b>' . ($i + 1) .
                "</b></font>\t" . @$lines[$i];
        }
    }

    $str .= trim(join("<br />\n", $context_lines)) . "<br />\n";
    return $str;
  }

  function getBacktrace($html = false)
  {
    $str = ($html ? "<h3>[Backtrace]</h3>\n" : "[Backtrace]\n");

    foreach($this->backtrace as $bc)
    {
      if (isset($bc['class'])) {
        $s = ($html ? "<b>%s</b>" : "%s") . "::";
        $str .= sprintf($s, $bc['class']);
      }
      if (isset($bc['function'])) {
        $s = ($html ? "<b>%s</b>" : "%s");
        $str .= sprintf($s, $bc['function']);
      }

      $str .= ' (';

      if (isset($bc['args']))
      {
        foreach($bc['args'] as $arg)
        {
          $s = ($html ? "<i>%s</i>, " : "%s, ");
          $str .= sprintf($s, gettype($arg));
        }
        $str = substr($str, 0, -2);
      }

      $str .= ')';
      $str .= ': ';
      $str .= '[ ';
      if (isset($bc['file'])) {
        $dir = substr(dirname($bc['file']), strrpos(dirname($bc['file']), '/') + 1);
        $file = basename($bc['file']);
        if ($html) $str .= "<a href=\"file:/" . $bc['file'] . "\">";
        $str .= $dir . '/' . $file;
        if ($html) $str .= "</a>";
      }
      $str .= isset($bc['line']) ? ', ' . $bc['line'] : '';
      $str .= ' ] ';
      $str .= ($html ? "<br />\n" : "\n");
    }

    return $str;
  }

}

