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
    
    $conn->close();
    return $active_player; 
}

function choose($game_id, $choices, $player_id) {
    $active_player = validateActivePlayer($game_id);
    
    if ($player_id !== $active_player) {
        return ["message" => "Error: Δεν είστε ο ενεργός παίκτης."];
    }
    
    $conn = openDatabaseConnection();
    $updated_columns = [];
    foreach ($choices as $choice) {
        if ($choice == 0) {
            continue;
        }
        //+1 sto progress gia to noymero poy dialekse o paixtis
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
    //opou iparxi 0 vazoyme tn kainoyria stili poy anevenei o paixtis
    $stmt_col = $conn->prepare("SELECT column1, column2, column3 FROM active_column WHERE game_id = ?");
    $stmt_col->bind_param("i", $game_id);
    $stmt_col->execute();
    $stmt_col->bind_result($column1, $column2, $column3);
    $stmt_col->fetch();
    $stmt_col->close();

    foreach ($choices as $choice) {
        if ($column1 == 0 && !in_array($choice, [$column1, $column2, $column3])) {
            $stmt = $conn->prepare("UPDATE active_column SET column1 = ? WHERE game_id = ?");
            $stmt->bind_param("ii", $choice, $game_id);
            $stmt->execute();
            $stmt->close();
            $column1 = $choice;
        } elseif ($column2 == 0 && !in_array($choice, [$column1, $column2, $column3])) {
            $stmt = $conn->prepare("UPDATE active_column SET column2 = ? WHERE game_id = ?");
            $stmt->bind_param("ii", $choice, $game_id);
            $stmt->execute();
            $stmt->close();
            $column2 = $choice;
        } elseif ($column3 == 0 && !in_array($choice, [$column1, $column2, $column3])) {
            $stmt = $conn->prepare("UPDATE active_column SET column3 = ? WHERE game_id = ?");
            $stmt->bind_param("ii", $choice, $game_id);
            $stmt->execute();
            $stmt->close();
            $column3 = $choice;
        }
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
$player_id = isset($_GET['active_player']) ? intval($_GET['active_player']) : 0;
//sindiasmi
$options = [
    1 => $valid_combinations[0],
    2 => $valid_combinations[1],
    3 => $valid_combinations[2]
];
//epilogi paixti
if (!isset($_GET['choice'])) {
    echo json_encode([
        "message" => "Please make your choice from the valid combinations below:",
        "options" => $options
    ]);
    exit;
}

$choice = intval($_GET['choice']);
$choices = parseChoices($choice, $valid_combinations);
$response = choose($game_id, $choices, $player_id);
echo json_encode($response);
?>
