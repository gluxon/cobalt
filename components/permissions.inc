<?php
class Permissions {

	private $database;
	private $dbho;

	public function __construct($database) {
		$this->database = $database;
		$this->dbho = $this->database;
	}

	public function hasPermission($user, $action) {
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
}
?>