<?php
function openDatabaseConnection() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "cant_stop_game";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    } else {
        echo "Connection successful!";
    }

    return $conn;
}

openDatabaseConnection();
?>
