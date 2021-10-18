<?php

require_once 'gamefuncs.inc.php';


if (isset($_POST['quit-submit'])) {
    try {
        removePlayer();
    } catch (TestException $e) {
        if ($e->getMessage() == "gamenotfound") {
            header("Location: ../game.php?gfuncerr=gamenotfound");
        }
    }
    
    // success - send the user back to the index page
    header("Location: ../lobby.php");
    exit();
}

