<?php
session_start();
include 'db_connection.php';

function validateActivePlayer($player_id, $game_id) {
    $conn = openDatabaseConnection();
    
    $stmt = $conn->prepare("SELECT active_player FROM games WHERE id = ?");
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $stmt->bind_result($active_player);
    $stmt->fetch();
    $stmt->close();
    
    return ($player_id == $active_player);
}

function updateProgress($game_id, $active_player_column) {
    $conn = openDatabaseConnection();
    
    $stmt = $conn->prepare("SELECT active_player_progress FROM game_boards WHERE game_id = ?");
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $stmt->bind_result($active_player_progress);
    $stmt->fetch();
    $stmt->close();
    
//tin proodo tou giro ston paixti
    $stmt = $conn->prepare("
        UPDATE game_boards
        SET $active_player_column = $active_player_column + ?, 
            active_player_progress = 0 
        WHERE game_id = ?
    ");
    $stmt->bind_param("ii", $active_player_progress, $game_id);
    $stmt->execute();
    $stmt->close();
}

function checkWinCondition($game_id, $active_player_column) {
    $conn = openDatabaseConnection();

    $stmt = $conn->prepare("
        SELECT SUM(CASE WHEN p1prog >= 3 THEN 1 ELSE 0 END +
                   CASE WHEN p2prog >= 3 THEN 1 ELSE 0 END +
                   CASE WHEN active_player_progress >= 3 THEN 1 ELSE 0 END) as progress_count
        FROM game_boards 
        WHERE game_id = ?
    ");
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $stmt->bind_result($progress_count);
    $stmt->fetch();
    $stmt->close();

    return ($progress_count >= 3);
}

function endTurn($game_id, $player_id) {
    if (!validateActivePlayer($player_id, $game_id)) {
        return ["message" => "Error: Δεν είστε ο ενεργός παίκτης. Περιμένετε τον γύρο σας."];
    }
    
    $conn = openDatabaseConnection();
    
    $stmt = $conn->prepare("
        SELECT player1_id, player2_id, active_player 
        FROM games 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $stmt->bind_result($player1_id, $player2_id, $active_player);
    
    if ($stmt->fetch()) {
        $stmt->close();
        
        $active_player_column = ($active_player == $player1_id) ? 'p1prog' : 'p2prog';
        
        // proodo tou paixti
        $stmt = $conn->prepare("
            SELECT active_player_progress 
            FROM game_boards 
            WHERE game_id = ?
        ");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $stmt->bind_result($active_player_progress);
        $stmt->fetch();
        $stmt->close();
        
        // update p*prog
        updateProgress($game_id, $active_player_column, $active_player_progress);
        
        // elenxos gia win 
        if (checkWinCondition($game_id, $active_player_column)) {
            $stmt = $conn->prepare("UPDATE games SET status = 'finished' WHERE id = ?");
            $stmt->bind_param("i", $game_id);
            $stmt->execute();
            $stmt->close();
            
            $winner = ($active_player == $player1_id) ? "Player1" : "Player2";
            return ["message" => "$winner κέρδισε!", "winner" => $winner];
        } else {
            //allios change turn
            $new_active_player = ($active_player == $player1_id) ? $player2_id : $player1_id;
            $stmt = $conn->prepare("UPDATE games SET active_player = ? WHERE id = ?");
            $stmt->bind_param("ii", $new_active_player, $game_id);
            if ($stmt->execute()) {
                $stmt->close();
                $conn->close();
                return ["message" => "Turn completed. Active player switched", "active_player"  => $new_active_player];
            } else{
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
$player_id = isset($_GET['player_id']) ? intval($_GET['player_id']) : 0;
$response = endTurn($game_id, $player_id);
echo json_encode($response);
?>
