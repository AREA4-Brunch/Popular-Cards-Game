<?php

$servername = "localhost";  // change if using online companies server
$dBUsername = "root";
$dBPassword = "";  // empty in XAMPP
$dBName = "qcardsdatabase";  // name of database in phpadmin

// connection
$conn = mysqli_connect($servername, $dBUsername, $dBPassword, $dBName);

// check if connection failed:
if (!$conn) {
    die("Connection failed ;( ".mysqli_connect_error());  // kill connection
}
