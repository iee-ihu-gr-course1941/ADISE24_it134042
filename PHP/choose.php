<?php
session_start();
include 'db_connection.php';

//  timeout  2m
set_time_limit(120);

function validateActivePlayer($game_id) {
    $conn = openDatabaseConnection();
    
    $stmt = $conn->prepare("SELECT active_player FROM games WHERE id = ?");
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $stmt->bind_result($active_player);
    $stmt->fetch();
    $stmt->close();
    
    $conn->close();
    return $active_player; 
}

function choose($game_id, $choices) {
    $active_player = validateActivePlayer($game_id);
    
    if ($active_player === null) {
        return ["message" => "Error: Δεν υπάρχει ενεργός παίκτης!"];
    }
    
    $conn = openDatabaseConnection();
    // update colum
    $updated_columns = [];
    foreach ($choices as $choice) {
        if ($choice == 0) {
            continue; // skip 0 
        }
        
        $stmt = $conn->prepare("
            UPDATE game_boards 
            SET active_player_progress = active_player_progress + 1 
            WHERE game_id = ? AND column_number = ?
        ");
        $stmt->bind_param("ii", $game_id, $choice);
        
        if ($stmt->execute()) {
            $updated_columns[] = $choice;
        } else {
            $stmt->close();
            return ["message" => "Error: " . $stmt->error];
        }
        $stmt->close();
    }

    $conn->close();
    return ["message" => "Progress updated for columns: " . implode(", ", $updated_columns)];
}

function parseChoices($selection, $valid_combinations) {
    switch ($selection) {
        case 1:
            return $valid_combinations[0];
        case 2:
            return $valid_combinations[1];
        case 3:
            return $valid_combinations[2];
        default:
            return [];
    }
}

header('Content-Type: application/json');
$game_id = isset($_GET['game_id']) ? intval($_GET['game_id']) : 0;
$valid_combinations = isset($_GET['valid_combinations']) ? json_decode($_GET['valid_combinations'], true) : [];

// choices
$options = [
    1 => $valid_combinations[0],
    2 => $valid_combinations[1],
    3 => $valid_combinations[2]
];

echo json_encode([
    "message" => "Please make your choice from the valid combinations below:",
    "options" => $options
]);


// apantisi k an dn apantisi timeout
$player_choice = isset($_GET['choice']) ? intval($_GET['choice']) : 0;
$choices = parseChoices($player_choice, $valid_combinations);

$response = choose($game_id, $choices);
echo json_encode($response);
?>
