<?php

// DISPLAYS THE DATA ABOUT THE OPEN GAMES IN THE TABLE
// ON LOBBY PAGE


require 'autoincluder.inc.php';
require_once 'gamefuncs.inc.php';


function buildTableRow(&$res_obj, $game_idx, $game_title, $joinedPlayers, $max_players, $host) {
    /*echo '
            buildTableRow("' . $game_idx . '", "' . $game_title . '", "' . $joinedPlayers . '", "' . $max_players . '", "' . $host . '");
         ';*/
    array_push($res_obj['openGames'], [$game_idx, $game_title, $joinedPlayers, $max_players, $host]);
}


function handleOpenGamesTable() {
    $echo_object['status'] = 'error';
    $echo_object['openGames'] = [];
    // get all open games and extract necessary data:
    require 'dbh.inc.php';

    $sql = "SELECT idGames, uidGames, titleGames, maxPlayers, joinedPlayers FROM games WHERE pwdGames=? AND joinedPlayers < maxPlayers";
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        // report the row could not be loaded
        $echo_object['status'] .= 'Failed to select any open games';
        echo json_encode($echo_object);
        exit();
    }

    $empty_pwd = '';  // since open games do not have a password

    mysqli_stmt_bind_param($stmt, "s", $empty_pwd);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    // store each row's data that is to be displayed:
    $table_rows = array();

    while ($row = mysqli_fetch_assoc($result)) {
        array_push($table_rows, $row);
    }

    // find the usernames of hosts based on idUsers (uidGames)
    // display each row of data:

    foreach ($table_rows as $table_row) {
        mysqli_stmt_close($stmt);

        // get data to display:
        $game_idx = $table_row['idGames'];
        $game_title = $table_row['titleGames'];
        $joinedPlayers = $table_row['joinedPlayers'];
        $max_players = $table_row['maxPlayers'];
        $host = '';

        $sql = "SELECT uidUsers FROM users WHERE idUsers=?";
        $stmt = mysqli_stmt_init($conn);

        if (!mysqli_stmt_prepare($stmt, $sql)) {
            // report the row could not be loaded
            //echo 'console.log(`Failed to perform query for username`);';
            continue;
        }

        mysqli_stmt_bind_param($stmt, "s", $table_row['uidGames']);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        if ($cur_query_data = mysqli_fetch_assoc($result)) {  // username was found
            $host = $cur_query_data['uidUsers'];  // username of host
        } else {  // no user with that id was found so do not display this game
            continue;
        }

        buildTableRow($echo_object, $game_idx, $game_title, $joinedPlayers, $max_players, $host);
    }

    //echo '</script>';
    $echo_object['status'] = 'success';
    echo json_encode($echo_object);

    mysqli_stmt_close($stmt);

    mysqli_close($conn);  // close off the connection with the database
}


// exits script when done
function joinOpenGame() {
    $dbg_info = 'original';

    try {
        $db = new DataBase();
        // get all data about the game based on it's ID
        $dbg_info = 'searching for '. $_POST['idGameOpen'];

        $cur_query_data = $db->getGameData('idGames', $_POST['idGameOpen']);
        $db->closeConnection();

        $game_pwd = $cur_query_data['pwdGames'];  // password for the game
        if ($game_pwd === '') {  // It is an open game
            session_start();
            // add the user to the game:
            $assigned_player_col = addPlayer($cur_query_data, $_SESSION['userUId']);  // is already under try block
            $_SESSION['gameId'] = $cur_query_data['idGames'];
            $_SESSION['gamePlayerCol'] = $assigned_player_col;
        }
    } catch (TestException $e) {  // could be game was not found or conn is invalid
        echo $e->getMessage() . $dbg_info;
        exit();
    }

    echo 'success';
    exit();
}


// ================
// DRIVER PROGRAM:

if (isset($_POST['idGameOpen'])) {
    //echo 'No POST data';
    joinOpenGame();  // exits when done
}

if (isset($_POST['updateOpenGamesTable'])) {
    handleOpenGamesTable();
}
