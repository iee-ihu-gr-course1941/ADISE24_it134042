<?php
session_start();
include 'db_connection.php';

function initializePlayer($username) {
    $session_id = session_id();
    $conn = openDatabaseConnection();
    
    // Έλεγχος αν ο παίκτης υπάρχει ήδη
    $stmt = $conn->prepare("SELECT id FROM players WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        // Παίκτης υπάρχει ήδη
        $stmt->close();
        $conn->close();
        return ["message" => "Username already exists"];
    } else {
        // Εισαγωγή νέου παίκτη
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO players (username, session_id) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $session_id);
        
        if ($stmt->execute()) {
            $player_id = $conn->insert_id;
            $stmt->close();
            $conn->close();
            return ["message" => "Player initialized", "player_id" => $player_id];
        } else {
            $error_message = $stmt->error;
            $stmt->close();
            $conn->close();
            return ["message" => "Error: " . $error_message];
        }
    }
}

header('Content-Type: application/json');
$username = isset($_GET['username']) ? $_GET['username'] : 'playerUsername';
$response = initializePlayer($username);
echo json_encode($response);
?>
