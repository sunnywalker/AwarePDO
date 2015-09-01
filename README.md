# AwarePDO

This package is an extension of [PDO](http://www.php.net/manual/en/book.pdo.php) with a few additional features.

* The statement handler class (AwarePDOStatement) supports `rowCount()` for `SELECT` statements.
* The statement handler class keeps track of bound named parameters and their values for easy debugging. You can use `getQuery()` to get the statement with the bound data in the place of the named parameters.

See the `test.php` page for some simple tests of AwarePDO. Note that the test page assumes you have a MySQL database available at localhost with a database named "test", accessible by a user named "root" with no password. Change this to fit your setup.

## Assumptions

AwarePDO makes the following assumptions. **If any of these do not fit your use case, AwarePDO is not for you.**

* Your PHP has the PDO MySQL driver available and you will be using that driver.
* Your statement binding will only use named parameters (version positional/question mark parameters).
* You will use the [Exception error handling mode](http://www.php.net/manual/en/pdo.error-handling.php) (by default).

## AwarePDO Class

This class adds nothing to the normal [PDO class](http://www.php.net/manual/en/class.pdo.php).

## AwarePDOStatement Class

### $conn

This property holds the AwarePDO connection instance which created it.

### $num_rows

This property holds the number of rows affected by the last query, *including* for `SELECT` statements. This property is also accessed by the `rowCount()` method.

### $query

This property displays the raw SQL statement with named variable placeholders (if any).

**Example value:**

`SELECT * FROM people WHERE last_name LIKE :search`

### getParams()

This method returns an associative array of parameter names and values that have been passed via `bindValue()`, `bindParam()`, or `execute()`.

**Example return value:**

`array ( ':search' => 'W%' )`

### getQuery()

This method displays the SQL statement with values in place of the named parameters.

**Example return value:**

`SELECT * FROM people WHERE last_name LIKE 'W%'`

### rowCount()

This method behaves normally (the same as [PDOStatement::rowCount()](http://php.net/manual/en/pdostatement.rowcount.php)) for `INSERT`, `UPDATE`, and `DELETE` statements. It also returns the number of rows selected from `SELECT` statements. Internally, it references the `$num_rows` property.

## Dependencies

This plugin was written to support PHP 5 >= 5.3 with PDO and the MySQL PDO driver. Anything other than this has been untested.

## Sample Usage

```php
try {
	$conn = new AwarePDO('mysql:host=localhost;dbname=test', 'root', '');
	$query_rsList = 'SELECT * FROM people WHERE last_name LIKE :search OR first_name LIKE :search';
	$rsList = $conn->prepare($query_rsList);
	$rsList->bindValue(':search', 'W%');
	$rsList->execute();
	echo 'Found '.$rsList->rowCount().' records matching the search term. The query used was '.$rsList->getQuery().'.';
} catch (PDOException $e) {
	die(htmlspecialchars($e->getMessage()));
}
```

## License

(The MIT License)

Copyright (c) 2013 Sunny Walker <swalker@hawaii.edu>

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the 'Software'), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
