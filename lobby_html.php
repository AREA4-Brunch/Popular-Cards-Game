<!DOCTYPE html>
<html>

    <head>

        <meta charset="utf-8">
        <meta name="description" content="Lobby where users can join games or create new ones">
        <meta name=viewport content="width=device-width, initial-scale=1">

        <title>Lobby QuarantineCards</title>

        <!-- Add styling -->
        <link rel="stylesheet" type="text/css" href="./css/animations.css">
        <link rel="stylesheet" type="text/css" href="./css/lobby_style.css">

        <!-- Add favicon -->
        <?php
            include_once 'icons.html';
        ?>

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600&display=swap" rel="stylesheet">

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

        <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/100/three.min.js"></script> -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r121/three.min.js"></script>
        <!-- <script src="https://cdn.jsdelivr.net/npm/vanta@latest/dist/vanta.halo.min.js"></script> -->
        <script src="./js/vanta_halo_17_10_2021.js"></script>

        <script src="https://unpkg.com/@barba/core"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.2.6/gsap.min.js"></script>


    </head>


    <body id="vantajs" >

        <!-- First row (Info, Logo, Username, Logout) -->
        <div class="row toprow" style="top: 2%;">

            <!-- Modal Trigger -->
            <div class="col s1" style="text-align: left;">
                <i data-target="modal1" class="material-icons modal-trigger" style="margin-left: 15px;">info_outline</i>
            </div>

            <!-- Logo -->
            <div class="col s2 offset-s4 logodiv">
                <img src="./imgs/qcards_logo_white.svg" class="brand-logo mylogo">
                <!--<h6>Quarantine Fun</h6>-->
            </div>

            <!-- Username -->
            <div class="col s2 offset-s2 username-text">
                <i>
                <?php
                    echo $_SESSION['userUId'];
                ?>
                </i>
            </div>

            <!-- Logout button #ef5350; -->
            <div class="col s1">
                <form action="includes/logout.inc.php" method="post">
                    <button class="btn-floating btn-small waves-effect waves-light" style="background-color: #573386 !important;" type="submit" name="logout-submit">
                        <i class="material-icons">settings_power</i>
                    </button>
                    <p class="logout-text">Logout</p>
                </form>
            </div>

        </div>

        <!-- Table and New/Join game forms -->
        <div class="row table-row">

            <div class="col s4 offset-s1 table">
                <table class=" highlight centered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Game Name</th>
                            <th>No. of players</th>
                            <th>Host</th>
                        </tr>
                    </thead>

                    <tbody>
                    </tbody>
                </table>
            </div>

            <!-- Join game form -->
            <div class="col s2 offset-s2" >
                <div class="row" style="margin-left: 15px;">
                    <div class="col s12 form-container center">
                        <p class="center form-heading"><b>Join Private Game</b></p>
                        <form action="includes/joingame.inc.php" method="post">
                            <div class="input-field" style="margin-bottom: 0;">
                                <input placeholder="Game Code" name="gcode" type="text" maxlength="30" autocomplete="off">
                                <span id="joinGameErrorStatus" class="helper-text" data-error="wrong" data-success="right"></span>
                            </div>
                            <button class="btn transparent btn-small center" type="submit" name="join-submit">Join</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Create/Host game form -->
            <div class="col s2 ">
                <div class="row" style="margin-left: 15px;">
                    <div class="col s12 form-container center">
                        <p class="center form-heading"><b>Host Your Game</b></p>
                        <div class="switch host-game-switch">
                            <label style="color: #fafafa; font-size: 14px;">
                              Public
                              <input type="checkbox" id="hostGameSwitch">
                              <span class="lever"></span>
                              Private
                            </label>
                        </div>
                        <br>
                        <form action="includes/hostgame.inc.php" method="post">

                            <div id="firstField" class="input-field">
                                <input placeholder="Game Name" name="gtitle" type="text" maxlength="30" autocomplete="off">
                            </div>

                            <div class="input-field" style="margin-bottom: 0;">
                                <input placeholder="Max number of players" name="gplayernum" type="number" min="2" max="12">
                                <span id="hostGameErrorStatus" class="helper-text" data-error="wrong" data-success="right"></span>
                            </div>

                            <button class="btn transparent btn-small" type="submit" name="host-submit">Host</button>
                        </form>
                    </div>
                </div>
            </div>


        </div>  <!-- End of main content row -->


        <!-- Modal which displays author and special thanks info -->
        <!-- Modal Structure -->
        <div id="modal1" class="modal">
            <div class="modal-content">
                <h5>About</h5>
                <br>
                <p>
                    <i style="font-size: 18px;">Made by:</i> <br>Aleksandar Radenković <i>in April 2020</i>
                    <br>
                    <br>
                    <i style="font-size: 18px;">Special thanks to:</i>
                    <br>
                    <a href="https://www.youtube.com/channel/UCzyuZJ8zZ-Lhfnz41DG5qLw" target="_blank">mmtuts</a><i> for the PHP courses and login system</i>
                    <br>
                    <a href="https://www.florin-pop.com/blog/2019/03/double-slider-sign-in-up-form/" target="_blank">Florin Pop</a><i> for login/signup card design</i>
                    <br>
                    <a href="https://www.vantajs.com/" target="_blank">vantajs</a><i> for the background animation</i>
                    <br>
                </p>
                <a href="#!" class="modal-close waves-effect waves-purple btn-flat right transparent" style="color: white">Close</a>
                <br>
            </div>
        </div>


    </body>


    <!-- Add script for animation -->
    <script src="./js/animations.js"></script>

    <script>
        // Driver program:
        animations_init();
        let duration = animateCircleShrinking();
        //console.log(`Animation duration: ${duration}`);
    </script>

    <script src="./js/lobby.js"></script>
