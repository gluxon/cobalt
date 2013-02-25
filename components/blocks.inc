<?php
class Blocks {

	private $database;
	private $format;
	private $theme_dir;
	private $theme;
	private $module;
	private $base;

	private $dbho;

	private $getBlocksStmt;

	public function __construct($options) {
		$this->database = (empty($options["database"])) ? null : $options["database"];
		$this->module = (empty($options["module"])) ? null : $options["module"];
		$this->format = (empty($options["format"])) ? null : $options["format"];
		$this->theme_dir = (empty($options["theme_dir"])) ? null : $options["theme_dir"];
		$this->theme = (empty($options["theme"])) ? null : $options["theme"];
		$this->base = (empty($options["base"])) ? null : $options["base"];

		$this->dbho = $this->database->getInstance();
	}

	public function getBlocks($parent) {

		if (is_null($this->getBlocksStmt)) {
			$this->getBlocksStmt = $this->dbho->prepare("SELECT * FROM block WHERE parent=? ORDER BY sort ASC");
		}

		$this->getBlocksStmt->execute(array($parent));
		return $this->getBlocksStmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public function printBlocks($options) {
		$blockname = (empty($options["blockname"])) ? null : $options["blockname"];
		$module = (empty($options["module"])) ? $this->module : $options["module"];

		switch ($blockname) {
			case 'lsidebar':
			case 'rsidebar':
				$module->block($blockname);
				$this->printSidebarLeft();
				break;
			default:
				if ($module->block($blockname) == false) {
					$this->printDefault($blockname);
				}
		}
	}

	public function printSidebarLeft() {
		foreach($this->getBlocks('lsidebar') as $row) {
			echo '<div class="sidebar-block">' . "\n";
			if (!empty($row["title"])) echo '<h2>' . $row["title"] . '</h2>' . "\n";
			$this->format->display($row["content"], $row["format"]);
			echo '</div>' . "\n";
		}
	}

	public function printDefault($blockname) {
		foreach($this->getBlocks($blockname) as $row) {
			$this->format->display($row["content"], $row["format"]);
		}
	}

}
?>