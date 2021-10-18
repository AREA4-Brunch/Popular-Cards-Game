<?php

session_start();

require_once 'gamefuncs.inc.php';


if (!isset($_SESSION['gameId'])) {
    header("Location: ../lobby.php");
    exit();
}


// handle the start vote form request
if (isset($_POST['initialiseJumpInVote'])) {
    startJumpInVote($_SESSION['gameId']);

    header("Location: ../game.php");
    exit();
}

// handle the vote form request
if (isset($_POST['jumpInVoteSubmit'])) {
    $vote = $_POST['jumpInVoteSubmit'];
    if ($vote == 'enable') {
        handleJumpInVote(1, $_SESSION['gameId'], $_SESSION['gamePlayerCol']);
    } else if ($vote == 'disable') {
        handleJumpInVote(0, $_SESSION['gameId'], $_SESSION['gamePlayerCol']);
    }
    exit();
}

header("Location: ../game.php");
exit();
