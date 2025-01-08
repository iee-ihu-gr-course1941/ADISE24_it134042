<?php
session_start();
include 'db_connection.php';


function validateActivePlayer($player_id, $game_id) {
    $conn = openDatabaseConnection();
    
    // Προετοιμασία του ερωτήματος για έλεγχο του ενεργού παίκτη
    $stmt = $conn->prepare("SELECT active_player FROM games WHERE id = ?");
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $stmt->bind_result($active_player);
    $stmt->fetch();
    $stmt->close();
    
    return ($player_id == $active_player);
}
//vale tin proodo stin stili toy paixti
function updateProgress($game_id, $active_player_column) {
    $conn = openDatabaseConnection();
    
    // Προετοιμασία του ερωτήματος για την ενημέρωση της προόδου του ενεργού παίκτη
    $stmt = $conn->prepare("
        UPDATE game_boards
        SET $active_player_column = $active_player_column + active_player_progress, 
            active_player_progress = 0
        WHERE game_id = ?
    ");
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $stmt->close();
}


function endTurn($game_id, $player_id) {
    // check active player
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
        
        // pios epeze
        $active_player_column = ($active_player == $player1_id) ? 'p1prog' : 'p2prog';
        
        
        updateProgress($game_id, $active_player_column);
        
        // prep check gia nikiti
        $stmt = $conn->prepare("
            SELECT $active_player_column 
            FROM game_boards 
            WHERE game_id = ?
        ");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $stmt->bind_result($current_progress);
        $stmt->fetch();
        $stmt->close();
            //an kerdise
        if ($current_progress >= 3) {
            $winner = ($active_player == $player1_id) ? "Player1" : "Player2";
            return ["message" => "$winner won!", "winner" => $winner];
        } else {
            // allagi paixti
            $new_active_player = ($active_player == $player1_id) ? $player2_id : $player1_id;
            $stmt = $conn->prepare("UPDATE games SET active_player = ? WHERE id = ?");
            $stmt->bind_param("ii", $new_active_player, $game_id);
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
$player_id = isset($_GET['player_id']) ? intval($_GET['player_id']) : 0;
$response = endTurn($game_id, $player_id);
echo json_encode($response);
?>
