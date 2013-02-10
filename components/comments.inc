<?php
class Comments {

	private $database;
	private $dbho;
	private $node;
	private $format;
	private $user;
	private $permissions;

	public function __construct($options) {
		$this->database = (empty($options["database"])) ? null : $options["database"];
		$this->node = (empty($options["node"])) ? null : $options["node"];
		$this->format = (empty($options["format"])) ? null : $options["format"];
		$this->user = (empty($options["user"])) ? null : $options["user"];
		$this->permissions = (empty($options["permissions"])) ? null : $options["permissions"];

		$this->dbho = $this->database->getInstance();
	}

	public function block($parent) {
		if ($parent == 'comments') {
			$this->printComments();
		}
	}

	public function printComments() {
		echo 'comments here';
	}

}
?>