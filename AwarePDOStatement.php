<?php
namespace AwarePDO;

use PDO, PDOStatement;

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
 * @subpackage AwarePDOStatement
 * @license    MIT
 * @version    2015-09-01
 * @author     Sunny Walker <swalker@hawaii.edu>
 */
class AwarePDOStatement extends PDOStatement
{
    const VERSION = '2015-09-01';

    /**
     * Original SQL query
     * @var string
     */
    public $query = '';

    /**
     * List of bound parameters and their values
     * @var array
     */
    protected $params = array();

    /**
     * The PDO::rowCount() for SELECT statements (which are not supported by MySQL)
     * @var integer
     */
    public $num_rows = null;

    /**
     * Pointer back to the AwarePDO instance which created the statement, for {@link execute()}
     * @var AwarePDO
     */
    public $conn = null;

    /**
     * Override PDO::bindValue() to keep track of the bound value
     *
     * @param  mixed $parameter    Parameter identifier
     * @param  mixed $value        Value to bind to the SQL statement parameter
     * @param  integer $data_type  PDO::PARAM_* constant
     * @return boolean
     */
    public function bindValue($parameter, $value, $data_type = PDO::PARAM_STR)
    {
        $this->params[':'.ltrim($parameter, ':')] = $value; // track the bound value
        return parent::bindValue($parameter, $value, $data_type);
    } // bindValue()

    /**
     * Override PDO::bindParam() to keep track of the bound variable
     *
     * @param  mixed   $parameter    Parameter identifier
     * @param  mixed   $variable     Name of the PHP variable to bind to the SQL statement parameter
     * @param  integer $data_type    PDO::PARAM_* constant
     * @param  integer $max_len      Max length
     * @param  mixed   $driver_data  Driver data
     * @return boolean
     */
    public function bindParam($parameter, &$variable, $data_type = null, $max_len = null, $driver_data = null)
    {
        $this->params[':'.ltrim($parameter, ':')] = &$variable; // track the bound parameter
        return parent::bindParam($parameter, $variable, $data_type, $max_len, $driver_data);
    } // bindParam()

    /**
     * Override PDO::execute() to keep track of any bound parameters
     *
     * @param  array  $input_parameters  Parameter identifiers and values
     * @return boolean
     */
    public function execute($input_parameters = null)
    {
        if (is_array($input_parameters)) {
            foreach ($input_parameters as $key => $value) {
                $this->params[':'.ltrim($key, ':')] = $value;
            }
        }
        $return = parent::execute($input_parameters);
        if ($this->conn && stripos($this->query, 'SELECT') === 0) {
            // it's a SELECT statement, so get the number of rows returned
            $this->num_rows = $this->conn->query('SELECT FOUND_ROWS()')->fetchColumn();
        } else {
            // fallback to normal PDO::rowCount() for non-SELECT statements
            $this->num_rows = parent::rowCount();
        }
        return $return;
    } // execute()

    /**
     * Return the number of rows affected by the query.
     *
     * This uses the built-in num_rows in lieu of the PDO::rowCount() to enable
     * support for rowCount() on SELECT statements.
     *
     * @return integer
     */
    public function rowCount()
    {
        return (int)$this->num_rows;
    } // rowCount()

    /**
     * Get the SQL statement with the bound parameter values in place of the parameter names
     *
     * @return string
     */
    public function getQuery()
    {
        $return = $this->query;
        foreach ($this->params as $key=>$value) {
            $return = preg_replace('/'.$key.'\\b/', $this->conn->quote($value), $return); // quote each value
        }
        return $return;
    } // getQuery()

    /**
     * Get the array of parameters and their values as an associative array.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    } // getParams()

} // \AwarePDO\AwarePDOStatement
