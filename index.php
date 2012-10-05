<?php
/*
Cobalt, the open source CMS
Copyright (C) 2012 Brandon Cheng (gluxon)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

define("ADDONS_PATH", "addons");
define("INCLUDES_PATH", "includes");
define("RESOURCES_PATH", "resources");
define("SETTINGS_PATH", "settings");
define("THEMES_PATH", "themes");

// php ini setting switchers
include(INCLUDES_PATH . "/php-settings.inc");

include(INCLUDES_PATH . "/functions.inc"); // custom cobalt functions
include(INCLUDES_PATH . "/pdo.inc"); // Cobalt's PDO library

include(SETTINGS_PATH . "/general.php"); // general cobalt settings
include(SETTINGS_PATH . "/sql.php"); // SQL connection settings

if (empty($database)) {
	header('Location: install.php');
}

// FireStats
if (file_exists('firestats/php/db-hit.php')) {
	include('firestats/php/db-hit.php');
	fs_add_site_hit(1);
}

$RELBASE='/';

$dbho = new NappalDatabase();
$user = new NappalUser();
$nodehandle = new NappalNode();
$commenthandle = new NappalComments();
$forumhandle = new NappalForum();
$bloghandle = new NappalBlog();

// Connect to the SQL database
try {
	$dbho->connect($database["host"], $database["name"], $database["user"], $database["password"]);
} catch (PDOException $e) {
	echo 'Error connecting to the database. Make sure sql details in sql.php are properly stated';
	die('<p>' . $e->getMessage() . '</p>');
}

// Create tables if they don't exist
$dbh->exec("CREATE TABLE if NOT EXISTS block (id int(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY(id), content longtext, title text, format varchar(32), parent varchar(32), sort tinyint)");
$dbh->exec("CREATE TABLE if NOT EXISTS comment (id int(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY(id), content mediumtext, title text, uid int(11), format varchar(32), parentthread varchar(32), parentid int(11), status varchar(32), created bigint(14), modified bigint(14))");
$dbh->exec("CREATE TABLE if NOT EXISTS forum (id int(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY(id), name varchar(32), description tinytext, parent varchar(32), url tinytext, sort tinyint");
$dbh->exec("CREATE TABLE if NOT EXISTS node (id int(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY(id), content longtext, title text, uid int(11), format varchar(32), type varchar(32), comments boolean DEFAULT '0', status varchar(32), created bigint(14), modified bigint(14), timestamp bigint(14), forum int(11))");
$dbh->exec("CREATE TABLE if NOT EXISTS settings (id int(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY(id), setting varchar(32), value text)");
$dbh->exec("CREATE TABLE if NOT EXISTS url_alias (id int(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY(id), url tinytext, alias tinytext)");
$dbh->exec("CREATE TABLE if NOT EXISTS users (uid int(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY(uid), username varchar(32), password varchar(32), email varchar(32), status varchar(32), role text, theme text, token varchar(32))");

// Check if we're online
$online = $dbho->getSetting('online');

// Get web site base
$ABSBASE = $dbho->getSetting('base');
$BASE = $ABSBASE;

// Get site name
$sitename = $dbho->getSetting('site_name');

// Start the session if it hasn't started
session_start();

// Alright, check if the user is logged in somehow
if (!empty($_SESSION["id"])) {
	// We have a logged in user!
	$current_user = $user->findWithUID($_SESSION["id"]);
}
/* NOTE: "Remember My Login" currently disabled due to cookies not instantly being deleted
else if (!empty($_COOKIE["id"])) {
	// The user chose to pernamently log in
	$current_user = $user->findWithUID($_COOKIE["id"]);
	if ( hash("sha256", $current_user["password"]) == $_COOKIE["password"] ) {
		$_SESSION["id"]=$current_user["uid"];
		setcookie("id", $current_user["uid"], time() + 60 * 60 * 24 * 14, "/");
		setcookie("password", $current_user["password"], time() + 60 * 60 * 24 * 14, "/");
	} else {
		setcookie("password", "", time() - 3600, "/");
		setcookie("id", "", time() - 3600, "/");
		session_destroy();
	}
}*/

// Log the user in! We need to have this up here to set cookies
if (!empty($_POST["username"]) && !empty($_POST["password"])) {
	$current_user = $user->findWithUsernameAndPassword($_POST["username"], $_POST["password"]);
	if ($current_user !== false) {
		$_SESSION["id"]=$current_user["uid"];
		setcookie('id', $current_user["uid"], time() + 60*60*24*30, "/");
		setcookie('password', hash("sha256", md5($_POST["password"])), time() + 60*60*24*30, "/");
	}
}

// Mark and get homepage
// NOTE: We need a better way of doing this where we can compare it to "" without notices. 0 equates to a homepage (shouldn't be)
if (empty($_GET["q"])) {
	$_GET["q"]=$dbho->getSetting('homepage');
	$homepage='1';
}

// This is on purpose in contrast the the last if statement (homepage setting)
if (!empty($_GET["q"])) {
	// Check if the URL is an assigned alias
	$alias = $dbho->getURLWithAlias($_GET["q"]);
	if (!empty($alias)) {
		$_GET["q"]=$alias;
	}

	// Explode each line into something we can read
	$q=explode("/", $_GET["q"]);

	// We do not need to check $q["0"] because $_GET["q"] is not empty.
	// This is a node page
	if (!empty($q["1"]) && is_numeric($q["1"]) && $q["0"]=="node") {

		// Load the page
		$node = $nodehandle->findWithNID($q["1"]);

		// Page exists! It wasn't a failure!
		if ($node !== false) {
			$title=$node["title"];

			if (!empty($q["2"])) switch ($q["2"]) {
				// User wants to add a comment to the page
				case 'add':
					// If the user is logged in, comments are enabled for the page, and has proper permissions, let it roll!
					if ( !empty($current_user) && $dbho->hasPermission($current_user, "addcomment", $node) ) {
						$title='Add a Comment';
						$load='addcomment';
					} else {
						$nodehandle->set403();
					}
				break;

				// User wants to edit the page
				case 'edit':
					if ( !empty($current_user) && $dbho->hasPermission($current_user, "editnode", $node) ) {
						$title="Edit Post";
						$load='editnode';
					} else {
						$nodehandle->set403();
					}
				break;

				// User wants to delete the page! No!!! :'(
				case 'delete':
					if ( !empty($current_user) && $dbho->hasPermission($current_user, "deletenode", $node) ) {
						// Delete confirmed! Commence operation!
						if (isset($_GET["confirm"])) {
							$nodehandle->delete($node["id"]);
							header('Location: /');
						// Delete confirmation page
						} else {
							$title='Delete Post?';
							$load='deletenode';
							unset($node);
						}
					// We don't have permission to do this sire!
					} else {
						$nodehandle->set403();
					}
				break;

				default:
					$nodehandle->set404();
			}
		// Page load was a failure, 404
		} else {
			$nodehandle->set404();
		}

	// This is a commment
	} else if (!empty($q["1"]) && $q["0"]=="comment") {

		// Load the comment
		$comment = $commenthandle->findWithCID($q["1"]);

		// Comment exists!
		if ($comment !== false) {
			$title=$comment["title"];

			if (!empty($q["2"])) switch ($q["2"]) {

				// User wants to add a reply
				case 'add':
					$thread = $nodehandle->findWithNID($commenthandle->getParentThread($comment["id"]));
					if ( !empty($current_user) && $dbho->hasPermission($current_user, "addreply", $thread) ) {
						$title='Add Reply';
						$load='addreply';
					} else {
						$nodehandle->set403();
					}
				break;

				// User wants to edit the comment
				case 'edit':
					if ( !empty($current_user) && $dbho->hasPermission( $current_user, "editcomment", $comment["id"]) ) {
						$title='Edit Comment';
						$load='editcomment';
					}
					else {
						$nodehandle->set403();
					}
				break;

				// User wants to delete the page! No!!! :'(
				case 'delete':
					if ( !empty($current_user) && $dbho->hasPermission( $current_user, "deletecomment", $comment) ) {
						// Delete confirmed! Commence operation!
						if (isset($_GET["confirm"])) {
							// Get comment nid before deleting
							$node["nid"]=$commenthandle->getParentThread($comment["id"]);
							$commenthandle->delete($comment["id"]);
							header('Location: ' . $ABSBASE . 'node/' . $node["nid"]);
						}
						else {
							$title='Delete Comment?';
							$load='deletecomment';
							unset($comment);
						}
					// We don't have permission to do this sire!
					} else {
						$nodehandle->set403();
					}
				break;

				default:
					$nodehandle->set404();
			}
		// Comment Load was a failure 404
		} else {
			$nodehandle->set404();
		}
	// Nappal page
	} else {
		switch ($q["0"]) {

			// User pages
			case 'user':
				switch ($q["1"]) {
					case 'login':
						$title="Log In";
						$load="login";
					break;
					case 'register':
						if (!empty($current_user)) {
							$nodehandle->set403();
						}
						else {
							$title="Register";
							$load="register";
						}
					break;
					case 'logout':
						setcookie("id", "", time() - 3600);
						setcookie("password", "", time() - 3600);
						session_destroy();
						header('Location: /');
					break;
					case 'password':
						$title="Password Reset";
						$load="resetpassword";
					break;
					default:
						// Personal User Pages
						$user_profile=$user->findWithUID($q["1"]);
						if ($user_profile !== false) {
							$title=$user_profile["username"];
							$load='account';
						}
						else {
							$nodehandle->set404();
						}
				}
			break;

			// Node pages
			case 'node':
				switch ($q["1"]) {
					case 'add':
						if ( !empty($current_user) && $dbho->hasPermission($current_user, "addnode") ) {
							$title="New Post";
							$load="addnode";
						} else {
							$nodehandle->set403();
						}
					break;
					default:
						$nodehandle->set404();
				}
			break;

			// Forum
			case 'forum':
				if (empty($q["1"])) {
					$title='Forums';
					$load='forum-landing';
				} else if (empty($q["2"])) {
					$forum=$forumhandle->getForumContainer($q["1"]);
					if ($forum !== false) {
						$title=$forum["name"] . " Forum Container";
						$load='forumcontainer';
					} else {
						$nodehandle->set404();
					}
				} else if (empty($q["3"])) {
					$container=$forumhandle->getForumContainer($q["1"]);
					$forum=$forumhandle->getForum($container["id"], $q["2"]);

					if ($forum !== false) {
						$title=$forum["name"];
						$load='forum';
					} else {
						$nodehandle->set404();
					}
				} else {
					$nodehandle->set404();
				}
			break;

			// Blog
			case 'blog':
				$title='Blog';
				$load='blog';
			break;

			case 'admin':
				if (!empty($current_user) && $dbho->hasPermission($current_user, "adminpanel")) {

					if (empty($q["1"])) {
						$title='Admin';
						$load='admin';
					} else if (empty($q["2"])) switch ($q["1"]) {
						case 'theme':
							$title='Themes';
							$load='admin-theme';

							// Having this here allows it to pass the hasPermission
							if (!empty($_POST["admin_theme"])) {
								$dbho->updateSetting("theme", $_POST["admin_theme"]);
							}
						break;

						case 'aliases':
							$title='Aliases';
							$load='admin-aliases';
						break;

						case 'site':
							$title='Site Information';
							$load='admin-site';
						break;

						default:
							$nodehandle->set404();
					}
					else {
						$nodehandle->set404();
					}

				}

			break;

			case 'tracker':
				$title='Tracker';
				$load='tracker';
			break;

			default:
				$nodehandle->set404();
		}
	}
}

// After URL parsing work
if (!empty($current_user["theme"])) {
	$theme=$current_user["theme"];
} else {
	$theme=$dbho->getSetting("theme");
}

// Form for Adding a node (needs to be in here to redirect but still interpret URL)
if ( !empty($current_user) && !empty($_POST["node_title"]) && !empty($_POST["node_content"]) ) {

	// Default format is fhtml
	$node["format"]='fhtml';

	if (!empty($load) && $load=='addnode') {

		// Checkout what content type this is
		if ($_POST["node_type"]=='page') {
			$nodehandle->addPage($_POST["node_content"], $_POST["node_title"], $current_user["uid"], 'html', '0', 'active');
		} else if ($_POST["node_type"]=='forum') {
			$forumhandle->addForumPost($_POST["node_content"], $_POST["node_title"], $current_user["uid"], $node["format"], '1', 'active', date("YmdHis"), date("YmdHis"), $q["3"]);
		} else if ($_POST["node_type"]=='blog') {
			$bloghandle->addBlog($_POST["node_content"], $_POST["node_title"], $current_user["uid"], $node["format"], '1', 'active', date("YmdHis"), date("YmdHis"));
		}

		// Redirect to newly posted content
		header('Location: ' . $ABSBASE . 'node/' . $dbho->lastInsertID());

	} else if (!empty($load) && $load=='editnode') {

		if ($_POST["node_type"]=='page') {
			$nodehandle->editPage($node["id"], $_POST["node_content"], $_POST["node_title"]);
		} else if ($_POST["node_type"]=='forum') {
			$forumhandle->editForumPost($node["id"], $_POST["node_content"], $_POST["node_title"], date("YmdHis"));
		} else if ($_POST["node_type"]=='blog') {
			$bloghandle->editBlog($node["id"], $_POST["node_content"], $_POST["node_title"], date("YmdHis"));
		}
		header('Location: ' . $ABSBASE . 'node/' . $node["id"]);

	} else {
		// Form was hijacked. Strange, because the user has privs anyway..... dumb monkey
		$nodehandle->set403();
	}

}

// Form for adding a comment
if ( !empty($current_user) && !empty($_POST["node_comment_content"]) ) {

	// Comment titles are optional, if none given, take from first 25
	if (empty($_POST["node_comment_title"])) {
		$_POST["node_comment_title"]=substr($_POST["node_comment_content"], 0, 25);
	} else {
		$_POST["node_comment_title"]=substr($_POST["node_comment_title"], 0, 25);
	}

	if (!empty($load) && $load=='addcomment') {

		$format='fhtml'; // Input types not implemented yet, fhtml is default

		$thread=$q["1"];
		$commenthandle->addComment($_POST["node_comment_content"], $_POST["node_comment_title"], $current_user["uid"], $format, $node["type"], $thread, date("YmdHis"));

		// Update timestamp for parent thread
		$nodehandle->updateTimestamp($thread, date("YmdHis"));

		header('Location: ' . $ABSBASE . 'node/' . $q["1"]);

	} else if (!empty($load) && $load=='addreply') {

		$node["type"]='comment';
		$format='fhtml'; // Input types not implemented yet, fhtml is default

		$thread=$commenthandle->GetParentThread($q["1"]);
		$commenthandle->addComment($_POST["node_comment_content"], $_POST["node_comment_title"], $current_user["uid"], $format, $q["1"], $thread, date("YmdHis"));

		// Update timestamp for parent thread
		$nodehandle->updateTimestamp($thread, date("YmdHis"));

		header('Location: ' . $ABSBASE . 'node/' . $thread);

	} else if (!empty($load) && $load=='editcomment') {

		$commenthandle->editComment($comment["id"], $_POST["node_comment_content"], $_POST["node_comment_title"], date("YmdHis"));

		if ($q["0"]=="comment") {
			$thread=$commenthandle->getParentThread($q["1"]);
		} else {
			$thread=$q["1"];
		}

		header('Location: ' . $ABSBASE . 'node/' . $thread);
	}
}

// Retrieve website information
$site["description"] = $dbho->getSetting('site_description');
$site["keywords"] = $dbho->getSetting('site_keywords');
$site["author"] = $dbho->getSetting('site_author');

?>
<!DOCTYPE HTML>
<html>
<head>
<title><?php
if (isset($homepage)) {
	echo $sitename;
} else if (!empty($title)) {
	echo $title, ' - ', $sitename;
} else {
	echo $sitename;
}
?></title>
<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
<meta name="description" content="<?php echo $site["description"] ?>" />
<meta name="keywords" content="<?php echo $site["keywords"] ?>" />
<meta name="author" content="<?php echo $site["author"] ?>" />
<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
<link rel="stylesheet" type="text/css" href="<?php echo $ABSBASE . THEMES_PATH . '/' . $theme; ?>/mainstyle.css" />

<!--[if lt IE 9]>
<script src="//html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->

</head>

<?php
// Template for <body>
$body=file(THEMES_PATH . "/$theme/body.html");

// Looping <body> file for CMS statements
foreach ($body as $current_line) {
	// Search for body opening
	$strpos=strpos($current_line, '<-- body ', 0);

	if ($strpos===FALSE) {
		// No template data, spit out raw
		echo $current_line;
	} else {
		// Yay! Template data to parse

		// Get position of blockname's start and end (to find content in between)
		$blockname_start=$strpos + strlen("<-- body ");
		$blockname_end=strpos($current_line, ' -->', $blockname_start);

		// Subtract blockname start by blockname end to get blockname's string length
		$blockname_length=$blockname_end - $blockname_start;

		// Use blockname start and length to get blockname (in between is then name)
		$blockname=substr($current_line, $blockname_start, $blockname_length);

		// Split the currently line by the statement and output the first/last parts to the web browser
		$current_line_split=explode("<-- body $blockname -->", $current_line);
		if ( !empty($current_line_split["0"]) ) {
			echo $current_line_split["0"];
		}
		if ( !empty($current_line_split["2"]) ) {
			echo $current_line_split["2"];
		}

		switch($blockname) {
			case 'rsidebar': // Same thing for now
			case 'lsidebar':
				foreach($dbho->getBlocks($blockname) as $row) {
					echo '<div class="sidebar-block">' . "\n";
					if (!empty($row["title"])) echo '<h2>' . $row["title"] . '</h2>' . "\n";
					DisplayContent($row["content"], $row["format"]);
					echo '</div>' . "\n";
				}
			break;

			case 'comments':
				if (!empty($node["comments"]) && $node["comments"]=='1') {
					foreach($commenthandle->getComments($node["type"], $node["id"]) as $row) {
						$commenthandle->LoadComment($row["id"], "0");
					}
				}
			break;

			case 'content':
				// Output the title
				if (!empty($title)) echo '<h2>' . $title . '</h2>';

				// Output an error for 404
				if (isset($notfound)) {
					page_error('404');
				}

				// Output an error for 403
				else if (isset($nopermissions)) {
					page_error('403');
				}

				// User account profile
				else if (isset($load) && $load == "account") {
					$user_profile["role"]=str_replace(',', ', ', $user_profile["role"]);
					echo '<p>' . "\n";
					echo 'Username: ' . $user_profile["username"];
					echo '<br />' . "\n";
					echo 'Roles: ' . $user_profile["role"];
					echo '</p>' . "\n";
				}

				// User login page
				else if (isset($load) && $load == "login") {
					if (!empty($current_user)) {
						echo 'You are already logged in!';
					}
					else {
						AccountLogin();
					}
				}

				// User registration
				else if (isset($load) && $load == "register") {
					if (!empty($_POST["register_username"]) && $user->findWithUsername($_POST["register_username"]) !== false) {
						echo "The username you chose already exists in our database." . "\n";
						$error='1';
					}
					if (isset($_POST["register_username"]) && empty($_POST["register_username"])) {
						echo 'You Forgot Your Username' . "\n";
						echo '<br />' . "\n";
						$error='1';
					}
					if (isset($_POST["register_password"]) && empty($_POST["register_password"])) {
						echo 'No Password' . "\n";
						echo '<br />' . "\n";
						$error='1';
					}
					if (isset($_POST["register_password2"]) && empty($_POST["register_password2"])) {
						echo 'Please Verify Password';
						echo '<br />'. "\n";
						$error='1';
					}
					if (!empty($_POST["register_password"]) && !empty($_POST["register_password2"]) && $_POST["register_password"] != $_POST["register_password2"]) {
						echo 'Passwords Are Not Equal' . "\n";
						echo '<br />' . "\n";
						$error='1';
					}
					if (!empty($_POST["recaptcha_challenge_field"]) && !empty($_POST["recaptcha_response_field"])) {
						require_once(ADDONS_PATH . "/recaptcha/recaptchalib.php");
						$resp=recaptcha_check_answer("", $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
						if (!$resp->is_valid) {
							echo "The reCAPTCHA wasn't entered correctly." . "\n";
							$error='1';
						}
					}

					// Use one required form value as check
					if (!isset($error) && !empty($_POST["register_username"])) {
						$user->addUser($_POST["register_username"], md5($_POST["register_password"]), $_POST["register_email"]);
						echo "<p>Thank you for taking the time to register, you may now log in.</p>" . "\n";
					}
					else {
						echo '<p>'. "\n";
						echo '<form action="' . $_SERVER["REQUEST_URI"] . '" method="post">' . "\n";
						echo '<strong>Username:</strong><input type="text" value="' . "\n";
							if (!empty($_POST["register_username"])) {
								echo $_POST["register_username"];
							}
						echo '" size="15" name="register_username" style="margin-bottom: 1px;" /><br />' . "\n";
						echo '<strong>Password:</strong><input type="password" size="25" name="register_password" style="margin-bottom: 1px;" /><br />' . "\n";
						echo '<strong>Password:</strong><input type="password" size="25" name="register_password2" style="margin-bottom: 1px;" /><br />' . "\n";

						echo '<strong>Email:</strong><input type="text" value="'. "\n";
							if (!empty($_POST["register_email"])) {
								echo $_POST["register_email"] . "\n";
							}
						echo '" size="20" name="register_email" style="margin-bottom: 1px; "/><br />' . "\n";

						require_once(ADDONS_PATH . "/recaptcha/recaptchalib.php");
						echo recaptcha_get_html("") . "\n";

						echo '<input type="submit" value="Register" />' . "\n";
						echo '</form>' . "\n";
						echo '</p>' . "\n";
					}
				}

				// Reset the user's password
				else if (isset($load) && $load == "resetpassword") {
					if (!empty($_GET["token"])) {
						$ouser=$user->findWithUID($_GET["user"]);
						echo 'User ' . $ouser["username"] . ' wishes to recover their password with ' . $_GET["token"];
						echo '<p>This part of the form needs user profile editing</p>';
					}
					else {
						if (!empty($_POST["recaptcha_challenge_field"]) && !empty($_POST["recaptcha_response_field"])) {
							require_once(ADDONS_PATH . "/recaptcha/recaptchalib.php");
							$resp=recaptcha_check_answer("", $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
							if (!$resp->is_valid) {
								echo "The reCAPTCHA wasn't entered correctly." . "\n";
								$error='1';
							}
						}
						// Use one required form value as check
						if (!isset($error) && !empty($_POST["reset_email"])) {
							$ouser = $user->findWithEmail($_POST["reset_email"]);

							if ($ouser !== false) {
								// Generate a token and set it in the database (is this unique and secure enough?)
								$token = md5(rand(1, 1000) . uniqid());
								$user->setToken($ouser["uid"], $token);

								// Send email
								mail($_POST["reset_email"], "Password Recovery Form", 'http://' . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"] . '?token=' . $token . '&user=' . $ouser["uid"]);
								echo "<p>Please check your email for a link to the reset form. Thanks!</p>" . "\n";
							} else {
								echo "<p>The email you selected was not found in our database. Sorry.</a>" . "\n";
							}
						}
						else {
							echo '<p>'. "\n";
							echo '<form action="' . $_SERVER["REQUEST_URI"] . '" method="post">' . "\n";

							echo '<strong>Email:</strong><input type="text" value="'. "\n";
							if (!empty($_POST["reset_email"])) {
								echo $_POST["reset_email"] . "\n";
							}
							echo '" size="20" name="reset_email" style="margin-bottom: 1px; "/>' . "\n";

							// CAPTCHA Form
							require_once(ADDONS_PATH . "/recaptcha/recaptchalib.php");
							echo recaptcha_get_html("") . "\n";

							echo '<input type="submit" value="Send" />' . "\n";
							echo '</form>' . "\n";
							echo '</p>' . "\n";
						}
					}
				}

				// Add or edit a node
				else if ( !empty($load) && ($load=='addnode' || $load=='editnode') ) {
					if (isset($_POST["node_title"]) && empty($_POST["node_title"])) {
						echo 'You need a title.<br />' . "\n";
						echo '' . "\n";
						$error='1';
					}
					if (isset($_POST["node_content"]) && empty($_POST["node_content"])) {
						echo 'You didn\'t type anything<br />' . "\n";
					}

					if ($load=="editnode") {
						$node=$nodehandle->findWithNID($q["1"]);
					}

					echo '<form action="' . $_SERVER["REQUEST_URI"] . '" method="post">' . "\n";
					echo 'Title: <input type="text" value="';
						if (!empty($_POST["node_title"])) {
							echo $_POST["node_title"];
						} else if (!empty($node["title"]) && $load=='editnode') {
							echo $node["title"];
						}
					echo '" size="30" name="node_title" /><br />' . "\n";

					echo 'Type: <select name="node_type">' . "\n";
						if ($dbho->hasPermission($current_user, 'addpage')) {
							echo '<option ';
							// If page was selected as type, page was already set as the type, or url has page
							if ((!empty($_POST["node_type"]) && $_POST["node_type"]=='page') ||
								(!empty($node["type"]) && $node["type"]=='page') ||
								(isset($q["2"]) && $q["2"]=="page") ) {
								echo 'selected="selected" ';
							}
							echo 'value="page">Page</option>' . "\n";
						}

						echo '<option ';
						if ((!empty($_POST["node_type"]) && $_POST["node_type"]=='forum') ||
							(!empty($node["type"]) && $node["type"]=='forum') ||
							(isset($q["2"]) && $q["2"]=="forum") ) {
							echo 'selected="selected" ';
						}
						echo 'value="forum">Forum</option> . "\n"';

						if ($dbho->hasPermission($current_user, 'addblog')) {
							echo '<option ';
							if ((!empty($_POST["node_type"]) && $_POST["node_type"]=='blog') ||
								(!empty($node["type"]) && $node["type"]=='blog') ||
								(isset($q["2"]) && $q["2"]=="blog")) {
								echo 'selected="selected" ';
							}
							echo 'value="blog">Blog</option>' . "\n";
						}
					echo '</select><br />' . "\n";

					echo 'Content:<br /><textarea style="width: 100%;" rows="30" name="node_content" />' . "\n";
						if (!empty($_POST["node_content"])) {
							echo $_POST["node_content"];
						} else if (!empty($node["content"])) {
							echo $node["content"];
						}
					echo '</textarea><br />' . "\n";

					echo '<input type="submit" value="Submit" />' . "\n";
					echo '</form>' . "\n";

					unset($node);
				}

				// Add or edit a comment
				else if ( !empty($load) && ($load=='addcomment' || $load=='editcomment' || $load=='addreply') ) {
					if (isset($_POST["node_comment_content"]) && empty($_POST["node_comment_content"])) {
						echo 'You didn\'t type anything<br />' . "\n";
					}

					if ($load == 'editcomment') {
						$comment = $commenthandle->findWithCID($q["1"]);
					}

					echo '<form action="' . $_SERVER["REQUEST_URI"] . '" method="post">' . "\n";
					echo 'Title: <input type="text" value="';
						if (!empty($_POST["node_comment_title"])) {
							echo $_POST["node_comment_title"];
						} else if (!empty($comment["title"]) && $load=='editcomment') {
							echo $comment["title"];
						}
					echo '" size="30" name="node_comment_title" /><br />' . "\n";

					echo 'Content:<br /><textarea style="width: 100%;" rows="15" name="node_comment_content" />';
						if (!empty($_POST["node_comment_content"])) {
							echo $_POST["node_comment_content"] . "\n";
						} else if (!empty($comment["content"]) && $load=='editcomment') {
							echo $comment["content"] . "\n";
						}
					echo '</textarea><br />';

					echo '<input type="submit" value="Submit" />' . "\n";
					echo '</form>' . "\n";

					unset($node);
				}

				// Delete form
				else if (!empty($load) && ($load=='deletenode' || $load=='deletecomment') ) {
					echo '<form action="' . $_SERVER["REQUEST_URI"] . '?confirm" method="post">' . "\n";
					echo '<p>Are you sure you want to delete this?</p></ br>';
					echo '<input type="submit" value="OK!" />' . "\n";
					echo '</form>' . "\n";
				}

				// Forum
				else if (!empty($load) && $load=='forum-landing') {
					echo '<p>This is a public alpha of the forums. Please report any bugs at the contact address at the bottom of this page. Thank you.</p>';

					echo '<table id="forum">' . "\n";
					echo '	<thead>' . "\n";
					echo '		<tr>' . "\n";
					echo '			<th id="forum">' . "\n";
					echo '				Forum' . "\n";
					echo '			</th>' . "\n";
					echo '			<th id="topics">' . "\n";
					echo '				Topics' . "\n";
					echo '			</th>' . "\n";
					echo '			<th id="posts">' . "\n";
					echo '				Posts' . "\n";
					echo '			</th>' . "\n";
					echo '			<th id="last-post">' . "\n";
					echo '				Last Post' . "\n";
					echo '			</th>' . "\n";
					echo '		</tr>' . "\n";
					echo '	</thead>' . "\n";
					echo '	<tbody>' . "\n";

					// loop through forum containers
					foreach ($forumhandle->getAllContainers() as $forum_container) {
						echo '		<tr>';
						echo '			<td class="container" colspan="4">';
						echo $forum_container["name"];
						echo '			</td>';
						echo '		</tr>';

						// loop through forums for each container
						foreach($forumhandle->getForums($forum_container["id"]) as $forum_row) {
							echo '	<tr>' . "\n";
							echo '		<td>' . "\n";
							echo '<a href="' . $ABSBASE . 'forum/' . $forum_container["sort"] . '/' . $forum_row["sort"] . '">';
							echo $forum_row["name"];
							echo '</a>';
							echo '		</td>' . "\n";
							echo '		<td>' . "\n";
							echo $forumhandle->getForumTopicAmount($forum_row["id"]);
							echo '		</td>' . "\n";
							echo '		<td>' . "\n";
							$amount='0';
							foreach($forumhandle->getTopicsID($forum_row["id"]) as $row) {
								$amount=$amount+$nodehandle->GetCommentAmount($row["id"]);
							}
							echo $amount;
							echo '		</td>' . "\n";

							//Looks like we're going to have to enumerate through all the comments of a thread and find the one with the greatest value (this will be slow >_<)
							echo '		<td>' . "\n";
							// THANK YOU _jesse_!!!
							//$max_node=$mysql->query("SELECT uid,created FROM node WHERE forum=$forum_row["id"] AND created = (SELECT MAX(created) FROM node WHERE forum=$forum_row["id"])");
							$max_node = $forumhandle->getNewestNode($forum_row["id"]);
							if ($max_node == false) {
								// There are no posts in this forum
								echo 'N/A';
							} else {
								$max_comment["created"]="0";

								foreach($forumhandle->getTopicsID($forum_row["id"]) as $topic_row) {
									foreach($commenthandle->getNewestComments($topic_row["id"]) as $comment_row) {
										if ($comment_row["created"] > $max_comment["created"]) {
											$max_comment=$comment_row;
										}
									}
									if (empty($max_comment)) {
										$max_comment="0";
									}
								}
								if ($max_node["created"] > $max_comment["created"]) {
									$max=$max_node;
								} else {
									$max=$max_comment;
								}

								// Note to self: Accomodate for Leap years 31/30 day months.
								$post_username = $user->getUsername($max["uid"]);

								$seconds=substr(date("YmdHis"), 12, 2) - substr($max["created"], 12, 2);
								$minutes=substr(date("YmdHis"), 10, 2) - substr($max["created"], 10, 2);
								$hours=substr(date("YmdHis"), 8, 2) - substr($max["created"], 8, 2);
								$days=substr(date("YmdHis"), 6, 2) - substr($max["created"], 6, 2);
								$months=substr(date("YmdHis"), 4, 2) - substr($max["created"], 4, 2);
								$years=substr(date("YmdHis"), 0, 2) - substr($max["created"], 0, 2);

								if (substr($seconds, 0, 1)=='-') {
									$minutes=$minutes-1;
									$seconds=$seconds+60;
								}
								if (substr($minutes, 0, 1)=='-') {
									$hours=$hours-1;
									$minutes=$minutes+60;
								}
								if (substr($hours, 0, 1)=='-') {
									$days=$days-1;
									$hours=$hours+24;
								}
								if (substr($days, 0, 1)=='-') {
									$months=$months-1;
									// Approximately 30
									$days=$days+30;
								}
								if (substr($months, 0, 1)=='-') {
									$years=$years-1;
									$months=$months+12;
								}

								if ($years!='0') {
									if ($months > 6) {
										$years+1;
									}
									if ($years=='1') {
										echo $years . ' year ';
									} else {
										echo $years . ' years ';
									}
									if ($months!='0') {
										if ($months=='1') {
											echo $months . ' month ';
										} else {
											echo $months . ' months ';
										}
									}
								} else if ($months!='0') {
									// Approximately 15 days in a month
									if ($days > 15) {
										$months+1;
									}
									if ($months=='1') {
										echo $months . ' month ';
									} else {
										echo $months . ' months ';
									}
									if ($days!='0') {
										if ($days=='1') {
											echo $days . ' day ';
										} else {
											echo $days . ' days ';
										}
									}
								} else if ($days!='0') {
									if ($hours > 12) {
										$days+1;
									}
									if ($days=='1') {
										echo $days . ' day ';
									} else {
										echo $days . ' days ';
									}
									if ($hours!='0') {
										if ($hours=='1') {
											echo $hours . ' hour ';
										} else {
											echo $hours . ' hours ';
										}
									}
								} else if ($hours!='0') {
									if ($minutes > 30) {
										$hours+1;
									}
									if ($hours=='1') {
										echo $hours . ' hour ';
									} else {
										echo $hours . ' hours ';
									}
									if ($minutes!='0') {
										if ($minutes=='1') {
											echo $minutes . ' minute ';
										} else {
											echo $minutes . ' minutes ';
										}
									}
								} else if ($minutes!='0') {
									if ($seconds > 30) {
										$minutes+1;
									}
									if ($minutes=='1') {
										echo $minutes . ' minute ';
									} else {
										echo $minutes . ' minutes ';
									}
									if ($seconds=='1') {
										echo $seconds . ' second ';
									} else {
										echo $seconds . ' seconds ';
									}
								}
								else if ($seconds=='1') {
									echo $seconds . ' second ';
								} else {
									echo $seconds . ' seconds ';
								}

								echo 'ago by <a href="' . $ABSBASE . 'user/' . $max_comment["uid"] . '">' . $post_username . '</a>';
							}

							echo '</td>' . "\n";
							echo '</tr>' . "\n";
						}
					}
					echo '	</tbody>' . "\n";
					echo '</table>' . "\n";
				}

				// Load forum container - Unsupported for now
				/*else if (!empty($load) && $load=='forumcontainer') {
					$forum=$mysql->query("SELECT * FROM forum WHERE url='" . $mysql->real_escape_string($q["1"]) . "' OR sort='" . $mysql->real_escape_string($q["1"]) . "'");
					if ($forum=$forum->fetch_array(MYSQLI_ASSOC)) {
						if (empty($forum["parent"])) {
							$title=$forum["name"] . " Forum Container";
						}
						else {
							$notfound='1';
							$title='404';
							header("HTTP/1.0 404 Not Found");
						}
					}
					else {
						$notfound='1';
						$title="404";
						header("HTTP/1.0 404 Not Found");
					}
				}*/

				// Load a forum
				else if (!empty($load) && $load=='forum') {
					if (!empty($current_user) && $dbho->hasPermission($current_user, 'addforumtopic')) {
						echo '<p><a href="' . $ABSBASE . 'node/add/forum/' . $forum["id"] . '">Add new topic</a></p>' . "\n";
					}

					// No need to reselect forum, already done when URL was parsed
					$topic = $forumhandle->getTopics($forum["id"]);

					// Topic row odd even counter
					$boolean_counter=true;

					if (empty($topic)) {
						echo '<p>There are no posts to display.</p>' . "\n";
					} else {
						// print out forum html
						echo '<table id="forum">' . "\n";
						echo '	<thead>' . "\n";
						echo '		<tr>' . "\n";
						echo '			<th id="icon">' . "\n";
						echo '			</th>' . "\n";
						echo '			<th id="topic">' . "\n";
						echo '				Topic' . "\n";
						echo '			</th>' . "\n";
						echo '			<th id="creator">' . "\n";
						echo '				Creator' . "\n";
						echo '			</th>' . "\n";
						echo '			<th id="date">' . "\n";
						echo '				Date' . "\n";
						echo '			</th>' . "\n";
						echo '		</tr>' . "\n";
						echo '	</thead>' . "\n";
						echo '	<tbody>' . "\n";

						// Loop through topics in the forum
						foreach($topic as $row) {
							if ($boolean_counter==true) {
								$boolean_counter=false;
								echo '		<tr class="even">' . "\n";
							} else {
								$boolean_counter=true;
								echo '		<tr class="odd">' . "\n";
							}
							echo '			<td>' . "\n";
								switch ($row["status"]) {
									case 'active':
										echo '<img src="' . $ABSBASE . RESOURCES_PATH . '/forum/active.png" />' . "\n";
									break;
									case 'locked':
										echo '<img src="' . $ABSBASE . RESOURCES_PATH . '/forum/locked.png" />' . "\n";
									break;
								}
							echo '			</td>' . "\n";
							echo '			<td>' . "\n";
								echo '<a href="' . $ABSBASE . 'node/' . $row["id"] . '">' . $row["title"] . '</a>' . "\n";
							echo '			</td>' . "\n";
							echo '			<td>' . "\n";
							$username = $user->getUsername($row["uid"]);
								echo '<a href="' . $ABSBASE . 'user/' . $row["uid"] . '">' . $username . '</a>' . "\n";
							echo '			</td>' . "\n";
							echo '			<td>' . "\n";
								echo substr($row["created"], 0, 4) . "-" . substr($row["created"], 4, 2) . "-" . substr($row["created"], 6, 2) . "";
							echo '			</td>' . "\n";
							echo '		</tr>' . "\n";
						}
						echo '	</tbody>' . "\n";
						echo '</table>' . "\n";
					}
				}

				// Blog
				else if (!empty($load) && $load=='blog') {
					$topic = $bloghandle->getBlogPosts();
					if (empty($topic)) {
						echo '<p>There are no blogs to display.</p>' . "\n";
					} else {
						foreach($topic as $row) {
							echo '<div class="blog-post">' . "\n";
							echo '	<h2><a href="' . $ABSBASE . 'node/' . $row["id"] . '">' . $row["title"] . '</a></h2>' . "\n";

							$year=substr($row["created"], 0, 4);
							$month=substr($row["created"], 4, 2);
							$day=substr($row["created"], 6, 2);

							$post_username = $user->getUsername($row["uid"]);
							echo '	<p class="meta">Published by <a href="' . $ABSBASE . 'user/' . $row["uid"] . '">' . $post_username . '</a> on <time datetime="' . $year . '-' . $month . '-' . $day . '">' . $month . '-' . $day . '-' .$year . '</time></p>' . "\n";

							$cut=strpos(substr($row["content"], 300), "\n");
							if ($cut < 200) {
								$pos=$cut + 300;
							} else {
								$cut=strpos(substr($row["content"], 300), '.');
								if ($cut < 100) {
									$pos=$cut + 301;
								} else {
									$cut=strpos(substr($row["content"], 300), ' ');
									if ($cut < 100) {
										$pos=$cut + 300;
									} else {
										$pos=300;
									}
								}
							}

							DisplayContent(substr($row["content"], 0, $pos), $row["format"]);
							echo '	<p class="add-comment"><a href="' . $ABSBASE . 'node/' . $row["id"] . '">';
								$amount = $nodehandle->GetCommentAmount($row["id"]);
								echo $amount . ' Comment';
								if ($amount!='1') {
									echo 's';
								}
							echo '</a></p>' . "\n";

							echo '	<p class="see-more"><a href="' . $ABSBASE . 'node/' . $row["id"] . '">Continue Reading</a></p>' . "\n";
							echo '</div>' . "\n" . "\n";
						}
					}
				}

				// Admin welcome
				else if (!empty($load) && $load=='admin') {
					echo '	<p>Here you can specify manage your site and choose settings. You can also change the theme and enable or disable addons.</p>' . "\n";
					echo '	<div class="admin-block" style="float: left;">' . "\n";
					echo '		<h3>Site Specific</h3>' . "\n";
					echo '		<ul>' . "\n";
					echo '			<li><a href="' . $ABSBASE . 'admin/site">Site Information</a></li>' . "\n";
					echo '		</ul>' . "\n";
					echo '	</div>' . "\n";
					echo '	<div class="admin-block" style="float: right;">' . "\n";
					echo '		<h3>Customization</h3>' . "\n";
					echo '		<ul>' . "\n";
					echo '			<li><a href="' . $ABSBASE . 'admin/theme">Themes</a></li>' . "\n";
					echo '			<li><a href="' . $ABSBASE . 'admin/aliases">URL Aliases</a></li>' . "\n";
					echo '		</ul>' . "\n";
					echo '	</div>' . "\n";
				}

				// Admin site information
				else if (!empty($load) && $load=='admin-site') {
					if (isset($_POST["admin_site_name"]) && empty($_POST["admin_site_name"])) {
						echo 'Website name is empty' . "\n";
						echo '<br />' . "\n";
						$error=1;
					}
					if (isset($_POST["admin_site_homepage"]) && empty($_POST["admin_site_homepage"])) {
						echo 'No homepage set' . "\n";
						echo '<br />' . "\n";
						$error=1;
					}
					// Use a non-NULL value "admin_site_name" as a check
					if (!isset($error) && isset($_POST["admin_site_name"])) {
						if (!empty($_POST["admin_site_name"]))
							$dbho->updateSetting('site_name', $_POST["admin_site_name"]);
						if (!empty($_POST["admin_site_slogan"]))
							$dbho->updateSetting('site_slogan', $_POST["admin_site_slogan"]);
						if (!empty($_POST["admin_site_description"]))							$dbho->updateSetting('site_description', $_POST["admin_site_description"]);
						if (!empty($_POST["admin_site_keywords"]))
							$dbho->updateSetting('site_keywords', $_POST["admin_site_keywords"]);
						if (!empty($_POST["admin_site_author"]))
							$dbho->updateSetting('site_author', $_POST["admin_site_author"]);
						if (!empty($_POST["admin_site_homepage"]))
							$dbho->updateSetting('homepage', $_POST["admin_site_homepage"]);
						if (!empty($_POST["admin_site_online"]))
							$dbho->updateSetting('online', $_POST["admin_site_online"]);
						echo '<p>The settings have been sucessfully applied.</p>';
					}

					$admin_site_name = $dbho->getSetting('site_name');
					$admin_site_slogan = $dbho->getSetting('site_slogan');
					$admin_site_description = $dbho->getSetting('site_description');
					$admin_site_keywords = $dbho->getSetting('site_keywords');
					$admin_site_author = $dbho->getSetting('site_author');
					$admin_site_homepage = $dbho->getSetting('homepage');

					echo '<form action="' . $_SERVER["REQUEST_URI"] . '" method="post">' . "\n";

					echo '<strong>Website name:</strong><input type="text" value="';
						if (!empty($_POST["admin_site_name"])) {
							echo $_POST["admin_site_name"];
						} else {
							echo $admin_site_name;
						}
					echo '" size="15" name="admin_site_name" style="margin: 1px;" /><br />' . "\n";

					echo '<strong>Website slogan:</strong><input type="text" value="';
						if (!empty($_POST["admin_site_slogan"])) {
							echo $_POST["admin_site_slogan"];
						} else {
							echo $admin_site_slogan;
						}
					echo '" size="15" name="admin_site_slogan" style="margin: 1px;" /><br />' . "\n";

					echo '<strong>Website description:</strong><input type="text" value="';
						if (!empty($_POST["admin_site_description"])) {
							echo $_POST["admin_site_description"];
						} else {
							echo $admin_site_description;
						}
					echo '" size="15" name="admin_site_description" style="margin: 1px;" /><br />' . "\n";

					echo '<strong>Website keyword:</strong><input type="text" value="';
						if (!empty($_POST["admin_site_keywords"])) {
							echo $_POST["admin_site_keywords"];
						} else {
							echo $admin_site_keywords;
						}
					echo '" size="15" name="admin_site_keyword" style="margin: 1px;" /><br />' . "\n";

					echo '<strong>Website author:</strong><input type="text" value="';
						if (!empty($_POST["admin_site_author"])) {
							echo $_POST["admin_site_author"];
						} else {
							echo $admin_site_author;
						}
					echo '" size="15" name="admin_site_author" style="margin: 1px;" /><br />' . "\n";

					echo '<strong>Website homepage:</strong><input type="text" value="';
						if (!empty($_POST["admin_site_homepage"])) {
							echo $_POST["admin_site_homepage"];
						} else {
							echo $admin_site_homepage;
						}
					echo '" size="15" name="admin_site_homepage" style="margin: 1px;" /><br />' . "\n";

					echo '<input type="submit" value="Submit" /><br />' . "\n";
					echo '</form>' . "\n";
				}

				// Admin theme
				else if (!empty($load) && $load=='admin-theme') {
					if (!empty($_POST["admin_theme"])) {
						echo '<p>Change successfully submitted.<p/>', "\n";
					}

					echo '<p>Here you can specify what theme you want to use on this site.</p>', "\n";
					echo '<form action="' . $_SERVER["REQUEST_URI"] . '" method="post">', "\n";

					// Scan for folders in /themes
					$theme_dir=scandir(THEMES_PATH);
					foreach($theme_dir as $theme) {
						// Parse if it's a folder and isn't dot, or dot dot
						if (is_dir(THEMES_PATH . "/$theme") && $theme != '.' && $theme != '..') {
							$theme_ini=parse_ini_file(THEMES_PATH . "/$theme/theme.ini",true);
							echo '<div style="overflow: hidden;">', "\n";
							echo '	<h3>' . $theme_ini['theme']['Name'] . '</h3>', "\n";
							echo '	<div style="margin-right: 5px; float: left;">', "\n";
							echo '		<input type="radio" name="admin_theme" style="margin-top: 30px;" value="' . $theme . '"  />', "\n";
							echo '	</div>', "\n";
							echo '	<div style="width: 290px; float: left;">', "\n";
							echo '		<p>' . $theme_ini['theme']['Description'] . '</p>', "\n";
							echo '	</div>', "\n";
							echo '	<div class="float-right">', "\n";
							echo '		<img alt="Screenshot of ' . $theme_ini['theme']['Name'] . '" src="' . $ABSBASE . THEMES_PATH . '/' . $theme . '/screenshot.png" />', "\n";
							echo '	</div>', "\n";
							echo '</div>', "\n";
						}
					}

					echo '<input type="submit" value="Submit" />';
					echo '</form>';
				}

				// Node content
				else if (!empty($node["content"])) {
					if (!empty($current_user)) {
						if ($dbho->hasPermission($current_user, 'editnode', $node)) {
							echo '<div class="controls">' . "\n";

							echo '<div class="button edit">' . "\n";
							echo '<a href="' . $ABSBASE . 'node/' . $q["1"] . '/edit">' . "\n";
							echo '<img src="' . $ABSBASE . RESOURCES_PATH . '/pages/edit.png" alt="Edit" />' . "\n";
							echo '</a>' . "\n";
							echo '</div>' . "\n";

							echo '<div class="button delete">' . "\n";
							echo '<a href="' . $ABSBASE . 'node/' . $q["1"] . '/delete">' . "\n";
							echo '<img src="' . $ABSBASE . RESOURCES_PATH . '/pages/delete.png" alt="Delete" />' . "\n";
							echo '</a>' . "\n";
							echo '</div>' . "\n";

							echo '</div>' . "\n";
						}
					}
					if (!empty($node["created"])) {
						$year=substr($node["created"], 0, 4);
						$month=substr($node["created"], 4, 2);
						$day=substr($node["created"], 6, 2);
						$hour=substr($node["created"], 8, 2);
						$minute=substr($node["created"], 10, 2);
						$second=substr($node["created"], 12, 2);
						$ouser = $user->findWithUID($node["uid"]);
						echo '<p class="meta"><a href="' . $ABSBASE . 'user/' . $node["uid"] . '">' . $ouser["username"] . '</a> - <time datetime="' . $year . '-' . $month . '-' . $day . 'T' . $hour . ':' . $minute . ':' . $second . '">' . $month . '-' . $day . '-' .$year . ' - ' . $hour . ':' . $minute . ':' .$second . '</time></p>' . "\n";
					}
					DisplayContent($node["content"], $node["format"]);
				}

				// Previous news
				if (isset($homepage) && $dbho->getSetting("previousnews")=="1") {
					echo '<p class="see-more"><a href="' . $ABSBASE . 'node/12">See Previous News</a></p>' . "\n";
				}

				// Add comment
				if (!empty($node)) {
					if (!empty($node["comments"]) && $node["comments"]=='1') {
						echo '<p class="add-comment"><a href="' . $ABSBASE . 'node/' . $node["id"] . '/add">Add new comment</a></p><br />' . "\n";
					}
				}
			break;

			default:
				foreach($dbho->getBlocks($blockname) as $row) {
					DisplayContent($row["content"], $row["format"]);
				}
		}

	}
}

?>
</html>