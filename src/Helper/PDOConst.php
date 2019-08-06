<?php

namespace SwooleKit\CoroDatabase\Helper;

/**
 * PDO常量定义
 * 为解除对PDO类库的依赖全部常量均分离出来定义
 * Class PDOConst
 * @package SwooleKit\CoroDatabase\Helper
 */
class PDOConst
{

    /**
     * Represents a boolean data type.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.param-bool
     */
    const PARAM_BOOL = 5;

    /**
     * Represents the SQL NULL data type.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.param-null
     */
    const PARAM_NULL = 0;

    /**
     * Represents the SQL INTEGER data type.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.param-int
     */
    const PARAM_INT = 1;

    /**
     * Represents the SQL CHAR, VARCHAR, or other string data type.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.param-str
     */
    const PARAM_STR = 2;

    /**
     * Flag to denote a string uses the national character set.
     * @since 7.2
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.param-str-natl
     */
    const PARAM_STR_NATL = 1073741824;

    /**
     * Flag to denote a string uses the regular character set.
     * @since 7.2
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.param-str-char
     */
    const PARAM_STR_CHAR = 536870912;

    /**
     * Sets the default string parameter type, this can be one of PDO::PARAM_STR_NATL and PDO::PARAM_STR_CHAR.
     * @since 7.2
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.attr-default-str-param
     */
    const ATTR_DEFAULT_STR_PARAM = 21;

    /**
     * Specifies that a function created with PDO::sqliteCreateFunction() is deterministic, i.e. it always returns the same result given the same inputs within a single SQL statement.
     * @since 7.1.4
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.sqlite-deterministic
     */
    const SQLITE_DETERMINISTIC = 2048;

    /**
     * @since 7.3
     */
    const SQLITE_OPEN_READONLY = 1;

    /**
     * @since 7.3
     */
    const SQLITE_OPEN_READWRITE = 2;

    /**
     * @since 7.3
     */
    const SQLITE_OPEN_CREATE = 4;

    /**
     * @since 7.3
     */
    const SQLITE_ATTR_OPEN_FLAGS = 1000;

    /**
     * Represents the SQL large object data type.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.param-lob
     */
    const PARAM_LOB = 3;

    /**
     * Represents a recordset type. Not currently supported by any drivers.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.param-stmt
     */
    const PARAM_STMT = 4;

    /**
     * Specifies that the parameter is an INOUT parameter for a stored
     * procedure. You must bitwise-OR this value with an explicit
     * PDO::PARAM_* data type.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.param-input-output
     */
    const PARAM_INPUT_OUTPUT = 2147483648;

    /**
     * Allocation event
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.param-evt-alloc
     */
    const PARAM_EVT_ALLOC = 0;

    /**
     * Deallocation event
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.param-evt-free
     */
    const PARAM_EVT_FREE = 1;

    /**
     * Event triggered prior to execution of a prepared statement.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.param-evt-exec-pre
     */
    const PARAM_EVT_EXEC_PRE = 2;

    /**
     * Event triggered subsequent to execution of a prepared statement.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.param-evt-exec-post
     */
    const PARAM_EVT_EXEC_POST = 3;

    /**
     * Event triggered prior to fetching a result from a resultset.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.param-evt-fetch-pre
     */
    const PARAM_EVT_FETCH_PRE = 4;

    /**
     * Event triggered subsequent to fetching a result from a resultset.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.param-evt-fetch-post
     */
    const PARAM_EVT_FETCH_POST = 5;

    /**
     * Event triggered during bound parameter registration
     * allowing the driver to normalize the parameter name.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.param-evt-normalize
     */
    const PARAM_EVT_NORMALIZE = 6;

    /**
     * Specifies that the fetch method shall return each row as an object with
     * variable names that correspond to the column names returned in the result
     * set. <b>PDO::FETCH_LAZY</b> creates the object variable names as they are accessed.
     * Not valid inside <b>PDOStatement::fetchAll</b>.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.fetch-lazy
     */
    const FETCH_LAZY = 1;

    /**
     * Specifies that the fetch method shall return each row as an array indexed
     * by column name as returned in the corresponding result set. If the result
     * set contains multiple columns with the same name,
     * <b>PDO::FETCH_ASSOC</b> returns
     * only a single value per column name.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.fetch-assoc
     */
    const FETCH_ASSOC = 2;

    /**
     * Specifies that the fetch method shall return each row as an array indexed
     * by column number as returned in the corresponding result set, starting at
     * column 0.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.fetch-num
     */
    const FETCH_NUM = 3;

    /**
     * Specifies that the fetch method shall return each row as an array indexed
     * by both column name and number as returned in the corresponding result set,
     * starting at column 0.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.fetch-both
     */
    const FETCH_BOTH = 4;

    /**
     * Specifies that the fetch method shall return each row as an object with
     * property names that correspond to the column names returned in the result
     * set.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.fetch-obj
     */
    const FETCH_OBJ = 5;

    /**
     * Specifies that the fetch method shall return TRUE and assign the values of
     * the columns in the result set to the PHP variables to which they were
     * bound with the <b>PDOStatement::bindParam</b> or
     * <b>PDOStatement::bindColumn</b> methods.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.fetch-bound
     */
    const FETCH_BOUND = 6;

    /**
     * Specifies that the fetch method shall return only a single requested
     * column from the next row in the result set.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.fetch-column
     */
    const FETCH_COLUMN = 7;

    /**
     * Specifies that the fetch method shall return a new instance of the
     * requested class, mapping the columns to named properties in the class.
     * The magic
     * <b>__set</b>
     * method is called if the property doesn't exist in the requested class
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.fetch-class
     */
    const FETCH_CLASS = 8;

    /**
     * Specifies that the fetch method shall update an existing instance of the
     * requested class, mapping the columns to named properties in the class.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.fetch-into
     */
    const FETCH_INTO = 9;

    /**
     * Allows completely customize the way data is treated on the fly (only
     * valid inside <b>PDOStatement::fetchAll</b>).
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.fetch-func
     */
    const FETCH_FUNC = 10;

    /**
     * Group return by values. Usually combined with
     * <b>PDO::FETCH_COLUMN</b> or
     * <b>PDO::FETCH_KEY_PAIR</b>.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.fetch-group
     */
    const FETCH_GROUP = 65536;

    /**
     * Fetch only the unique values.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.fetch-unique
     */
    const FETCH_UNIQUE = 196608;

    /**
     * Fetch a two-column result into an array where the first column is a key and the second column
     * is the value.
     * @since 5.2.3
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.fetch-key-pair
     */
    const FETCH_KEY_PAIR = 12;

    /**
     * Determine the class name from the value of first column.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.fetch-classtype
     */
    const FETCH_CLASSTYPE = 262144;

    /**
     * As <b>PDO::FETCH_INTO</b> but object is provided as a serialized string.
     * Available since PHP 5.1.0. Since PHP 5.3.0 the class constructor is never called if this
     * flag is set.
     * @since 5.1 Available
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.fetch-serialize
     */
    const FETCH_SERIALIZE = 524288;

    /**
     * Call the constructor before setting properties.
     * @since 5.2.0
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.fetch-props-late
     */
    const FETCH_PROPS_LATE = 1048576;

    /**
     * Specifies that the fetch method shall return each row as an array indexed
     * by column name as returned in the corresponding result set. If the result
     * set contains multiple columns with the same name,
     * <b>PDO::FETCH_NAMED</b> returns
     * an array of values per column name.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.fetch-named
     */
    const FETCH_NAMED = 11;

    /**
     * If this value is <b>FALSE</b>, PDO attempts to disable autocommit so that the
     * connection begins a transaction.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.attr-autocommit
     */
    const ATTR_AUTOCOMMIT = 0;

    /**
     * Setting the prefetch size allows you to balance speed against memory
     * usage for your application. Not all database/driver combinations support
     * setting of the prefetch size. A larger prefetch size results in
     * increased performance at the cost of higher memory usage.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.attr-prefetch
     */
    const ATTR_PREFETCH = 1;

    /**
     * Sets the timeout value in seconds for communications with the database.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.attr-timeout
     */
    const ATTR_TIMEOUT = 2;

    /**
     * @see https://php.net/manual/en/pdo.error-handling.php Errors and error handling
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.attr-errmode
     */
    const ATTR_ERRMODE = 3;

    /**
     * This is a read only attribute; it will return information about the
     * version of the database server to which PDO is connected.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.attr-
     */
    const ATTR_SERVER_VERSION = 4;

    /**
     * This is a read only attribute; it will return information about the
     * version of the client libraries that the PDO driver is using.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.attr-client-version
     */
    const ATTR_CLIENT_VERSION = 5;

    /**
     * This is a read only attribute; it will return some meta information about the
     * database server to which PDO is connected.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.attr-server-info
     */
    const ATTR_SERVER_INFO = 6;
    const ATTR_CONNECTION_STATUS = 7;

    /**
     * Force column names to a specific case specified by the PDO::CASE_*
     * constants.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.attr-case
     */
    const ATTR_CASE = 8;

    /**
     * Get or set the name to use for a cursor. Most useful when using
     * scrollable cursors and positioned updates.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.attr-cursor-name
     */
    const ATTR_CURSOR_NAME = 9;

    /**
     * Selects the cursor type. PDO currently supports either
     * <b>PDO::CURSOR_FWDONLY</b> and
     * <b>PDO::CURSOR_SCROLL</b>. Stick with
     * <b>PDO::CURSOR_FWDONLY</b> unless you know that you need a
     * scrollable cursor.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.attr-cursor
     */
    const ATTR_CURSOR = 10;

    /**
     * Convert empty strings to SQL NULL values on data fetches.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.attr-oracle-nulls
     */
    const ATTR_ORACLE_NULLS = 11;

    /**
     * Request a persistent connection, rather than creating a new connection.
     * @see https://php.net/manual/en/pdo.connections.php Connections and Connection Management
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.attr-persistent
     */
    const ATTR_PERSISTENT = 12;

    /**
     * Sets the class name of which statements are returned as.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.attr-statement-class
     */
    const ATTR_STATEMENT_CLASS = 13;

    /**
     * Prepend the containing table name to each column name returned in the
     * result set. The table name and column name are separated by a decimal (.)
     * character. Support of this attribute is at the driver level; it may not
     * be supported by your driver.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.attr-fetch-table-names
     */
    const ATTR_FETCH_TABLE_NAMES = 14;

    /**
     * Prepend the containing catalog name to each column name returned in the
     * result set. The catalog name and column name are separated by a decimal
     * (.) character. Support of this attribute is at the driver level; it may
     * not be supported by your driver.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.attr-fetch-catalog-names
     */
    const ATTR_FETCH_CATALOG_NAMES = 15;

    /**
     * Returns the name of the driver.
     * <p>
     * using <b>PDO::ATTR_DRIVER_NAME</b>
     * <code>
     * if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql') {
     * echo "Running on mysql; doing something mysql specific here\n";
     * }
     * </code>
     * </p>
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.attr-driver-name
     */
    const ATTR_DRIVER_NAME = 16;

    /**
     * Forces all values fetched to be treated as strings.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.attr-stringify-fetches
     */
    const ATTR_STRINGIFY_FETCHES = 17;

    /**
     * Sets the maximum column name length.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.attr-max-column-len
     */
    const ATTR_MAX_COLUMN_LEN = 18;

    /**
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.attr-emulate-prepares
     * @since 5.1.3
     */
    const ATTR_EMULATE_PREPARES = 20;

    /**
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.attr-default-fetch-mode
     * @since 5.2.0
     */
    const ATTR_DEFAULT_FETCH_MODE = 19;

    /**
     * Do not raise an error or exception if an error occurs. The developer is
     * expected to explicitly check for errors. This is the default mode.
     * @see https://php.net/manual/en/pdo.error-handling.php  Errors and Error Handling
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.errmode-silent
     */
    const ERRMODE_SILENT = 0;

    /**
     * Issue a PHP <b>E_WARNING</b> message if an error occurs.
     * @see https://php.net/manual/en/pdo.error-handling.php  Errors and Error Handling
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.errmode-warning
     */
    const ERRMODE_WARNING = 1;

    /**
     * Throw a <b>PDOException</b> if an error occurs.
     * @see https://php.net/manual/en/pdo.error-handling.php  Errors and Error Handling
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.errmode-exception
     */
    const ERRMODE_EXCEPTION = 2;

    /**
     * Leave column names as returned by the database driver.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.case-natural
     */
    const CASE_NATURAL = 0;

    /**
     * Force column names to lower case.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.case-lower
     */
    const CASE_LOWER = 2;

    /**
     * Force column names to upper case.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.case-upper
     */
    const CASE_UPPER = 1;

    /**
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.null-natural
     */
    const NULL_NATURAL = 0;

    /**
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.null-empty-string
     */
    const NULL_EMPTY_STRING = 1;

    /**
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.null-to-string
     */
    const NULL_TO_STRING = 2;

    /**
     * Corresponds to SQLSTATE '00000', meaning that the SQL statement was
     * successfully issued with no errors or warnings. This constant is for
     * your convenience when checking <b>PDO::errorCode</b> or
     * <b>PDOStatement::errorCode</b> to determine if an error
     * occurred. You will usually know if this is the case by examining the
     * return code from the method that raised the error condition anyway.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.err-none
     */
    const ERR_NONE = 00000;

    /**
     * Fetch the next row in the result set. Valid only for scrollable cursors.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.fetch-ori-next
     */
    const FETCH_ORI_NEXT = 0;

    /**
     * Fetch the previous row in the result set. Valid only for scrollable
     * cursors.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.fetch-ori-prior
     */
    const FETCH_ORI_PRIOR = 1;

    /**
     * Fetch the first row in the result set. Valid only for scrollable cursors.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.fetch-ori-first
     */
    const FETCH_ORI_FIRST = 2;

    /**
     * Fetch the last row in the result set. Valid only for scrollable cursors.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.fetch-ori-last
     */
    const FETCH_ORI_LAST = 3;

    /**
     * Fetch the requested row by row number from the result set. Valid only
     * for scrollable cursors.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.fetch-ori-abs
     */
    const FETCH_ORI_ABS = 4;

    /**
     * Fetch the requested row by relative position from the current position
     * of the cursor in the result set. Valid only for scrollable cursors.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.fetch-ori-rel
     */
    const FETCH_ORI_REL = 5;

    /**
     * Create a <b>PDOStatement</b> object with a forward-only cursor. This is the
     * default cursor choice, as it is the fastest and most common data access
     * pattern in PHP.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.cursor-fwdonly
     */
    const CURSOR_FWDONLY = 0;

    /**
     * Create a <b>PDOStatement</b> object with a scrollable cursor. Pass the
     * PDO::FETCH_ORI_* constants to control the rows fetched from the result set.
     * @link https://php.net/manual/en/pdo.constants.php#pdo.constants.cursor-scroll
     */
    const CURSOR_SCROLL = 1;

    /**
     * If this attribute is set to <b>TRUE</b> on a
     * <b>PDOStatement</b>, the MySQL driver will use the
     * buffered versions of the MySQL API. If you're writing portable code, you
     * should use <b>PDOStatement::fetchAll</b> instead.
     * <p>
     * Forcing queries to be buffered in mysql
     * <code>
     * if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql') {
     * $stmt = $db->prepare('select * from foo',
     * array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true));
     * } else {
     * die("my application only works with mysql; I should use \$stmt->fetchAll() instead");
     * }
     * </code>
     * </p>
     * @link https://php.net/manual/en/ref.pdo-mysql.php#pdo.constants.mysql-attr-use-buffered-query
     */
    const MYSQL_ATTR_USE_BUFFERED_QUERY = 1000;

    /**
     * <p>
     * Enable LOAD LOCAL INFILE.
     * </p>
     * <p>
     * Note, this constant can only be used in the <i>driver_options</i>
     * array when constructing a new database handle.
     * </p>
     * @link https://php.net/manual/en/ref.pdo-mysql.php#pdo.constants.mysql-attr-local-infile
     */
    const MYSQL_ATTR_LOCAL_INFILE = 1001;

    /**
     * <p>
     * Command to execute when connecting to the MySQL server. Will
     * automatically be re-executed when reconnecting.
     * </p>
     * <p>
     * Note, this constant can only be used in the <i>driver_options</i>
     * array when constructing a new database handle.
     * </p>
     * @link https://php.net/manual/en/ref.pdo-mysql.php#pdo.constants.mysql-attr-init-command
     */
    const MYSQL_ATTR_INIT_COMMAND = 1002;

    /**
     * <p>
     * Maximum buffer size. Defaults to 1 MiB. This constant is not supported when
     * compiled against mysqlnd.
     * </p>
     * @link https://php.net/manual/en/ref.pdo-mysql.php#pdo.constants.mysql-attr-max-buffer-size
     */
    const MYSQL_ATTR_MAX_BUFFER_SIZE = 1005;

    /**
     * <p>
     * Read options from the named option file instead of from
     * my.cnf. This option is not available if
     * mysqlnd is used, because mysqlnd does not read the mysql
     * configuration files.
     * </p>
     * @link https://php.net/manual/en/ref.pdo-mysql.php#pdo.constants.mysql-attr-read-default-file
     */
    const MYSQL_ATTR_READ_DEFAULT_FILE = 1003;

    /**
     * <p>
     * Read options from the named group from my.cnf or the
     * file specified with <b>MYSQL_READ_DEFAULT_FILE</b>. This option
     * is not available if mysqlnd is used, because mysqlnd does not read the mysql
     * configuration files.
     * </p>
     * @link https://php.net/manual/en/ref.pdo-mysql.php#pdo.constants.mysql-attr-read-default-group
     */
    const MYSQL_ATTR_READ_DEFAULT_GROUP = 1004;

    /**
     * <p>
     * Enable network communication compression. This is not supported when
     * compiled against mysqlnd.
     * </p>
     * @link https://php.net/manual/en/ref.pdo-mysql.php#pdo.constants.mysql-attr-compress
     */
    const MYSQL_ATTR_COMPRESS = 1006;

    /**
     * <p>
     * Perform direct queries, don't use prepared statements.
     * </p>
     * @link https://php.net/manual/en/ref.pdo-mysql.php#pdo.constants.mysql-attr-direct-query
     */
    const MYSQL_ATTR_DIRECT_QUERY = 1007;

    /**
     * <p>
     * Return the number of found (matched) rows, not the
     * number of changed rows.
     * </p>
     * @link https://php.net/manual/en/ref.pdo-mysql.php#pdo.constants.mysql-attr-found-rows
     */
    const MYSQL_ATTR_FOUND_ROWS = 1008;

    /**
     * <p>
     * Permit spaces after function names. Makes all functions
     * names reserved words.
     * </p>
     * @link https://php.net/manual/en/ref.pdo-mysql.php#pdo.constants.mysql-attr-ignore-space
     */
    const MYSQL_ATTR_IGNORE_SPACE = 1009;

    /**
     * <p>
     * The file path to the SSL key.
     * </p>
     * @since 5.3.7
     * @link https://php.net/manual/en/ref.pdo-mysql.php#pdo.constants.mysql-attr-ssl-key
     */
    const MYSQL_ATTR_SSL_KEY = 1010;

    /**
     * <p>
     * The file path to the SSL certificate.
     * </p>
     * @since 5.3.7
     * @link https://php.net/manual/en/ref.pdo-mysql.php#pdo.constants.mysql-attr-ssl-cert
     */
    const MYSQL_ATTR_SSL_CERT = 1011;

    /**
     * <p>
     * The file path to the SSL certificate authority.
     * </p>
     * @since 5.3.7
     * @link https://php.net/manual/en/ref.pdo-mysql.php#pdo.constants.mysql-attr-ssl-ca
     */
    const MYSQL_ATTR_SSL_CA = 1012;

    /**
     * <p>
     * The file path to the directory that contains the trusted SSL
     * CA certificates, which are stored in PEM format.
     * </p>
     * @since 5.3.7
     * @link https://php.net/manual/en/ref.pdo-mysql.php#pdo.constants.mysql-attr-ssl-capath
     */
    const MYSQL_ATTR_SSL_CAPATH = 1013;

    /**
     * <p>
     * A list of one or more permissible ciphers to use for SSL encryption,
     * in a format understood by OpenSSL.
     * For example: DHE-RSA-AES256-SHA:AES128-SHA
     * </p>
     * @since 5.3.7
     * @link https://php.net/manual/en/ref.pdo-mysql.php#pdo.constants.mysql-attr-cipher
     */
    const MYSQL_ATTR_SSL_CIPHER = 1014;

    /**
     * <p>
     * Disables multi query execution in both {@see PDO::prepare()} and {@see PDO::query()} when set to FALSE.
     * </p>
     * <p>
     * Note, this constant can only be used in the driver_options array when constructing a new database handle.
     * </p>
     * @since 5.5.21 and 5.6.5
     * @link https://php.net/manual/en/ref.pdo-mysql.php#pdo.constants.mysql-attr-multi-statements
     */
    const MYSQL_ATTR_MULTI_STATEMENTS = 1015;

    /**
     * <p>
     * Disables SSL peer verification when set to FALSE.
     * </p>
     * @since 7.0.18
     * @since 7.1.4
     * @link https://bugs.php.net/bug.php?id=71003
     */
    const MYSQL_ATTR_SSL_VERIFY_SERVER_CERT = 1016;

    /**
     * @deprecated 5.6.0 Use PDO::ATTR_EMULATE_PREPARES instead.
     */
    const PGSQL_ASSOC = 1;
    const PGSQL_ATTR_DISABLE_NATIVE_PREPARED_STATEMENT = 1000;
    const PGSQL_BAD_RESPONSE = 5;
    const PGSQL_BOTH = 3;
    const PGSQL_TRANSACTION_IDLE = 0;
    const PGSQL_TRANSACTION_ACTIVE = 1;
    const PGSQL_TRANSACTION_INTRANS = 2;
    const PGSQL_TRANSACTION_INERROR = 3;
    const PGSQL_TRANSACTION_UNKNOWN = 4;

    const PGSQL_CONNECT_ASYNC = 4;
    const PGSQL_CONNECT_FORCE_NEW = 2;
    const PGSQL_CONNECTION_AUTH_OK = 5;
    const PGSQL_CONNECTION_AWAITING_RESPONSE = 4;
    const PGSQL_CONNECTION_BAD = 1;
    const PGSQL_CONNECTION_OK = 0;
    const PGSQL_CONNECTION_MADE = 3;
    const PGSQL_CONNECTION_SETENV = 6;
    const PGSQL_CONNECTION_SSL_STARTUP = 7;
    const PGSQL_CONNECTION_STARTED = 2;
    const PGSQL_COMMAND_OK = 1;
    const PGSQL_CONV_FORCE_NULL = 4;
    const PGSQL_CONV_IGNORE_DEFAULT = 2;
    const PGSQL_CONV_IGNORE_NOT_NULL = 8;
    const PGSQL_COPY_IN = 4;
    const PGSQL_COPY_OUT = 3;
    const PGSQL_DIAG_CONTEXT = 87;
    const PGSQL_DIAG_INTERNAL_POSITION = 112;
    const PGSQL_DIAG_INTERNAL_QUERY = 113;
    const PGSQL_DIAG_MESSAGE_DETAIL = 68;
    const PGSQL_DIAG_MESSAGE_HINT = 72;
    const PGSQL_DIAG_MESSAGE_PRIMARY = 77;
    const PGSQL_DIAG_SEVERITY = 83;
    const PGSQL_DIAG_SOURCE_FILE = 70;
    const PGSQL_DIAG_SOURCE_FUNCTION = 82;
    const PGSQL_DIAG_SOURCE_LINE = 76;
    const PGSQL_DIAG_SQLSTATE = 67;
    const PGSQL_DIAG_STATEMENT_POSITION = 80;
    const PGSQL_DML_ASYNC = 1024;
    const PGSQL_DML_EXEC = 512;
    const PGSQL_DML_NO_CONV = 256;
    const PGSQL_DML_STRING = 2048;
    const PGSQL_DML_ESCAPE = 4096;
    const PGSQL_EMPTY_QUERY = 0;
    const PGSQL_ERRORS_DEFAULT = 1;
    const PGSQL_ERRORS_TERSE = 0;
    const PGSQL_ERRORS_VERBOSE = 2;
    const PGSQL_FATAL_ERROR = 7;
    const PGSQL_NONFATAL_ERROR = 6;
    const PGSQL_NOTICE_ALL = 2;
    const PGSQL_NOTICE_CLEAR = 3;
    const PGSQL_NOTICE_LAST = 1;
    const PGSQL_NUM = 2;
    const PGSQL_POLLING_ACTIVE = 4;
    const PGSQL_POLLING_FAILED = 0;
    const PGSQL_POLLING_OK = 3;
    const PGSQL_POLLING_READING = 1;
    const PGSQL_POLLING_WRITING = 2;
    const PGSQL_SEEK_CUR = 1;
    const PGSQL_SEEK_END = 2;
    const PGSQL_SEEK_SET = 0;
    const PGSQL_STATUS_LONG = 1;
    const PGSQL_STATUS_STRING = 2;
    const PGSQL_TUPLES_OK = 2;
    const SQLSRV_TXN_READ_UNCOMMITTED = "READ_UNCOMMITTED";
    const SQLSRV_TXN_READ_COMMITTED = "READ_COMMITTED";
    const SQLSRV_TXN_REPEATABLE_READ = "REPEATABLE_READ";
    const SQLSRV_TXN_SNAPSHOT = "SNAPSHOT";
    const SQLSRV_TXN_SERIALIZABLE = "SERIALIZABLE";
    const SQLSRV_ENCODING_BINARY = 2;
    const SQLSRV_ENCODING_SYSTEM = 3;
    const SQLSRV_ENCODING_UTF8 = 65001;
    const SQLSRV_ENCODING_DEFAULT = 1;
    const SQLSRV_ATTR_ENCODING = 1000;
    const SQLSRV_ATTR_QUERY_TIMEOUT = 1001;
    const SQLSRV_ATTR_DIRECT_QUERY = 1002;
    const SQLSRV_ATTR_CURSOR_SCROLL_TYPE = 1003;
    const SQLSRV_ATTR_CLIENT_BUFFER_MAX_KB_SIZE = 1004;
    const SQLSRV_ATTR_FETCHES_NUMERIC_TYPE = 1005;
    const SQLSRV_PARAM_OUT_DEFAULT_SIZE = -1;
    const SQLSRV_CURSOR_KEYSET = 1;
    const SQLSRV_CURSOR_DYNAMIC = 2;
    const SQLSRV_CURSOR_STATIC = 3;
    const SQLSRV_CURSOR_BUFFERED = 42;

}