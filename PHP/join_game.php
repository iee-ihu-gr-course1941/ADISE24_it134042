<?php
session_start();
include 'db_connection.php';

function joinGame($player_id) {
    $conn = openDatabaseConnection();
    
    // Αναζήτηση παιχνιδιού
    $stmt = $conn->prepare("SELECT id, player1_id, player2_id FROM games WHERE status = 'open' LIMIT 1");
    $stmt->execute();
    $stmt->bind_result($game_id, $player1_id, $player2_id);
    
    if ($stmt->fetch()) {
        if (is_null($player1_id)) {
            // Αν δεν υπάρχει player1 μπαίνει εκεί
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
            // Αν δεν υπάρχει player2 μπαίνει εκεί και status σε active
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
            return ["message" => "Error: Game already has two players"];
        }
    } else {
        // Αν δεν υπάρχει παιχνίδι δημιούργησε καινούριο
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO games (status, player1_id) VALUES ('open', ?)");
        $stmt->bind_param("i", $player_id);
        if ($stmt->execute()) {
            $game_id = $conn->insert_id;

            // αρχικοποίηση νέου πίνακα παιχνιδιού
            $stmt = $conn->prepare("CALL InitNewGameBoard(?)");
            $stmt->bind_param("i", $game_id);
            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();
                return ["message" => "Player joined new game and board initialized", "game_id" => $game_id];
            } else {
                return ["message" => "Error: " . $stmt->error];
            }
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
