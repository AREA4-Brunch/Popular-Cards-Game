VANTA.HALO({
    el: "#vantajs",
    mouseControls: true,
    touchControls: true,
    minHeight: 200.00,
    minWidth: 200.00,
    backgroundColor: 0x1a2254,
    amplitudeFactor: 1.40,
    size: 0.85
});

// ==================================
// Global constants and DOM Elements:

const switch_tag = document.getElementById("hostGameSwitch");
const host_game_first_div = document.getElementById("firstField");

const hostGameErrorSpan = document.getElementById("hostGameErrorStatus");
const joinGameErrorSpan = document.getElementById("joinGameErrorStatus");

const maxPlayersInputField = document.getElementsByName("gplayernum")[0];

const openGamesTableBodyTag = document.getElementsByTagName("tbody")[0];

// =================================
// set the modal
document.addEventListener('DOMContentLoaded', function() {
    let elems = document.querySelectorAll('.modal');
    let instances = M.Modal.init(elems, {});
});


// set the switch Private/Public acitvity and load appropriate form:
switch_tag.addEventListener("change", function(e) {
    let is_private = this.checked;

    if (is_private === true) {
        renderPrivateHostGameForm();
    } else {
        renderPublicHostGameForm();
    }
});


// ===============
// DRIVER PROGRAM
updateTable();
setInterval(function() {
    updateTable();
}, 4000);



function renderPrivateHostGameForm() {
    host_game_first_div.innerHTML = "";

    let input_field = document.createElement("input");
    input_field.setAttribute("name", "gpwd");
    input_field.setAttribute("placeholder", "Password");
    input_field.setAttribute("type", "text");
    input_field.setAttribute("autocomplete", "off");

    host_game_first_div.appendChild(input_field);
}


function renderPublicHostGameForm() {
    host_game_first_div.innerHTML = "";

    let input_field = document.createElement("input");
    input_field.setAttribute("name", "gtitle");
    input_field.setAttribute("placeholder", "Game Name");
    input_field.setAttribute("type", "text");
    input_field.setAttribute("autocomplete", "off");

    host_game_first_div.appendChild(input_field);
}


// send the join game request for an open game:
function joinOpenGame(idGame) {
    $.ajax({
        type : "POST",  //type of method
        url  : "./includes/handleopengtables.inc.php",  //your page
        data : { idGameOpen : idGame },
        success: function(result_data){  
                    if (result_data !== "success") {
                        // log an error message and notify user
                        console.log(`TestError message: ${result_data}`);
                        alert(result_data);
                        return;
                    }
                    // else everything went awesome and user is now
                    // a part of the game, so just route him to
                    // the game page, all session variables so far have
                    // been handled and set
                    console.log("Routing to the open game.");
                    window.location.replace("./game.php");  // it is relative to .html page location, not .js
                }
    });
}

// set on click listener to the open games table
// which triggers with the click on any table row
openGamesTableBodyTag.addEventListener("click", function(e) {
    if (e.target) {
        console.log("Trying to join open game..");
        let idGame = "";

        switch(e.target.nodeName) {
            case "TR":
                idGame = e.target.children[0].children[0].textContent;
            break;
            case "TD":
                idGame = e.target.parentNode.children[0].children[0].textContent;
            break;
            default:  // most likely any italic text among all columns
                idGame = e.target.parentNode.parentNode.children[0].children[0].textContent;
        }

        joinOpenGame(idGame);
    }

    console.log("Somebody touched me");
});


function addTableColumn(table_row, column_value) {
    let table_data = document.createElement("td");
    let italic = document.createElement("i");

    // set columns one by one
    italic.innerHTML = column_value;
    italic.setAttribute("style", "font-size: 14px;");
    table_data.appendChild(italic);
    table_row.appendChild(table_data);

    return table_row;
}


// builds the table with open games data:
function buildTableRow(game_idx, game_title, joined_players, max_players, host) {
    // joined_players, max_players are strings
    console.log(`Open game: ${game_title}, ${joined_players}, ${max_players}, ${host}`);

    /*
        Example of one table row:
        <tr>
            <td><i>Game ID</i></td>
            <td><i>Empty Name</i></td>
            <td><i>8 / 12</i></td>
            <td><i>Long Username</i></td>
        </tr>
    */
    let table_row = document.createElement("tr");

    // add each column to cur row
    table_row = addTableColumn(table_row, game_idx);
    table_row = addTableColumn(table_row, game_title);
    table_row = addTableColumn(table_row, joined_players + " / " + max_players);
    table_row = addTableColumn(table_row, host);

    openGamesTableBodyTag.appendChild(table_row);
}


function updateTable() {
    $.ajax({
        type : "POST",
        url  : "./includes/handleopengtables.inc.php",
        data : {
            updateOpenGamesTable : true
        },
        dataType: "JSON",  // format of recieved data from server
        success: function(result_json_data) {  
                if (result_json_data.status !== "success") {
                    // log an error message and notify user
                    console.log(`TestError message through ajax: ${result_json_data.status}`);
                    return;
                }
                
                // Update table values:
                openGamesTableBodyTag.innerHTML = "";  // clear table first
                for (let i = 0, n = result_json_data['openGames'].length; i < n; i++) {
                    let params = result_json_data['openGames'][i];  // list of parameters for building 1 table row
                    buildTableRow(params[0], params[1], params[2], params[3], params[4]);
                }
                
            }
    });
}
