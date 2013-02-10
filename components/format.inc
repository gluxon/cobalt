<?php
class Format {

	private $database;
	private $types_dir;
	private $rules_dir;

	private $types;

	public function __construct($options) {
		$this->database = (empty($options["database"])) ? null : $options["database"];
		$this->types_dir = (empty($options["types_dir"])) ? null : $options["types_dir"];
		$this->rules_dir = (empty($options["rules_dir"])) ? null : $options["rules_dir"];

		$this->types = array();
	}

	public function cullTypes() {
		foreach (scandir($this->types_dir) as $type) {
			array_push($this->types, $type);
		}
	}

	public function display($content, $type) {
		include('includes/content/' . $type . '.php');
	}
}
?>