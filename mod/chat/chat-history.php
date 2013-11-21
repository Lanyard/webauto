<?php
	require_once "../../config.php";
	require_once $CFG->dirroot."/db.php";
	require_once $CFG->dirroot."/lib/lti_util.php";

	session_start();
	header('Content-type: application/json');

	// Sanity checks
	$LTI = $_SESSION['lti'];
	if ( !isset($LTI['user_id']) || !isset($LTI['link_id']) ) {
		die('A user_id and link_id are required for this tool to function.');
	}

	// Add chat history to the json
	$history = array();

	$p = $CFG->dbprefix;

	// Retrieve chat history from the database
	$stmt = $db->query("SELECT message, message_time, displayname FROM {$p}chat 
	 JOIN {$p}lti_user ON {$p}chat.user_id = {$p}lti_user.user_id ORDER BY message_time ASC");

	// Create the json
	while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
		$messageTimeObj = new DateTime($row['message_time']);
		$messageTime = $messageTimeObj->format('m/d h:i a');
		$history [] = array(
			'displayname' => $row['displayname'],
			'messageTime' => $messageTime,
			'message' => $row['message']
		);
	}

	echo(json_encode($history));
?>