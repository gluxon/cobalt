<?php
class Module {

	private $modules;
	private $modules_dir;

	private $registers_block;

	public function __construct($modules_dir) {
		$this->modules = array();
		$this->modules_dir = $modules_dir;

		$this->registers_block = array();
	}

	public function scanModules() {
		foreach($this->modules_dir as $modules) {
			// No custom module support yet
		}
	}

	public function addModule($name, $module) {
		$this->modules[$name] = $module;
	}

	public function getModule($module) {
		return $this->$module;
	}

	public function registerBlock($module_name, $parent) {
		if (empty($this->registers_block[$module_name])) {
			$this->registers_block[$module_name] = array();
		}
		array_push($this->registers_block[$module_name], $parent);
	}

	public function isBlockRegistered($module_name, $parent) {
		if (!empty($this->registers_block[$module_name])) {
			if (array_search($parent, $this->registers_block[$module_name]) !== false) {
				return true;
			}
		}
		return false;
	}

	public function request($path) {
		$module = array_shift($path);

		if (empty($this->modules[$module])) {
			$return = '404';
			$this->title = '404';
		} else if (method_exists($this->modules[$module], 'request')) {
			$return = $this->modules[$module]->request($path);
			$this->title = $this->modules[$module]->title();
		}

		// Special handler for node
		if ($return == '404' && $module != 'node') {
			$this->modules['node']->setError('404');
		}

		return $return;
	}

	public function title() {
		return $this->title;
	}

	public function block($parent) {
		$claimed = false;

		foreach($this->modules as $name => $module) {
			if (method_exists($module, 'block') && $this->isBlockRegistered($name, $parent)) {
				$module->block($parent);
				$claimed = true;
			}
		}
		if ($claimed) {
			return true;
		}
		return false;
	}

	public function formHandler() {
		$claimed = false;

		// Create copy for removal of user module
		$modules = $this->modules;

		// User needs to login first (user module is prioritized)
		$modules['user']->formHandler();

		// Remove user module from copy
		unset($modules['user']);

		foreach($modules as $name => $module) {
			if (method_exists($module, 'formHandler')) {
				$module->formHandler();
				$claimed = true;
			}
		}
		if ($claimed) {
			return true;
		}
		return false;
	}

}

?>