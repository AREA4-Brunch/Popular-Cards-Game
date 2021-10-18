<?php

// Check if user got to this link by pressing a signup button
// instead of just directly openning it
if (isset($_POST['signup-submit'])) {  // signup-submit is name of the signup button in the form on signup page
    // dbh is data base handler
    require 'dbh.inc.php';

    $username = $_POST['uid'];  // name for username input tag in signup page
    $email = $_POST['mail'];
    $password = $_POST['pwd'];
    $passwordRepeat = $_POST['pwd-repeat']; 


    // error handlers for incorrectly inputted data
    // for now only simple checks:

    // check for empty fields
    if (empty($username) || empty($email) || empty($password) || empty($passwordRepeat)) {
        // link user back to the page added as parameter to header ,
        // add the old data so the user does not have to retype certain data,
        // make sure not to include passwords in the url => user will have to retype them
        header("Location: ../signup.php?error=emptyfields&uid=".$username."&mail=".$email);
        exit();  // stop sript from running
    }


    $isValidMail = filter_var($email, FILTER_VALIDATE_EMAIL);
    $isValidUsername = preg_match("/^[a-zA-Z0-9 ]*$/", $username);

    if (!$isValidMail && !$isValidUsername) {  // send back the user with no old information
        header("Location: ../signup.php?error=invalidmailuid");
        exit();
    }

    if (!$isValidMail) {  // send back just the user name
        header("Location: ../signup.php?error=invalidmail&uid=".$username);
        exit();
    }

    if (!$isValidUsername) {  // send back just the email
        header("Location: ../signup.php?error=invaliduid&mail=".$email);
        exit();
    }

    // check if passwoird was mistyped
    if ($password !== $passwordRepeat) {
        header("Location: ../signup.php?error=passwordcheck&uid=".$username."&mail=".$email);
        exit();
    }

    // Set the sql
    $sql = "SELECT uidUsers FROM users WHERE uidUsers=?";
    $stmt = mysqli_stmt_init($conn);  // statement

    if (!mysqli_stmt_prepare($stmt, $sql)) {  // check if it is going to work inside database
        header("Location: ../signup.php?error=sqlerror");
        exit();
    }

    // sendind the data from user using the statement created above
    mysqli_stmt_bind_param($stmt, "s", $username);  // would write "sss" for example if there were 3 parameters ($stmt, "sss", $1, $2, $3)
    mysqli_stmt_execute($stmt);

    // check if the username already exists:
    mysqli_stmt_store_result($stmt);  // stores the result in the statement variable
    // count the number of found results in the database
    $resultCheck = mysqli_stmt_num_rows($stmt);
    if ($resultCheck > 0) {
        header("Location: ../signup.php?error=usertaken&mail=".$email);
        exit();
    }

    // Prepare to store the data:

    $sql = "INSERT INTO users (uidUsers, emailUsers, pwdUsers) VALUES(?, ?, ?)";
    $stmt = mysqli_stmt_init($conn);  // new sql statement

    if (!mysqli_stmt_prepare($stmt, $sql)) {  // check if it failed
        header("Location: ../signup.php?error=sqlerror");
        exit();
    }

    // hash the password:
    $hashedPwd = password_hash($password, PASSWORD_DEFAULT);  // use default hashing function cause it never gets outdated

    mysqli_stmt_bind_param($stmt, "sss", $username, $email, $hashedPwd);  // would write "sss" for example if there were 3 parameters ($stmt, "sss", $1, $2, $3)
    mysqli_stmt_execute($stmt);

    header("Location: ../signup.php?signup=success");
    exit();

    // UNREACHABLE CODE ???
    mysqli_stmt_close($stmt);
    mysqli_close($conn);  // close off the conncetion from the database file!

} else {  // user reached this page without clicking the signup button
    echo "<p>Naughty, naughty!</p>";
    sleep(3);  // wait 3 seconds and return back
    header("Location: ../signup.php");
    exit();
}
