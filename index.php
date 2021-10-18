<?php
    require "login_header.php";  // carries session
    require "includes/loginerrhandler.inc.php";
    require_once "loginsignupform.php";  // display login/signup form
    // include page animation for leaving the page when logged in


    // check if session variables are available which means user is logged in
    if(isset($_SESSION['userId'])) {  // can check for any of session variables here
        //loadUsernameLogoutForm();
        echo '<script>
                animations_init();
                let animation_duration = animateCircleExpanding();  // seconds
                setTimeout(function() {
                    window.location.replace("./lobby.php");
                }, animation_duration * 1000);
             </script>';
        //header("Location: ./lobby.php");  // redirect to lobby since user is now logged in
        exit();
    } else {
        handleLoginError();  // handle it if there was any otherwise just echos logged out msg
    }

