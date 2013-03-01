<?php
class User {

	private $database;
	private $dbho;
	private $base;

	private $user;

	private $addUserStmt;
	private $getUsernameStmt;

	public function __construct($options) {
		$this->database = (empty($options["database"])) ? null : $options["database"];
		$this->base = (empty($options["base"])) ? null : $options["base"];

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

	public function uid() {
		return $this->user['uid'];
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
				// This should check if the key is actually an existing collumn (issue #7)
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

	public function logout() {
		setcookie("id", "", time() - 3600);
		setcookie("password", "", time() - 3600);
		session_destroy();
		header('Location: /');
	}

	public function request($path) {

		switch(@$path[0]) {
			case 'logout':
				$this->title = 'Logout';
				$this->logout();
				break;
			default:
				$this->title = '404';
				return '404';
		}

	}

	public function title() {
		return $this->title;
	}

	public function formHandler() {

		if (!empty($_SESSION["id"])) {
			// We have a logged in user!
			$this->user = $this->find(array(
				'uid' => $_SESSION["id"]
			));
		}

		if (!empty($_POST["username"]) && !empty($_POST["password"])) {
			$this->user = $this->find(array(
				'username' => $_POST["username"],
				'password' => md5($_POST["password"])
			));

			if ($this->user !== false) {
				$_SESSION["id"] = $this->user["uid"];
				setcookie('id', $this->user["uid"], time() + 60*60*24*30, "/");
				setcookie('password', hash("sha256", md5($_POST["password"])), time() + 60*60*24*30, "/");
			}
		}
	}

	public function block($parent) {
		$this->printLoginBlock();
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