<?php
class Template {
	
	private $database;
	private $dbho;

	private $theme_dir;
	private $theme;

	public function __construct($options) {
		$this->database = (empty($options["database"])) ? null : $options["database"];
		$this->theme_dir = (empty($options["theme_dir"])) ? null : $options["theme_dir"];
		$this->theme = (empty($options["theme"])) ? null : $options["theme"];

		$this->dbho = $this->database;
	}

	public function printStart() {
		println('<!DOCTYPE HTML>');
		println('<html>');
	}

	public function printHead($options) {
		$title = (empty($options["title"])) ? null : $options["title"];
		$description = (empty($options["description"])) ? null : $options["description"];
		$keywords = (empty($options["keywords"])) ? null : $options["keywords"];
		$author = (empty($options["author"])) ? null : $options["author"];
		$theme_dir = (empty($options["theme_dir"])) ? $this->theme_dir : $options["theme_dir"];
		$theme = (empty($options["theme"])) ? $this->theme : $options["theme"];
		$base = (empty($options["base"])) ? '/' : $options["base"];

		println('<head>');
		println('<title>' . $title . '</title>');
		println('<meta http-equiv="Content-Type" content="text/html;charset=UTF-8">');
		println('<meta name="description" content="' . $description . '" />');
		println('<meta name="keywords" content="' . $keywords . '" />');
		println('<meta name="author" content="' . $author . '" />');
		println('<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0"/>');
		println('<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />');
		println('<link rel="stylesheet" type="text/css" href="' . $base . $theme_dir . '/' . $theme . '/mainstyle.css" />');
		println('</head>');
	}

	public function printBody($options) {
		$blocks = (empty($options["blocks"])) ? null : $options["blocks"];
		$module = (empty($options["module"])) ? null : $options["module"];
		$theme_dir = (empty($options["theme_dir"])) ? $this->theme_dir : $options["theme_dir"];
		$theme = (empty($options["theme"])) ? $this->theme : $options["theme"];
		$base = (empty($options["base"])) ? null : $options["base"];

		$body = file($theme_dir . '/' . $theme . '/body.html');

		foreach ($body as $current_line) {
			// Search for body opening
			$strpos = strpos($current_line, '<-- body ', 0);

			if ($strpos===FALSE) {
				// No template data, spit out raw
				echo $current_line;

			} else {

				// Get position of blockname's start and end (to find content in between)
				$blockname_start=$strpos + strlen("<-- body ");
				$blockname_end=strpos($current_line, ' -->', $blockname_start);

				// Subtract blockname start by blockname end to get blockname's string length
				$blockname_length=$blockname_end - $blockname_start;

				// Use blockname start and length to get blockname (in between is then name)
				$blockname=substr($current_line, $blockname_start, $blockname_length);

				// Split the currently line by the block statement and output the first/last parts to the web browser
				$current_line_split=explode("<-- body $blockname -->", $current_line);
				if ( !empty($current_line_split["0"]) ) {
					echo $current_line_split["0"];
				}
				if ( !empty($current_line_split["2"]) ) {
					echo $current_line_split["2"];
				}

				$blocks->printBlocks(array(
					'blockname' => $blockname,
					'module' => $module
				));

			}
		}
	}

	public function printEnd() {
		println('</html>');
	}
}
?>