<?php
    session_start();  // make sure the session is started when logged in throughout the whole website
?>

<!DOCTYPE html>
<html>

    <head>
        <?php
            include_once 'icons.html';
        ?>

        <meta charset="utf-8">
        <meta name="description" content="Login page for online cards playing website">
        <meta name=viewport content="width=device-width, initial-scale=1">

        <title>QuarantineCards</title>

        <!-- Add styling -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">

        <script src="https://unpkg.com/@barba/core"></script>


        <style>
            .video-background {
                position: fixed;
                z-index: -1;
                backdrop-filter: blur(8px);
            }

            @media (min-aspect-ratio: 16/9) {
                .video-background {
                    width: 100%;
                    height: auto;
                }
            }

            @media (max-aspect-ratio: 16/9) {
                .video-background {
                    width: auto;
                    height: 100%;
                }
            }

            body {
                overflow: hidden;
            }

        </style>

    </head>

    

    <video class="video-background" poster="./videos/firstframe.png" autoplay muted loop>
        <source src="./videos/cardsvideo.mp4" type="video/mp4">
    </video>
