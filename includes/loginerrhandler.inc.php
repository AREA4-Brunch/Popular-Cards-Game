<?php

function handleLoginError() {
    if (isset($_GET['error'])) {
        // display the error message
        echo '<script>  // start the new mini script
                let errorSpan = document.getElementById("loginErrorStatus");
                errorSpan.style.color = "red";
                ';

        switch ($_GET['error']) {
            case "nouser":
                echo '
                        errorSpan.innerHTML = "There is no account with this username";
                        console.log(`No account with this username found in the database.`);
                        ';
            break;
            case "wrongpwd":
                echo '
                        console.log(`Incorrect password typed in to the account: '.$_GET['mailuid'].'`);
                        errorSpan.innerHTML = "Incorrect password!";
                        loginUsernameInputField.value = "'.$_GET['mailuid'].'";
                        ';
            break;
            default:
                echo '
                        console.log("Uknown error occurred!");
                        errorSpan.innerHTML = "Sorry, there was an error ;(";
                        signupUsernameInputField.value = "'.$_GET['uid'].'";
                        signupEmailInputField.value = "'.$_GET['mail'].'";
                        ';
        }

        // close off the script tag
        echo '</script>';
    }

    //echo '<p>You are logged out!</p>';

}