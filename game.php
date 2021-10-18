<?php

session_start();

if (!isset($_SESSION['userUId'])) {
    header("Location: ./index.php");
    exit();
}

if (!isset($_SESSION['gameId'])) {
    header("Location: ./lobby.php");
    exit();
}


// if game is private
// ALERT USER WHAT IS THE PREFIX OF THA GAME, ALSO LOG IT VIA JAVASCRIPT

require './includes/autoincluder.inc.php';
require_once './includes/handlegfuncerrors.inc.php';

try {
    $db = new DataBase();
    if ($db->isGamePrivate($_SESSION['gameId'])) {
        $host_uid_raw = $db->getGameData('idGames', $_SESSION['gameId'], 'uidGames');
        $host_uid = $host_uid_raw['uidGames'] . '_';
        echo '<script>
                alert("Password prefix for this game is: ' . $host_uid . '\nFor example if password was set to `1234` and prefix `25_`, game code needed to join it is `25_1234`");
                console.log("Game prefix: `' . $host_uid . '`");
             </script>';
    }
    $db->closeConnection();
} catch (TestException $e) {
    if ($e->getMessage() == 'gamenotfound') {
        unsetAllGameData();
        header('Location: ./lobby.php');
        exit();
    }
}


require_once 'game_html.php';



// handles error get request
try {
    handleGameErrors();
} catch (TestException $e) {
    if ($e->getMessage() == 'return to lobby') {
        // all game data has already been unset, just go to lobby
        echo '<script>
                window.location.replace("./lobby.php");
             </script>';  
        exit();
    }
}

