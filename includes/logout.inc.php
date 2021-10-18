<?php

if (isset($_POST['logout-submit'])) {
    session_start();
    session_unset();  // deletes all sessionvariables we have
    session_destroy();  // kill the session

    // send the user back to the index page
    header("Location: ../index.php");
}
