<?php
session_start();
include 'db_connection.php';

// Function to validate the active player
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

// Ρολάρισμα Ζαριών
function rollDice() {
    $dice = [
        rand(1, 6), //d1
        rand(1, 6), //d2
        rand(1, 6), //d3
        rand(1, 6)//d4
    ];

    $combinations = [
        [$dice[0] + $dice[1], $dice[2] + $dice[3]],//p1
        [$dice[0] + $dice[2], $dice[1] + $dice[3]],//p2
        [$dice[0] + $dice[3], $dice[1] + $dice[2]]//p3
    ];

    return $combinations;
}

function validateColumns($game_id, $combinations) {//protos elenxos an einai 0 sta active colum i iparxi ekei pernaei
    $conn = openDatabaseConnection();
    
    $stmt = $conn->prepare("SELECT column1, column2, column3 FROM active_column WHERE game_id = ?");
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $stmt->bind_result($column1, $column2, $column3);
    $stmt->fetch();
    $stmt->close();
    
    $valid_combinations = [];
    foreach ($combinations as $pair) {
        $valid_pair = [0, 0];
        if ($pair[0] == $column1 || $pair[0] == $column2 || $pair[0] == $column3 || $column1 == 0 || $column2 == 0 || $column3 == 0) {
            $valid_pair[0] = $pair[0];
        }
        
        if ($pair[1] == $column1 || $pair[1] == $column2 || $pair[1] == $column3 || $column1 == 0 || $column2 == 0 || $column3 == 0) {
            $valid_pair[1] = $pair[1];
        }
        $valid_combinations[] = $valid_pair;
    }

    return $valid_combinations;
}

function validateMaxProgress($game_id, $valid_combinations) {//2os elenxos an exei termatisi o paixtis 
    $conn = openDatabaseConnection();
    
    $stmt = $conn->prepare("SELECT active_player, player1_id, player2_id FROM games WHERE id = ?");
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $stmt->bind_result($active_player, $player1_id, $player2_id);
    $stmt->fetch();
    $stmt->close();
    
    $valid_final_combinations = [];
    $active_player_column = ($active_player == $player1_id) ? 'p1prog' : 'p2prog';

    $stmt_boards = $conn->prepare("SELECT $active_player_column, active_player_progress, max_progress 
                                   FROM game_boards 
                                   WHERE game_id = ? AND column_number = ?");
    foreach ($valid_combinations as $pair) {
        if ($pair[0] == 0 && $pair[1] == 0) {
            continue; 
        }
        $valid_pair = [0, 0]; 
        foreach ($pair as $index => $num) {
            if ($num == 0) {
                $valid_pair[$index] = 0; 
                continue;
            }
            $stmt_boards->bind_param("ii", $game_id, $num);
            $stmt_boards->execute();
            $stmt_boards->bind_result($player_progress, $active_player_progress, $max_progress);
            $stmt_boards->fetch();
            if (($player_progress + $active_player_progress) < $max_progress) {
                $valid_pair[$index] = $num; 
            } else {
                $valid_pair[$index] = 0; 
            }
        }
        $valid_final_combinations[] = $valid_pair;
    }
    
    $stmt_boards->close();
    $conn->close();
    return $valid_final_combinations;
}

header('Content-Type: application/json');
$game_id = isset($_GET['game_id']) ? intval($_GET['game_id']) : 0;
$player_id = isset($_GET['player_id']) ? intval($_GET['player_id']) : 0;

$active_player = validateActivePlayer($game_id);

if ($player_id !== $active_player) {
    echo json_encode(["message" => "Not the active player."]);
    exit;
}

$combinations = rollDice();
$valid_combinations = validateColumns($game_id, $combinations);
$valid_final_combinations = validateMaxProgress($game_id, $valid_combinations);

echo json_encode([
    "message" => "Valid combinations found.",
    "valid_combinations" => $valid_final_combinations
]);

?>
