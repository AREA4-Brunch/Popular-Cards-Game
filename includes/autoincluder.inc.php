<?php

// starts when on top of php file is this:
// include 'includes/autoincluder.inc.php';

spl_autoload_register(function ($className) {
    $root = "classes/";

    // check if we are calling this script from includes folder
    $url = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

    if (strpos($url, "includes") !== false) {  // if inside includes folder
        $root = "../classes/";
    }

    $extension = ".class.php";

    $path = $root . $className . $extension;

    if (!file_exists($path)) {  // returning false if sth is incorrect, causeit results in a cleaner error message
        return false;
    }

    require_once $path;
});
/*
// function to handle auto include of class files:
function autoInclude($className) {
    $root = "classes/";

    // check if we are calling this script from includes folder
    $url = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

    if (strpos($url, "includes") !== false) {  // if inside includes folder
        $root = "../classes/";
    }

    $extension = ".class.php";

    $path = $root . $className . $extension;

    if (!file_exists($path)) {  // returning false if sth is incorrect, causeit results in a cleaner error message
        return false;
    }

    require_once $path;
}*/
