<?php
session_start();
include 'db_connection.php';

function endTurn($game_id) {
    $conn = openDatabaseConnection();
    
    // Έλεγχος προόδου
    $stmt = $conn->prepare("
        SELECT 
            CheckPlayerProgress(player1_id, ?) AS player1_prog,
            CheckPlayerProgress(player2_id, ?) AS player2_prog,
            active_player
        FROM games
        WHERE id = ?
    ");
    $stmt->bind_param("iii", $game_id, $game_id, $game_id);
    $stmt->execute();
    $stmt->bind_result($player1_prog, $player2_prog, $active_player);

    if ($stmt->fetch()) {
        $stmt->close();
        //check αν κερδισε καποιος
        if ($player1_prog >= 3) {
            return ["message" => "Player1 won!", "winner" => "Player1"];
        } elseif ($player2_prog >= 3) {
            return ["message" => "Player2 won!", "winner" => "Player2"];
        } else {
            // Αλλαγή  παίκτη
            $new_active_player = ($active_player == "player1_id") ? "player2_id" : "player1_id";
            $stmt = $conn->prepare("UPDATE games SET active_player = ? WHERE id = ?");
            $stmt->bind_param("si", $new_active_player, $game_id);
            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();
                return ["message" => "Turn completed. Active player switched", "active_player" => $new_active_player];
            } else {
                return ["message" => "Error: " . $stmt->error];
            }
        }
    } else {
        $stmt->close();
        $conn->close();
        return ["message" => "Game not found"];
    }
}

header('Content-Type: application/json');
$game_id = isset($_GET['game_id']) ? intval($_GET['game_id']) : 0;
$response = endTurn($game_id);
echo json_encode($response);
?>">
