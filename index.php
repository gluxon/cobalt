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

define('ADDONS_PATH', 'addons');
define('COMPONENTS_PATH', 'components');
define('INCLUDES_PATH', 'includes');
define('RESOURCES_PATH', 'resources');
define('SETTINGS_PATH', 'settings');
define('THEMES_PATH', 'themes');

include(COMPONENTS_PATH . '/' . 'blocks.inc');
include(COMPONENTS_PATH . '/' . 'comments.inc');
include(COMPONENTS_PATH . '/' . 'database.inc');
include(COMPONENTS_PATH . '/' . 'format.inc');
include(COMPONENTS_PATH . '/' . 'module.inc');
include(COMPONENTS_PATH . '/' . 'node.inc');
include(COMPONENTS_PATH . '/' . 'permissions.inc');
include(COMPONENTS_PATH . '/' . 'request.inc');
include(COMPONENTS_PATH . '/' . 'template.inc');
include(COMPONENTS_PATH . '/' . 'user.inc');

include(INCLUDES_PATH . '/' . 'php-settings.inc'); // php ini setting switchers

include(INCLUDES_PATH . '/' . 'functions.inc'); // custom cobalt functions
include(INCLUDES_PATH . '/' . 'pdo.inc'); // Cobalt's PDO library

include(SETTINGS_PATH . '/' . 'general.php'); // general cobalt settings
include(SETTINGS_PATH . '/' . 'sql.php'); // SQL connection settings

$base = '/';

// Dependency Injection Magic
//
//             o
//                  O       /`-.__
//                         /  \·'^|
//            o           T    l  *
//                       _|-..-|_
//                O    (^ '----' `)
//                      `\-....-/^   Dependicus Injectus
//            O       o  ) "/ " (
//                      _( (-)  )_
//                  O  /\ )    (  /\
//                    /  \(    ) |  \
//                o  o    \)  ( /    \
//                  /     |(  )|      \
//                 /    o \ \( /       \
//           __.--'   O    \_ /   .._   \
//          //|)\      ,   (_)   /(((\^)'\
//             |       | O         )  `  |
//             |      / o___      /      /
//            /  _.-''^^__O_^^''-._     /
//          .'  /  -''^^    ^^''-  \--'^
//        .'   .`.  `'''----'''^  .`. \
//      .'    /   `'--..____..--'^   \ \
//     /  _.-/                        \ \
// .::'_/^   |                        |  `.
//        .-'|                        |    `-.
//  _.--'`   \                        /       `-.
// /          \                      /           `-._
// `'---..__   `.                  .´_.._   __       \
//          ``'''`.              .'      `'^  `''---'^
//                 `-..______..-'

// Redirect to installation if there are no database settings.
if (empty($connection)) {
	header('Location: install.php');
}

// Initialize the database class
$database = new Database();

// Connect to the SQL database
try {
	$database->connect(array(
		'host' => $connection['host'],
		'name' => $connection['name'],
		'user' => $connection['user'],
		'password' => $connection['password']
	));
} catch (PDOException $e) {
	echo 'Error connecting to the database. Make sure sql connection settings are correct.';
	die('<p>' . $e->getMessage() . '</p>');
}

$user = new User($database);
$permissions = new Permissions($database);

$format = new Format(array(
	'database' => $database,
	'content_dir' => INCLUDES_PATH . '/content',
	'rules_dir' => INCLUDES_PATH . '/content/rules'
));

$module = new Module(ADDONS_PATH);

// Create our node module
$node = new Node(array(
	'database' => $database,
	'user' => $user,
	'permissions' => $permissions,
	'format' => $format,
	'base' => $base
));

$module->addModule('node', $node);
$module->registerBlock('node', 'content');

// Handle URL
$request = new Request(array(
	'database' => $database,
	'module' => $module,
	'path' => @$_GET['q']
));

$identifier = $request->parse();

// Templating
$theme = $database->getSetting('theme');

$options = array(
	'database' => $database,
	'theme_dir' => THEMES_PATH,
	'theme' => $theme
);

$template = new Template($options);

// Start HTML document
$template->printStart();

// Print the <HEAD>
$template->printHead(array(
	'title' => $request->title(),
	'description' => $database->getSetting('site_description'),
	'keywords' => $database->getSetting('site_keywords'),
	'author' => $database->getSetting('site_author'),
	'base' => $base
));

// Print <BODY>
$blocks = new Blocks(array(
	'database' => $database,
	'format' => $format,
	'module' => $module,
	'theme_dir' => THEMES_PATH,
	'theme' => $theme,
	'base' => $base
));

$template->printBody(array(
	'blocks' => $blocks,
	'module' => $module,
	'base' => $base
));

// End the HTML document
$template->printEnd();
?>