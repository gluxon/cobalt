<?php
// Generate a 403 or 404 page
function page_error($error_code) {
	global $RELBASE, $ABSBASE;
	echo "<div style=\"text-align:center;\">" . "\n";
	echo "<p>" . "\n";
	If ($error_code == '404') {
		echo "File or Page at http://$_SERVER[SERVER_NAME]$_SERVER[REQUEST_URI] was not found." . "\n";
		echo "</p>" . "\n";
		echo "<p>" . "\n";
		echo '<img alt="404" src="' . $ABSBASE . '404.png" />' . "\n";
	}
	If ($error_code == '403') {
		echo "You don't have permissions to access http://$_SERVER[SERVER_NAME]$_SERVER[REQUEST_URI]" . "\n";
		echo "</p>" . "\n";
		echo "<p>" . "\n";
		echo '<img alt="403" src="' . $ABSBASE . '403.png" />' . "\n";
	}
	echo "</p>" . "\n";
	echo "</div>" . "\n";
}

// Get Parent Thread
function GetParentThread($id) {
	/*global $mysql;
	while (1) {
		$comment=$mysql->query("SELECT * FROM comment WHERE id='" . $mysql->real_escape_string($id) . "'");
		If (!$comment===FALSE) $comment=$comment->fetch_array(MYSQLI_ASSOC);
		If (!is_numeric($comment["parenttype"])) {
			$thread=$mysql->query("SELECT * FROM node WHERE id='" . $mysql->real_escape_string($comment["parentid"]) . "'");
			If (!$thread===FALSE) $thread=$thread->fetch_array(MYSQLI_ASSOC);
			return $thread;
		}
		$id=$comment["parentid"];
	}
	*/
	// The above code was for the previous SQL Design.

	global $mysql;
	$comment=$mysql->query("SELECT parentid FROM comment WHERE id='" . $mysql->real_escape_string($id) . "'");
	If (!$comment===FALSE) $comment=$comment->fetch_array(MYSQLI_NUM);
	return $comment["0"];
}
// Get Comment Amount
function GetCommentAmount($id) {
	/*
	global $mysql;
	$amount="0";
	$comment=$mysql->query("SELECT id FROM comment WHERE parentthread='" . $mysql->real_escape_string($type) . "' AND parentid='" . $mysql->real_escape_string($id) . "'");
	While ($row=$comment->fetch_array(MYSQLI_ASSOC)) {
		$amount++; // Add 1 for this comment
		$add=GetCommentAmount($row["id"], "comment"); // Add whatever for child comments, and more child commands and more..
		$amount=$amount+$add;
	}
	return $amount;*/
	
	// The above code was for the previous SQL Design.
	global $mysql;
	$comment=$mysql->query("SELECT id FROM comment WHERE parentid='" . $mysql->real_escape_string($id) . "'");
	return $comment->num_rows;
}
// Load Comment
function LoadComment($id, $width) {
	global $user, $node, $mysql, $theme, $ABSBASE;
	$comment=$mysql->query("SELECT * FROM comment WHERE id='" . $mysql->real_escape_string($id) . "'");
	If ($comment!==FALSE) $comment=$comment->fetch_array(MYSQLI_ASSOC);
	$theme_ini=parse_ini_file("themes/$theme/theme.ini", TRUE);
	//$width=str_replace('%', '', $theme_ini["comments"]["width"]);
	$shrink=str_replace('%', '', $theme_ini["comments"]["shrink"]);

	echo '<div class="comment" style="margin-left: ' . $width . '% ;">';
	echo '<h2>' . $comment["title"] . '</h2>' . "\n";
	If (strpbrk($user["role"], 'admin') || strpbrk($user["role"], 'moderator') || $user["uid"]==$comment["uid"]) {
		echo '<div class="controls">' . "\n";
		echo '<div class="button edit">' . "\n";
		echo '<a href="' . $ABSBASE . 'comment/' . $comment["id"] . '/edit">' . "\n";
		echo '<img src="' . $ABSBASE . 'files/forum/edit.png" alt="Edit" />' . "\n";
		//echo '<div class="edit-text">Edit</div>' . "\n";
		echo '</a>' . "\n";
		echo '</div>' . "\n";
		echo '<div class="button delete">' . "\n";
		echo '<a href="' . $ABSBASE . 'comment/' . $comment["id"] . '/delete">' . "\n";
		echo '<img src="' . $ABSBASE . 'files/forum/delete.png" alt="Delete" />' . "\n";
		//echo '<div class="edit-text">Delete</div>' . "\n";
		echo '</a>' . "\n";
		echo '</div>' . "\n";
		echo '</div>' . "\n";
	}
	If (!empty($comment["created"])) {
		$year=substr($comment["created"], 0, 4);
		$month=substr($comment["created"], 4, 2);
		$day=substr($comment["created"], 6, 2);
		$hour=substr($comment["created"], 8, 2);
		$minute=substr($comment["created"], 10, 2);
		$second=substr($comment["created"], 12, 2);
		$ouser=$mysql->query("SELECT username FROM users WHERE uid='" . $comment["uid"] . "'");
		$ouser=$ouser->fetch_array(MYSQLI_NUM);
		echo '<p class="meta"><a href="' . $ABSBASE . 'user/' . $comment["uid"] . '">' . $ouser["0"] . '</a> - <time datetime="' . $year . '-' . $month . '-' . $day . 'T' . $hour . ':' . $minute . ':' . $second . '" pubdate>' . $month . '-' . $day . '-' .$year . ' - ' . $hour . ':' . $minute . ':' .$second . '</time></p>' . "\n";
	}
	DisplayContent($comment["content"], $comment["format"]);
	echo '<p class="reply"><a href="' . $ABSBASE . 'comment/' . $comment["id"] . '/add">Reply</a></p>' . "\n";
	echo '</div>' . "\n";
	$comment=$mysql->query("SELECT id FROM comment WHERE parentthread='" . $mysql->real_escape_string($comment["id"]) . "' AND parentid='" . $mysql->real_escape_string($node["id"]) . "' ORDER BY created ASC");
	While ($row=$comment->fetch_array(MYSQLI_ASSOC)) {
		If ($comment!==FALSE) {
			LoadComment($row["id"], $width+5);
		}
	}
}
// Function to check if a table exists
function table_exists($mysql, $table_name) {
	$result=$mysql->query("SHOW TABLES LIKE '" . $table_name . "'");
	If ($result->fetch_array() != NULL) {
		$result->free();
		return true;
	}
	$result->free();
	return false;
}
// DisplayContent
function DisplayContent($content, $format) {
	include("includes/content/$format.php");
}
// Function to display Search block in desired area
function Search() {
	echo '<h2>Search</h2>' . "\n";
	echo '<div class="sidebar-block center">' . "\n";
	echo '<form action="" method="post">' . "\n";
	echo '<input type="text" size="15" name="search" />' . "\n";
	echo '<input type="submit" value="Search" />' . "\n";
	echo '</form>' . "\n";
	echo '</div>' . "\n";
}

// Function to display Login block in desired area
function AccountLogin() {
	global $mysql, $ABSBASE, $current_user;
	If (!empty($current_user)) {
		echo '<h2>' . $current_user["username"] . '</h2>';
		echo '<ul>';
		echo '<li><a href="' . $ABSBASE . 'user/' . $current_user["uid"]. '">My Account</a></li>';
		If (strpbrk($current_user["role"], 'admin')) {
			echo '<li><a href="' . $ABSBASE . 'admin">Administer</a></li>';
		}
		echo '<li><a href="' . $ABSBASE . 'user/logout">Logout</a></li>';
		echo '</ul>';
	}
	Else {
		echo '<h2>Account</h2>';
		echo '<div class="login-block">';
		echo '<form action="' . $_SERVER["REQUEST_URI"] . '" method="post">';
		echo 'Username:<br /><input type="text" size="15" name="username" /><br />';
		echo 'Password:<br /><input type="password" size="15" name="password" /><br />';
		echo '<input type="submit" value="Log In" />';
		echo '</form>';
		echo '<p class="sidebar-paragraph" style="font-size: 12px;"><a href="' . $ABSBASE . 'user/register"><i>Register</i></a> / <a href="' . $ABSBASE . 'user/password"><i>Forgot Password?</i></a></p>';
		echo '</div>';
	}
}
?>