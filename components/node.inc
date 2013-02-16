<?php
class Node {

	private $database;
	private $dbho;
	private $user;
	private $permissions;
	private $format;

	private $base;

	private $path;

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

	/**
	 * Built-in requred function: Parse module's URLs
	 * 
	 * @param array $path Exploded path from Request
	 */
	public function request($path) {
		if (!is_numeric($path["0"])) return '404';

		$this->content = $this->findWithNID($path["0"]);

		if ($this->content === false) {
			$this->error = '404';
			return '404';
		}
		else if (!isset($path["1"])) {
			$this->title = $this->content["title"];
			return 'page';
		}
		else if (isset($path["2"])) {
			// edit pages
		}
	}
	
	public function title() {
		return $this->title;
	}

	public function block($parent) {
		if ($parent == 'content') {
			$this->printContent();
		}
	}

	/**
	 * Find a node given its cid
	 *
	 * @param mixed $nid Node ID
	 * @return array PDO ASSOC Array
	 */
	public function findWithNID($nid) {
		$findWithNIDStmt = $this->dbho->prepare("SELECT * FROM node WHERE id = ?");

		$findWithNIDStmt->execute(array($nid));
		$node = $findWithNIDStmt->fetch(PDO::FETCH_ASSOC);

		return $node;
	}

	private function printContent() {
		// Print the title if there is one
		if (!empty($this->content['title'])) {
			println('<h2>' . $this->content['title'] . '</h2>') ;
		}

		// Output an error
		if (isset($this->error)) {
			$this->printError($this->error);
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

	public function printError($error_no) {
		println('<div style="text-align: center;">');

		$url = $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

		switch($error_no) {
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

}

?>