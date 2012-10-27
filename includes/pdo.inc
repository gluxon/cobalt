<?php
class NappalDatabase {
	private $getSettingStatement;
	private $updateSettingStatement;
	private $getURLWithAliasStatement;
	private $getBlocksStatement;

	public function __construct() { /* We don't need anything here for now */ }

	/**
	 * Connect to the database
	 * 
	 * @param mixed $host host of the database
	 * @param mixed $name name of the database
	 * @param mixed $user username with proper access to database
	 * @param mixed $password password to database user
	 * @throws PDOException exception from pdo
	 */
	public function connect($host, $name, $user, $password) {
		global $dbh; // we should be safe declaring this global, since its only an instance
		$dsn = 'mysql';
		$dbh = new PDO($dsn.':host=' . $host . ';dbname=' . $name, $user, $password);
	}

	public function lastInsertId() {
		global $dbh;
		return $dbh->lastInsertId();
		// that was easy
	}

	/**
	 * Get a setting from the database
	 * 
	 * @param mixed $setting setting in sql
	 * @return mixed value in setting
	 */
	public function getSetting($setting) {
		global $dbh;
		if (is_null($this->getSettingStatement)) {
			$this->getSettingStatement = $dbh->prepare("SELECT value FROM settings WHERE setting = ?");
		}
		$this->getSettingStatement->execute(array($setting));
		$return = $this->getSettingStatement->fetch(PDO::FETCH_NUM);
		return $return['0'];
	}

	/**
	 * Update a setting in the database
	 * 
	 * @param mixed $setting setting in sql
	 * @param mixed $value New Value
	 */
	public function updateSetting($setting, $value) {
		global $dbh;
		if (is_null($this->updateSettingStatement)) {
			$this->updateSettingStatement = $dbh->prepare("UPDATE settings SET value=? WHERE setting=?");
		}
		$this->updateSettingStatement->execute(array($value, $setting));
	}

	/**
	 * Get a block data
	 * 
	 * @param mixed $parent Parent blocks belong to
	 * @param mixed Nappal PDO Block Assoc
	 */
	public function getBlocks($parent) {
		global $dbh;

		if (is_null($this->getBlocksStatement)) {
			$this->getBlocksStatement = $dbh->prepare("SELECT * FROM block WHERE parent=? ORDER BY sort ASC");
		}
		$this->getBlocksStatement->execute(array($parent));
		return $this->getBlocksStatement->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Get the url to an assigned alias with the alias
	 * 
	 * @param mixed $alias Alias tied to URL
	 * @return mixed URL of alias
	 */
	public function getURLWithAlias($alias) {
		global $dbh;
		if (is_null($this->getURLWithAliasStatement)) {
			$this->getURLWithAliasStatement = $dbh->prepare("SELECT url FROM url_alias WHERE alias = ?");
		}
		$this->getURLWithAliasStatement->execute(array($alias));
		$return = $this->getURLWithAliasStatement->fetch(PDO::FETCH_NUM);
		return $return["0"];
	}

	/**
	 * Check if a user has permision to perform the action
	 * 
	 * @param array $user Nappal PDO User Assoc
	 * @param mixed $action The action the user wishes to do
	 * @param array $node Nappal PDO Node Assoc
	 * @param array $comment Nappal PDO Comment Assoc
	 * @return bolean True or false
	 */
	public function hasPermission($user, $action, $node = null, $comment = null) {
		if (empty($user)) {
			return false;
		}

		switch ($action) {
			case 'addnode':
				// requirement is logging in as a user
				return true;
			break;

			case 'addforumtopic':
				// requirement is logging in as a user
				return true;
			break;

			case 'addpage':
				if ( strpbrk($user["role"], 'admin') || strpbrk($user["role"], 'moderator') ) {
					return true;
				}
			break;

			case 'addblog':
				// Only allow admin and moderators to blog on the site for now
				if ( strpbrk($user["role"], 'admin') || strpbrk($user["role"], 'moderator') ) {
					return true;
				}
			break;

			case 'addcomment':
				if ( $node["comments"]=='1' || strpbrk($user["role"], 'admin') || strpbrk($user["role"], 'moderator') ) {
					return true;
				}
			break;

			case 'addreply':
				if ( ($node["comments"]=='1' && $user["uid"]==$node["uid"]) || strpbrk($user["role"], 'admin') || strpbrk($user["role"], 'moderator') ) {
					return true;
				}
			break;

			case 'editcomment':
				if ( ($node["comments"]=='1' && $user["uid"]==$comment["uid"]) || strpbrk($user["role"], 'admin') || strpbrk($user["role"], 'moderator') ) {
					return true;
				}
			break;

			case 'editnode':
				if ( $user["uid"]==$node["uid"] || strpbrk($user["role"], 'admin') || strpbrk($user["role"], 'moderator') ) {
					return true;
				}
			break;

			case 'deletenode':
				if ( $user["uid"]==$node["uid"] || strpbrk($user["role"], 'admin') || strpbrk($user["role"], 'moderator') ) {
					return true;
				}
			break;

			case 'deletecomment':
				if ( $user["uid"]==$node["uid"] || strpbrk($user["role"], 'admin') || strpbrk($user["role"], 'moderator') ) {
					return true;
				}
			break;

			case 'adminpanel':
				if (strpbrk($user["role"], 'admin')) {
					return true;
				}
			break;

			default:
				return false;
		}
		return false;
	}

	/**
	 * Close a statement after usage
	 * 
	 * @param mixed $statement statement
	 */
	public function closeStatement($statement) {
		unset($statement);
	}

	public function __destruct() {
		// close pdo instance
		$dbh = null;
	}
}

class NappalUser extends NappalDatabase {
	public function __construct() { /* We don't need anything here for now */ }

	/**
	 * Add a new user
	 */
	public function addUser($username, $password, $email) {
		global $dbh, $addUserStatement;

		if (is_null($addUserStatement)) {
			$addUserStatement = $dbh->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
		}
		$addUserStatement->execute(array($username, $password, $email));
	}

	public function getUsername($uid) {
		if ( !is_numeric($uid) && strpbrk($uid, '.') ) {
			return false;
		}

		global $dbh, $getUsernameStatement;
		if (is_null($getUsernameStatement)) {
			$getUsernameStatement = $dbh->prepare("SELECT username FROM users WHERE uid = ?");
		}
		$getUsernameStatement->execute(array($uid));
		$username = $getUsernameStatement->fetch(PDO::FETCH_NUM);
		return $username["0"];
	}

	/**
	 * Find a user given their user id
	 * 
	 * @param mixed $uid User ID
	 * @return array PDO ASSOC Array
	 */
	public function findWithUID($uid) {
		if ( !is_numeric($uid) && strpbrk($uid, '.') ) {
			return false;
		}

		global $dbh, $findWithUIDStatement;
		if (is_null($findWithUIDStatement)) {
			$findWithUIDStatement = $dbh->prepare("SELECT * FROM users WHERE uid = ?");
		}
		$findWithUIDStatement->execute(array($uid));
		$currentuser = $findWithUIDStatement->fetch(PDO::FETCH_ASSOC);
		return $currentuser;
	}

	/**
	* Find a user given their username
	*
	* @param mixed $username username
	* @return array PDO ASSOC Array
	*/
	public function findWithUsername($username) {
		global $dbh, $findWithUsernameStatement;
		if (is_null($findWithUsernameStatement)) {
			$findWithUsernameStatement = $dbh->prepare("SELECT * FROM users WHERE username = ?");
		}
		$findWithUsernameStatement->execute(array($username));
		$currentuser = $findWithUsernameStatement->fetch(PDO::FETCH_ASSOC);
		return $currentuser;
	}

	/**
	* Find a user given their username and password
	*
	* @param mixed $username username
	* @param mixed $password password
	* @return array PDO ASSOC Array
	*/
	public function findWithUsernameAndPassword($username, $password) {
		global $dbh, $findWithUsernameAndPasswordStatement;
		if (is_null($findWithUsernameAndPasswordStatement)) {
			$findWithUsernameAndPasswordStatement = $dbh->prepare("SELECT * FROM users WHERE username=? AND password=?");
		}
		$findWithUsernameAndPasswordStatement->execute( array($username, md5($password)) );
		$currentuser = $findWithUsernameAndPasswordStatement->fetch(PDO::FETCH_ASSOC);
		return $currentuser;
	}

	/**
	 * Find a user given their email
	 *
	 * @param mixed $email email
	 * @return array PDO ASSOC Array
	 */
	public function findWithEmail($email) {
		global $dbh, $findWithEmailStatement;
		if (is_null($findWithEmailStatement)) {
			$findWithEmailStatement = $dbh->prepare("SELECT * FROM users WHERE email = ?");
		}
		$findWithEmailStatement->execute(array($email));
		$currentuser = $findWithEmailStatement->fetch(PDO::FETCH_ASSOC);
		return $currentuser;
	}

	/**
	 * Set a password reset token for the specified user
	 * 
	 * @param mixed $uid of user
	 * @param mixed $token token to be assigned
	 */
	public function setToken($uid, $token) {
		global $dbh, $setTokenStatement;
		
		if (is_null($setTokenStatement)) {
			$setTokenStatement = $dbh->prepare("UPDATE users SET token=? WHERE uid=?");
		}
		$setTokenStatement->execute(array($token, $uid));
	}
}

class NappalNode {
	public function __construct() { /* We don't need anything here for now */ }

	/**
	 * Find a node given its cid
	 *
	 * @param mixed $nid Node ID
	 * @return array PDO ASSOC Array
	 */
	public function findWithNID($nid) {
		if ( !is_numeric($nid) && strpbrk($nid, '.') ) {
			return false;
		}

		global $dbh, $findWithNIDStatement;
		if (is_null($findWithNIDStatement)) {
			$findWithNIDStatement = $dbh->prepare("SELECT * FROM node WHERE id = ?");
		}
		$findWithNIDStatement->execute(array($nid));
		$node = $findWithNIDStatement->fetch(PDO::FETCH_ASSOC);
		return $node;
	}

	/**
	 * Delete a node
	 * 
	 * @param mixed $nid Node ID
	 */
	public function delete($nid) {
		if ( !is_numeric($nid) && strpbrk($nid, '.') ) {
			return false;
		}

		global $dbh, $deleteStatement;
		if (is_null($deleteStatement)) {
			$deleteStatement = $dbh->prepare("DELETE FROM node WHERE id = ?");
		}
		$deleteStatement->execute(array($nid));

	}

	/**
	 * Add a new page
	 * 
	 * @param mixed $content Body of new page
	 * @param mixed $title Title of new page
	 * @param mixed $uid Creator UID
	 * @param mixed $format Input type
	 * @param mixed $comments 1 or 0 boolean value for enabling comments
	 * @param mixed $status Active, locked, etc
	 */
	public function addPage($content, $title, $uid, $format, $comments, $status) {
		global $dbh, $addPageStatement;

		if (is_null($addPageStatement)) {
			$addPageStatement = $dbh->prepare("INSERT INTO node (content, title, uid, format, type, comments, status) VALUES (:content, :title, :uid, :format, :type, :comments, :status)");
		}
		$addPageStatement->execute(array(
			':content' => $content,
			':title' => $title,
			':uid' => $uid,
			':format' => $format,
			':type' => 'page',
			':comments' => $comments,
			':status' => $status)
		);
	}

	/**
	 * Update a page
	 *
	 * @param mixed $content Body of updated page
	 * @param mixed $title Title of updated page
	 * @param mixed $nid Node ID
	 */
	public function editPage($nid, $content, $title) {
		global $dbh, $editPageStatement;

		if (is_null($editPageStatement)) {
			$editPageStatement = $dbh->prepare("UPDATE node SET content=?, title=?, type='page' WHERE id=?");
		}
		$editPageStatement->execute(array($content,$title,$nid));
	}

	/**
	 * Update the timestamp of a node
	 * 
	 * @param mixed $nid Node ID
	 * @param mixed $timestamp YmdHis timestamp
	 */
	public function updateTimestamp($nid, $timestamp) {
		if ( !is_numeric($nid) && strpbrk($nid, '.') ) {
			return false;
		}

		global $dbh, $updateTimestampStatement;
		if (is_null($updateTimestampStatement)) {
			$updateTimestampStatement = $dbh->prepare("UPDATE node SET timestamp=? WHERE id=?");
		}
		$updateTimestampStatement->execute(array($timestamp, $nid));
	}

	public function getCommentAmount($nid) {
		global $dbh, $getCommentAmountStatement;
		if (is_null($getCommentAmountStatement)) {
			$getCommentAmountStatement = $dbh->prepare("SELECT COUNT(*) FROM comment WHERE parentid=?");
		}
		$getCommentAmountStatement->execute( array($nid) );
		return $getCommentAmountStatement->fetchColumn();
	}

	/**
	 * Set a 404 message
	 */
	public function set404() {
		global $node, $notfound, $title;
		unset($node);

		header("HTTP/1.0 404 Not Found");
		$notfound='1';
		$title='404';
	}

	/**
	* Set a 403 message
	*/
	public function set403() {
		global $node, $nopermissions, $title;
		unset($node);

		header("HTTP/1.0 403 Forbidden");
		$nopermissions='1';
		$title='403';
	}


}

class NappalComments extends NappalUser {
	public function __construct() { /* We don't need anything here for now */ }

	/**
	 * Add a new comment
	 *
	 * @param mixed $content Body of new page
	 * @param mixed $title Title of new page
	 * @param mixed $uid Creator UID
	 * @param mixed $format Input type
	 * @param mixed $parentthread Parent thread
	 * @param mixed $parentid ID of Parent thread
	 * @param mixed $created YmdHis of creation time
	 */
	public function addComment($content, $title, $uid, $format, $parentthread, $parentid, $created) {
		global $dbh, $addCommentStatement;

		if (is_null($addCommentStatement)) {
			$addCommentStatement = $dbh->prepare("INSERT INTO comment (content, title, uid, format, parentthread, parentid, created) VALUES (:content, :title, :uid, :format, :parentthread, :parentid, :created)");
		}
		$addCommentStatement->execute(array(
				':content' => $content,
				':title' => $title,
				':uid' => $uid,
				':format' => $format,
				':parentthread' => $parentthread,
				':parentid' => $parentid,
				':created' => $created)
		);
	}

	/**
	 * Update a Comment
	 *
	 * @param mixed $cid Comment CID
	 * @param mixed $content Body of updated Comment
	 * @param mixed $title Title of updated Comment
	 * @param mixed $modified YmdHis of modified time
	 */
	public function editComment($cid, $content, $title, $modified) {
		global $dbh, $editCommentStatement;
	
		if (is_null($editCommentStatement)) {
			$editCommentStatement = $dbh->prepare("UPDATE comment SET content=?, title=?, modified=? WHERE id=?");
		}
		$editCommentStatement->execute(array($content,$title,$modified,$cid));
	}

	/**
	 * Find a comment given its cid
	 *
	 * @param mixed $id Comment ID
	 * @return array PDO ASSOC Array
	 */
	public function findWithCID($cid) {
		global $dbh, $findWithCIDStatement;
		if (is_null($findWithCIDStatement)) {
			$findWithCIDStatement = $dbh->prepare("SELECT * FROM comment WHERE id = ?");
		}
		$findWithCIDStatement->execute(array($cid));
		$comment = $findWithCIDStatement->fetch(PDO::FETCH_ASSOC);
		return $comment;
	}

	/**
	 * Returns the node id (nid) the comment belongs to
	 * 
	 * @param mixed $cid Comment ID
	 * @return mixed Node ID (nid)
	 */
	public function getParentThread($cid) {
		global $dbh, $getParentThreadStatement;
		if (is_null($getParentThreadStatement)) {
			$getParentThreadStatement = $dbh->prepare("SELECT parentid FROM comment WHERE id= ?");
		}
		$getParentThreadStatement->execute(array($cid));
		$return = $getParentThreadStatement->fetch(PDO::FETCH_NUM);
		return $return['0'];
	}

	public function getNewestComments($nid) {
		global $dbh, $getNewestCommentStatement;
		if (is_null($getNewestCommentStatement)) {
			$getNewestCommentStatement = $dbh->prepare("SELECT id,uid,created FROM comment WHERE parentid=:nid AND created = (SELECT MAX(created) FROM comment WHERE parentid=:nid)");
		}
		$getNewestCommentStatement->execute( array('nid' => $nid) );
		return $getNewestCommentStatement->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getComments($parentthread, $parentid) {
		global $dbh, $getReplyStatement;
		if (is_null($getReplyStatement)) {
			$getReplyStatement = $dbh->prepare("SELECT id FROM comment WHERE parentthread=? AND parentid=? ORDER BY created ASC");
		}
		$getReplyStatement->execute( array($parentthread, $parentid) );
		return $getReplyStatement->fetchAll(PDO::FETCH_ASSOC);
	}

	public function loadComment($cid, $width) {
		global $current_user, $node, $theme, $ABSBASE;

		// Load this comment
		$comment = $this->findWithCID($cid);

		// Get settings from theme INI
		$theme_ini = parse_ini_file("themes/$theme/theme.ini", true);
		//$width=str_replace('%', '', $theme_ini["comments"]["width"]);
		$shrink=str_replace('%', '', $theme_ini["comments"]["shrink"]);

		echo '<div class="comment" style="margin-left: ' . $width . '% ;">';
		echo '<h2>' . $comment["title"] . '</h2>' . "\n";

		// Controls
		//If (strpbrk($user["role"], 'admin') || strpbrk($user["role"], 'moderator') || $user["uid"]==$comment["uid"]) {
		if ($this->hasPermission($current_user, 'editcomment', $this->getParentThread($comment["id"]), $comment)) {
			echo '<div class="controls">' . "\n";
			echo '	<div class="button edit">' . "\n";
			echo '		<a href="' . $ABSBASE . 'comment/' . $comment["id"] . '/edit">' . "\n";
			echo '		<img src="' . $ABSBASE . RESOURCES_PATH . '/pages/edit.png" alt="Edit" />' . "\n";
			echo '		</a>' . "\n";
			echo '	</div>' . "\n";
			echo '	<div class="button delete">' . "\n";
			echo '		<a href="' . $ABSBASE . 'comment/' . $comment["id"] . '/delete">' . "\n";
			echo '		<img src="' . $ABSBASE . RESOURCES_PATH . '/pages/delete.png" alt="Delete" />' . "\n";
			echo '		</a>' . "\n";
			echo '	</div>' . "\n";
			echo '</div>' . "\n";
		}

		// Comment creation date
		if (!empty($comment["created"])) {
			$year=substr($comment["created"], 0, 4);
			$month=substr($comment["created"], 4, 2);
			$day=substr($comment["created"], 6, 2);
			$hour=substr($comment["created"], 8, 2);
			$minute=substr($comment["created"], 10, 2);
			$second=substr($comment["created"], 12, 2);
			$comment_username = $this->getUsername($comment["uid"]);
			echo '<p class="meta"><a href="' . $ABSBASE . 'user/' . $comment["uid"] . '">' . $comment_username . '</a> - <time datetime="' . $year . '-' . $month . '-' . $day . 'T' . $hour . ':' . $minute . ':' . $second . '" pubdate>' . $month . '-' . $day . '-' .$year . ' - ' . $hour . ':' . $minute . ':' .$second . '</time></p>' . "\n";
		}
		DisplayContent($comment["content"], $comment["format"]);
		echo '<p class="reply"><a href="' . $ABSBASE . 'comment/' . $comment["id"] . '/add">Reply</a></p>' . "\n";
		echo '</div>' . "\n";

		//$comment=$mysql->query("SELECT id FROM comment WHERE parentthread='" . $mysql->real_escape_string($comment["id"]) . "' AND parentid='" . $mysql->real_escape_string($node["id"]) . "' ORDER BY created ASC");
		foreach($this->getComments($comment["id"], $node["id"]) as $row) {
		//while ($row=$comment->fetch_array(MYSQLI_ASSOC)) {
			if ($comment!==FALSE) {
				$this->loadComment($row["id"], $width+5);
			}
		}
	}

	/**
	 * Delete a comment
	 * 
	 * @param $cid Comment ID
	 */
	public function delete($cid) {
		if ( !is_numeric($cid) && strpbrk($cid, '.') ) {
			return false;
		}

		global $dbh, $deleteCommentStatement;
		if (is_null($deleteCommentStatement)) {
			$deleteCommentStatement = $dbh->prepare("DELETE FROM comment WHERE id = ?");
		}
		$deleteCommentStatement->execute(array($cid));

	}

}

class NappalForum {
	public function __construct() { /* We don't need anything here for now */ }

	/**
	 * Add a new forum post
	 *
	 * @param mixed $content Body of new forum post
	 * @param mixed $title Title of new forum post
	 * @param mixed $uid Creator UID
	 * @param mixed $format Input type
	 * @param mixed $comments 1 or 0 boolean value for enabling comments
	 * @param mixed $status Active, locked, etc
	 * @param mixed $created YmdHis of created time
	 * @param mixed $timestamp YmdHis of last new comment post time
	 * @param mixed $forum Forum category
	 */
	public function addForumPost($content, $title, $uid, $format, $comments, $status, $created, $timestamp, $forum) {
		global $dbh, $addForumPostStatement;
	
		if (is_null($addForumPostStatement)) {
			$addForumPostStatement = $dbh->prepare("INSERT INTO node (content, title, uid, format, type, comments, status, created, timestamp, forum) VALUES (:content, :title, :uid, :format, :type, :comments, :status, :created, :timestamp, :forum)");
		}
		$addForumPostStatement->execute(array(
			':content' => $content,
			':title' => $title,
			':uid' => $uid,
			':format' => $format,
			':type' => 'forum',
			':comments' => $comments,
			':status' => $status,
			':created' => $created,
			':timestamp' => $timestamp,
			':forum' => $forum)
		);
	}

	/**
	 * Update a forum post
	 *
	 * @param mixed $nid Node's ID
	 * @param mixed $content Body of updated forum post
	 * @param mixed $title Title of updated forum post
	 * @param mixed $modified YmdHis of time
	 */
	public function editForumPost($nid, $content, $title, $modified) {
		global $dbh, $editForumPostStatement;
	
		if (is_null($editForumPostStatement)) {
			$editForumPostStatement = $dbh->prepare("UPDATE node SET content=?, title=?, type='forum', modified=? WHERE id=?");
		}
		$editForumPostStatement->execute(array($content,$title,$modified,$nid));
	}

	public function getForumContainer($sort) {
		global $dbh, $getForumContainerStatement;
		if (is_null($getForumContainerStatement)) {
			$getForumContainerStatement = $dbh->prepare("SELECT * FROM forum WHERE parent is null AND (url=? OR sort=?)");
		}
		$getForumContainerStatement->execute( array($sort, $sort) );
		$container = $getForumContainerStatement->fetch(PDO::FETCH_ASSOC);
		return $container;
	}

	public function getForum($container, $sort) {
		global $dbh, $getForumStatement;
		if (is_null($getForumStatement)) {
			$getForumStatement = $dbh->prepare("SELECT * FROM forum WHERE parent=? AND (url=? OR sort=?)");
		}
		$getForumStatement->execute( array($container, $sort, $sort) );
		$forum = $getForumStatement->fetch(PDO::FETCH_ASSOC);
		return $forum;
	}

	public function getForums($container) {
		global $dbh, $getForumsStatement;
		if (is_null($getForumsStatement)) {
			$getForumsStatement = $dbh->prepare("SELECT * FROM forum WHERE parent=? ORDER BY sort ASC");
		}
		$getForumsStatement->execute( array($container) );
		$forum = $getForumsStatement->fetchAll(PDO::FETCH_ASSOC);
		return $forum;
	}

	public function getTopicsID($forum) {
		global $dbh, $getTopicsIDStatement;
		if (is_null($getTopicsIDStatement)) {
			$getTopicsIDStatement = $dbh->prepare("SELECT id FROM node WHERE type='forum' AND (status='active' OR status='locked') AND forum=?");
		}
		$getTopicsIDStatement->execute( array($forum) );
		return $getTopicsIDStatement->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getTopics($fid) {
		global $dbh, $getTopicsStatement;
		if (is_null($getTopicsStatement)) {
			$getTopicsStatement = $dbh->prepare("SELECT * FROM node WHERE type='forum' AND (status='active' OR status='locked') AND forum=? ORDER BY timestamp DESC");
		}
		$getTopicsStatement->execute( array($fid) );
		return $getTopicsStatement->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getAllContainers() {
		global $dbh, $getAllContainersStatement;
		if (is_null($getAllContainersStatement)) {
			$getAllContainersStatement = $dbh->prepare("SELECT * FROM forum WHERE parent IS NULL ORDER BY sort ASC");
		}
		$getAllContainersStatement->execute();
		$all_containers = $getAllContainersStatement->fetchAll(PDO::FETCH_ASSOC);
		return $all_containers;
	}
	
	public function getForumTopicAmount($fid) {
		global $dbh, $getForumTopicAmountStatement;
		if (is_null($getForumTopicAmountStatement)) {
			//$getForumTopicAmountStatement = $dbh->prepare("SELECT id FROM node WHERE type='forum' AND (status='active' OR status='locked') AND forum=?");
			$getForumTopicAmountStatement = $dbh->prepare("SELECT COUNT(*) FROM node WHERE type='forum' AND (status='active' OR status='locked') AND forum=?");
		}
		$getForumTopicAmountStatement->execute( array($fid) );
		return $getForumTopicAmountStatement->fetchColumn();
	}

	public function getNewestNode($fid) {
		global $dbh, $getNewestNodeStatement;
		if (is_null($getNewestNodeStatement)) {
			$getNewestNodeStatement = $dbh->prepare("SELECT uid,created FROM node WHERE forum=:fid AND created = (SELECT MAX(created) FROM node WHERE forum=:fid)");
		}
		$getNewestNodeStatement->execute( array('fid' => $fid) );
		return $getNewestNodeStatement->fetch(PDO::FETCH_ASSOC);
	}

}

class NappalBlog {
	public function __construct() { /* We don't need anything here for now */ }

	/**
	 * Add a new Blog
	 *
	 * @param mixed $content Body of new blog
	 * @param mixed $title Title of new blog
	 * @param mixed $uid Creator UID
	 * @param mixed $format Input type
	 * @param mixed $comments 1 or 0 boolean value for enabling comments
	 * @param mixed $status Active, locked, etc
	 * @param mixed $created YmdHis of created time
	 * @param mixed $timestamp YmdHis of last new comment post time
	 */
	public function addBlog($content, $title, $uid, $format, $comments, $status, $created, $timestamp) {
		global $dbh, $addBlogStatement;
	
		if (is_null($addBlogStatement)) {
			$addBlogStatement = $dbh->prepare("INSERT INTO node (content, title, uid, format, type, comments, status, created, timestamp) VALUES (:content, :title, :uid, :format, :type, :comments, :status, :created, :timestamp)");
		}
		$addBlogStatement->execute(array(
			':content' => $content,
			':title' => $title,
			':uid' => $uid,
			':format' => $format,
			':type' => 'blog',
			':comments' => $comments,
			':status' => $status,
			':created' => $created,
			':timestamp' => $timestamp)
		);
	}

	/**
	 * Update a blog
	 *
	 * @param mixed $nid Node's ID
	 * @param mixed $content Body of updated blog
	 * @param mixed $title Title of updated blog
	 * @param mixed $modified YmdHis of time
	 */
	public function editBlog($nid, $content, $title, $modified) {
		global $dbh, $editBlogStatement;
	
		if (is_null($editBlogStatement)) {
			$editBlogStatement = $dbh->prepare("UPDATE node SET content=?, title=?, type='blog', modified=? WHERE id=?");
		}
		$editBlogStatement->execute(array($content,$title,$modified,$nid));
	}

	public function getBlogPosts() {
		global $dbh, $getBlogPostsStatement;
		if (is_null($getBlogPostsStatement)) {
			$getBlogPostsStatement = $dbh->prepare("SELECT * FROM node WHERE type='blog' AND (status='active' OR status='locked') ORDER BY timestamp ASC");
		}
		$getBlogPostsStatement->execute();
		return $getBlogPostsStatement->fetchAll(PDO::FETCH_ASSOC);
	}
}

?>