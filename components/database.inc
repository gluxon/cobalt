<?php
class Database {

	private $host;
	private $port;
	private $name;
	private $user;
	private $password;
	private $dsn;

	private $dbho;

	private $getSettingStmt;
	private $setSettingStmt;
	private $updateSettingStmt;
	private $getResolvedAliasStmt;

	public function __construct($options = null) {
		if ($options != null) $this->connect($options);
	}

	/**
	 * Connect to the database
	 * 
	 * @param mixed $host host of the database
	 * @param int $port port of the database
	 * @param mixed $name name of the database
	 * @param mixed $user username with proper access to database
	 * @param mixed $password password to database user
	 * @param mixed $dsn Data Source Name
	 * @throws PDOException exception from pdo
	 */
	public function connect($options) {
		$this->host = (empty($options["host"])) ? 'localhost' : $options["host"];
		$this->port = (empty($options["port"])) ? '3306' : $options["port"];
		$this->name = (empty($options["name"])) ? null : $options["name"];
		$this->user = (empty($options["user"])) ? null : $options["user"];
		$this->password = (empty($options["password"])) ? null : $options["password"];
		$this->dsn = (empty($options["dsn"])) ? 'mysql' : $options["dsn"];

		$this->dbho = new PDO($this->dsn.':host=' . $this->host . ';dbname=' . $this->name, $this->user, $this->password);
	}

	/**
	 * Get the database instance
	 *
	 * @return PDO PDO Object
	 */
	public function getInstance() {
		return $this->dbho;
	}

	/**
	 * Get the last insert ID
	 *
	 * @return int Last insert ID
	 */
	public function lastInsertId() {
		return $this->dbho->lastInsertId();
	}

	/**
	 * Get a setting from the database
	 * 
	 * @param mixed $setting setting in database
	 * @return mixed value in setting
	 */
	public function getSetting($setting) {

		if (is_null($this->getSettingStmt)) {
			$this->getSettingStmt = $this->dbho->prepare("SELECT value FROM settings WHERE setting = ?");
		}

		$this->getSettingStmt->execute(array($setting));
		$return = $this->getSettingStmt->fetch(PDO::FETCH_NUM);

		if ($return === false) {
			return false;
		} else {
			return $return['0'];
		}
	}

	/**
	 * Set a setting in the database
	 * 
	 * @param mixed $setting setting in database
	 * @param mixed $value new value
	 */
	public function setSetting($setting, $value) {
		if (is_null($this->setSettingStmt)) {
			$this->setSettingStmt = $this->dbho->prepare("INSERT INTO settings (setting, value) VALUES (?, ?)");
		}

		if ($this->getSetting($setting) === false) {
			$this->setSettingStmt->execute(array($setting, $value));
		} else {
			$this->updateSetting($setting, $value);
		}
	}

	/**
	 * Update a setting in the database
	 * 
	 * @param mixed $setting setting in database
	 * @param mixed $value value to update
	 */
	public function updateSetting($setting, $value) {
		if (is_null($this->updateSettingStmt)) {
			$this->updateSettingStmt = $this->dbho->prepare("UPDATE settings SET value=? WHERE setting=?");
		}

		$this->updateSettingStmt->execute(array($value, $setting));
	}

	/**
	 * Get the url to an assigned alias with the alias
	 * 
	 * @param mixed $alias Alias tied to URL
	 * @return mixed URL of alias
	 */
	public function getResolvedAlias($alias) {

		if (is_null($this->getResolvedAliasStmt)) {
			$this->getResolvedAliasStmt = $this->dbho->prepare("SELECT url FROM url_alias WHERE alias = ?");
		}

		$this->getResolvedAliasStmt->execute(array($alias));
		$return = $this->getResolvedAliasStmt->fetch(PDO::FETCH_NUM);
		return $return["0"];
	}

	/**
	 * Close a statement after usage
	 * 
	 * @param mixed $statement statement
	 */
	public function closeStatement($statement) {
		unset($this->$statement);
	}

	/**
	 * Close the database instance
	 */
	public function __destruct() {
		$this->dbho = null;
	}

}
?>