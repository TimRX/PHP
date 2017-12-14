<?php
$servername = "";
$username = "";
$password = "";
$dbname = "";

// Create connection
$mysqli = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($mysqli->connect_error) {
	$response = array("Status" => "Connection failed: " . $mysqli->connect_error);
	die(json_encode($response));
}
// Set character set of DB
if (!$mysqli->set_charset("utf8")) {
	$response = array("Status" => "Error loading character set utf8");
	die(json_encode($response));
}
?>