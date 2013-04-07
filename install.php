<?php
// PHP INI Switchers
include('includes/php-settings.inc');
include('includes/functions.inc');

// Include database component
include('components/database.inc');

// Initialize the session
session_start();

// Include sql settings file
@include('settings/sql.php');

function route() {
	global $connection;

	// Initialize stage as 1
	if (!isset($_SESSION['stage'])) $_SESSION['stage'] = 1;

	// Set the language
	if (!empty($_POST['language'])) {
		$_SESSION['language'] = $_POST['language'];
	}

	// Progress through stages if prerequisites are set.
	if (@$_SESSION['welcome_passed']) $_SESSION['stage'] = 2;
	if (@$_SESSION['settings_passed']) $_SESSION['stage'] = 3;
	if (@$_SESSION['database_passed']) $_SESSION['stage'] = 4;
	if (@$_SESSION['setup_passed']) $_SESSION['stage'] = 5;
	if (@$_SESSION['admin_passed']) $_SESSION['stage'] = 6;

	$connection_failed = false;

	if (!empty($connection)) {
		try {
			// Attempt to connect to database.
			$database = new Database();
			$database->connect(array(
					'host' => $connection['host'],
					'name' => $connection['name'],
					'user' => $connection['user'],
					'password' => $connection['password']
			));
		} catch (PDOException $e) {
			// Failed connection. Database settings have not been defined yet.
			$connection_failed = true;
		}
	} else {
		$connection_failed = true;
	}

	// Connection success. Grab install stage.
	if ( !empty($connection) && !$connection_failed ) {
		echo 'Install Stage: ' . $database->getSetting('install_stage');
		$_SESSION['stage'] = $database->getSetting('install_stage');
	}

	switch($_SESSION['stage']) {
		case 1:
			welcome();
			break;
		case 2:
			settings();
			break;
		case 3:
			database();
			break;
		case 4:
			setup();
			break;
		case 5;
			admin();
			break;
		case 6:
			finish();
			break;
	}

	echo 'Session stage: ' . $_SESSION['stage'];
}

function printSidebar() {
	println('<div class="sidebar-block">');
	println("\t\t\t\t" . '<ol>');
		println("\t\t\t\t\t" . '<li><a href="?page=1">Welcome</a></li>');
		println("\t\t\t\t\t" . '<li><a href="?page=2">Check Settings</a></li>');
		println("\t\t\t\t\t" . '<li><a href="?page=3">Setup SQL Database</a></li>');
		println("\t\t\t\t\t" . '<li><a href="?page=4">Setup Website</a></li>');
		println("\t\t\t\t\t" . '<li><a href="?page=5">Create Admin User</a></li>');
	println("\t\t\t\t" . '</ol>');
	println("\t\t\t" . '</div>');
}

function welcome() {
	println('<p>Welcome to the CMS installer. Please specify what language you
		would like to use for this installation.</p>');

	println('<p>');
		println('<form action="?page=2" method="post">');
		println('Language: <select name="language">');
		println('<option selected="selected" value="english">English</option>');
		println('</select>');
		println('<input type="submit" value="Submit" />');
		println('</form>');
	println('</p>');
}

function settings() {
	// Check permissions for settings.php here
	$writable = @fopen("settings/sql.php", 'w');

	if ($writable)
	{
		$_SESSION['settings_passed'] = true;

		println('<p>Yay! The settings file has successfully been renamed and
				set with the correct permissions.</p>');

		println('<form action="?page=3" method="post">');
		println('<input type="submit" value="Continue" />');
		println('</form>');
		println('</p>');
	}
	else
	{
		println('<p>To continue, you must complete the following tasks.</p>');

		println('<ol>');
		println('<li>Rename default.sql.php to sql.php</li>');
		println('<li>Modify permissions to make sql.php writable (usually 755 rwx-rw-rw)</li>');
		println('</ol>');
	}
}

function database() {
	// Figure out if there are connection errors and print them.
	$error = database_errors();

	$connection_failed = false;

	if (!empty($_POST["database_name"]) && !$error) {
		try {
			// Attempt to connect to database.
			$database = new Database();
			$database->connect(array(
					'host' => $_POST['database_server'],
					'name' => $_POST['database_name'],
					'user' => $_POST['database_user'],
					'password' => $_POST['database_password']
			));
		} catch (PDOException $e) {
			echo '<p>Error Connecting to the database. Make sure the connection
					credentials are correct.</p>';
		}
	} else {
		$connection_failed = true;
	}

	if ($connection_failed) {
		database_form();
	} else {
		database_success($database);
	}
}

function database_success($database) {
	$sql = fopen("settings/sql.php", 'w');
	fwrite($sql,
		'<?php' . "\n" .
		'$connection["host"]="' . $_POST["database_server"] . '";' . "\n" .
		'$connection["user"]="' . $_POST["database_user"] . '";' . "\n" .
		'$connection["password"]="' . $_POST["database_password"] . '";' . "\n" .
		'$connection["name"]="' . $_POST["database_name"] . '";' . "\n" .
		'?>');

	$database->setSetting('install_stage', '4');

	println('<p>SQL connection was a success. Please hit the next button to continue.</p>');

	println('<form action="?page=4" method="post">');
	println('<input type="submit" value="Next" />');
	println('</form>');

	$_SESSION['database_passed'] = true;
}

function database_errors() {
	$error = false;

	if (isset($_POST["database_server"]) && empty($_POST["database_server"])) {
		println('No database server was defined' . '<br />');
		$error = true;
	}
	if (isset($_POST["database_user"]) && empty($_POST["database_user"])) {
		println('No database user was defined' . '<br />');
		$error = true;
	}
	if (isset($_POST["database_password"]) && empty($_POST["database_password"])) {
		println('No database password was defined' . '<br />');
		$error = true;
	}
	if (isset($_POST["database_name"]) && empty($_POST["database_name"])) {
		println('No database name was defined' . '<br />');
		$error = true;
	}

	return $error;
}

function database_form() {
	println('<form action="' . $_SERVER["REQUEST_URI"] . '" method="post">');

	if (isset($_POST["database_server"])) {
		$database_server = $_POST["database_server"];
	} else {
		$database_server = 'localhost';
	}

	if (isset($_POST["database_port"])) {
		$database_port = $_POST["database_port"];
	} else {
		$database_port = 3306;
	}

	if (isset($_POST["database_user"])) {
		$database_user = $_POST["database_user"];
	}

	if (isset($_POST["database_name"])) {
		$database_name = $_POST["database_name"];
	}

	println('<strong>Database Server:</strong><input type="text"
			value="' . @$database_server . '" size="15" name="database_server">');
	println('<strong>Port:</strong><input type="text"
			value="' . @$database_port . '" size="4" name="database_port">');

	println('<br />');

	println('<strong>Database User:</strong><input type="text"
			value="' . @$database_user . '" size="15" name="database_user">');

	println('<br />');

	println('<strong>Database Password:</strong><input type="password" size="15"
			name="database_password">');

	println('<br />');

	println('<strong>Database Name:</strong><input type="text"
			value="' . @$database_name . '" size="15" name="database_name">');

	println('<br />');

	println('<input type="submit" value="Submit" />');
	println('</form>');
}

function setup() {
	echo '<p>Setup the site</p>';
}

function admin() {}
function finish() {}
?>
<!DOCTYPE HTML>
<html>

<head>
	<title>Cobalt Installer</title>
	<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
	<meta name="description" content="CMS Installer" />
	<meta name="author" content="Brandon Cheng" />
	<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
	<link rel="stylesheet" type="text/css" href="themes/zxone/mainstyle.css" />
</head>

<body>
	<div id="wrapper">
	<div id="container">
		<div id="content">
			<article>
				<?php route(); ?>
			</article>
		</div>
		<div id="sidebar">
			<?php printSidebar(); ?>
		</div>
	</div>
	</div>
</body>

</html>