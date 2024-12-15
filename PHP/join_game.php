<?php
session_start();
include 'db_connection.php';

function joinGame($player_id) {
    $conn = openDatabaseConnection();
    
    // Αναζήτηση για ανοιχτό παιχνίδι
    $stmt = $conn->prepare("SELECT id, player1_id, player2_id FROM games WHERE status = 'open' LIMIT 1");
    $stmt->execute();
    $stmt->bind_result($game_id, $player1_id, $player2_id);
    
    if ($stmt->fetch()) {
        if (is_null($player1_id)) {
            // Αν το player1_id είναι κενό, προσθέστε τον παίκτη ως player1
            $stmt->close();
            $stmt = $conn->prepare("UPDATE games SET player1_id = ? WHERE id = ?");
            $stmt->bind_param("ii", $player_id, $game_id);
            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();
                return ["message" => "Player1 joined game", "game_id" => $game_id];
            } else {
                return ["message" => "Error: " . $stmt->error];
            }
        } elseif (is_null($player2_id)) {
            // Αν το player2_id είναι κενό, προσθέστε τον παίκτη ως player2 και αλλάξτε το status σε active
            $stmt->close();
            $stmt = $conn->prepare("UPDATE games SET player2_id = ?, status = 'active' WHERE id = ?");
            $stmt->bind_param("ii", $player_id, $game_id);
            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();
                return ["message" => "Player2 joined game, game is now active", "game_id" => $game_id];
            } else {
                return ["message" => "Error: " . $stmt->error];
            }
        } else {
            $stmt->close();
            return ["message" => "Error: Game already has two players"];
        }
    } else {
        // Αν δεν υπάρχει ανοιχτό παιχνίδι, δημιουργήστε ένα νέο
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO games (status, player1_id) VALUES ('open', ?)");
        $stmt->bind_param("i", $player_id);
        if ($stmt->execute()) {
            $game_id = $conn->insert_id;
            $stmt->close();
            $conn->close();
            return ["message" => "Player joined new game", "game_id" => $game_id];
        } else {
            return ["message" => "Error: " . $stmt->error];
        }
    }
}

header('Content-Type: application/json');
$player_id = isset($_GET['player_id']) ? intval($_GET['player_id']) : 0;
$response = joinGame($player_id);
echo json_encode($response);
?>
