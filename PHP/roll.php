<?php
session_start();
include 'db_connection.php';

// activeplayer check
function validateActivePlayer($player_id, $game_id) {
    $conn = openDatabaseConnection();
    
    $stmt = $conn->prepare("SELECT active_player FROM games WHERE id = ?");
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $stmt->bind_result($active_player);
    $stmt->fetch();
    $stmt->close();
    
    $conn->close();
    return ($player_id == $active_player);
}

function rollDice() {
    $dice = [
        rand(1, 6), // d1
        rand(1, 6), // d2
        rand(1, 6), // d3
        rand(1, 6)  // d4
    ];

    $combinations = [
        [$dice[0] + $dice[1], $dice[2] + $dice[3]], // p1
        [$dice[0] + $dice[2], $dice[1] + $dice[3]], // p2
        [$dice[0] + $dice[3], $dice[1] + $dice[2]]  // p3
    ];

    return $combinations;
}

function validateColumns($game_id, $combinations) {
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
            $valid_pair[0] = $pair[0]; // check to 1o
        }
        
        if ($pair[1] == $column1 || $pair[1] == $column2 || $pair[1] == $column3 || $column1 == 0 || $column2 == 0 || $column3 == 0) {
            $valid_pair[1] = $pair[1]; // check to 2
        }

        $valid_combinations[] = $valid_pair;
    }

    return $valid_combinations;
}

function validateMaxProgress($game_id, $valid_combinations) {
    $conn = openDatabaseConnection();
    
    $stmt = $conn->prepare("SELECT active_player, player1_id, player2_id 
                            FROM games 
                            WHERE id = ?");
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $stmt->bind_result($active_player, $player1_id, $player2_id);
    $stmt->fetch();
    $stmt->close();
    
    $valid_final_combinations = [];
    $active_player_column = ($active_player == $player1_id) ? 'p1prog' : 'p2prog';        

    foreach ($valid_combinations as $pair) {
        if ($pair[0] == 0 && $pair[1] == 0) {
            continue; // an [0, 0] skip
        }
        
        $valid_pair = [0, 0]; 
        
        foreach ($pair as $index => $num) {
            if ($num == 0) {
                $valid_pair[$index] = 0; // skip  0
                continue;
            }
            
            // check max_progress ston  game_boards
            $stmt = $conn->prepare("SELECT $active_player_column, active_player_progress, max_progress 
                                    FROM game_boards 
                                    WHERE game_id = ? AND column_number = ?");
            $stmt->bind_param("ii", $game_id, $num);
            $stmt->execute();
            $stmt->bind_result($player_progress, $active_player_progress, $max_progress);
            $stmt->fetch();
            $stmt->close();

            if (($player_progress + $active_player_progress) < $max_progress) {
                $valid_pair[$index] = $num; 
            } else {
                $valid_pair[$index] = 0; 
            }
        }

        $valid_final_combinations[] = $valid_pair;
    }

    $conn->close();
    return $valid_final_combinations;
}

header('Content-Type: application/json');
$game_id = isset($_GET['game_id']) ? intval($_GET['game_id']) : 0;
$player_id = isset($_SESSION['player_id']) ? intval($_SESSION['player_id']) : 0;

if (!validateActivePlayer($player_id, $game_id)) {
    echo json_encode(["message" => "Not the active player."]);
    exit;
}

$combinations = rollDice();
$valid_combinations = validateColumns($game_id, $combinations);
$valid_final_combinations = validateMaxProgress($game_id, $valid_combinations);

$all_zero = true;
foreach ($valid_final_combinations as $pair) {
    if ($pair[0] != 0 || $pair[1] != 0) {
        $all_zero = false;
        break;
    }
}

if ($all_zero) {
    $conn = openDatabaseConnection();
    $stmt = $conn->prepare("
        UPDATE games 
        SET active_player = CASE 
                                WHEN active_player = player1_id THEN player2_id 
                                ELSE player1_id 
                            END, 
            active_player_progress = 0 
        WHERE id = ?");
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    echo json_encode(["message" => "No valid combinations. Active player switched."]);
} else {
    // an iparxi esto k enas valid
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, "./choose.php?" . http_build_query(["valid_combinations" => json_encode($valid_final_combinations)]));
    curl_exec($curl);
    curl_close($curl);

    echo json_encode(["message" => "Valid combinations found. `choose.php` called."]);
}
?>
