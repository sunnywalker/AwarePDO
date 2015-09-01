<?php
namespace AwarePDO;

use PDO;

/**
 * AwarePDO is an extension of PDO which helps debugging of named parameters
 * and solves the problem of PDO::rowCount() not returning the number of rows
 * selected in a SELECT statement.
 *
 * There are two classes defined:
 * - {@link AwarePDO} extends PDO
 * - {@link AwarePDOStatement} extends PDOStatement
 *
 * This package makes the following assumptions:
 * - Your PHP installation has the PDO MySQL driver.
 * - You will be using a MySQL DSN.
 * - Your statement binding will only use named parameters (versus positional).
 * - You will use the Exception error mode for error handling (by default).
 *
 * @package    AwarePDO
 * @subpackage AwarePDO
 * @license    MIT
 * @version    2015-09-01
 * @author     Sunny Walker <swalker@hawaii.edu>
 */
class AwarePDO extends PDO
{
    const VERSION = '2015-09-01';

    /**
     * Constructor which changes the statement class and error mode.
     *
     * If you want to use PDO::ERRMODE_SILENT or PDO::ERRMODE_WARNING,
     * add the appropriate PDO::ATTR_ERRMODE to the $driver_options array.
     * PDO::ERRMODE_EXCEPTION is set by default.
     *
     * @param string $dsn             Data Source Name for the database connection information
     * @param string $user            User name for the DSN
     * @param string $password        Password for the DSN
     * @param array  $driver_options  Driver-specific options
     */
    public function __construct($dsn, $user, $password, $driver_options = array()) {
        $driver_options[PDO::ATTR_STATEMENT_CLASS] = array('\AwarePDO\AwarePDOStatement');
        if (!isset($driver_options[PDO::ATTR_ERRMODE])) {
            $driver_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        }
        parent::__construct($dsn, $user, $password, $driver_options);
        return $this;
    } // __construct()

    /**
     * Override PDO::query() to send introspective data to the statement handler
     *
     * @param  string $statement  SQL statement to prepare and execute
     * @return AwarePDOStatement|false
     */
    public function query($statement)
    {
        $argc = func_num_args(); // PDO::query() supports different calling options, so look for them
        if ($argc === 3) {
            $sth = parent::query($statement, func_get_arg(1), func_get_arg(2));
        } elseif ($argc === 4) {
            $sth = parent::query($statement, func_get_arg(1), func_get_arg(2), func_get_arg(3));
        } else {
            $sth = parent::query($statement);
        }
        if ($sth instanceof AwarePDOStatement && strtoupper($statement) != 'SELECT FOUND_ROWS()') {
            // add the meta data to the statement handler, if it's not looking for the number of returned rows in a SELECT
            $sth->query = $statement;
            $sth->conn = $this;
            if (stripos($statement, 'SELECT') === 0) {
                //get the row count on a select statement, but not a select count()
                $sth->num_rows = $this->query('SELECT FOUND_ROWS()')->fetchColumn();
            } else {
                $sth->num_rows = $sth->rowCount();
            }
        }
        return $sth;
    } // query()

    /**
     * Override PDO::prepare() to send introspective data to the statement handler
     *
     * @param  string $statement       SQL statement to prepare and execute
     * @param  array  $driver_options  Options for the driver
     * @return AwarePDOStatement|false
     */
    public function prepare($statement, $driver_options = array())
    {
        $sth = parent::prepare($statement, $driver_options);
        if ($sth instanceof AwarePDOStatement) {
            $sth->query = $statement;
            $sth->conn = $this;
        }
        return $sth;
    } // prepare()
} // \AwarePDO\AwarePDO
