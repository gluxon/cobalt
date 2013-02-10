<?php
class Request {

	private $database;
	private $module;
	private $path;

	private $title;

	/**
	 * Constructor for request
	 * 
	 * @param mixed $path Full path
	 */
	public function __construct($options) {
		$this->database = (empty($options["database"])) ? null : $options["database"];
		$this->module = (empty($options["module"])) ? null : $options["module"];

		$this->path = (empty($options["path"])) ? null : $options["path"];
	}

	/**
	 * Return the corresponding code to a path
	 */
	public function parse() {
		// We don't want to modify the original path for now
		$path = $this->path;

		// If path is empty, retrieve homepage
		$path = (empty($path)) ? $this->database->getSetting('homepage') : $path;

		// Check if path is an alias
		$path = ($this->database->getResolvedAlias($path)) ?: $path;

		// Explode the path to parse
		$path = explode('/', $path);

		// Send request to modules to handle
		$return = $this->module->request($path);
		$this->title = $this->module->title($path);

		return $return;
	}

	public function title() {
		return $this->title;
	}

}

?>