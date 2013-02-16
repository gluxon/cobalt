<?php
class Theme {

	private $database;
	private $dbho;

	private $base;

	private $theme_dir;
	private $themes;
	private $theme;

	public function __construct($options) {
		$this->database = (empty($options["database"])) ? null : $options["database"];
		$this->theme_dir = (empty($options["theme_dir"])) ? null : $options["theme_dir"];
		$this->theme = (empty($options["theme"])) ? null : $options["theme"];
		$this->base = (empty($options["base"])) ? null : $options["base"];

		$this->dbho = $this->database->getInstance();

		$this->themes = array();
	}

	public function scanThemes() {
		foreach (scandir($this->themes_dir) as $theme) {
			array_push($this->themes, $theme);
		}
	}

	public function setTheme($theme) {
		$this->theme = $theme;
	}

	public function getThemesDir() {
		return $this->theme_dir;
	}

	public function getCurrentThemeDir() {
		return $this->theme_dir . '/' . $this->theme;
	}

}
?>