<?php

    session_start();  // make sure the session is started when logged in throughout the whole website

    // user is not logged in therefore not wanted on this page
    if (!isset($_SESSION['userUId'])) {
        header("Location: ./index.php");
        exit();
    }


    // open the game that user is a part of if any
    if (isset($_SESSION['gameId'])) {
        // join game
        // route to the game, try without history, but most likely it will fail
        echo '<script>
                window.location.replace("./game.php");
             </script>';

        // So far set variables:
        //unset($_SESSION['gameId']);
        //unset($_SESSION['gamePlayerCol']);
        exit();
    }


    require_once 'lobby_html.php';
    require_once './includes/handlegforms.inc.php';
    require_once './includes/handleopengtables.inc.php';

    // handle the forms (host/join)
    handleGameForms();

    // display open games in the table
    //handleOpenGamesTable();

