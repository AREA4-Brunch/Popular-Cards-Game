<?php

// ADDS THE GAME DATA TO THE GAMES DATABASE

if (!isset($_POST['host-submit'])) {
    //echo "<p>Naughty, naughty!</p>";
    sleep(3);  // wait 3 seconds and return back
    header("Location: ../index.php");
    exit();
}

require_once 'gamefuncs.inc.php';
require 'autoincluder.inc.php';
/*
require_once '../classes/dbfunctions.class.php';
require_once '../classes/exceptions.class.php';
*/
session_start();


function buildGame($game_password, $game_title, $max_num_players) {  // password is only empty string if public game
    $uid = $_SESSION['userId'];  // auto incremented primary key which is index of game's host in users table

    $db = new DataBase();
    try {
        $cur_query_data = $db->getGameData('uidGames', $uid);
        $db->closeConnection();
        header("Location: ../lobby.php?error=alreadyhost");
        exit();
    } catch (TestException $e) {  // $cur_query_data is empty
        // user is not a host of any game so
        // they are allowed to create one
        $err_msg = $e->getMessage();
        if ($err_msg != "gamenotfound") {  // some unwanted error
            header("Location: ../lobby.php?error=" . $err_msg);
            exit();
        }
    }

    // Create Game - Store the data/game:
    // hash the password:
    $hashedPwd = '';
    if ($game_password !== '') {
        $hashedPwd = password_hash($game_password, PASSWORD_DEFAULT);  // use default hashing function cause it never gets outdated
    }

    try {
        // Store the data/game:
        $db->resetConnection();
        $db->insertNewGame($uid, $hashedPwd, $game_title, $max_num_players);
        $db->closeConnection();

        // Make a query to find the gameId since it is the primary key
        // and immediately add the user to that game

        // get all data about the game based on it's uID
        $db->resetConnection();
        $cur_query_data = $db->getGameData('uidGames', $uid);
        $db->closeConnection();

        // add user to the game in database
        session_start();
        $assigned_player_col = addPlayer($cur_query_data, $_SESSION['userUId']);
        $_SESSION['gameId'] = $cur_query_data['idGames'];
        $_SESSION['gamePlayerCol'] = $assigned_player_col;

    } catch (TestException $e) {  // could be game was not found or conn is invalid
        header("Location: ../lobby.php?error=" . $e->getMessage() . "&gsearch=" . $uid);
        exit();
    }

    // everything is awesome:
    header("Location: ../lobby.php?hgame=success");
    exit;
}


// ==================
// DRIVER PROGRAM:
function main() {
    if (isset($_POST['host-submit'])) {
        $max_num_players = $_POST['gplayernum'];

        if (empty($max_num_players)) {
            $max_num_players = 12;
        } else {
            $max_num_players = min(12, $max_num_players);
            $max_num_players = max(2, $max_num_players);
        }


        if (isset($_POST['gtitle'])) {  // It is an open game
            $game_name = $_POST['gtitle'];

            // Error handling:
            if (empty($game_name)) {
                header("Location: ../lobby.php?error=emptyfield&maxp=".$max_num_players."&gtype=open");
                exit;
            }

            // store hosted game in database:
            buildGame('', $game_name, $max_num_players);  // exits script when done
        } else {  // it is a private game
            $game_password = $_POST['gpwd'];

            // Error handling:
            if (empty($game_password)) {
                header("Location: ../lobby.php?error=emptyfield&maxp=".$max_num_players."&gtype=private");
                exit;
            }

            // store hosted game in database:
            buildGame($game_password, '', $max_num_players);  // exits script when done
        }

    }
}


main();
