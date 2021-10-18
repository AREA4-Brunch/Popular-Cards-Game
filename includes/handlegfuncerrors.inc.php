<?php

require 'autoincluder.inc.php';
require_once 'gamefuncs.inc.php';


function handleGameErrors() {
    // Handle success and error messages sent from the game
    if (isset($_GET['gfuncerr'])) {
        switch ($_GET['gfuncerr']) {
            case "gamenotfound":
                unsetAllGameData();
                echo '<script>alert("This game was not found. Returning you to lobby..");
                     </script>';
                sleep(3);  // give user time to read the message
                throw new TestException('return to lobby');
            break;
            case "fromgame_gamenotfound":
                unsetAllGameData();
                echo 'clearedsessionvars';  // js will change the page
            break;
            default:
                echo '<script>alert("An unknown error occurred. Please screenshot and send to admin.\nSorry ;(");
                      </script>';
        }
    }
    exit();
}
