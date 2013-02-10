<?php
class User {

	private $database;
	private $dbho;

	public function __construct($database) {
		$this->database = $database;
		$this->dbho = $this->database;
	}
}
?>