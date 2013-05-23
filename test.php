<?php
require_once '_AwarePDO.php';

/**
 * Display a tick for true and cross for false boolean values.
 *
 * Unicode characters are used so some computers (old Windows machines) may
 * not support these characters.
 *
 * @param  mixed $boolean  Value to compare (treated as truthy/falsey)
 * @return string
 */
function yesno($boolean) {
	return $boolean ? '<span class="yes">&#x2713;</span>' : '<span class="no">&#x2717;</span>';
} // yesno()

/**
 * Helper command to display a comment/command and the results of same.
 *
 * @param  string  $comment       Comment to display preceding the result
 * @param  mixed   $result        Result to display after the comment
 * @param  boolean $yesno_result  Filter the result through {@link yesno()}?
 * @return string
 */
function run($comment, $result, $yesno_result=true) {
	if ($yesno_result) {
		echo '<p class="log">'.htmlspecialchars($comment).': '.yesno($result).'</p>';
	} else {
		echo '<p class="log">'.htmlspecialchars($comment).': <code>'.htmlspecialchars($result).'</code></p>';
	}
} // run()
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>AwarePDO Test</title>
	<!-- <link rel="stylesheet" href="/css/simple.css" type="text/css" /> -->
	<style>
	* { -webkit-box-sizing: border-box; -moz-box-sizing: border-box; box-sizing: border-box; }
	#container { max-width: 50em; margin: 0 auto; }
	code { background-color: #eee; border: 1px solid #ccc; padding: 0 3px; border-radius: 3px; }
	.yes { color: green; }
	.no { color: orange; }
	.data-block { background-color: #f0f8ff; border: 1px solid #ccc; padding: 3px; border-radius: 3px; margin-bottom: 2em; }
	.data-block p { padding: 0 0 3px 0; margin: 0; line-height: 1.23;}
	p.log { margin: 0; padding: 0 0 3px 0; font-family: monospace; }
	.notice { border: 2px solid #f60; background-color: #fe9; padding: 10px; border-radius: 3px; box-shadow: 0 2px 4px #ccc; }
	</style>
</head>
<body>
<div id="container">
	<h1>AwarePDO Test</h1>

	<h2>Test Setup</h2>
<?php
try {
	run('$conn = new AwarePDO(\'mysql:host=localhost;dbname=test\', \'root\', \'\')', $conn = new AwarePDO('mysql:host=localhost;dbname=test', 'root', ''));
	run('$conn->query(\'DROP TABLE IF EXISTS pdo_test\')', $conn->query('DROP TABLE IF EXISTS pdo_test'));
	run('$conn->query(\'CREATE TABLE pdo_test (id INT AUTO_INCREMENT NOT NULL PRIMARY KEY, something CHAR(50)) CHARACTER SET utf8 COLLATE utf8_general_ci\')', $conn->query('CREATE TABLE pdo_test (id INT AUTO_INCREMENT NOT NULL PRIMARY KEY, something CHAR(50)) CHARACTER SET utf8 COLLATE utf8_general_ci'));
	run('$conn->query(\'INSERT INTO pdo_test (something) VALUES ("a"), ("xyz"), ("apple"), ("banana"), ("96720")\')', $conn->query('INSERT INTO pdo_test (something) VALUES ("a"), ("xyz"), ("apple"), ("banana"), ("96720")'));
	run('$conn instanceof AwarePDO', $conn instanceof AwarePDO);
} catch (PDOException $e) {
	die('<p class="notice">'.htmlspecialchars($e->getMessage()).'</p>');
}
?>


	<h2>Test query()</h2>
<?php
try {
	run('$query_rsList = "SELECT * FROM pdo_test"', $query_rsList = "SELECT * FROM pdo_test");
	run('$query_rsList', $query_rsList, false);
	run('$rsList = $conn->query($query_rsList)', $rsList = $conn->query($query_rsList));
	run('$rsList instance of AwarePDOStatement', $rsList instanceof AwarePDOStatement);
	run('$rsList->query', $rsList->query, false);
	echo '<p>Found <strong>'.$rsList->num_rows.'</strong> rows.</p>';
	if ($rsList->num_rows > 0) {
		echo '<div class="data-block">';
		while ($row_rsList = $rsList->fetch(PDO::FETCH_ASSOC)) {
			echo '<p>'.json_encode($row_rsList).'</p>';
		}
		echo '</div>';
	}
} catch (PDOException $e) {
	die('<p class="notice">'.htmlspecialchars($e->getMessage()).'</p>');
}
?>


	<h2>Test prepare()&rarr;execute()</h2>
<?php
try {
	run('$query_rsList = "SELECT * FROM pdo_test WHERE something LIKE :search"', $query_rsList = "SELECT * FROM pdo_test WHERE something LIKE :search");
	run('$query_rsList', $query_rsList, false);
	run('$rsList = $conn->prepare($query_rsList)', $rsList = $conn->prepare($query_rsList));
	run('$rsList instanceof AwarePDOStatement', $rsList instanceof AwarePDOStatement);
	run('$rsList->execute(array(\':search\'=>\'a%\'))', $rsList->execute(array(':search'=>'a%')));
	run('$rsList->query', $rsList->query, false);
	run('$rsList->getParams()', json_encode($rsList->getParams()), false);
	run('$rsList->getQuery()', $rsList->getQuery(), false);
	echo '<p>Found <strong>'.$rsList->num_rows.'</strong> rows.</p>';
	if ($rsList->num_rows > 0) {
		echo '<div class="data-block">';
		while ($row_rsList = $rsList->fetch(PDO::FETCH_ASSOC)) {
			echo '<p>'.json_encode($row_rsList).'</p>';
		}
		echo '</div>';
	}
} catch (PDOException $e) {
	die('<p class="notice">'.htmlspecialchars($e->getMessage()).'</p>');
}
?>


	<h2>Test prepare()&rarr;bindValue()&rarr;execute()</h2>
<?php
try {
	run('$query_rsList = "SELECT * FROM pdo_test WHERE something LIKE :search"', $query_rsList = "SELECT * FROM pdo_test WHERE something LIKE :search");
	run('$query_rsList', $query_rsList, false);
	run('$rsList = $conn->prepare($query_rsList)', $rsList = $conn->prepare($query_rsList));
	run('$rsList instanceof AwarePDOStatement', $rsList instanceof AwarePDOStatement);
	run('$rsList->bindValue(\':search\', \'a%\')', $rsList->bindValue(':search', 'a%'));
	run('$rsList->query', $rsList->query, false);
	run('$rsList->getParams()', json_encode($rsList->getParams()), false);
	run('$rsList->getQuery()', $rsList->getQuery(), false);
	$rsList->execute();
	echo '<p>Found <strong>'.$rsList->num_rows.'</strong> rows.</p>';
	if ($rsList->num_rows > 0) {
		echo '<div class="data-block">';
		while ($row_rsList = $rsList->fetch(PDO::FETCH_ASSOC)) {
			echo '<p>'.json_encode($row_rsList).'</p>';
		}
		echo '</div>';
	}
} catch (PDOException $e) {
	die('<p class="notice">'.htmlspecialchars($e->getMessage()).'</p>');
}
?>


	<h2>Test prepare()&rarr;bindParam()&rarr;execute()&times;3</h2>
<?php
try {
	run('$query_rsList = "SELECT * FROM pdo_test WHERE something LIKE :search"', $query_rsList = "SELECT * FROM pdo_test WHERE something LIKE :search");
	run('$query_rsList', $query_rsList, false);
	run('$rsList = $conn->prepare($query_rsList)', $rsList = $conn->prepare($query_rsList));
	run('$rsList instanceof AwarePDOStatement', $rsList instanceof AwarePDOStatement);
	run('$rsList->bindParam(\':search\', $search)', $rsList->bindParam(':search', $search));
	run('$search = \'apple\'', $search = 'apple');
	run('$rsList->query', $rsList->query, false);
	run('$rsList->getParams()', json_encode($rsList->getParams()), false);
	run('$rsList->execute()', $rsList->execute());
	run('$rsList->getQuery()', $rsList->getQuery(), false);
	echo '<p>Found <strong>'.$rsList->num_rows.'</strong> rows.</p>';
	if ($rsList->num_rows > 0) {
		echo '<div class="data-block">';
		while ($row_rsList = $rsList->fetch(PDO::FETCH_ASSOC)) {
			echo '<p>'.json_encode($row_rsList).'</p>';
		}
		echo '</div>';
	}
	run('$search = \'orange\'', $search = 'orange');
	run('$rsList->execute()', $rsList->execute());
	run('$rsList->getQuery()', $rsList->getQuery(), false);
	echo '<p>Found <strong>'.$rsList->num_rows.'</strong> rows.</p>';
	if ($rsList->num_rows > 0) {
		echo '<div class="data-block">';
		while ($row_rsList = $rsList->fetch(PDO::FETCH_ASSOC)) {
			echo '<p>'.json_encode($row_rsList).'</p>';
		}
		echo '</div>';
	}
	run('$search = \'%a%\'', $search = '%a%');
	run('$rsList->execute()', $rsList->execute());
	run('$rsList->getQuery()', $rsList->getQuery(), false);
	echo '<p>Found <strong>'.$rsList->num_rows.'</strong> rows.</p>';
	if ($rsList->num_rows > 0) {
		echo '<div class="data-block">';
		while ($row_rsList = $rsList->fetch(PDO::FETCH_ASSOC)) {
			echo '<p>'.json_encode($row_rsList).'</p>';
		}
		echo '</div>';
	}
} catch (PDOException $e) {
	die('<p class="notice">'.htmlspecialchars($e->getMessage()).'</p>');
}
?>


	<h2>Test Breakdown</h2>
<?php
try {
	run('$conn->query(\'DROP TABLE IF EXISTS pdo_test\')', $conn->query('DROP TABLE IF EXISTS pdo_test'));
} catch (PDOException $e) {
	die('<p class="notice">'.htmlspecialchars($e->getMessage()).'</p>');
}
?>

	<p class="notice">Pau.</p>
</div>
</body>
</html>