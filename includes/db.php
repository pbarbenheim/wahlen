<?php
require_once "config.php";

$servername = "db:3306";
$username = "root";
$password = "root";

$DBCONN = new mysqli($servername, $username, $password);
if ($DBCONN->connect_error) {
    die("Connection failed: " . $DBCONN->connect_error);
}
$DBCONN->set_charset("utf8mb4");

// Check if the database exists
$db_check = $DBCONN->query("SHOW DATABASES LIKE 'wahlen_tool'");
if ($db_check->num_rows == 0) {
    // Database does not exist, create it
    $create_db = $DBCONN->query("CREATE DATABASE wahlen_tool");
    if (!$create_db) {
        die("Error creating database: " . $DBCONN->error);
    }
    $DBCONN->select_db("wahlen_tool");
    $initDB = include "init_db.php";
    $initDB($DBCONN);
} else {
    $DBCONN->select_db("wahlen_tool");
}
return $DBCONN;