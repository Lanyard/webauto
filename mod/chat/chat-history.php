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

	// Initialize the history json
	$history = array();

	// If we have a history, put it in the json
	$p = $CFG->dbprefix;
	if ( isset($_SESSION['history']) ) {

		// Get the display name so we can add it to the json.
		$stmt = $db->prepare("SELECT displayname FROM {$p}lti_user 
				WHERE  user_id = :UID");
		$stmt->execute(array(
			":UID" => $LTI['user_id']
		));

		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		// Create the json
		foreach ($_SESSION['history'] as $message) {
			$history [] = array(
				'displayname' => $row['displayname'],
				'messageTime' => $message['time'],
				'message' => $message['message']
			);
		}
	}

	echo(json_encode($history));
?>