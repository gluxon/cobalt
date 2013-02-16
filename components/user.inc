<?php
class User {

	private $database;
	private $dbho;

	private $user;

	private $addUserStmt;
	private $getUsernameStmt;

	public function __construct($database) {
		$this->database = $database;
		$this->dbho = $this->database->getInstance();
	}

	public function addUser($options) {
		$username = (empty($options["username"])) ? null : $options["username"];
		$password = (empty($options["password"])) ? null : $options["password"];
		$email = (empty($options["email"])) ? null : $options["email"];

		if (is_null($addUserStmt)) {
			$addUserStmt = $this->dbho->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
		}

		$addUserStmt->execute(array($username, $password, $email));
	}

	public function getUsername($uid) {
		// Only integer values for uid
		if ( !is_numeric($uid) || strpbrk($uid, '.') ) {
			return false;
		}

		if (is_null($getUsernameStmt)) {
			$getUsernameStmt = $dbh->prepare("SELECT username FROM users WHERE uid = ?");
		}

		$getUsernameStmt->execute(array($uid));
		$username = $getUsernameStmt->fetch(PDO::FETCH_NUM);
		return $username["0"];
	}

	public function find($options) {
		if ( empty($options) || !is_array($options) ) {
			throw new Exception('Invalid arguments');
		}

		$counter = 0;
		$statement = 'SELECT * FROM users WHERE ';

		foreach($options as $key => $val) {
			if (!ctype_alpha($key)) {
				// This should the key is actually an existing collumn (issue #7)
				throw new Exception('Invalid collumn name');
			} else {
				if ($counter >= 1) {
					$statement .= ' AND ';
				}
				$statement .= $key . '=?';
				$counter++;
			}
		}

		$values = array_values($options);

		$findStmt = $this->dbho->prepare($statement);
		$findStmt->execute($values);
		return $findStmt->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * Set a password reset token for the specified user
	 * 
	 * @param mixed $uid of user
	 * @param mixed $token token to be assigned
	 */
	public function setToken($uid, $token) {

		if (is_null($setTokenStatement)) {
			$setTokenStatement = $this->dbho->prepare("UPDATE users SET token=? WHERE uid=?");
		}
		$setTokenStatement->execute(array($token, $uid));
	}

	public function login($uid) {
		$this->user = $this->find( array('uid' => $uid) );
	}

	public function isLoggedIn() {
		return !empty($this->user);
	}

	public function uid() {
		return $this->user['uid'];
	}

	public function printLoginBlock() {

		if ($this->isLoggedIn())
		{
			println('<h2>' . $this->user["username"] . '</h2>');
			println('<ul>');
			println('<li><a href="' . $this->base . 'user/' . $this->user["uid"]. '">My Account</a></li>');
			// This if statement should be replaced with permissions check
			if (strpbrk($this->user["role"], 'admin')) {
				println('<li><a href="' . $this->base . 'admin">Administer</a></li>');
			}
			println('<li><a href="' . $this->base . 'user/logout">Logout</a></li>');
			println('</ul>');
		}
		else
		{
			println('<h2>Account</h2>');
			println('<div class="login-block">');
			println('<form action="' . $_SERVER["REQUEST_URI"] . '" method="post">');
			println('Username:<br /><input type="text" size="15" name="username" /><br />');
			println('Password:<br /><input type="password" size="15" name="password" /><br />');
			println('<input type="submit" value="Log In" />');
			println('</form>');

			println('<p class="sidebar-paragraph" style="font-size: 12px;">');
			println('<a href="' . $this->base . 'user/register"><i>Register</i></a> /
				<a href="' . $this->base . 'user/password"><i>Forgot Password?</i></a>');
			println('</p>');
			println('</div>');
		}

	}

}
?>