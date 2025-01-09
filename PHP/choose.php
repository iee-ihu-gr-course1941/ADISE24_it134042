<?php
session_start();
include 'db_connection.php';

function validateActivePlayer($game_id) {
    $conn = openDatabaseConnection();
    
    $stmt = $conn->prepare("SELECT active_player FROM games WHERE id = ?");
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $stmt->bind_result($active_player);
    $stmt->fetch();
    $stmt->close();
    
    return $active_player; 
}

function choose($game_id, $choices) {
    $active_player = validateActivePlayer($game_id);
    
    if ($active_player === null) {
        return ["message" => "Error: Δεν υπάρχει ενεργός παίκτης!"];
    }
    
    $conn = openDatabaseConnection();
    //gia kathe noymero kanyome update ton pinaka me +1
    $updated_columns = [];
    foreach ($choices as $choice) {
        $stmt = $conn->prepare("
            UPDATE game_boards 
            SET active_player_progress = active_player_progress + 1 
            WHERE game_id = ? AND column_number = ?
        ");
        $stmt->bind_param("ii", $game_id, $choice);
        
        if ($stmt->execute()) {
            $updated_columns[] = $choice;
            $stmt->close();
        } else {
            return ["message" => "Error: " . $stmt->error];
        }
    }

    $conn->close();
    return ["message" => "Progress updated for columns: " . implode(", ", $updated_columns)];
}

header('Content-Type: application/json');
$game_id = isset($_GET['game_id']) ? intval($_GET['game_id']) : 0;
$choices = isset($_GET['choices']) ? explode(",", $_GET['choices']) : [];
$response = choose($game_id, $choices);
echo json_encode($response);
?>
