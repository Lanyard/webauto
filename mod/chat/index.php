<?php
	require_once "../../config.php";
	require_once $CFG->dirroot."/db.php";
	require_once $CFG->dirroot."/lib/lti_util.php";
	require_once $CFG->dirroot."/lib/lms_lib.php";

	session_start();

	// Sanity checks
	if ( !isset($_SESSION['lti']) ) {
		die('This tool must be launched using LTI');
	}
	$LTI = $_SESSION['lti'];
	if ( !isset($LTI['user_id']) || !isset($LTI['link_id']) ) {
		die('A user_id and link_id are required for this tool to function.');
	}
	$p = $CFG->dbprefix;
	$instructor = isset($LTI['role']) && $LTI['role'] == 1;

	// Set timezone for time-collection
	date_default_timezone_set('America/New_York');

	if( isset($_POST['chat-submit']) ) {
	
		// When a message is sent, update the session's chat history
		if ( $_POST['chat-submit'] == "Chat" && isset($_POST['message']) ) {
			if( $_POST['message'] != '') {

				// Check post content for magic quotes setting before adding to the database
				if ( get_magic_quotes_gpc() ) {
						$message = stripslashes($_POST['message']);
					} else {
					$message = $_POST['message'];
				}

				$stmt = $db->prepare("INSERT INTO {$p}chat (user_id, message, message_time)
					VALUES (:UID, :MSG, NOW())");
				$stmt->execute(array(
					':UID' => $LTI['user_id'],
					':MSG' => $message
				));
			}
		}
	
		// If the instructor hit reset, clear the chat history
		elseif ( $_POST['chat-submit'] == "Reset" ) {
			$stmt = $db->prepare("TRUNCATE TABLE {$p}chat");
			$stmt->execute();
			header('Location: ' . sessionize('index.php'));
			return;
		}
	}
// View 
headerContent();
?>
</head>
<body>
	<h1>Chatroom</h1>
	<p><strong>Chatting as:</strong> <?php echo htmlentities($LTI['user_displayname'])?></p>
	<form method="post" action="index.php">
		<input type="text" name="message" size="60" />
		<input type="submit" name="chat-submit" value="Chat" />
		<?php
			if ($instructor === TRUE) {
				echo '<input type="submit" name="chat-submit" value="Reset" />';
			}
		?>
	</form>
	<div id="chat-content">
	</div>
	<script src="<?php echo($CFG->staticroot); ?>/static/js/jquery-1.10.2.min.js"></script>
    <script src="<?php echo($CFG->bootstrap); ?>/js/bootstrap.min.js"></script>
	<script type="text/javascript">
		//Equivalent of PHP's htmlentities for JavaScript
		function htmlEntities(str) {
		    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&apos;');
		}

		function updateMsg() {
			console.log("Made request.");

			// Retrieve the chat history using json.
			$.getJSON('<?php echo(sessionize('chat-history.php')); ?>', function(history) {

				console.log("Got data back: " + history);

				var historyHtml = '';
				for(var i = history.length - 1; i >= 0; i--) {
					historyHtml += '<p><strong>' + htmlEntities(history[i].displayname) + '</strong> - ' + history[i].messageTime + '<br />' + htmlEntities(history[i].message) + '</p>';
				}

				$("#chat-content").html(historyHtml);
				setTimeout('updateMsg()', 10000);

			});

			console.log("History updated."); 
		}
		$( document ).ready(function() {
			updateMsg();
		});
	</script>
</body>
</html>