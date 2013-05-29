<?php
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
 * - Your PHP has the PDO MySQL driver.
 * - You will be using a MySQL DSN.
 * - Your statement binding will only use named parameters (versus positional).
 * - You will use the Exception error mode for error handling (by default).
 *
 * @package AwarePDOClasses
 * @license MIT
 * @version 2013-05-23.00
 * @author Sunny Walker <swalker@hawaii.edu>
 */


/**
 * This class extends PDO
 *
 * @package AwarePDO
 */
class AwarePDO extends PDO {
	const VERSION = '2013-05-29.00';

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
	public function __construct($dsn, $user, $password, $driver_options=array()) {
		$driver_options[PDO::ATTR_STATEMENT_CLASS] = array('AwarePDOStatement');
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
	public function query($statement) {
		$argc = func_num_args(); // PDO::query() supports different calling options, so look for them
		if ($argc===3) {
			$sth = parent::query($statement, func_get_arg(1), func_get_arg(2));
		} elseif ($argc===4) {
			$sth = parent::query($statement, func_get_arg(1), func_get_arg(2), func_get_arg(3));
		} else {
			$sth = parent::query($statement);
		}
		if ($sth instanceof AwarePDOStatement && $statement!='SELECT FOUND_ROWS()') {
			// add the meta data to the statement handler, if it's not looking for the number of returned rows in a SELECT
			$sth->query = $statement;
			$sth->conn = $this;
			if (stripos($statement, 'SELECT')===0) {
			// if (stripos($statement, 'SELECT')===0 && stripos($statement, 'SELECT COUNT(')===false) {
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
	public function prepare($statement, $driver_options=array()) {
		$sth = parent::prepare($statement, $driver_options);
		if ($sth instanceof AwarePDOStatement) {
			$sth->query = $statement;
			$sth->conn = $this;
		}
		return $sth;
	} // prepare()
} // AwarePDO


/**
 * This class extends PDOStatement to include parameter binding introspection
 * and to fix the rowCount() problem with MySQL and SELECT.
 *
 * @package AwarePDOStatement
 */
class AwarePDOStatement extends PDOStatement {
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
	public function bindValue($parameter, $value, $data_type=PDO::PARAM_STR) {
		$this->params[':'.ltrim($parameter, ':')] = $value; // track the bound value
		return parent::bindValue($parameter, $value, $data_type);
	} // bindValue()

	/**
	 * Override PDO::bindParam() to keep track of the bound variable
	 *
	 * @param  mixed $parameter    Parameter identifier
	 * @param  mixed $variable     Name of the PHP variable to bind to the SQL statement parameter
	 * @param  integer $data_type  PDO::PARAM_* constant
	 * @return boolean
	 */
	public function bindParam($parameter, &$variable, $data_type=NULL, $max_len=NULL, $driver_data=NULL) {
		$this->params[':'.ltrim($parameter, ':')] = &$variable; // track the bound parameter
		return parent::bindParam($parameter, $variable, $data_type, $max_len, $driver_data);
	} // bindParam()

	/**
	 * Override PDO::execute() to keep track of any bound parameters
	 *
	 * @param  array  $input_parameters  Parameter identifiers and values
	 * @return boolean
	 */
	public function execute($input_parameters=null) {
		if (is_array($input_parameters)) {
			foreach ($input_parameters as $key=>$value) {
				$this->params[':'.ltrim($key, ':')] = $value;
			}
		}
		$return = parent::execute($input_parameters);
		if ($this->conn && stripos($this->query, 'SELECT')===0) {
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
	public function rowCount() {
		return (int)$this->num_rows;
	} // rowCount()

	/**
	 * Get the SQL statement with the bound parameter values in place of the parameter names
	 *
	 * @return string
	 */
	public function getQuery() {
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
	public function getParams() {
		return $this->params;
	} // getParams()

} // AwarePDOStatement