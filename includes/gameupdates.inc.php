<?php

require 'autoincluder.inc.php';
require_once 'gamefuncs.inc.php';


session_start();

if (!isset($_SESSION['userUId'])) {
    header("Location: ./index.php");
    exit();
}

if (!isset($_SESSION['gameId'])) {
    header("Location: ./lobby.php");
    exit();
}


// handle all requests:
function main() {
    if (!isset($_POST['actionType'])) {
        return;
    }

    switch ($_POST['actionType']) {
        case "playCard":  // user is playing a card from his hand
            $out['status'] = playCardFromHand($_SESSION['gameId'], $_POST['cardPlayed'], $_POST['didShout'], $_SESSION['gamePlayerCol']);
            echo json_encode($out);
        break;
        case "getUpdate":
            $out = getUpdateData($_SESSION['gameId'], $_SESSION['gamePlayerCol']);
            echo json_encode($out);
        break;
        case "removeWinner":
            $out['status'] = removeWinnerNameFromPlayerCol($_SESSION['gameId'], $_SESSION['gamePlayerCol']);
            echo json_encode($out);
        break;
        case "timeRanOut":
            $out['status'] = handleCurPlayersTimeRunOut($_SESSION['gameId'], $_SESSION['gamePlayerCol']);
            echo json_encode($out);
        break;
        case "payPenalty":
            $out['status'] = payPenalty($_SESSION['gameId'], $_SESSION['gamePlayerCol']);
            echo json_encode($out);
        break;
        case "passTurn":
            $out['status'] = passTurn($_SESSION['gameId'], $_SESSION['gamePlayerCol']);
            echo json_encode($out);
        break;
        case "drawCard":
            $out['status'] = drawCard($_SESSION['gameId'], $_SESSION['gamePlayerCol']);
            echo json_encode($out);
        break;
        default:
            $out['status'] = 'invalid actionType';
            echo json_encode($out);
    }
}


// DRIVER PROGRAM:
main();
exit();
