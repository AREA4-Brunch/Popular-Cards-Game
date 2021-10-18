// DOM Elements:
const CARDS_LIST = document.getElementById("cardsList");
const SCROLL_LEFT_BTN = document.getElementsByClassName("scroll-left")[0];
const SCROLL_RIGHT_BTN = document.getElementsByClassName("scroll-right")[0];
const SHOUT_BTN = document.getElementById("shoutBtn");
const VOTE_FORM = document.getElementsByClassName("jump-in-vote")[0];
const ENABLE_JUMPIN_BTN = document.getElementById("enableJumpIn");
const DISABLE_JUMPIN_BTN = document.getElementById("disableJumpIn");
const CARDS_ON_TABLE_DIV = document.getElementById("cardsOnTable");
const WINNER_SONG_AUDIO = new Audio("./sounds/winner.m4a");
const BACKGROUND_COLOUR_INPUT_FIELD = document.getElementById("backgroundColourInput");
const JUMP_IN_FORM_BTN = document.getElementById("jumpinformbtn");
const DRAW_CARDS_FROM_DECK_BTN = document.getElementById("drawCardsBtn");
let CHOOSE_BLACK_CARD_COLOUR_MODAL;  // materialize object set on document load

/* Loaded from php:
// none of THESE ARE NECCASSARY ???
const GAME_ID;
const USERNAME;
const USER_PLAYER_IDX;*/


// ================
// AJAX UPDATED:
let USER_PLAYER_IDX = 1;  // (int) 1 - indexed
let PLAYER_TO_MOVE_IDX = 1;  // (int) 1-indexed
let IS_PLAYING = false;  // keep track of whether is still waiting for his 1st game or not
let IS_JUMP_IN_ALLOWED = false;  // in order to know whether to sand the request via AJAX when it is not users turn
let DO_DISPLAY_VOTE_FORM = false;  // bool know whether jumping in was put to vote
// following two lists contains empty data if player is not part of the game:
let PLAYERS_USERNAMES = ['', '', '', '', '', '', '', '', '', '', '', ''];
let PLAYERS_NUM_CARDS_LEFT = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];  // stores the number of cards in each player's hand (or str `waiting`)
let CARDS_ON_TABLE_ARR = [];  // stores last x cards on the table from oldest to newest
let USERS_HAND_ARR = [];  // stores all cards user has in his hand
let WINNERS_NAME = "";  // string - the name of the winner
let HAS_PENALTY_TO_PAY = false;  // keep track of having to draw cards as penalty or not
let HAS_DRAWN_FROM_DECK = false;  // keep track of whether player should have the option of passing

// Set and updated client wise:
let DID_SHOUT = false;  // keep track of if player shouted that they have 1 card left
let TIMERS_INTERVAL_ID;
let IS_DEALING = false;  // keep track of whether new set of cards is being dealt
let WAS_ALONE = false;
let CUR_BLACK_CARD = '';  // the black card whose colour user is setting
let HAS_SENT_TIME_RAN_OUT_REQUEST = false;  // make sure the time running ouot request is sent only once


function setTestValues() {
    PLAYER_TO_MOVE_IDX = 1;  // 1-indexed
    IS_PLAYING = true;  // keep track of whether is still waiting for his 1st game or not
    IS_JUMP_IN_ALLOWED = false;  // in order to know whether to sand the request via AJAX when it is not users turn
    DO_DISPLAY_VOTE_FORM = false;  // bool know whether jumping in was put to vote
// following two lists contains empty data if player is not part of the game:
    PLAYERS_USERNAMES = ['Pl 1', 'Pl 2', 'Pl 3', 'Pl 4', 'Pl 5', 'Pl 6', 'Pl 7', 'Pl 8', 'Pl 9', 'Pl 10', 'Pl 11', 'Pl 12'];
    PLAYERS_NUM_CARDS_LEFT = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];  // stores the number of cards in each player's hand
    CARDS_ON_TABLE_ARR = ["2B", "7B", "wR"];  // stores last x cards on the table from oldest to newest
    USERS_HAND_ARR = ["D", "3B", "w", "w", "7R", "dB", "rY"];  // stores all cards user has in his hand
    WINNERS_NAME = "";  // string - the name of the winner
}


// ==============
// Set event listeners:

// set the modals:
document.addEventListener('DOMContentLoaded', function() {
    let elems = document.querySelectorAll('.modal');
    let instances = M.Modal.init(elems, {});
    CHOOSE_BLACK_CARD_COLOUR_MODAL = instances[1];

    // set event listener on all colours in the modal:
    let divs = document.getElementsByClassName("colour-col");
    for (let i = 0, n = divs.length; i < n; i++) {
        divs[i].addEventListener("click", function(e) {
            pickBlackCardColour(e.target.getAttribute("value"));
        });
    }
});

// IE9, Chrome, Safari, Opera
CARDS_LIST.addEventListener("mousewheel", scrollCardsHorizontally, false);
// Firefox
CARDS_LIST.addEventListener("DOMMouseScroll", scrollCardsHorizontally, false);

CARDS_LIST.addEventListener("click", function(e) {
    if (e.target && (e.target.nodeName === "LI" || e.target.nodeName === "IMG")) {
        let card_idx = 0;
        for (card_idx = 0, n = CARDS_LIST.children.length; card_idx < n; ++card_idx) {
            const li_tag = CARDS_LIST.children[card_idx];
            if (e.target === li_tag || e.target === li_tag.children[0]) {  // check the image tag as well
                break;
            }
        }
        let card_to_play = USERS_HAND_ARR[card_idx];
        if (card_to_play === 'D' || card_to_play === 'w') {  // if it is a black card
            showColourChoices(card_to_play);  // open the option of picking the colour of the card
        } else {
            playCard(card_to_play);
        }
    }
});

SCROLL_RIGHT_BTN.addEventListener("click", function() {
    CARDS_LIST.scrollLeft += CARDS_LIST.offsetWidth - 50;
});

SCROLL_LEFT_BTN.addEventListener("click", function() {
    CARDS_LIST.scrollLeft -= CARDS_LIST.offsetWidth - 50;
});

SHOUT_BTN.addEventListener("click", function() {
    DID_SHOUT = !DID_SHOUT;
    // update the icon on the button
    if (DID_SHOUT) {
        SHOUT_BTN.children[1].innerHTML = "notifications_active";
    } else {
        SHOUT_BTN.children[1].innerHTML = "notifications_off";
    }
});

ENABLE_JUMPIN_BTN.addEventListener("click", function() {
    submitJumpInVote("enable");
    DO_DISPLAY_VOTE_FORM = false;
    setVoteForm();
});

DISABLE_JUMPIN_BTN.addEventListener("click", function() {
    submitJumpInVote("disable");
    DO_DISPLAY_VOTE_FORM = false;
    setVoteForm();
});

BACKGROUND_COLOUR_INPUT_FIELD.addEventListener("keydown", function(e) {
    if (e && e.code === "Enter") {
        changeBackground();
    }
});

JUMP_IN_FORM_BTN.addEventListener("click", function() {
    $.ajax({
        type : "POST",
        url  : "./includes/jumpinvote.inc.php",
        data : {
            initialiseJumpInVote : true
        }
    });
});

DRAW_CARDS_FROM_DECK_BTN.addEventListener("click", function() {
    handleDrawCardsBtn();
});


// ==========================
// Game mechanics, DRIVER program:
//setTestValues();  // REMOVE THIS

setTimeout(function() {  // all of these are called just once
    alertIfAlone();
    setUsersIcon();  // called just once at the beginning, sets cur user's icon
    initialiseUsersIconsImages();  // sets random images for each player's icon only once, when this page is entered
}, 450);

getUpdatedGameVariables();


// ============================
// Functions:


// ========================
// functions called on each update:
function performUpdate() {  // After updating variables:
    // change on update and do not depend on previous state
    gameInitialise();

    if (WINNERS_NAME !== "") {
        displayWinner(WINNERS_NAME);
        // WINNERS_NAME = ""; it is cleared through ajax
        // in case there was a winner send back the info to erase it so no update resends it
        noteWinnerWasFetched();

        // START DEALING NEW CARDS HERE
        // cards that are now in the user's hand should appear as
        // currently being dealt
        dealUsersHands();
    } else if (!IS_DEALING) {
        updateUsersHand();  // Update user's hand normally without any animations
    }

    // happen only on change (depend on previous state):


    setTimeout(function() {
        getUpdatedGameVariables();  // this will call perfromUpdate again
    }, 800);
}


// does not unset or update prevoius values, only makes changes
// based on already set values sets new ones
function gameInitialise() {
    setScreenSize();
    setVoteForm();
    setPlayersIcons();
    setCurPlayersTimer();
    updateCardsOnTable();
    updateDrawCardsForm();
}


// gets the game values used to update client side:
function getUpdatedGameVariables() {
    //console.log(`requesting update`);
    $.ajax({
        type : "POST",
        url  : "./includes/gameupdates.inc.php",
        data : {
            actionType : "getUpdate"
        },
        dataType: "JSON",  // format of recieved data from server
        success: function(result_json_data){
                    //console.log(`Recieved update`);
                    if (result_json_data.status !== "success") {
                        // log an error message and notify user
                        console.log(`TestError message through ajax: ${result_json_data.status}`);
                        if (result_json_data.status == 'gamenotfound') {
                            //alert("Game no longer exists");  already set by php
                            handleGameNotFoundError();  // call ajax and handle it
                        }
                        return;  // updates stop from now
                    }
                    //console.log(`Fetched update data: ${result_json_data}`);
                    
                    let old_hand = USERS_HAND_ARR;
                    updateAllVariables(result_json_data);

                    //logUpdatedValues();
                    performUpdate();  // depends on upated values

                    // game has begun for the very first time ever so deal the cards
                    if (!IS_DEALING && ((WAS_ALONE && IS_PLAYING) || ((!old_hand || !old_hand.length) && (USERS_HAND_ARR && USERS_HAND_ARR.length)))) {
                        dealUsersHands();
                    }

                    if (isAlone()) {
                        IS_PLAYING = false;
                        WAS_ALONE = true;
                    } else {
                        WAS_ALONE = false;
                    }
                }
    });
}


function logUpdatedValues() {
    console.log(`USER_PLAYER_IDX: ${USER_PLAYER_IDX}`);
    console.log(`PLAYER_TO_MOVE_IDX: ${PLAYER_TO_MOVE_IDX}`);
    console.log(`IS_PLAYING: ${IS_PLAYING}`);
    console.log(`IS_JUMP_IN_ALLOWED: ${IS_JUMP_IN_ALLOWED}`);
    console.log(`DO_DISPLAY_VOTE_FORM: ${DO_DISPLAY_VOTE_FORM}`);
    console.log(`PLAYERS_USERNAMES: ${PLAYERS_USERNAMES}`);
    console.log(`PLAYERS_NUM_CARDS_LEFT: ${PLAYERS_NUM_CARDS_LEFT}`);
    console.log(`CARDS_ON_TABLE_ARR: ${CARDS_ON_TABLE_ARR}`);
    console.log(`USERS_HAND_ARR: ${USERS_HAND_ARR}`);
    console.log(`WINNERS_NAME: ${WINNERS_NAME}`);
}


// set all of variables in this file
function updateAllVariables(data_json) {
    USER_PLAYER_IDX = parseInt(data_json.userPlayerIdx);  // 1-indexed
    PLAYER_TO_MOVE_IDX = parseInt(data_json.playerToMoveIdx);  // 1-indexed
    IS_PLAYING = data_json.isPlaying;  // keep track of whether is still waiting for his 1st game or not
    IS_JUMP_IN_ALLOWED = data_json.isJumpInAllowed;  // in order to know whether to sand the request via AJAX when it is not users turn
    DO_DISPLAY_VOTE_FORM = data_json.didNotVote;  // bool know whether jumping in was put to vote
    // following two lists contains empty data if player is not part of the game:
    PLAYERS_USERNAMES = data_json.playersUsernames;
    PLAYERS_NUM_CARDS_LEFT = data_json.playersNumCardsLeft;  // stores the number of cards in each player's hand
    CARDS_ON_TABLE_ARR = data_json.cardsOnTable;  // stores last x cards on the table from oldest to newest
    USERS_HAND_ARR = data_json.usersHand;  // stores all cards user has in his hand
    WINNERS_NAME = data_json.winnersName;  // string - the name of the winner / empty if no winner yet
    HAS_PENALTY_TO_PAY = data_json.hasPenaltyToPay;
    HAS_DRAWN_FROM_DECK = data_json.hasDrawn;
}


function handleGameNotFoundError() {
    alert(`Game you are a part of seems not to exist anymore.\nReturning you to lobby..`);
    $.ajax({
        type : "GET",
        url  : "./includes/handlegfuncerrors.inc.php",
        data : {
            gfuncerr : "fromgame_gamenotfound"
        },
        success: function(data){
                    window.location.href = "./lobby.php";
                }
    });
}


// Enable horizontal scrolling through cards without holding shift key
function scrollCardsHorizontally(e) {
    e = window.event || e;
    var delta = Math.max(-1, Math.min(1, (e.wheelDelta || -e.detail)));
    CARDS_LIST.scrollLeft -= (delta*40);
    e.preventDefault();
}


// sets fullscreen or loads cards whether player is waiting or playing
function setScreenSize() {
    if (!IS_PLAYING || IS_DEALING) {
        loadFullScreen();
    } else {
        unloadFullScreen();
    }
}


// changes layout when player is waiting to join a game
function loadFullScreen() {
    // remove cards list
    const footerTag = document.getElementsByTagName("footer")[0];
    footerTag.style.display = "none";  // normal is block

    // set logo
    const logoDiv = document.getElementsByClassName("logodiv")[0];
    logoDiv.style.top = "38vh";  // normal is 27vh

    // set deck of cards
    const cardsDeckDiv = document.getElementsByClassName("cards-deck")[0];
    cardsDeckDiv.style.top = "33vh";  // normal is 25vh
    cardsDeckDiv.children[0].style.height = "200px";  // it was 40vh

    // set position of icons' rows
    const players_rows = document.getElementsByClassName("players-row");
    for (let i = 0; i < players_rows.length; i++) {
        players_rows[i].setAttribute("style", "margin-top: 11vh;");  // normal is 7vh
    }

    // set cards on the table:
    const cardsOnTable = document.getElementsByClassName("card-on-table");
    for (let i = 0, n = cardsOnTable.length; i < n; i++) {
        cardsOnTable[i].style.top = "33vh";
    }

    players_rows[players_rows.length - 1].setAttribute("style", "margin-top: 13vh;");  // noraml is 10vh

    // set icons size
    const player_icons = document.getElementsByClassName("player-icon");
    for (let i = 0; i < player_icons.length; i++) {
        player_icons[i].style.height = player_icons[i].style.width = "10vh";  // normal is 7vh
    }

    // set position of 2 special, right icons:
    const player6_icon = document.getElementById("player6");
    player6_icon.style.top = "30vh";  // noraml is 20vh

    const player8_icon = document.getElementById("player8");
    player8_icon.style.top = "50vh";  // noraml is 35vh
}


// returns layout back to normal (while a part of the game)
function unloadFullScreen() {
    // remove cards list
    const footerTag = document.getElementsByTagName("footer")[0];
    footerTag.style.display = "block";  // normal is block

    // set logo
    const logoDiv = document.getElementsByClassName("logodiv")[0];
    logoDiv.style.top = "27vh";  // normal is 27vh
    logoDiv.children[0].style.height = "18vh";  // it was 24vh

    // set deck of cards
    const cardsDeckDiv = document.getElementsByClassName("cards-deck")[0];
    cardsDeckDiv.style.top = "25vh";  // normal is 25vh
    cardsDeckDiv.children[0].style.height = "160px";  // it was 185px

    // set cards on the table:
    const cardsOnTable = document.getElementsByClassName("card-on-table");
    for (let i = 0, n = cardsOnTable.length; i < n; i++) {
        cardsOnTable[i].style.top = "25vh";
    }

    // set position of icons' rows
    const players_rows = document.getElementsByClassName("players-row");
    for (let i = 0; i < players_rows.length; i++) {
        players_rows[i].setAttribute("style", "margin-top: 7vh;");  // normal is 7vh
    }
    
    players_rows[players_rows.length - 1].setAttribute("style", "margin-top: 10vh;");  // noraml is 10vh

    // set icons size
    const player_icons = document.getElementsByClassName("player-icon");
    for (let i = 0; i < player_icons.length; i++) {
        player_icons[i].style.height = player_icons[i].style.width = "7vh";  // normal is 7vh
    }

    // set position of 2 special, right icons:
    const player6_icon = document.getElementById("player6");
    player6_icon.style.top = "23vh";  // noraml is 20vh

    const player8_icon = document.getElementById("player8");
    player8_icon.style.top = "38vh";  // noraml is 35vh
}


// handles what happens to voting card when bool
// DO_DISPLAY_VOTE_FORM is set
function setVoteForm() {
    // `local_phone` is the default
    // perm_phone_msg
    let icon = document.getElementsByClassName("jump-in-icon")[0].children[0];
    if (DO_DISPLAY_VOTE_FORM) {
        VOTE_FORM.style.display = "block";
        icon.innerHTML = "perm_phone_msg";
    } else {
        VOTE_FORM.style.display = "none";
        icon.innerHTML = "settings";
    }
}


// performs form submit of the vote regarding jumping in form
function submitJumpInVote(vote) {
    $.ajax({
        type : "POST",  //type of method
        url  : "./includes/jumpinvote.inc.php",  //your page
        data : { jumpInVoteSubmit : vote }  // vote can be 'enable' or 'disable'
        /*success: function(result_data){
                    console.log(`server msg: ${result_data}`);
                }*/
    });
}


// Update values of # of cards under each player's icon
function setPlayersIcons() {
    // if there are no players display text waiting for players
    for (let i = 0, n = PLAYERS_USERNAMES.length; i < n; i++) {
        const cur_player_tag = document.getElementById("player" + (i + 1).toString());
        if (!PLAYERS_USERNAMES[i] || PLAYERS_USERNAMES[i] === "") {
            cur_player_tag.style.visibility = "collapse";
            continue;
        }
        cur_player_tag.style.visibility = "visible";
        let updated_text = PLAYERS_USERNAMES[i] + " (" + PLAYERS_NUM_CARDS_LEFT[i].toString() + " cards)";
        if (PLAYERS_NUM_CARDS_LEFT[i] === "waiting") {
            updated_text = PLAYERS_USERNAMES[i] + " (" + PLAYERS_NUM_CARDS_LEFT[i].toString() + ")";
        }
        cur_player_tag.children[0].textContent = updated_text;
    }
}


// changes font colour to green and adds the timer
// timer depends on the number of cards
function setCurPlayersTimer() {
    if (isAlone()) {  // there is no game yet
        return;
    }
    let player_to_move_tag = document.getElementById("player" + PLAYER_TO_MOVE_IDX);
    // check if this is the new player so the old timer should
    // be deleted, and only then this one created,
    // or there is no change so do nothing
    if (player_to_move_tag.className.indexOf("timer") >= 0) {  // no change
        return;
    }

    let exists = document.getElementsByClassName("timer")[0];
    if (exists) {
        unsetCurPlayersTimer();
        HAS_SENT_TIME_RAN_OUT_REQUEST = false;
    }

    if (USER_PLAYER_IDX !== PLAYER_TO_MOVE_IDX) {  // never change user's colour
        player_to_move_tag.style.color = "#50dc67";
    }
    // <i style="left: 0; font-size: 16px;">12s</i>
    let i_tag = document.createElement("i");
    i_tag.setAttribute("style", "left: 0; font-size: 16px; padding-right: 30px;");
    i_tag.textContent = calcPlayersTime() + "s";
    player_to_move_tag.className += " timer";
    player_to_move_tag.className = player_to_move_tag.className.replace(/\s+/g,' ').trim();
    player_to_move_tag.appendChild(i_tag);

    setUsersIcon();

    TIMERS_INTERVAL_ID = setInterval(function() {
        updateTimer();
    }, 1000);
    /*setTimeout(function() {
        updateTimer();
    }, 1000);*/
}


// calculates players time in seconds to make a move based on
// the number of cards they have left
function calcPlayersTime() {
    // index in list represents # of cards, value is time in seconds
    let times = [10, 6, 9, 10, 11, 12, 13, 15, 16, 17, 18];
    let players_num_cards = PLAYERS_NUM_CARDS_LEFT[PLAYER_TO_MOVE_IDX - 1];

    if (players_num_cards >= times.length) {
        return times[times.length - 1];
    }

    return times[players_num_cards];
}


// changes time value in given player's tag
function updateTimer() {
    const player_tag = document.getElementsByClassName("timer")[0];
    let player_tag_children = player_tag.children;

    // return if the timer has been deleted
    if (player_tag_children.length < 3) {
        return;
    }

    let timer_tag = player_tag_children[player_tag_children.length - 1];
    let cur_time = parseInt(timer_tag.textContent);

    if (typeof cur_time === "NaN" || null == cur_time) {
        unsetCurPlayersTimer();
        return;
    }

    if (cur_time <= 0) {
        if (!HAS_SENT_TIME_RAN_OUT_REQUEST) {
            sendTimeRanOutRequest();
        }
        return;  // no need for future updates
    }

    cur_time--;
    timer_tag.textContent = cur_time.toString() + "s";
}


// sends ajax request to handle time running out:
function sendTimeRanOutRequest() {
    HAS_SENT_TIME_RAN_OUT_REQUEST = true;
    $.ajax({
        type : "POST",
        url  : "./includes/gameupdates.inc.php",
        data : {
            actionType : "timeRanOut"
        },
        dataType: "JSON",  // format of recieved data from server
        success: function(result_json_data){  
                    //console.log(`returned time data: ${result_json_data.status}`);
                    if (result_json_data.status.includes("error")) {
                        // log an error message and notify user
                        console.log(`TestError message ajax: ${result_json_data.status}`);
                        if (result_json_data.status === 'gamenotfound') {
                            handleGameNotFoundError();  // call ajax and handle it
                        }
                        return;
                    }
                    // successfully changed the cur player index
                }
    });
}


// removes green colour and the timer
function unsetCurPlayersTimer() {
    let player_to_move_tag = document.getElementsByClassName("timer")[0];
    if (!player_to_move_tag) {
        return;
    }
    if (USER_PLAYER_IDX !== PLAYER_TO_MOVE_IDX) {  // never change user's colour
        player_to_move_tag.style.color = "#fafafa";
    }
    // remove timer
    let players_children = player_to_move_tag.children;
    player_to_move_tag.removeChild(players_children[players_children.length - 1]);
    let cur_class_name = player_to_move_tag.className;
    player_to_move_tag.className = cur_class_name.substring(0, cur_class_name.indexOf("timer"));

    clearInterval(TIMERS_INTERVAL_ID);

    setUsersIcon();
}


// called only first time when entering the game
function setUsersIcon() {
    let users_tag = document.getElementById("player" + USER_PLAYER_IDX);
    users_tag.style.color = "rgb(221, 181, 94)";
}


// called after CARDS_ON_TABLE_ARR has been updated
// updates cards on table, does not change rotation of previous cards
function updateCardsOnTable() {
    // if player whose turn it is has not yet played then there aro no changes
    let cur_cards_imgs = CARDS_ON_TABLE_DIV.children;
    let new_srcs = getCardsSources(CARDS_ON_TABLE_ARR);

    // remove each card from bottom until cur is found
    let i = 0;  // index of items in new_srcs
    let n = new_srcs.length;

    if (n == 0) {  // remove cur cards, there are none on the table
        CARDS_ON_TABLE_DIV.innerHTML = "";
        return;
    }

    // keep as many old cards on table as possible if
    // there are not any old ones then just add new ones
    for (let j = 0; i < n; i++) {
        let cur_new_src = new_srcs[i];
        while (j < cur_cards_imgs.length) {
            if (cur_cards_imgs[j].getAttribute("src") === cur_new_src) {
                j++;
                break;
            }
            // currently displayed card is obsolete
            CARDS_ON_TABLE_DIV.removeChild(cur_cards_imgs[j]);
            //j++;
        }

        if (j >= cur_cards_imgs.length) {  // all old srcs have been deleted or kept
            break;
        }
    }

    // add the srcs that are left:
    // check if last idx was set or not:
    if (i < n && cur_cards_imgs.length > 0) {  // it was set so move to next i
        i++;
    }

    // Add the cards that were not on the table before
    for (; i < n; i++) {
        let img_tag = document.createElement("img");
        img_tag.setAttribute("src", new_srcs[i]);

        // append when class is added so style can be overwritten
        img_tag.setAttribute("class", "card-on-table");        
        CARDS_ON_TABLE_DIV.appendChild(img_tag);

        let random_angle = getRandomInt(86);
        if (getRandomInt(2) == 1) {  // make direction negative
            random_angle *= -1;
        }
        let transform_value = `transform:rotate(${random_angle}deg)`;
        img_tag.setAttribute("style", transform_value);
        if (IS_PLAYING) {
            img_tag.style.top = "25vh";
        } else {
            img_tag.style.top = "33vh";
        }
        
    }
}


// returns source of each card passed in a list
function getCardsSources(cards) {    
    if (!cards || typeof cards === "undefined") {
        return;
    }

    let sources = [];

    for (let i = 0, n = cards.length; i < n; i++) {
        let cur_src = "./imgs/cards_graphics/";

        // handle first the black cards
        if (cards[i][0] === "D") {  // change colour
            if (cards[i] === "D") {  // no colour was set
                cur_src += "draw4/card_draw4.svg";
            } else {
                cur_src += `draw4/card_draw4${cards[i][1]}.svg`;
            }
            sources.push(cur_src);
            continue;
        }

        if (cards[i][0] === "w") {  // change colour
            if (cards[i] === "w") {  // no colour was set
                cur_src += "changecolour/card_colour_change.svg";
            } else {
                cur_src += `changecolour/card_colour_change${cards[i][1]}.svg`;
            }
            sources.push(cur_src);
            continue;
        }

        // handle cards with colour:
        let card = cards[i];
        let value = card[0];
        let colour = "";

        switch (card[card.length - 1]) {
            case "B":
                cur_src += "blue/";
                colour = "blue";
            break;
            case "Y":
                cur_src += "yellow/";
                colour = "yellow";
            break;
            case "G":
                cur_src += "green/";
                colour = "green";
            break;
            case "R":
                cur_src += "red/";
                colour = "red";
            break;
            default:
                // console.log(`Undefined card category srcs.`);
                continue;
            break;
        }

        let card_name = `card_${colour}`;

        switch (value) {
            case "d":  // draw 2
                card_name += "_plus2";
            break;
            case "s":  // skip player
                card_name += "_skip";
            break;
            case "r":  // reverse
                card_name += "_reverse";
            break;
            default:  // just a number
                card_name += value;
            break;
        }

        card_name += ".svg";
        cur_src += card_name;

        sources.push(cur_src);
    }

    return sources;
}


// returns integer from 0 to max, max is exclusive
function getRandomInt(max) {
    return Math.floor(Math.random() * Math.floor(max));
}


// updates the user's hand (cards)
function updateUsersHand() {
    if (!IS_PLAYING || !USERS_HAND_ARR || typeof USERS_HAND_ARR === "undefined" || USERS_HAND_ARR == "" || USERS_HAND_ARR.length <= 0) {
        return;
    }

    // sort the users's cards appropriately:
    USERS_HAND_ARR = sortHand(USERS_HAND_ARR);

    // get img srcs for each users card
    let sources = getCardsSources(USERS_HAND_ARR);

    /*
        <li class="collection-item">
            <img class="users_card" src="./imgs/cards_graphics/green/card_green6.svg">
        </li>
    */

    CARDS_LIST.innerHTML = "";  // clear old data
    // generate list and display it
    for (let i = 0, n = USERS_HAND_ARR.length; i < n; i++) {
        let li_tag = document.createElement("li");
        li_tag.className = "collection-item";

        let img_tag = document.createElement("img");
        img_tag.className = "users_card";
        img_tag.setAttribute("src", sources[i]);

        li_tag.appendChild(img_tag);
        CARDS_LIST.appendChild(li_tag);
    }

}


// sorts given array of cards, firstly come black cards, then
// cards of the most frequent colour, inside of which special cards are priority
function sortHand(hand) {
    if (!hand || typeof hand === "undefined") {
        return;
    }

    let sorted_hand = [];

    let card_types = new Map();
    // initialise types of cards as keys in the mpa
    card_types.set("D", []);  card_types.set("w", []);
    card_types.set("B", []);  card_types.set("R", []);
    card_types.set("G", []);  card_types.set("Y", []);

    for (let i = 0, n = hand.length; i < n; i++) {
        let cur_card = hand[i];
        let map_key = cur_card[cur_card.length - 1];
        let cur_arr = card_types.get(map_key);
        cur_arr.push(cur_card);  // last char is the key in the map
        card_types.set(map_key, cur_arr);  // update map value
    }

    // add special dark cards first, since there is nothing to sort there
    for (let i = 0, keys = ["w", "D"], n = keys.length; i < n; i++) {
        let key = keys[i];
        let cur_type_cards_arr = card_types.get(key);
        // go through all cards under current key
        for (let j = 0, m = cur_type_cards_arr.length; j < m; j++) {
            sorted_hand.push(cur_type_cards_arr[j]);
        }
        // remove special cards from map:
        card_types.delete(key);
    }

    // sort cards in each subarray (`type`) by significance (plus2, skip, reverse):
    let all_coloured_sorted = [];
    for (let types = card_types.values(), type = types.next().value; type; type = types.next().value) {
        type.sort(compareCardsSameColour);
        all_coloured_sorted.push(type);
    }

    // sort by size of each type of cards:
    all_coloured_sorted.sort(function (a, b) {
        return b.length - a.length;  // from greatest to lowest
    });


    // go through map and insert all subarrays' elements into final output array
    for (let i = 0, n = all_coloured_sorted.length; i < n; i++) {
        let cur_type = all_coloured_sorted[i];
        for (let j = 0, m = cur_type.length; j < m; j++) {
            sorted_hand.push(cur_type[j]);
        }
    }

    return sorted_hand;
}


// criteria: plus2 (d), skip (s), reverse (r), 1, 2, 3...
// returns value smaller than 0 if first card comes
// before the second one, =0 if same, > 0 otherwise
function compareCardsSameColour(first, second) {
    let a = first[0];  // colour is not needed
    let b = second[0];

    // handle special cases first:
    let significance = ["d", "s", "r"];  // lower idx of el in this arr means returning that el first
    let a_idx = significance.indexOf(a);
    let b_idx = significance.indexOf(b);

    // handle both cards being normal:
    if (a_idx < 0 && b_idx < 0) {
        return a - b;  // in ascending order
    }

    // one of the cards is normal
    if (a_idx < 0 || b_idx < 0) {
        return b_idx - a_idx;  // normal card (idx is -1) comes second
    }

    // both cards are special
    return a_idx - b_idx;  // smaller index comes first
}


// for this to work animation.js needs to be loaded first
function displayWinner(winner_name) {
    animateWinnerText(winner_name, 6);
    // duration of audio is 5 seconds
    WINNER_SONG_AUDIO.load();  // reset to start
    WINNER_SONG_AUDIO.play();
}


// chanegs background to what user has specified:
function changeBackground() {
    let data = document.getElementById("backgroundColourInput").value;
    const body_el = document.getElementsByTagName("body")[0];

    if (data.includes("http")) {
        body_el.style.cssText += "background-image: url(" + data + ");";
        console.log(`Here`);
        return;
    }

    // non CSS
    if (!data.includes(":")) {
        body_el.style.cssText += "background-color: " + data + ";";
        return;
    }

    // it is css
    document.body.style.cssText += data;
}


// sets images of all players (even those abscent from the game)
function initialiseUsersIconsImages() {
    for (let i = 0, n = PLAYERS_USERNAMES.length; i < n; i++) {
        const cur_player_tag = document.getElementById("player" + (i + 1).toString());
        cur_player_tag.getElementsByTagName("img")[0].setAttribute("src", generateRandomImageSrc());
    }
}


// returns srcs of icon images from imgs folder:
function generateRandomImageSrc() {
    let folder_size = 10;
    let icon_idx = 2 + getRandomInt(folder_size - 1);  // excluding 1st icon since it is not pretty
    return `./imgs/players_icons/icon${icon_idx}.svg`;
}


// handle user playing his card:
function playCard(card_played) {
    $.ajax({
        type : "POST",
        url  : "./includes/gameupdates.inc.php",
        data : {
            actionType : "playCard",
            cardPlayed : card_played,
            didShout : DID_SHOUT
        },
        dataType: "JSON",  // format of recieved data from server
        success: function(result_json_data){
                    if (result_json_data.status.includes("error")) {
                        // log an error message and notify user
                        console.log(`TestError message through ajax: ${result_json_data.status}`);
                        //alert(status);
                        if (result_json_data.status === 'gamenotfound') {
                            handleGameNotFoundError();  // call ajax and handle it
                        }
                        return;
                    }
                    switch (result_json_data.status) {
                        case "notusersturn":
                            notifyUser("Not your turn just yet");
                        break;
                        case "notusersturn2":  // jump in is on, but it 1st move of new game
                            notifyUser("You cannot jump in on the 1st move of the game");
                        break;
                        case "notuserscard":
                            notifyUser("You are not holding this card?!");
                        break;
                        case "invalidmove":
                            notifyUser("This is an invalid move");
                        break;
                        case "penalisedinvalidmove":
                            notifyUser("Invalid move, you're penalised! Next time draw cards if you don't have what to play.");
                        break;
                        case "invalidshout":
                            notifyUser("You should shout only when you have 2 cards and your current move will leave you with 1");
                        break;
                        case "invalidcard":
                            notifyUser("Card you played is somehow invalid!?");
                        break;
                        default:  // success, do nothing
                        break;
                    }

                }
    });
}


// show UI for picking the colour of the black card and
// once the colour is picked play that newly formed card
function showColourChoices(card_to_play) {
    // show UI, store the current card inside the UI
    CHOOSE_BLACK_CARD_COLOUR_MODAL.open();

    // set the card to change so the on click function can access it
    CUR_BLACK_CARD = card_to_play;
}


// on click function for choosing the colour of the black card
function pickBlackCardColour(colour) {
    switch (colour) {
        case "red":
            CUR_BLACK_CARD += 'R';
        break;
        case "blue":
            CUR_BLACK_CARD += 'B';
        break;
        case "yellow":
            CUR_BLACK_CARD += 'Y';
        break;
        default:
            CUR_BLACK_CARD += 'G';
        break;
    }

    playCard(CUR_BLACK_CARD);

    // hide the colour choices
    CHOOSE_BLACK_CARD_COLOUR_MODAL.close();
}


// sets a toast in the middle of the screen
function notifyUser(msg) {
    M.toast({html: msg, classes: 'rounded'});
}


// send the ajax request to remove the winners name from
// user's playerX column, since he has recieved once, also
// denotes it is the time to deal a new hand since new game begins
function noteWinnerWasFetched() {
    $.ajax({
        type : "POST",
        url  : "./includes/gameupdates.inc.php",
        data : {
            actionType : "removeWinner"
        },
        dataType: "JSON",  // format of recieved data from server
        success: function(result_json_data){  
                    if (result_json_data.status !== "success") {
                        // log an error message and notify user
                        console.log(`TestError message through ajax: ${result_json_data.status}`);
                        if (result_json_data.status === 'gamenotfound') {
                            handleGameNotFoundError();  // call ajax and handle it
                        }
                        return;
                    }
                    //console.log(`removed winner's name`);
                }
    });
}


// alerts if user is only one on the server, this is run just once entered:
function alertIfAlone() {
    if (isAlone()) {
        alert(`Waiting for other players to join..\nIf you leave now the game will be deleted forever.`);
    }
}


// returns true if the game has not yet started and waiting
// for players to join
function isAlone() {
    let is_alone = true;

    for (let i = 0, n = PLAYERS_USERNAMES.length; i < n; i++) {
        // user himself does not count as company
        if (i + 1 == USER_PLAYER_IDX || !parseInt(PLAYERS_NUM_CARDS_LEFT[i]) || PLAYERS_USERNAMES[i] === "") {
            continue;
        }
        is_alone = false;
        break;
    }

    //IS_PLAYING = false;
    return is_alone;
}


// deal the user cards 3 per dealing turn
// display cards being dealt to all players who are not waiting
function dealUsersHands() {
    if (isAlone()) {
        return;
    }
    IS_DEALING = true;

    // find the number of cards to deal to each player
    let num_cards_hand = 0;
    let active_pl_indices = [];
    for (let i = 0, n = PLAYERS_NUM_CARDS_LEFT.length; i < n; i++) {
        if (!parseInt(PLAYERS_NUM_CARDS_LEFT[i]) || PLAYERS_NUM_CARDS_LEFT[i] === "waiting") {
            continue;  // these players do not get their cards dealt in the animation
        }
        num_cards_hand = parseInt(PLAYERS_NUM_CARDS_LEFT[i]);
        active_pl_indices.push(i);
    }


    // deal to all players just one card, with small delay:
    
    // the card which represents the deck:
    const deck_card = document.getElementsByClassName("cards-deck")[0].children[0];
    let one_animation_duration = 1.2;  // in seconds, animation has 350ms delay at the end before removing the card
    let delay = 1.75;  // delay between two rounds of dealt cards in seconds

    loadFullScreen();

    // deal by 3 or less
    let card_per_turn = 3;
    for (let z = 0, n = Math.ceil(num_cards_hand / card_per_turn); z < n; z++) {
        setTimeout(function() {  // each dealing turn is delayed
            for (let j = 0, m = active_pl_indices.length; j < m; j++) {
                const cur_player_tag = document.getElementById("player" + (active_pl_indices[j] + 1).toString());
                let rect = cur_player_tag.getBoundingClientRect();
                let pl_icon_tag_coo = [rect["left"], rect["top"]];
                // deal all the cards for this turn (3 or less)
                for (let i = 0, k = Math.min(card_per_turn, num_cards_hand - z * card_per_turn); i < k; i++) {
                    setTimeout(function() {
                        animateCardTravelling(pl_icon_tag_coo, one_animation_duration, deck_card);
                    }, i * 275);
                }
            }
        }, 1000 * delay * z);
    }

    // display all cards user got only 100ms after the last round's card that reached him was dealt and has faded
    let max_z = Math.ceil(num_cards_hand / card_per_turn) - 1;
    let max_k = Math.min(card_per_turn, num_cards_hand - max_z * card_per_turn) - 1;
    let time_of_last_card = one_animation_duration * 1000 + 275 * max_k + 1000 * delay * max_z;

    setTimeout(function() {
        unloadFullScreen();
        updateUsersHand();
        IS_DEALING = false;
    }, time_of_last_card + 350 + 100);
}


// animation of user drawing cards from the deck:
function animateDrawingCardsFromDeck(num_cards) {
    const deck_card = document.getElementsByClassName("cards-deck")[0].children[0];
    const rect = CARDS_LIST.getBoundingClientRect();
    const target_tag_coo = [rect["left"] + 0.5 * rect["width"], rect["top"] - 110];  // add to middle of cards list and some value from top

    const one_animation_duration = 0.8;  // in seconds
    for (let i = 0; i < num_cards; i++) {
        setTimeout(function() {
            animateCardTravelling(target_tag_coo, one_animation_duration, deck_card);
        }, i * 250);
    }
}


// update the value of the button to send the request for drawing
// cards or paying penalty if any
function updateDrawCardsForm() {
    /*
        <button id="drawCardsBtn" class="btn-small waves-effect waves-light" type="submit" name="actionType">
            <i style="margin-right: 5px;"><b>Draw Card</b></i>
        </button>
    */
    if (HAS_PENALTY_TO_PAY) {  // display option of paying the penalty
        DRAW_CARDS_FROM_DECK_BTN.setAttribute("value", "payPenalty");
        DRAW_CARDS_FROM_DECK_BTN.style = "color: #fcfcfd; background-color: #cfa023;";
        DRAW_CARDS_FROM_DECK_BTN.children[0].children[0].textContent = "Pay Penalty";
    } else if (HAS_DRAWN_FROM_DECK) {  // show the option of passing since user cannot play a move
        DRAW_CARDS_FROM_DECK_BTN.setAttribute("value", "passTurn");
        DRAW_CARDS_FROM_DECK_BTN.style = "color: #fcfcfd; background-color: #1b603a;";
        DRAW_CARDS_FROM_DECK_BTN.children[0].children[0].textContent = "Pass";
    } else {
        DRAW_CARDS_FROM_DECK_BTN.setAttribute("value", "drawCard");
        DRAW_CARDS_FROM_DECK_BTN.style = "";
        DRAW_CARDS_FROM_DECK_BTN.children[0].children[0].textContent = "Draw Card";
    }
}


// handle ajax requests for drawing a card, passing a turn and paying penalty
function handleDrawCardsBtn() {
    let requestValue = DRAW_CARDS_FROM_DECK_BTN.getAttribute("value");
    $.ajax({
        type : "POST",
        url  : "./includes/gameupdates.inc.php",
        data : {
            actionType : requestValue.toString()
        },
        dataType: "JSON",  // format of recieved data from server
        success: function(result_json_data){
                    if (result_json_data.status.includes("error")) {
                        // log an error message and notify user
                        console.log(`TestError message through ajax: ${result_json_data.status}`);
                        //alert(status);
                        if (result_json_data.status === 'gamenotfound') {
                            handleGameNotFoundError();  // call ajax and handle it
                        }
                        return;
                    }

                    if (result_json_data.status.includes("drawn:")) {  // display when cards are drawn
                        let num_cards = parseInt(result_json_data.status.substring("drawn:".length));
                        console.log(`NUm cards to draw: ${num_cards}`);
                        animateDrawingCardsFromDeck(num_cards);
                        return;
                    }

                    switch (result_json_data.status) {
                        case "notusersturn":
                            notifyUser("Not your turn just yet");
                        break;
                        case "alreadydrawn":
                            notifyUser("You have already drawn a card!? You should pass your turn.");
                        break;
                        case "invalidmove":
                            notifyUser("This is an invalid move");
                        break;
                        case "nopenalty":
                            notifyUser("There is no penalty to pay!?");
                        break;
                        case "hasnotdrawn":
                            notifyUser("You have to draw card(s) in order to pass a turn");
                        break;
                        default:  // success, do nothing
                        break;
                    }

                }
    });
}
