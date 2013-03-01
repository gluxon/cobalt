<?php
class Node {

	private $database;
	private $dbho;
	private $user;
	private $permissions;
	private $format;

	private $base;

	private $path;

	private $load;
	private $content;
	private $title;

	private $error;

	/**
	 * Constructor for request
	 * 
	 * @param array $dependencies
	 */
	public function __construct($options) {
		$this->database = (empty($options["database"])) ? null : $options["database"];
		$this->user = (empty($options["user"])) ? null : $options["user"];
		$this->permissions = (empty($options["permissions"])) ? null : $options["permissions"];
		$this->format = (empty($options["format"])) ? null : $options["format"];

		$this->base = (empty($options["base"])) ? null : $options["base"];

		$this->dbho = $this->database->getInstance();
	}

	public function getError() {
		return $this->error;
	}

	public function setError($error) {
		$this->error = $error;
	}

	/**
	 * Built-in requred function: Parse module's URLs
	 * 
	 * @param array $path Exploded path from Request
	 */
	public function request($path) {

		// Store path
		$this->path = $path;

		if ($path["0"] == 'add') {
			$this->title = 'Add';
			$this->load = 'add';
			return 'add';
		}

		if (!is_numeric($path["0"])) {
			$this->setError('404');
			return '404';
		}

		$this->content = $this->find(array(
			'id' => $path["0"]
		));

		if ($this->content === false) {
			$this->setError('404');
			return '404';
		}
		else if (!isset($path["1"])) {
			$this->title = $this->content["title"];
			$this->load = 'content';
			return 'page';
		}
		else if (isset($path["1"])) {
			if ($path["1"] == 'edit') {
				$this->title = 'Edit';
				$this->load = 'edit';
				return 'editnode';
			}
		}
	}

	public function title() {
		return $this->title;
	}

	public function block($parent) {
		if ($parent == 'content') {
			$this->route();
		}
	}

	public function route() {
		if (@$this->getError()) {
			$this->printError();
		} else if ($this->load == 'content') {
			$this->printContent();
		} else if ($this->load == 'edit') {
			$this->printModify();
		} else if ($this->load == 'add') {
			$this->printModify();
		}
	}

	public function find($options) {
		if ( empty($options) || !is_array($options) ) {
			throw new Exception('Invalid arguments');
		}

		$counter = 0;
		$statement = 'SELECT * FROM node WHERE ';

		foreach($options as $key => $val) {
			if (!ctype_alpha($key)) {
				// This should check if the key is actually an existing collumn (issue #7)
				throw new Exception('Invalid collumn name');
			} else {
				if ($counter >= 1) {
					$statement .= ' AND ';
				}
				$statement .= $key . '=?';
				$counter++;
			}
		}

		$values = array_values($options);

		$findStmt = $this->dbho->prepare($statement);
		$findStmt->execute($values);
		return $findStmt->fetch(PDO::FETCH_ASSOC);
	}

	public function add($options) {

		if ( empty($options) || !is_array($options) ) {
			throw new Exception('Invalid arguments');
		}

		// INSERT INTO Persons (FirstName, LastName, Age)
		// VALUES ('Peter', 'Griffin', 35)

		$counter = 0;
		$statement = 'INSERT INTO node';
		$names = ' (';
		$values = ' VALUES (';

		foreach($options as $key => $val) {
			if (!ctype_alpha($key)) {
				// This should check if the key is actually an existing collumn (issue #7)
				throw new Exception('Invalid collumn name');
			} else {
				if ($counter >= 1) {
					$names .= ', ';
					$values .= ', ';
				}
				$names .= $key;
				$values .= '?';
				$counter++;
			}
		}

		$names .= ')';
		$values .= ')';

		$statement = $statement . $names . $values;

		$values = array_values($options);

		$findStmt = $this->dbho->prepare($statement);
		$findStmt->execute($values);
		return $findStmt->fetch(PDO::FETCH_ASSOC);
	}

	private function printContent() {
		// Print the title if there is one
		if (!empty($this->content['title'])) {
			println('<h2>' . $this->content['title'] . '</h2>') ;
		}

		// Output an error
		$error = $this->getError();
		if (!empty($error)) {
			$this->printError();
		}

		else {
			// Output the page controls if user has permissions
			//if ( $this->permissions->hasPermission($this->user->uid(), 'editnode') ) {
			if (true) {
				$this->printControls();
			}
			$this->format->display($this->content["content"], $this->content["format"]);
		}

	}

	public function printControls() {
		$base = $this->base;

		println('<div class="controls">');

		println('<div class="button edit">');
		println('<a href="' . $base . 'node/' . $this->content["id"] . '/edit">');
		println('<img src="' . $base . RESOURCES_PATH . '/pages/edit.png" alt="Edit" />');
		println('</a>');
		println('</div>');

		println('<div class="button delete">');
		println('<a href="' . $base . 'node/' . $this->content["id"] . '/delete">');
		println('<img src="' . $base . RESOURCES_PATH . '/pages/delete.png" alt="Delete" />');
		println('</a>');
		println('</div>');

		println('</div>');
	}

	public function printModify() {
		// Check if form is satisfied
		if (isset($_POST["node_title"]) && empty($_POST["node_title"])) {
			println('You need a title.<br />');
			$error='1';
		}

		if (isset($_POST["node_content"]) && empty($_POST["node_content"])) {
			println('You didn\'t type anything<br />');
		}

		// Title
		println('<form action="' . $_SERVER["REQUEST_URI"] . '" method="post">');
		echo 'Title: <input type="text" value="';
			if (!empty($_POST["node_title"])) {
				echo $_POST["node_title"];
			} else if ($this->load == 'edit') {
				echo $this->content["title"];
			}
		echo '" size="30" name="node_title" /><br />' . "\n";

		// Type
		echo 'Type: <select name="node_type">' . "\n";

			echo '<option ';
			if ((!empty($_POST["node_type"]) && $_POST["node_type"] == 'page') ||
				(!empty($this->content['type']) && $this->content['type'] == 'page') ||
				(isset($this->path['2']) && $this->path['2'] == 'page') ) {
				echo 'selected="selected" ';
			}
			echo 'value="page">Page</option>' . "\n";

			echo '<option ';
			if ((!empty($_POST["node_type"]) && $_POST["node_type"] == 'forum') ||
				(!empty($this->content['type']) && $this->content['type'] == 'forum') ||
				(isset($this->path['2']) && $this->path['2'] == 'forum') ) {
				echo 'selected="selected" ';
			}
			echo 'value="forum">Forum</option>' . "\n";

			echo '<option ';
			if ((!empty($_POST["node_type"]) && $_POST["node_type"] == 'blog') ||
				(!empty($this->content['type']) && $this->content['type'] == 'blog') ||
				(isset($this->path['2']) && $this->path['2'] == 'blog')) {
				echo 'selected="selected" ';
			}
			echo 'value="blog">Blog</option>' . "\n";

		echo '</select><br />' . "\n";

		// Content
		echo 'Content:<br /><textarea style="width: 100%;" rows="30" name="node_content" />' . "\n";
			if (!empty($_POST["node_content"])) {
				echo $_POST["node_content"];
			} else if (!empty($this->content["content"])) {
				echo $this->content["content"];
			}
		echo '</textarea><br />' . "\n";

		echo '<input type="submit" value="Submit" />' . "\n";
		echo '</form>' . "\n";

		unset($this->content);
	}

	public function printError() {
		println('<div style="text-align: center;">');

		$url = $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

		switch($this->getError()) {
			case '403':
				println('<p>You don\'t have permissions to access http://' . $url . '</p>');
				println('<p><img alt="403" src="' . $this->base . '403.png" /></p>');
				break;
			case '404':
				println('<p>File or page at http://' . $url . ' not found</p>');
				println('<p><img alt="404" src="' . $this->base . '404.png" /></p>');
				break;
		}

		println('</p>');
		println('</div>');
	}

	public function formHandler() {

		if (!$this->user->isLoggedIn())
			return false;
		if (empty($_POST["node_title"]))
			return false;
		if (empty($_POST["node_content"]))
			return false;

		if ($this->load == 'add') {
			$this->formAdd();
		} else if ($this->load == 'edit')
			$this->formEdit();

	}

	private function formAdd() {

		// Note: A permissions check is needed here

		switch($_POST['node_type']) {
			case 'page':
				$this->add(array(
					'content' => $_POST['node_content'],
					'title' => $_POST['node_title'],
					'uid' => $this->user->uid(),
					'format' => 'html',
					'type' => 'page',
					'status' => 'active',
					'created' => date('YmdHis'),
				));
				break;
			case 'forum':
				$this->add(array(
					'content' => $_POST['node_content'],
					'title' => $_POST['node_title'],
					'uid' => $this->user->uid(),
					'format' => 'fhtml',
					'type' => 'forum',
					'status' => 'active',
					'created' => date('YmdHis'),
					'forum' => $this->path['2'],
				));
				break;
			case 'blog':
				$this->add(array(
					'content' => $_POST['node_content'],
					'title' => $_POST['node_title'],
					'uid' => $this->user->uid(),
					'format' => 'fhtml',
					'type' => 'blog',
					'status' => 'active',
					'created' => date('YmdHis'),
				));
				break;
		}

		// Redirect to newly posted content
		header('Location: ' . $this->base . 'node/' . $this->database->lastInsertId());
	}

	private function formEdit() {

		switch($_POST["node_type"]) {
			case 'page':
				break;
			case 'forum':
				break;
			case 'blog':
				break;
		}

		header('Location: ' . $this->base . 'node/' . $this->content["id"]);
	}

}

?>