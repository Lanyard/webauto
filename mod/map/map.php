<?php
require_once "../../config.php";
require_once $CFG->dirroot."/db.php";
require_once $CFG->dirroot."/lib/lti_util.php";

session_start();

// Sanity checks
if ( !isset($_SESSION['lti']) ) {
	die('This tool needs to be launched using LTI');
}
$LTI = $_SESSION['lti'];
if ( !isset($LTI['user_id']) || !isset($LTI['context_id']) ) {
	die('A user_id and context_id are required for this tool to function.');
}
$instructor = isset($LTI['role']) && $LTI['role'] == 1 ;

$p = $CFG->dbprefix;
if ( isset($_POST['lat']) && isset($_POST['lng']) ) {
	$sql = "INSERT INTO {$p}context_map 
		(context_id, user_id, lat, lng, updated_at) 
		VALUES ( :CID, :UID, :LAT, :LNG, NOW() ) 
		ON DUPLICATE KEY 
		UPDATE lat = :LAT, lng = :LNG, updated_at = NOW()";
	$stmt = $db->prepare($sql);
	$stmt->execute(array(
		':CID' => $LTI['context_id'],
		':UID' => $LTI['user_id'],
		':LAT' => $_POST['lat'],
		':LNG' => $_POST['lng']));
    $_SESSION['success'] = 'Location updated...';
	header( 'Location: '.sessionize('map.php') ) ;
	return;
}

// Retrieve our row
$stmt = $db->prepare("SELECT lat,lng FROM {$p}context_map 
		WHERE context_id = :CID AND user_id = :UID");
$stmt->execute(array(":CID" => $LTI['context_id'], ":UID" => $LTI['user_id']));
$row = $stmt->fetch(PDO::FETCH_ASSOC);
// The default for latitude and longitude
$lat = 42.279070216140425;
$lng = -83.73981015789798;
if ( $row !== false ) {
	$lat = $row['lat'];
	$lng = $row['lng'];
}

//Retrieve the other rows
$stmt = $db->prepare("SELECT lat,lng,displayname FROM {$p}context_map 
		JOIN {$p}lti_user
		ON {$p}context_map.user_id = {$p}lti_user.user_id
		WHERE context_id = :CID AND {$p}context_map.user_id <> :UID");
$stmt->execute(array(":CID" => $LTI['context_id'], ":UID" => $LTI['user_id']));
$points = array();
$names = array();
while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
	$points[] = array($row['lat']+0.0,$row['lng']+0.0);
  $names[] = array($row['displayname']);
}

?>
<html><head><title>Map for 
<?php echo($LTI['context_title']); ?>
</title>
<script src="//maps.googleapis.com/maps/api/js?v=3.exp&sensor=false"></script>
<script type="text/javascript">

//Equivalent of PHP's htmlentities for JavaScript
function htmlEntities(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

var map;

// https://developers.google.com/maps/documentation/javascript/reference
function initialize_map() {
  var myLatlng = new google.maps.LatLng(<?php echo($lat.", ".$lng); ?>);
  window.console && console.log("Building map...");

  var myOptions = {
     zoom: 2,
     center: myLatlng,
     mapTypeId: google.maps.MapTypeId.ROADMAP
  }

  map = new google.maps.Map(document.getElementById("map_canvas"), myOptions); 

  var currentMarker = new google.maps.Marker({
    draggable: true,
    position: myLatlng, 
    map: map,
    title: <?php echo('"'.htmlentities($_SESSION['lti']['user_displayname']).'"'); ?>
  });

  google.maps.event.addListener(currentMarker, 'dragend', function (event) {
    var lat = currentMarker.getPosition().lat();
    var lng = currentMarker.getPosition().lng();
	// getPosition returns a google.maps.LatLng class for
	// for the dropped marker
	window.console && console.log(this.getPosition());
    window.console && console.log('lat = ' + lat + '; lng = ' + lng);
	// TODO: Fix these next two lines - search the web for a solution
    document.getElementById("latbox").value = lat;
    document.getElementById("lngbox").value = lng;
  });

  // Add the other points
  window.console && console.log("Loading "+other_points.length+" points");
  for ( var i = 0; i < other_points.length; i++ ) {
  	var row = other_points[i];
    var name = other_names[i].toString();
  	// if ( i < 3 ) { alert(row); }
  	var newLatlng = new google.maps.LatLng(row[0], row[1]);
  	var iconpath = '<?php echo($CFG->staticroot); ?>/static/img/icons/';
  	var icon = 'green.png';
    var marker = new google.maps.Marker({
      position: newLatlng,
      map: map,
      icon: iconpath + icon,
      // TODO: See if you can get the user's displayname here
      title: htmlEntities(name)
     });
  }
}

// Load the other points 
other_points = 
<?php echo( json_encode($points));?> 
;
other_names = 
<?php echo( json_encode($names));?>
;
</script>
</head>
<body style="font-family: sans-serif;" onload="initialize_map();">
<?php
if ( isset($_SESSION['error']) ) {
    echo '<p style="color:red">'.$_SESSION['error']."</p>\n";
    unset($_SESSION['error']);
}
if ( isset($_SESSION['success']) ) {
    echo '<p style="color:green">'.$_SESSION['success']."</p>\n";
    unset($_SESSION['success']);
}

?>
<div id="map_canvas" style="margin: 10px; width:500px; max-width: 100%; height:500px"></div>
<form method="post">
 Latitude: <input size="30" type="text" id="latbox" name="lat" 
  <?php echo(' value="'.htmlent_utf8($lat).'" '); ?> >
 Longitude: <input size="30" type="text" id="lngbox" name="lng"
  <?php echo(' value="'.htmlent_utf8($lng).'" '); ?> >
 <button type="submit">Save Location</button>
</form>
<?php

echo("<p>Here is the session information:\n<pre>\n");
var_dump($_SESSION);
echo("\n</pre>\n");
?>