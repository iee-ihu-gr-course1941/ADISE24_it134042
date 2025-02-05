<?php
session_start();
include 'db_connection.php';

function joinGame($player_id) {
    $conn = openDatabaseConnection();

    // Check gia games
    $stmt = $conn->prepare("SELECT id, player1_id, player2_id FROM games WHERE status = 'open' LIMIT 1");
    $stmt->execute();
    $stmt->bind_result($game_id, $player1_id, $player2_id);

    if ($stmt->fetch()) {
        $stmt->close();

        //  player1
        if ($player1_id === NULL) {
            $stmt = $conn->prepare("UPDATE games SET player1_id = ?, active_player = ? WHERE id = ?");
            $stmt->bind_param("iii", $player_id, $player_id, $game_id);
            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();
                return ["message" => "Player1 joined game", "game_id" => $game_id];
            } else {
                $stmt->close();
                $conn->close();
                return ["message" => "Error: " . $stmt->error];
            }
        } elseif ($player2_id === NULL) {
            // player2
            $stmt = $conn->prepare("UPDATE games SET player2_id = ?, status = 'active' WHERE id = ?");
            $stmt->bind_param("ii", $player_id, $game_id);
            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();
                return ["message" => "Player2 joined game, game is now active", "game_id" => $game_id];
            } else {
                $stmt->close();
                $conn->close();
                return ["message" => "Error: " . $stmt->error];
            }
        } else {
            $stmt->close();
            $conn->close();
            return ["message" => "Error: Game already has two players"];
        }
    } else {
        $stmt->close();

        // an dn iparxoyn games kanoyme kainourio
        $stmt = $conn->prepare("INSERT INTO games (player1_id, active_player) VALUES (?, ?)");
        $stmt->bind_param("ii", $player_id, $player_id);
        if ($stmt->execute()) {
            $game_id = $stmt->insert_id;
            $stmt->close();
            
            // new gameboard
            $stmt = $conn->prepare("CALL InitNewGameBoard(?)");
            $stmt->bind_param("i", $game_id);
            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();
                return ["message" => "Player joined new game as Player1 and game board initialized.", "game_id" => $game_id];
            } else {
                $stmt->close();
                $conn->close();
                return ["message" => "Error: " . $stmt->error];
            }
        } else {
            return ["message" => "Error: Game could not be joined", "details" => $stmt->error];
        }
    }

}

header('Content-Type: application/json');
$player_id = isset($_GET['player_id']) ? intval($_GET['player_id']) : 0;
$response = joinGame($player_id);
echo json_encode($response);
?>
