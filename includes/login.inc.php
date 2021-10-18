<?php

if (isset($_POST['login-submit'])) {
    require 'dbh.inc.php';  // get connection with the server

    $mailuid = $_POST['mailuid'];  // username can be either the name of the Profile or e-mail address
    $password = $_POST['pwd'];

    if (empty($mailuid) || empty($password)) {
        header("Location: ../index.php?error=emptyfields");
        exit();
    }

    $sql = "SELECT * FROM users WHERE uidUsers=?";
    $stmt = mysqli_stmt_init($conn);

    if (!mysqli_stmt_prepare($stmt, $sql)) {
        header("Location: ../index.php?error=sqlerror");
        exit();
    }

    mysqli_stmt_bind_param($stmt, "s", $mailuid);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {  // user found
        // check if password user typed in is equal to the password in the database for found username
        $pwdCheck = password_verify($password, $row['pwdUsers']);
        if ($pwdCheck === true) {  // it is possible for it to be string or sth
            session_start();  // start session since user is logged in
            // store non sensitive data on the website as session variables
            $_SESSION['userId'] = $row['idUsers'];
            $_SESSION['userUId'] = $row['uidUsers'];  // user's username
            // return user back to the index page
            header("Location: ../index.php?login=success");
            exit();
        }

        header("Location: ../index.php?error=wrongpwd&mailuid=".$mailuid);
        exit();
    } else {  // no such user exists in the database
        header("Location: ../index.php?error=nouser");
        exit();
    }

} else {  // user reached this page without clicking the login button
    header("Location: ../index.php");
    exit();
}
