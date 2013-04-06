<?php
include("includes/php-settings.inc"); // php.ini setting switchers
include("settings/sql.php");

session_start();

if (!empty($database["host"]) && !empty($database["user"]) && !empty($database["password"]) && !empty($database["name"])) {
	$mysql=new mysqli($database["host"], $database["user"], $database["password"], $database["name"]);
	if (isset($mysql->connect_error)) {
		die('Error Connecting to MySQL database. Please go back to step 3.');
	}
	else {
		$install_stage=$mysql->query("SELECT value FROM settings WHERE setting='install_stage'");
		if ($install_stage->num_rows!=FALSE) {
			$install_stage=$install_stage->fetch_array(MYSQLI_NUM);
			$install_stage=$install_stage["0"];
		}
		else {
			$_SESSION["stage"]="1";
			$install_stage="1";
		}

		// Stage 4: Website setup data
		if (!empty($_POST["admin_site_name"])) {
			if (!empty($_POST["admin_site_name"]))
			$mysql->query("INSERT INTO settings (setting, value) VALUES ('site_name', '" . $mysql->real_escape_string($_POST["admin_site_name"]) . "')");
			if (!empty($_POST["admin_site_slogan"]))
			$mysql->query("INSERT INTO settings (setting, value) VALUES ('site_slogan', '" . $mysql->real_escape_string($_POST["admin_site_slogan"]) . "')");
			if (!empty($_POST["admin_site_description"]))
			$mysql->query("INSERT INTO settings (setting, value) VALUES ('site_description', '" . $mysql->real_escape_string($_POST["admin_site_description"]) . "')");
			if (!empty($_POST["admin_site_keywords"]))
			$mysql->query("INSERT INTO settings (setting, value) VALUES ('site_keywords', '" . $mysql->real_escape_string($_POST["admin_site_keywords"]) . "')");
			if (!empty($_POST["admin_site_author"]))
			$mysql->query("INSERT INTO settings (setting, value) VALUES ('site_author', '" . $mysql->real_escape_string($_POST["admin_site_author"]) . "')");

			$mysql->query("UPDATE settings SET value='5' WHERE setting='install_stage'");
			header('Location: ?page=5');
		}
	}
}
else {
	// Stage 1: Language
	if (!empty($_POST["language"])) {
		$_SESSION["language"]=$_POST["language"];
		$_SESSION["stage"]="2";
	}
	// Stage 2: File Check
	// Note: This command is erasing the entire sql file, replace with permission check
	else if (!empty($_SESSION["stage"]) && $_SESSION["stage"]=="2") {
		$_SESSION["stage"]="3";
	}
	// Stage 0: Installer just started
	else {
		$_SESSION["stage"]="1";
	}
	$install_stage=$_SESSION["stage"];
}

echo $install_stage;

?>
<!DOCTYPE HTML>
<html>
<head>
<title>CMS Installer</title>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
<meta name="description" content="CMS Installer" />
<meta name="author" content="Brandon Cheng" />
<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
<link rel="stylesheet" type="text/css" href="themes/zxone/mainstyle.css" />
<!--[if lt IE 9]>
<script src="//html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
</head>

<body>
<div id="wrapper">
	<div id="container">
		<div id="content">
			<article>
				<?php 
				if (!isset($_GET["page"]) || (isset($_GET["page"]) && $_GET["page"] == "1") ) {
					echo '<p>Welcome to the CMS installer. Please specify what language you would like to use for this installation.</p>';
					echo '<p>';

					echo '<form action="?page=2" method="post">' . "\n";
					echo 'Language: <select name="language">' . "\n";
					echo '<option selected="selected" value="english">English</option>' . "\n";
					echo '</select>' . "\n";
					echo '<input type="submit" value="Submit" />' . "\n";
					echo '</form>' . "\n";

					echo '</p>';
				} else if ($_GET["page"] == "2") {
					// Check permissions for settings.php here
					if (fopen("settings/sql.php", 'w')) {
						echo '<p>Yay! The settings file has sucessfully been renamed and has been set with the correct permissions.</p>';

						echo '<form action="?page=3" method="post">' . "\n";
						echo '<input type="submit" value="Next" />' . "\n";
						echo '</form>' . "\n";
						echo '</p>' . "\n";
					} else {
						echo '<p>To continue, you must complete the following tasks.</p>';
						echo '<ol>';
						echo '<li>Rename default.sql.php to sql.php</li>';
						echo '<li>Give CMS the correct permissions to write to sql.php (usually 755 rwx-rw-rw)</li>';
						echo '</ol>';
					}
				} else if ($_GET["page"] == "3") {
					// $_POST["database_name"] is here since sql connect can succeed without a database name.
					if (!empty($_POST["database_name"]) && !isset($error)) {
						$mysql=new mysqli($_POST["database_server"], $_POST["database_user"], $_POST["database_password"], $_POST["database_name"]);
						if (isset($mysql->connect_error)) {
							echo '<p>Error Connecting to MySQL database. Make sure the sql details are correct.</p>';
						} else {
							// Database details writing here
							$sql=fopen("settings/sql.php", 'w');
							fwrite($sql,
								'<?php' . "\n" .
								'$connection["host"]="' . $_POST["database_server"] . '";' . "\n" .
								'$connection["user"]="' . $_POST["database_user"] . '";' . "\n" .
								'$connection["password"]="' . $_POST["database_password"] . '";' . "\n" .
								'$connection["name"]="' . $_POST["database_name"] . '";' . "\n" .
								'?>');

							// Stage 3: Database check
							$mysql->query("INSERT INTO settings (setting, value) VALUES ('install_stage', '4')");

							echo '<p>SQL connection was a sucess. Please hit the next button to continue.</p>';
							echo '<p>'. "\n";

							echo '<form action="?page=4" method="post">' . "\n";
							echo '<input type="submit" value="Next" />' . "\n";
							echo '</form>' . "\n";

							echo '</p>' . "\n";

							$database_sucess=1;
						}
					}

					if (!isset($database_sucess)) {
						echo '<p>Unnamed CMS requires an SQL database to store website information and content. Below, please provide database connection details.</p>';

						if (isset($_POST["database_server"]) && empty($_POST["database_server"])) {
							echo 'No database server was defined' . "\n";
							echo '<br />' . "\n";
							$error='1';
						}
						if (isset($_POST["database_user"]) && empty($_POST["database_user"])) {
							echo 'No database user was defined' . "\n";
							echo '<br />' . "\n";
							$error='1';
						}
						if (isset($_POST["database_password"]) && empty($_POST["database_password"])) {
							echo 'No database password was defined' . "\n";
							echo '<br />' . "\n";
							$error='1';
						}
						if (isset($_POST["database_name"]) && empty($_POST["database_name"])) {
							echo 'No database name was defined' . "\n";
							echo '<br />' . "\n";
							$error='1';
						}

						echo '<p>'. "\n";

						echo '<form action="' . $_SERVER["REQUEST_URI"] . '" method="post">' . "\n";
						echo '<strong>Database Server:</strong><input type="text" value="' . "\n";
						if (isset($_POST["database_server"])) {
							echo $_POST["database_server"];
						}
						else {
							echo 'localhost';
						}
						echo '" size="15" name="database_server" style="margin-bottom: 1px;" />' . "\n";

						echo '<strong>Port:</strong><input type="text" value="' . "\n";
						if (isset($_POST["database_port"])) {
							echo $_POST["database_port"];
						}
						else {
							echo '3306';
						}
						echo '" size="4" name="database_port" style="margin-bottom: 1px;" />' . "\n";

						echo '<br />';

						echo '<strong>Database User:</strong><input type="text" value="' . "\n";
						if (isset($_POST["database_user"])) {
							$_POST["database_user"];
						}
						echo '" size="15" name="database_user" style="margin-bottom: 1px;" />' . "\n";

						echo '<br />';

						echo '<strong>Database Password:</strong><input type="password" size="15" name="database_password" style="margin-bottom: 1px;" />' . "\n";

						echo '<br />';

						echo '<strong>Database Name:</strong><input type="text" value="' . "\n";
						if (isset($_POST["database_name"])) {
							$_POST["database_name"];
						}
						echo '" size="15" name="database_name" style="margin-bottom: 1px;" />' . "\n";

						echo '<br />';

						echo '<input type="submit" value="Submit" />' . "\n";
						echo '</form>' . "\n";
						echo '</p>' . "\n";
					}
				} else if ($_GET["page"] == "4") {
					echo '<p>Here, you must specify your website\'s information. This part is mainly just meta data.';

					if (isset($_POST["admin_site_name"]) && empty($_POST["admin_site_name"])) {
						echo 'Website name is empty' . "\n";
						echo '<br />' . "\n";
						$error=1;
					}

					echo '<p>'. "\n";
					echo '<form action="' . $_SERVER["REQUEST_URI"] . '" method="post">' . "\n";

					// Website name
					echo '<strong>Website name:</strong><input type="text" value="' . "\n";
					if (!empty($_POST["admin_site_name"])) echo $_POST["admin_site_name"];
					echo '" size="15" name="admin_site_name" style="margin: 1px;" />' . "\n";

					echo "<br />";

					// Website slogan
					echo '<strong>Website slogan:</strong><input type="text" value="' . "\n";
					if (!empty($_POST["admin_site_slogan"])) echo $_POST["admin_site_slogan"];
					echo '" size="15" name="admin_site_slogan" style="margin: 1px;" />' . "\n";

					echo "<br />";

					// Website description
					echo '<strong>Website description:</strong><input type="text" value="' . "\n";
					if (!empty($_POST["admin_site_description"])) echo $_POST["admin_site_description"];
					echo '" size="15" name="admin_site_description" style="margin: 1px;" />' . "\n";

					echo "<br />";

					// Website keywords
					echo '<strong>Website keywords:</strong><input type="text" value="' . "\n";
					if (!empty($_POST["admin_site_keywords"])) echo $_POST["admin_site_keywords"];
					echo '" size="15" name="admin_site_keywords" style="margin: 1px;" />' . "\n";

					echo "<br />";

					// Website author
					echo '<strong>Website author:</strong><input type="text" value="' . "\n";
					if (!empty($_POST["admin_site_author"])) echo $_POST["admin_site_author"];
					echo '" size="15" name="admin_site_author" style="margin: 1px;" />' . "\n";

					echo "<br />";

					echo '<input type="submit" value="Submit" />' . "\n";
					echo '</form>' . "\n";
					echo '</p>' . "\n";
				} else if ($_GET["page"] == "5") {
					echo '<p>Please create the admin user account.</p>';
				}
				?>
			</article>
		</div>
		<div id="sidebar">
			<div class="sidebar-block">
				<ol>
					<li><a href="?page=1">Welcome</a><?php if ($install_stage > "1") echo ' (Pass)'; ?></li>
					<li><a href="?page=2">Check Settings</a><?php if ($install_stage > "2") echo ' (Pass)'; ?></li>
					<li><a href="?page=3">Setup SQL Database</a><?php if ($install_stage > "3") echo ' (Pass)'; ?></li>
					<li><a href="?page=4">Setup Website</a><?php if ($install_stage > "4") echo ' (Pass)'; ?></li>
					<li><a href="?page=5">Create Admin User</a><?php if ($install_stage > "5") echo ' (Pass)'; ?></li>
				</ol>
			</div>
		</div>
	</div>
</div>
</body>

</html>