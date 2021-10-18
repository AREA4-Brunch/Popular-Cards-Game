<?php


if (!isset($_POST['join-submit'])) {
    //echo "<p>Naughty, naughty!</p>";
    sleep(3);  // wait 3 seconds and return back
    header("Location: ../index.php");
    exit();
}

require_once 'gamefuncs.inc.php';
require 'autoincluder.inc.php';


function joinGame($game_code) {
    // Error handling:
    if (empty($game_code)) {
        header("Location: ../lobby.php?errorjoin=emptyfield");
        exit();
    }

    // search database for the correct host (uidGames) and
    // check the password:
    if ($separator_idx = strpos($game_code, '_')) {
        $uid = substr($game_code, 0, $separator_idx);
        $pwd = substr($game_code, $separator_idx + 1);

        // Find game in database based on host\s uId
        $db = new DataBase();

        try {
            $cur_query_data = $db->getGameData('uidGames', $uid);

            // check if password user typed in is equal to the password in the database for found host
            $pwdCheck = password_verify($pwd, $cur_query_data['pwdGames']);

            if ($pwdCheck === true) {  // it is possible for it to be string or sth
                // add player to the game inside database:
                $db->closeConnection();

                session_start();
                $assigned_player_col = addPlayer($cur_query_data, $_SESSION['userUId']);
                $_SESSION['gameId'] = $cur_query_data['idGames'];
                $_SESSION['gamePlayerCol'] = $assigned_player_col;

                // return user back to the index page
                header("Location: ../lobby.php?jgame=success");
                exit();
            }
            
            // incorect password or the s
            header("Location: ../lobby.php?errorjoin=wrongcode");
            exit();

        } catch (TestException $e) {
            // user is not a host of any game
            $err_msg = $e->getMessage();
            if (!($err_msg == "gamenotfound")) {  // some unwanted error
                header("Location: ../lobby.php?errorjoin=" . $err_msg);
                exit();
            }
            // else Game with specified uId was not found
            header("Location: ../lobby.php?errorjoin=wrongcode");
            exit();
        }

    } else {  // game code does not contain `_` separator
        header("Location: ../lobby.php?errorjoin=wrongcode");
        exit();
    }
}


//=====================
// DRIVER PROGRAM:

function main() {
    if (isset($_POST['join-submit'])) {
        $game_code = $_POST['gcode'];
        joinGame($game_code);
    }
}


main();
