<?php
// HANDLES HOST GAME FORM ERRORS

function handleGameForms() {
    // Handle signup errors
    if (isset($_GET['error'])) {  // host/create new game form
        // make signup card active and prepare span variable
        echo '<script>';
        // add the appropriate error/success message in span tag underneath the form and
        // include previously typed correct non-sensitive data
        switch ($_GET['error']) {
            case "emptyfield":
                echo 'hostGameErrorSpan.textContent = "Fill in the first field!";
                        maxPlayersInputField.value = "'.$_GET['maxp'].'";
                        ';
            break;
            case "alreadyhost":  // User is allowed to host only one game at a time
                echo 'hostGameErrorSpan.textContent = "You are already hosting one game! Only when all players leave it can you host a new one.";
                        ';
            break;
            default:
                echo 'alert("An unknown error occurred. Please screenshot and send to admin.\nSorry ;(");
                        ';
        }

        if (isset($_GET['gtype'])) {
            if ($_GET['gtype'] === 'private') {  // remembered user's switch choice
                echo '
                        switch_tag.click();
                        ';
            }
        }

        echo '</script>';

    } else if (isset($_GET['errorjoin'])) {  // handle join game form
        // make signup card active and prepare span variable
        echo '<script>';
        // add the appropriate error/success message in span tag underneath the form and
        // include previously typed correct non-sensitive data
        switch ($_GET['errorjoin']) {
            case "emptyfield":
                echo 'joinGameErrorSpan.textContent = "Enter the game code!";
                        ';
            break;
            case "wrongcode":  // User is allowed to host only one game at a time
                echo 'joinGameErrorSpan.textContent = "Invalid game code";
                        ';
            break;
            case "noroom":
                echo 'joinGameErrorSpan.textContent = "There is currently no room for more players in this game.";
                        ';
            break;
            default:
                echo 'alert("An unknown error occurred. Please screenshot and send to admin.\nSorry ;(");
                        ';
        }

        echo '</script>';

    }

    // else user has not submitted any forms yet or
    // no errors occured and were rerouted to the game page
}

