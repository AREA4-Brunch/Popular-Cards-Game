<?php
    // HANDLES SIGNUP ERRORS AND REPRESENTS THE MIRROR PAGE OF INDEX

    // get HTML for mirroring
    require "login_header.php";
    require_once "loginsignupform.php";


    // Handle signup errors
    if (isset($_GET['error'])) {
        // make signup card active and prepare span variable
        echo '//<script>
                //const container = document.getElementById("container");
                container.classList.add("right-panel-active");

                let errorSpan = document.getElementById("signupErrorStatus");
                errorSpan.style.color = "red";
              ';
        // add the appropriate error/success message in span tag above form and
        // include previously typed correct non-sensitive data
        switch ($_GET['error']) {
            case "emptyfields":
                //echo "<p>Fill in all the fields!</p>";
                echo 'errorSpan.innerHTML = "Fill in all the fields!";
                        signupUsernameInputField.value = "'.$_GET['uid'].'";
                        signupEmailInputField.value = "'.$_GET['mail'].'";
                      </script>';
            break;
            case "invalidmailuid":
                //echo "<p>Invalid username and e-mail!</p>";
                echo 'errorSpan.innerHTML = "Invalid username and e-mail!";
                      </script>';
            break;
            case "invalidmail":
                //echo "<p>Invalid e-mail!</p>";
                echo 'errorSpan.innerHTML = "Invalid e-mail!";
                      signupUsernameInputField.value = "'.$_GET['uid'].'";
                      </script>';
            break;
            case "invaliduid":
                //echo "<p>Invalid username!</p>";
                echo 'errorSpan.innerHTML = "Invalid username! Please use only letters, digits and spaces";
                      signupEmailInputField.value = "'.$_GET['mail'].'";
                      </script>';
            break;
            case "passwordcheck":
                //echo "<p>Incorrectly repeated password!</p>";
                echo 'errorSpan.innerHTML = "Incorrectly repeated password!";
                      signupUsernameInputField.value = "'.$_GET['uid'].'";
                      signupEmailInputField.value = "'.$_GET['mail'].'";
                      </script>';
            break;
            case "usertaken":
                //echo "<p>Username already exists! Try different username!</p>";
                echo 'errorSpan.innerHTML = "Username already exists! Try different username!";
                      signupEmailInputField.value = "'.$_GET['mail'].'";
                      </script>';
            break;
            default:
                //echo "<p>There was a problem when signing in ;(</p>";
                echo 'errorSpan.innerHTML = "There was a problem when signing in ;(";
                        signupUsernameInputField.value = "'.$_GET['uid'].'";
                        signupEmailInputField.value = "'.$_GET['mail'].'";
                      </script>';
        }
    } else if (isset($_GET['signup'])) {
        if ($_GET['signup'] == "success") {
            //echo "<p>Signed up successfully! Now log in and enjoy!</p>";
            echo '//<script>
                    //const container = document.getElementById("container");
                    container.classList.add("right-panel-active");

                    let errorSpan = document.getElementById("signupErrorStatus");
                    errorSpan.style.color = "green";
                    errorSpan.innerHTML = "Signed up successfully! Now log in and enjoy!"
                  </script>
                 ';
        }
    }
