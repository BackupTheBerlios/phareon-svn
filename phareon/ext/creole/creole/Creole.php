<?php
/*
 *  $Id: Creole.php,v 1.7 2004/06/05 19:37:00 micha Exp $
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

include_once 'creole/SQLException.php';
include_once 'creole/Connection.php';

define('CREOLE_ERROR',                     -1);
define('CREOLE_ERROR_SYNTAX',              -2);
define('CREOLE_ERROR_CONSTRAINT',          -3);
define('CREOLE_ERROR_NOT_FOUND',           -4);
define('CREOLE_ERROR_ALREADY_EXISTS',      -5);
define('CREOLE_ERROR_UNSUPPORTED',         -6);
define('CREOLE_ERROR_MISMATCH',            -7);
define('CREOLE_ERROR_INVALID',             -8);
define('CREOLE_ERROR_NOT_CAPABLE',         -9);
define('CREOLE_ERROR_TRUNCATED',          -10);
define('CREOLE_ERROR_INVALID_NUMBER',     -11);
define('CREOLE_ERROR_INVALID_DATE',       -12);
define('CREOLE_ERROR_DIVZERO',            -13);
define('CREOLE_ERROR_NODBSELECTED',       -14);
define('CREOLE_ERROR_CANNOT_CREATE',      -15);
define('CREOLE_ERROR_CANNOT_DELETE',      -16);
define('CREOLE_ERROR_CANNOT_DROP',        -17);
define('CREOLE_ERROR_NOSUCHTABLE',        -18);
define('CREOLE_ERROR_NOSUCHFIELD',        -19);
define('CREOLE_ERROR_NEED_MORE_DATA',     -20);
define('CREOLE_ERROR_NOT_LOCKED',         -21);
define('CREOLE_ERROR_VALUE_COUNT_ON_ROW', -22);
define('CREOLE_ERROR_INVALID_DSN',        -23);
define('CREOLE_ERROR_CONNECT_FAILED',     -24);
define('CREOLE_ERROR_EXTENSION_NOT_FOUND',-25);
define('CREOLE_ERROR_ACCESS_VIOLATION',   -26);
define('CREOLE_ERROR_NOSUCHDB',           -27);
define('CREOLE_ERROR_CONSTRAINT_NOT_NULL',-29);

// static:
// track errors is used by drivers to get better error messages
// make sure it's set.

@ini_set('track_errors', true);

/**
 * @class Creole
 * @brief This is the class that manages the database drivers.
 *
 * There are a number of default drivers (at the time of writing this comment: MySQL, MSSQL, SQLite, PgSQL, Oracle)
 * that are "shipped" with Creole.  You may wish to either add a new driver or swap out one of the existing drivers
 * for your own custom driver.  To do this you simply need to register your driver using the registerDriver() method.
 *
 * Note that you register your Connection class because the Connection class is responsible for calling the other
 * driver classes (e.g. ResultSet, PreparedStatement, etc.).
 *
 * @author    Hans Lellelid <hans@xmpl.org>
 * @author    Michael Aichler <aichler@mediacluster.de>
 * @version   $Revision: 1.7 $
 * @ingroup   creole
 */
class Creole
{
  /** @name Public Constants */
  // @{
  /**
  * Constant that indicates a connection object should be used.
  */
  function PERSISTENT() { return 1; }
  /**
  * Flag to pass to the connection to indicate that no case conversions
  * should be performed by ResultSet on keys of fetched rows.
  */
  function NO_ASSOC_LOWER() { return 16; }
  // @}

  /**
  * Map of built-in drivers.
  * Change or add your own using registerDriver()
  *
  * @var array Hash mapping phptype => driver class (in dot-path notation, e.g. 'mysql' => 'creole.drivers.mysql.MySQLConnection').
  * @see registerDriver ()
  */
  var $driverMap = array
      (
        'mysql' => 'creole.drivers.mysql.MySQLConnection',
        'pgsql' => 'creole.drivers.pgsql.PgSQLConnection',
        'mssql' => 'creole.drivers.mssql.MSSQLConnection',
      );
      /*
        'sqlite' => 'creole.drivers.sqlite.SQLiteConnection',
        'oracle' => 'creole.drivers.oracle.OCI8Connection',
      */

  /**
  * Register your own RDBMS driver class.
  *
  * You can use this to specify your own class that replaces a default driver or
  * adds support for a new driver.  Register your own class by specifying the
  * 'phptype' (e.g. mysql) and a dot-path notation to where your Connection class is
  * relative to any location on the include path.  You can also specify '*' as the phptype
  * if you want to register a driver that will handle any native type (e.g. if creating
  * a set of decorator classes that log SQL before calling native driver methods).  YOU CAN
  * ONLY REGISTER ONE CATCHALL ('*') DRIVER.
  * <p>
  * Note: the class you need to register is your Connection class because this is the
  * class that's responsible for instantiating the other classes that are part of your
  * driver.  It is possible to mix & match drivers -- i.e. to write a custom driver where
  * the Connection object just instantiates stock classes for ResultSet and PreparedStatement.
  * Note that if you wanted to "override" only the ResultSet class you would also have to override
  * the Connection and PreparedStatement classes so that they would return the correct ResultSet
  * class.  In the future we may implement a more "packaged" approach to drivers; for now we
  * want to keep it simple.
  *
  * @param Connection $phptype The phptype (mysql, mssql, etc.). This is first part of DSN URL (e.g. mysql://localhost/...).
  *                            You may also specify '*' to register a driver that will "wrap" the any native drivers.
  * @param string $dotpath A dot-path locating your class.  For example 'creole.drivers.mssql.MSSQLConnection'
  *                        will be included like: include 'creole/drivers/mssql/MSSQLConnection.php' and the
  *                        classname will be assumed to be 'MSSQLConnection'.
  * @return void
  * @see deregisterDriver () 
  */
  function registerDriver($phptype, $dotpath)
  {
    $self =& Creole::getInstance();
    $self->driverMap[$phptype] = $dotpath;
  }

  /**
  * Removes the driver for a PHP type.  Note that this will remove user-registered
  * drivers _and_ the default drivers.
  *
  * @param string $phptype The PHP type for driver to de-register.
  * @return void
  * @see registerDriver ()
  */
  function deregisterDriver($phptype)
  {
    $self =& Creole::getInstance();
    unset($self->driverMap[$phptype]);
  }

  /**
  * Returns the class path to the driver registered for specified type.
  *
  * @param string $phptype The phptype handled by driver (e.g. 'mysql', 'mssql', '*').
  * @return string The driver class in dot-path notation (e.g. creole.drivers.mssql.MSSQLConnection)
  *                  or NULL if no registered driver found.
  */
  function getDriver($phptype)
  {
    $self =& Creole::getInstance();

    if (isset($self->driverMap[$phptype])) {
      return $self->driverMap[$phptype];
    } else {
      return null;
    }
  }

  /**
  * Create a new DB connection object and connect to the specified
  * database
  *
  * @param mixed $dsn "data source name", see the \ref parseDSN method 
  *                   for a description of the dsn format. Can also be
  *                   specified as an array of the format returned by 
  *                   DB::parseDSN ().
  * @param int $flags Connection flags (e.g. \ref PERSISTENT ()).
  *
  * @return Connection Newly created Connection object on success, SQLException object on failure.
  * @see parseDSN ()
  */
  function getConnection($dsn, $flags = 0)
  {
    if (is_array($dsn)) {
      $dsninfo = $dsn;
    } else {
      $dsninfo = Creole::parseDSN($dsn);
    }

    $self =& Creole::getInstance();

    // support "catchall" drivers which will themselves handle the details of connecting
    // using the proper RDBMS driver.
    if (isset($self->driverMap['*'])) {
      $type = '*';
    }
    else
    {
      $type = $dsninfo['phptype'];
      if (! isset($self->driverMap[$type])) {
        return new SQLException(CREOLE_ERROR_NOT_FOUND, "No driver has been registered to handle connection type: $type");
      }
    }

    // may need to make this more complex if we add support
    // for 'dbsyntax'
    $clazz = Creole::import($self->driverMap[$type]);

    if (Creole::isError($clazz)) {
      return $clazz;
    }

    $obj = new $clazz();

    if (! is_a($obj, 'Connection')) {
      return new SQLException(CREOLE_ERROR_NOT_FOUND, "Class does not implement creole.Connection interface: $clazz");
    }

    if (($e = $obj->connect($dsninfo, $flags)) !== true) {
      $e->setUserInfo($dsninfo);
      return $e;
    }

    return $obj;
  }

  /**
  * Parse a data source name.
  *
  * This isn't quite as powerful as DB::parseDSN(); it's also a lot simpler, a lot faster,
  * and many fewer lines of code.
  *
  * An array with the following keys will be returned:
  *
  * @arg @c phptype Database backend used in PHP (mysql, odbc etc.)
  * @arg @c protocol Communication protocol to use (tcp, unix etc.)
  * @arg @c hostspec Host specification (hostname[:port])
  * @arg @c database Database to use on the DBMS server
  * @arg @c username User name for login
  * @arg @c password Password for login
  *
  * The format of the supplied DSN is in its fullest form:
  *
  * @code
  * phptype://username:password@protocol+hostspec/database
  * @endcode
  *
  * Most variations are allowed:
  *
  * @code
  * phptype://username:password@protocol+hostspec:110//usr/db_file.db
  * phptype://username:password@hostspec/database_name
  * phptype://username:password@hostspec
  * phptype://username@hostspec
  * phptype://hostspec/database
  * phptype://hostspec
  * phptype
  * @endcode
  *
  * @param string $dsn Data Source Name to be parsed
  * @return array An associative array
  */
  function parseDSN($dsn)
  {
    if (is_array($dsn)) {
        return $dsn;
    }

    $parsed = array(
        'phptype'  => null,
        'username' => null,
        'password' => null,
        'protocol' => null,
        'hostspec' => null,
        'port'     => null,
        'socket'   => null,
        'database' => null
    );

    $info = parse_url($dsn);

    if (count($info) === 1) { // if there's only one element in result, then it must be the phptype
        $parsed['phptype'] = array_pop($info);
        return $parsed;
    }

    // some values can be copied directly
    $parsed['phptype'] = @$info['scheme'];
    $parsed['username'] = @$info['user'];
    $parsed['password'] = @$info['pass'];
    $parsed['port'] = @$info['port'];

    $host = @$info['host'];
    if (false !== ($pluspos = strpos($host, '+'))) {
        $parsed['protocol'] = substr($host,0,$pluspos);
        if ($parsed['protocol'] === 'unix') {
            $parsed['socket'] = substr($host,$pluspos+1);
        } else {
            $parsed['hostspec'] = substr($host,$pluspos+1);
        }
    } else {
        $parsed['hostspec'] = $host;
    }

    if (isset($info['path'])) {
        $parsed['database'] = substr($info['path'], 1); // remove first char, which is '/'
    }

    if (isset($info['query'])) {
            $opts = explode('&', $info['query']);
            foreach ($opts as $opt) {
                list($key, $value) = explode('=', $opt);
                if (!isset($parsed[$key])) { // don't allow params overwrite
                    $parsed[$key] = urldecode($value);
                }
            }
    }

    return $parsed;
  }

  /**
  * Include once a file specified in DOT notation.
  * Package notation is expected to be relative to a location on the PHP include_path.
  *
  * @param string $class
  * @return mixed unqualified classname or SQLException
  * - if class does not exist and cannot load file
  * - if after loading file class still does not exist
  */
  function import($class)
  {
    if (! class_exists($class))
    {
      $path = strtr($class, '.', DIRECTORY_SEPARATOR) . '.php';
      $ret = @(include_once($path));
      if ($ret === false) {
        return new SQLException(CREOLE_ERROR_NOT_FOUND, "Unable to load driver class: " . $class);
      }

      // get just classname ('path.to.ClassName' -> 'ClassName')
      $pos = strrpos($class, '.');
      if ($pos !== false) {
        $class = substr($class, $pos + 1);
      }

      if (!class_exists($class)) {
        return new SQLException(CREOLE_ERROR_NOT_FOUND, "Unable to find loaded class: $class (Hint: make sure classname matches filename)");
      }
    }
    return $class;
  }

  /**
  * Tell whether a result code is an error
  *
  * @param int $value result code
  * @return bool whether @c $value is an error
  */
  function isError($value)
  {
    return (is_a($value, 'Exception'));
  }

  /**
  * Verifies that @c $value is of type @c $type.
  *
  * @param object $value
  * @param string $type
  * @param string $class
  * @param string $func
  * @param int $param
  * @return void
  */
  function typeHint(&$value, $type, $class, $func, $param = 1)
  {
    if (! is_a($value, "$type")) {
      trigger_error (
        "$class::$func(): parameter '$param' not of type '$type' !",
        E_USER_ERROR
      );
    }
  }

  /**
  * Attempts to return a reference to a Creole instance, only creating
  * a new instance if no Creole instance currently exists.
  *
  * @protected
  * @return Creole A Creole instance.
  */
  function & getInstance()
  {
    static $instance;

    if ($instance === null)
    {
      $instance = new Creole();
    }

    return $instance;
  }

}
