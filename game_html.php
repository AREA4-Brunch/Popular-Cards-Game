<!DOCTYPE html>
<html>

    <head>

        <meta charset="utf-8">
        <meta name="description" content="Active game's window">
        <meta name=viewport content="width=device-width, initial-scale=1">

        <title>Game</title>

        <!-- Add styling -->
        <link rel="stylesheet" type="text/css" href="./css/game.css">

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

        <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.2.6/gsap.min.js"></script>


        <script src="./js/animations.js"></script>

    </head>


    <body>

        <main>

            <!-- Modal Trigger for JumpIn request-->
            <div class="jump-in-icon">
                <i data-target="modal1" class="material-icons modal-trigger">settings</i>
            </div>

            <!-- Quit button -->
            <div class="quit-form">
                <form action="includes/quit.inc.php" method="post">
                    <button class="quit-btn btn-small waves-effect waves-light" type="submit" name="quit-submit">
                        <i style="margin-right: 5px;"><b>Quit</b></i><i class="material-icons">exit_to_app</i>
                    </button>
                </form>
            </div>

            <!-- Logo -->
            <div class="col s2 offset-s4 logodiv">
                <img src="./imgs/qcards_logo_white.svg" class="brand-logo mylogo">
                <canvas></canvas>
            </div>

            <!-- Shout button -->
            <button class="btn-small waves-effect waves-purple transparent" id="shoutBtn">
                <i style="margin-right: 5px;"><b>Shout</b></i><i class="material-icons">notifications_off</i>
            </button>

            <!-- Cards deck -->
            <div class="cards-deck">
                <img src="./imgs/card_back_round.svg">
                <!-- Draw card/pay penalty form -->
                <button id="drawCardsBtn" class="btn-small waves-effect waves-light" type="submit" value="drawCard">
                    <i style="margin-right: 5px;"><b>Draw Card</b></i>
                </button>
            </div>

            <!-- Cards on the table -->
            <div id="cardsOnTable">
                <!-- <img class="card-on-table" src="./imgs/cards_graphics/red/card_red7.svg">-->
            </div>

            <!-- PLayers: 9, 1, 3, 11 -->
            <div class="row players-row" style="margin-top: 4vh;">
                <div class="col s2 offset-s2" id="player9">
                    <p>Player 9 (7 cards)</p>
                    <img class="player-icon" src="">
                </div>
                <div class="col s2" id="player1">
                    <p>Player 1 (7 cards)</p>
                    <img class="player-icon" src="">
                </div>
                <div class="col s2" id="player3">
                    <p>Player 3 (7 cards)</p>
                    <img class="player-icon" src="">
                </div>
                <div class="col s2" id="player11">
                    <p>Player 11 (7 cards)</p>
                    <img class="player-icon" src="">
                </div>
            </div>

            <!-- PLayers: 5, 6 -->
            <div class="row players-row">
                <div class="col s2 offset-s1" id="player5">
                    <p>Player 5 (7 cards)</p>
                    <img class="player-icon" src="">
                </div>
                <div class="col s2 offset-s9 player6" id="player6">
                    <p>Player 6 (7 cards)</p>
                    <img class="player-icon" src="">
                </div>
            </div>

            <!-- PLayers: 7, 8 -->
            <div class="row players-row">
                <div class="col s2 offset-s1" style="padding-top: 2vh;" id="player7">
                    <p>Player 7 (7 cards)</p>
                    <img class="player-icon" src="">
                </div>
                <div class="col s2 offset-s9 player8" id="player8">
                    <p>Player 8 (7 cards)</p>
                    <img class="player-icon" src="">
                </div>
            </div>

            <!-- PLayers: 12, 2, 4, 10 -->
            <div class="row players-row" style="margin-top: 10vh;">
                <div class="col s2 offset-s2" id="player12">
                    <p>Player 12 (7 cards)</p>
                    <img class="player-icon" src="">
                </div>
                <div class="col s2" id="player2">
                    <p>Player 2 (7 cards)</p>
                    <img class="player-icon" src="">
                </div>
                <div class="col s2" id="player4">
                    <p>Player 4 (7 cards)</p>
                    <img class="player-icon" src="">
                </div>
                <div class="col s2" id="player10">
                    <p>Player 10 (7 cards)</p>
                    <img class="player-icon" src="">
                </div>
            </div>

            <!-- Modal which displays jump in votes form -->
            <!-- Modal Structure -->
            <div id="modal1" class="modal">
                <div class="modal-content">
                    <h5 class="headings" style="font-size: 35px;">Settings</h5>
                    <br>
                    <h3 class="headings">Jumping in</h3>
                    <p style="font-size: 14px;">
                        <i> By default jumping in is not allowed.
                            <br>
                            If you want to disable or enable jumping in option, put it to a vote.
                            <br>
                            It takes a majority of joined players to pass a vote.
                        </i>
                    </p>

                    <!--<form class="jump-in-form" action="includes/jumpinvote.inc.php" method="post">
                       <button id="jumpinformbtn" class="btn transparent waves-effect waves-teal btn-small center" type="submit" name="jump-in-submit">Put to vote</button>

                    </form>-->
                    <button id="jumpinformbtn" class="btn transparent waves-effect waves-teal btn-small center">Put to vote</button>
                    <br>
                    <br>
                    <h3 class="headings">Change background</h3>
                    <p style="font-size: 14px;">
                        <i> Change background by providing a link to an image or entering valid CSS (hex values of colours, linear gradient commands, etc).
                            You can find a colour pallete on this <a href="https://materializecss.com/color.html" target="_blank">link</a>, just scroll down.
                            <br>
                            <input autocomplete="off" placeholder="e.g: #880e4f" id="backgroundColourInput" type="text">
                        </i>
                    </p>
                    <a href="#!" class="modal-close waves-effect waves-teal btn-flat right transparent" style="color: seashell">Close</a>
                    <br>
                </div>
            </div>

            <!-- Modal which displays colour options for played black card -->
            <!-- Modal Structure -->
            <div id="modal2" class="modal">
                <div class="modal-content">
                    <h4 class="headings" style="font-size: 28px;">Pick Colour</h4>
                    <br>
                    <!-- GREEN -->
                    <div value="green" class="btn colour-col" style="margin-left: 22.5%; background-color: #669e42; border-top-left-radius: 33%; border-bottom-right-radius: 33%;"></div>
                    <!-- BLUE -->
                    <div value="blue" class="btn colour-col" style="margin-left: 15px; background-color: #30c7f2; border-top-right-radius: 33%; border-bottom-left-radius: 33%;"></div>
                    <!-- delimeter -->
                    <div style='clear:both'></div>
                    <!-- RED -->
                    <div value="red" class="btn colour-col" style="margin-left: 22.5%; margin-top: 15px; background-color: #ca182e; border-bottom-left-radius: 33%; border-top-right-radius: 33%;"></div>
                    <!-- YELLOW -->
                    <div value="yellow" class="btn colour-col" style="margin-left: 15px; margin-top: 15px; background-color: #f0e51b; border-bottom-right-radius: 33%; border-top-left-radius: 33%;"></div>
                </div>
            </div>

            <!-- Jump in vote card -->
            <div class="card yellow darken-4 jump-in-vote">
                <div class="card-content text" style="padding: 10px 5px 10px 10px;">
                    <span class="card-title" style="font-size: 13px;">Jump In Vote</span>
                    <p style="font-size: 10px;">Jumping in option has been put to vote. Choose a side!</p>
                </div>
                <div class="card-action" style="padding: 5px 5px 10px 10px;">
                    <button id="enableJumpIn" class="btn transparent waves-effect waves-teal btn-small center">Enable</button>
                    <button id="disableJumpIn" class="btn transparent waves-effect waves-teal btn-small center">Disable</button>
                </div>
            </div>

        </main>

        <footer>

            <!-- Reveal cards in player's hand on the left -->
            <button class="btn scroll-left transparent"><i class="large material-icons center">chevron_left</i></button>

            <!-- Players cards -->
            <ul class="collection cards-ul" id="cardsList">
                <!--<li class="collection-item">
                    <img class="users_card" src="./imgs/cards_graphics/changecolour/card_colour_change.svg">
                </li>-->
            </ul>

            <!-- Reveal cards in player's hand on the right -->
            <button class="btn scroll-right transparent"><i class=" large material-icons center">chevron_right</i></button>

        </footer>


    </body>


    <script src="./js/game.js"></script>
