<?php


require 'autoincluder.inc.php';


if (function_exists('random_int')) {
    function csprngShuffle (&$arr) {  // Fisher-Yates algo
        // Start from the last element  
        // and swap one by one. We  
        // don't need to run for the 
        // first element that's why i > 0 

        $n = count($arr);
        for($i = $n - 1; $i > 0; $i--) { 
            // Pick a random index 
            // from 0 to i (inclusive)
            $j = random_int(0, $i);
    
            // Swap arr[i] with the  
            // element at random index 
            $tmp = $arr[$i]; 
            $arr[$i] = $arr[$j]; 
            $arr[$j] = $tmp; 
        }
    }
}


// shuffles deck if possible with cryptographically secure RNG
function shuffleDeckArray(&$deck) {
    if (function_exists('csprngShuffle')) {
        csprngShuffle($deck);
        csprngShuffle($deck);
    } else {
        echo 'Doing normal shuffle';
        shuffle($deck);
        shuffle($deck);
    }
}


function formStrDeckFromArr($deck) {
    $new_deck = '';
    foreach ($deck as $card) {
        $new_deck .= $card . '_';
    }
    return $new_deck;
}


// returns string which represents a shuffled deck
function generateDeck() {
    // deck of cards: http://play-k.kaserver5.org/Uno.html

    // d - draw 2 cards, D - draw 4 cards, s - skip player, r - reverse direction, w - wild card - change suit
    // wR - means that colour was changed to red using wild card (set client wise, not here in the deck)
    $special_cards = array('D', 'w');  // there are 4 wild and 4 draw-4 cards

    // set correct number of special cards
    foreach ($special_cards as $cur_card) {
        for ($i = 0; $i < 3; $i++) {
            array_push($special_cards, $cur_card);
        }
    }


    $normal_cards = array('d', 's', 'r');  // each suit contains only one 0 card and 2 other normal cards    
    foreach ($normal_cards as $cur_card) {
        array_push($normal_cards, $cur_card);
    }

    array_push($normal_cards, '0');

    for ($i = 1; $i <= 9; $i++) {
        array_push($normal_cards, strval($i), strval($i));
    }

    // form the deck with cards of each suit:

    $deck = $special_cards;

    $suits = array('B', 'R', 'Y', 'G');  // blue, red, yellow, green
    foreach ($suits as $suit) {
        foreach ($normal_cards as $cur_card) {
            array_push($deck, $cur_card . $suit);
        }
    }

    // try shuffling with cryptographically secure RNG
    shuffleDeckArray($deck);

    // deck is outputted as string
    $new_deck = formStrDeckFromArr($deck);
    
    return $new_deck;
}


// handle adding a new player to the game:
function addPlayer($gameOldData, $username) {  // input is database row
    // can't fit the player
    if ($gameOldData['joinedPlayers'] >= $gameOldData['maxPlayers']) {
        throw new TestException('noroom');
    }

    // update joinedPLayers - increase it by 1
    // user is added to the cur round players only when new round starts so change nothing there
    // to know which playerX col to assign to new user check for first empty
    // column, no joined player's column should be empty, they should have
    // at least some indicator if there are no cards in player's hand

    $assigned_player_col = 0;

    for ($i = 1, $n = $gameOldData['maxPlayers']; $i <= $n; $i++) {
        $col_key = 'player' . strval($i);
        // assign first empty player column to new user
        if ($gameOldData[$col_key] === '') {
            $assigned_player_col = $i;
            break;
        }
    }

    if (!$assigned_player_col) {
        throw new TestException('noplayerx');
    }


    // Update data:
    // make sure not to update before all possible Test exceptions are thrown
    $db = new DataBase();

    $gameId = $gameOldData['idGames'];

    // increase the number  of joined players
    $updated_joinedPlayers = 1 + (int)$gameOldData['joinedPlayers'];
    $db->setGameCol($gameId, 'joinedPlayers', $updated_joinedPlayers);

    // reserve playerX column for this user
    $col_to_reserve = 'player' . strval($assigned_player_col);
    //$db->resetConnection();
    // `_1_` indicates that the user has voted (set as 1 by default, changes only when voting is initialised by some user)
    // additional `_` represents end of winners name (should be between two `_`)
    $db->setGameCol($gameId, $col_to_reserve, '_1__' . $username . ':' . 'waiting');
    $db->closeConnection();

    if ($updated_joinedPlayers == 2) {
        startNewGame($gameId);
    }

    return strval($assigned_player_col);
    // Session variables will get set in the script which calls this function
}


// unset all session variables
function unsetAllGameData() {
    session_start();
    unset($_SESSION['gameId']);
    unset($_SESSION['gamePlayerCol']);
}


// returns index of last `:` in the given string
// returns false if not found
function lastColonPos($haystack) {
    $i = strlen($haystack) - 1;
    for (; $i >= 0; $i--) {
        if ($haystack[$i] === ':') {
            break;
        }
    }
    if ($i < 0) {
        return false;
    }
    return $i;
}


// when user QUITs the game
function removePlayer() {
    // Clear user's cards data (add to the bottom of the deck and shuffle deck),
    // remove him from cur round players or joined players
    // update cur player idx if necessary
    session_start();

    $gameId = $_SESSION['gameId'];
    $players_data_col = 'player' . $_SESSION['gamePlayerCol'];

    $db = new DataBase();
    $cols_to_get = 'joinedPlayers curRoundPlayers curPlayerIdx deck ' . $players_data_col;
    try {
        $cur_data = $db->getGameData('idGames', $gameId, $cols_to_get);  // errors handled by caller of removePlayer
    } catch (TestException $e) {
        $db->closeConnection();
        unsetAllGameData();  // removes all session variables
    }
    

    // clear user's cards from database:
    $db->setGameCol($gameId, $players_data_col, '');

    // append user's cards to deck and shuffle
    // do nothing if he had no cards in his hands:
    $players_hand = substr($cur_data[$players_data_col], 1 + lastColonPos($cur_data[$players_data_col]));
    if ($players_hand !== 'waiting' && $players_hand !== 'winner') {
        $new_deck_arr = array_merge(explode("_", $cur_data['deck']), explode("_", $players_hand));
        shuffleDeckArray($new_deck_arr);
        $new_deck = formStrDeckFromArr($new_deck_arr);  // string version

        $db->setGameCol($gameId, 'deck', $new_deck);
    }
    // update cur players value:

    // DELETE TH GAME if cur user was last user in the game:
    $cur_joined_players = (int) $cur_data['joinedPlayers'];
    if ($cur_joined_players - 1 <= 0) {
        $db->deleteGame($gameId);
        $db->closeConnection();
        unsetAllGameData();  // removes all session variables
    } else {
        $db->setGameCol($gameId, 'joinedPlayers', max(0, $cur_data['joinedPlayers'] - 1));
    }


    // update cur round players:
    $new_cur_round_players = str_replace($_SESSION['gamePlayerCol'] . '_', '', $cur_data['curRoundPlayers']);
    // clear player if it was at start of string, therefore no underscore
    //$new_cur_round_players = str_replace($_SESSION['gamePlayerCol'], '', $new_cur_round_players);

    $db->setGameCol($gameId, 'curRoundPlayers', $new_cur_round_players);

    
    // if there is going to be just one player left alone, set him
    // to be waiting
    if ($cur_joined_players - 1 === 1) {
        $players_col_name = 'player' . $new_cur_round_players[0];
        $old_players_hand_data = $db->getGameData('idGames', $gameId, $players_col_name)[$players_col_name];
        $db->setGameCol($gameId, $players_col_name, substr($old_players_hand_data, 0, 1 + lastColonPos($old_players_hand_data)) . 'waiting');
    }

    // update cur player idx if it was this player's turn to play
    if ($cur_data['curPlayerIdx'] == $_SESSION['gamePlayerCol']) {
        /*
        $old_cur_round_players = $cur_data['curRoundPlayers'];
        $last_player = substr($old_cur_round_players, strlen($old_cur_round_players) - 3);  // -3 in case it is 2 digit index
        if (strpos($last_player, $_SESSION['gamePlayerCol'] . '_')) {
            // set to the first cur round player
            $db->setGameCol($gameId, 'curPlayerIdx', $new_cur_round_players[0]);
        }*/
        $game_direction = (int) ($db->getGameData('idGames', $gameId, 'direction'))['direction'];  // neccessary to determine next player's index
        $db->setGameCol($gameId, 'curPlayerIdx', strval(getNextPlayerIdx($_SESSION['gamePlayerCol'], $cur_data['curRoundPlayers'], $game_direction)));
    }

    $db->closeConnection();
    unsetAllGameData();  // removes all session variables
}


function startJumpInVote($gameId) {
    // sets `?` to denote voting has started and
    // resets previous voting result
    $db = new DataBase();
    $db->setGameCol($gameId, 'jumpInVotes', '?0');

    // reset previous user's data which means they now have not yet voted
    for ($i = 1; $i <= 12; $i++) {
        $cur_pl_hand = ($db->getGameData('idGames', $gameId, 'player' . $i))['player' . $i];
        if (!$cur_pl_hand || $cur_pl_hand === '') {  // no need to change empty column
            continue;
        }
        $cur_pl_hand = '_0_' . substr($cur_pl_hand, strlen('_0_'));
        $db->setGameCol($gameId, 'player' . $i, $cur_pl_hand);
    }

    $db->closeConnection();
}


function handleJumpInVote($vote, $gameId, $playersColIdx) {  // vote is 1 or 0
    // if there is `?` game is not going to allow jumpin
    // `?` is removed if the votes go over the half of joined users
    $db = new DataBase();

    $cur_data = $db->getGameData('idGames', $gameId, 'jumpInVotes joinedPlayers');
    $cur_votes_raw = $cur_data['jumpInVotes'];

    $cur_votes = (int) substr($cur_votes_raw, 1);  // get # of votes
    $new_votes = $cur_votes + $vote;

    // update value:
    $updated = '';

    // check if with this vote it makes more than half:
    $half = floor(((int) $cur_data['joinedPlayers']) / 2);
    if ($new_votes > $half) {  // remove `?`
        $updated = strval($new_votes);
    } else {
        $updated = '?' . strval($new_votes);
    }

    $db->setGameCol($gameId, 'jumpInVotes', $updated);
    // mark that user has voted
    $cur_pl_hand = ($db->getGameData('idGames', $gameId, 'player' . $playersColIdx))['player' . $playersColIdx];
    $cur_pl_hand = '_1_' . substr($cur_pl_hand, strlen('_1_'));
    $db->setGameCol($gameId, 'player' . $playersColIdx, $cur_pl_hand);

    $db->closeConnection();
    //return 'new value:' . $updated;
}


// helper function returns true if `string` ends with `test`
function endswith($haystack, $needle) {
    $strlen = strlen($haystack);
    $needle_len = strlen($needle);
    if ($needle_len > $strlen) return false;
    return substr_compare($haystack, $needle, $strlen - $needle_len, $needle_len) === 0;
}


// modifies deck and deals cards to each player, # of cards is
// determined by the # of players
function dealCards(&$deck, $num_players) {
    $dealt_hands = array_fill(0, $num_players, '');

    // if num of players <= 6 then deal 7 cards
    // otherwise 6 cards, if greater than 10 then 5 cards
    $num_cards = 7;
    if ($num_players <= 6) {
        $num_cards = 7;
    } else if ($num_players < 10) {
        $num_cards = 6;
    } else {
        $num_cards = 5;
    }

    // deal each player in groups of 3
    while ($num_cards > 0) {
        $num_cards_to_deal = min(3, $num_cards);  // # of cards to deal in this group
        $num_cards -= $num_cards_to_deal;

        for ($player_idx = 0; $player_idx < $num_players; $player_idx++) {
            // find the index of the end of the nth card
            $i = 0;
            for ($counter = 0, $n = strlen($deck); $i < $n; $i++) {
                if ($deck[$i] === '_') {  // the end of `counter`th card
                    $counter++;
                    if ($counter === $num_cards_to_deal) {
                        break;
                    }
                }
            }
            $cur_hand = substr($deck, 0, $i + 1);
            $deck = substr($deck, $i + 1);  // remove dealt cards

            $dealt_hands[$player_idx] .= $cur_hand;
        }
    }

    return $dealt_hands;
}


// starts a new game:
function startNewGame($gameId) {
    $db = new DataBase();
    $gameOldData = $db->getGameData('idGames', $gameId);
    // first add all players who have been waiting for the game and
    // update cur round players value as well
    $added_players_indices = [];
    for ($i = 1, $n = (int) $gameOldData['maxPlayers']; $i <= $n; $i++) {
        // select only players who have been waiting
        $cur_pl_data = $gameOldData['player' . strval($i)];
        if (!$cur_pl_data || $cur_pl_data == '' || lastColonPos($cur_pl_data) === false) {
            continue;  // this player is not joined in
        }
        array_push($added_players_indices, strval($i));
        // exclude the `waiting` keyword from the end, it is being replaced with dealt cards anyway
        //$db->setGameCol($gameId, 'player' . strval($i), substr($cur_pl_data, 0, 1 + lastColonPos($cur_pl_data)));
    }

    $new_cur_round_players = '';
    foreach ($added_players_indices as $player_idx) {
        $new_cur_round_players .= $player_idx . '_';
    }

    $db->setGameCol($gameId, 'curRoundPlayers', $new_cur_round_players);

    // no need to change cur players idx unless it is invalid
    if (!((int) $gameOldData['curPlayerIdx']) || !$gameOldData['curPlayerIdx'] || $gameOldData['curPlayerIdx'] === '' || strpos($new_cur_round_players, strval($gameOldData['curPlayerIdx'])) === false) {
        $db->setGameCol($gameId, 'curPlayerIdx', strval($new_cur_round_players[0]));  // set first player, remove `_`
        //$db->setGameCol($gameId, 'curPlayerIdx', '8');  // set first player, remove `_`
    }

    // change direction to 1 (clockwise which is the default)
    $db->setGameCol($gameId, 'direction', '1');

    // clear the timer data
    $db->setGameCol($gameId, 'timeRanOut', '');

    // clear last penalty
    $db->setGameCol($gameId, 'wasPenaltyPayed', '0');

    // set the value to indicate the player to move has not drawn any cards yet
    $db->setGameCol($gameId, 'hasDrawn', '0');

    // generate deck
    $new_deck = generateDeck();

    // set talon as first normal(contains a number) card:
    $start_of_normal_card = 0;
    for ($i = 0, $n = strlen($new_deck); $i < $n; $i++) {
        if (is_numeric($new_deck[$i])) {
            $start_of_normal_card = $i;
            break;
        }
    }

    // clear out the cards on talon and set new one
    $end_of_normal_card = strpos($new_deck, '_', $start_of_normal_card);
    $db->setGameCol($gameId, 'lastTwoCards', substr($new_deck, $start_of_normal_card, 1 + $end_of_normal_card - $start_of_normal_card));

    $new_deck = substr($new_deck, 0, $start_of_normal_card) . substr($new_deck, 1 + $end_of_normal_card);

    // then deal cards to all players (timing of animation is handled client wise)
    // ignore last `_`
    $players_to_deal_to = explode('_', substr($new_cur_round_players, 0, strlen($new_cur_round_players) - 1));
    $dealt_cards = dealCards($new_deck, count($players_to_deal_to));  // modifies the deck
    for ($i = 0, $n = count($players_to_deal_to); $i < $n; $i++) {
        $pl_idx = $players_to_deal_to[$i];
        $start_user_hand_idx = lastColonPos($gameOldData['player' . $pl_idx]);  // index of the beginning of user's hand data
        $db->setGameCol($gameId, 'player' . $pl_idx, substr($gameOldData['player' . $pl_idx], 0, 1 + $start_user_hand_idx) . $dealt_cards[$i]);
    }

    // finally update the deck with the new generated one,
    // which lost some cards by dealing them to players
    $db->setGameCol($gameId, 'deck', $new_deck);

    $db->closeConnection();
}


// returns index at which the name of the user in playerX column starts
function getStartOfUserNameIdx($player_col_data) {
    $j = strlen('_0_');  // starting index, lookinf for 1st `_`
    for ($n = strlen($player_col_data); $j < $n; $j++) {
        if ($player_col_data[$j] === '_') {
            break;
        }
    }
    return $j + 1;
}


// send all neccessarry ajax data:
function getUpdateData($gameId, $gamePlayerCol) {
    $new_data['status'] = 'error';

    $db = new DataBase();
    try {
        $gameOldData = $db->getGameData('idGames', $gameId);
    } catch (TestException $e) {
        $db->closeConnection();
        $new_data['status'] = $e->getMessage();
        return $new_data;
    }
    
    $db->closeConnection();

    // start new game if all players happen to be waiting or
    // just one of them is not waiting:
    if (substr_count($gameOldData['curRoundPlayers'], '_') <= 1 && (int) $gameOldData['joinedPlayers'] > 1) {
        startNewGame($gameId);
    }


    // return user's player idx
    $new_data['userPlayerIdx'] = $gamePlayerCol;

    // set the idx of player to move:
    $new_data['playerToMoveIdx'] = $gameOldData['curPlayerIdx'];

    // return if the user is a part of the game:
    $players_hand_str = $gameOldData['player' . $gamePlayerCol];
    // if it contains info he is waiting then he is not part of the game
    if (endswith($players_hand_str, 'waiting')) {
        $new_data['isPlaying'] = false;
    } else {
        $new_data['isPlaying'] = true;
    }

    // is jumping in allowed, if there is `?` then it has not yet been allowed
    if (strpos($gameOldData['jumpInVotes'], '?') === false) {
        $new_data['isJumpInAllowed'] = true;
    } else {
        $new_data['isJumpInAllowed'] = false;
    }

    // mark if user is suppost to vote
    if (substr($players_hand_str, 0, strlen('_x_')) === '_0_') {
        // user has not yet voted:
        $new_data['didNotVote'] = true;
    } else {
        $new_data['didNotVote'] = false;
    }

    // check if user has a penalty piled up to pay and whether he can pass or has to draw:
    if ($gamePlayerCol === $new_data['playerToMoveIdx'] && ((int) $gameOldData['wasPenaltyPayed']) > 0) {
        $new_data['hasPenaltyToPay'] = true;
    } else {
        $new_data['hasPenaltyToPay'] = false;
    }

    // track if player should be shown the option of passing the turn:
    if ($gamePlayerCol === $new_data['playerToMoveIdx']) {
        $new_data['hasDrawn'] = ($gameOldData['hasDrawn'] === '1' ? true : false);  // 0 or 1
    } else {
        $new_data['hasDrawn'] = false;
    }

    // return players' usernames:
    $players_usernames = [];
    $players_num_cards_left = [];  // contains # of cards each player is holding
    for ($i = 1; $i <= 12; $i++) {
        $cur_pl_hand = $gameOldData['player' . $i];
        $last_colon_idx = lastColonPos($cur_pl_hand);
        // the name of the winner comes after the data regarding jump in votes
        // search for that last `_` before the username
        $j = getStartOfUserNameIdx($cur_pl_hand);  // starting index, looking for 1st `_`
        $cur_pl_username = substr($cur_pl_hand, $j, $last_colon_idx - $j);
        array_push($players_usernames, $cur_pl_username);

        // display waiting text or the # of cards
        $cur_hand_cards_display = substr($cur_pl_hand, 1 + $last_colon_idx);
        if ($cur_hand_cards_display === 'waiting') {
            array_push($players_num_cards_left, $cur_hand_cards_display);
        } else {
            // each card has `_`
            $num_cards_holding = substr_count($cur_hand_cards_display, '_');
            array_push($players_num_cards_left, (string) $num_cards_holding);
        }
        // reached users playerX column, set everything which depends on it
        if (strval($i) == $gamePlayerCol) {
            // turn into array, also do not include last `_` from the end
            if ($cur_hand_cards_display === 'waiting') {
                $new_data['usersHand'] = [];
            } else {
                $new_data['usersHand'] = explode('_', substr($cur_hand_cards_display, 0, strlen($cur_hand_cards_display) - 1));
            }
            // send winners name if any, otherwise just empty string
            $length_of_winners_name = $j - 1 - strlen('_0_');
            if ($length_of_winners_name > 0) {
                $new_data['winnersName'] = substr($cur_pl_hand, strlen('_0_'), $length_of_winners_name);
            } else {
                $new_data['winnersName'] = '';
            }
        }
    }
    $new_data['playersUsernames'] = $players_usernames;
    $new_data['playersNumCardsLeft'] = $players_num_cards_left;

    // get the array of cards on the table,
    // also do not include last `_` from the end:
    $new_data['cardsOnTable'] = explode('_', substr($gameOldData['lastTwoCards'], 0, strlen($gameOldData['lastTwoCards']) - 1));

    $new_data['status'] = 'success';

    return $new_data;
}


// returns true if given card is playable on the given talon
function isValidMove($card_to_play, $did_jump_in, $top_of_talon) {
    // move is valid only if the cards are of the same colour or the same type
    // when jumping in somebody else's turn they must be both
    // special cards' effect was handled in the `playCardFromHand` function when called
    // on previous player

    if ($did_jump_in) {
        // if the card is black ignore the suit
        if ($card_to_play[0] === 'D' || $card_to_play[0] === 'w') {
            if ($card_to_play[0] === $top_of_talon[0]) {
                return true;
            }
            return false;
        }

        if ($card_to_play[0] === $top_of_talon[0] && $card_to_play[1] === $top_of_talon[1]) {
            return true;
        }
        return false;
    }

    // if current card is a black one it can go on top of any card
    if ($card_to_play[0] === 'D' || $card_to_play[0] === 'w') {
        return true;
    }

    if ($card_to_play[0] !== $top_of_talon[0] && $card_to_play[1] !== $top_of_talon[1]) {
        return false;
    }

    return true;
}


// returns the index of playerX from current round to play, keeps
// track of the weird order of players on clients side
// also pays attention to the direction in which the game is played
function getNextPlayerIdx($cur_pl_idx, $cur_round_players, $game_direction) {
    $order = [1, 3, 11, 6, 8, 10, 4, 2, 12, 7, 5, 9];  // client side order of players (clockwise)
    $active_players = explode('_', substr($cur_round_players, 0, strlen($cur_round_players) - 1));  // turn to array of indices, exclude last `_`
    $cur_idx = (int) $cur_pl_idx;

    // game direction is 1 for clokwise, -1 for not clockwise
    if ((int) $game_direction < 0) {
        $order = array_reverse($order);
    }

    // find next position from current which exists among active players:
    $match_idx = 0;  // # of how many players to skip
    // find the current player in the `order` array:
    $i = 0;
    for ($j = 0, $n = count($order); $j < $n; $j++) {
        if ($order[$j] === $cur_idx) {
            $i = $j;
            break;
        }
    }


    $matches = array();  // stores player indices of soonest players to in the `order`
    do {
        $i++;
        $i = $i % count($order);
        if (array_search($order[$i], $active_players) !== false) {  // found valid position
            $match_idx--;
            array_push($matches, $order[$i]);
        }
    } while ($match_idx >= 0);

    return $matches[count($matches) - 1];
}


// return a card drawn from the deck, remove those cards from the deck
function drawCardsFromDeck($num_cards_to_draw, &$deck) {
    if (!strlen($deck)) {
        return '';
    }
    $i = 0;  // index of the end of an nth card
    for ($counter = 0, $n = strlen($deck); $i < $n; $i++) {
        if ($deck[$i] === '_') {
            $counter++;
        }
        if ($counter === $num_cards_to_draw) {
            break;
        }
    }

    $drawn_cards = substr($deck, 0, $i + 1);
    $deck = substr($deck, $i + 1);
    return $drawn_cards;
}


// handle the case when user's time runs out (this request can be sent from any user) if
// >=65% of current players sent the request with the correct current player index then it is no longer that
// player's turn, also handle case with 2 players playing, each player can send request just once since the timer
// update is stopped on client's side first time this request is sent
function handleCurPlayersTimeRunOut($gameId, $player_reporting_change_idx) {
    $db = new DataBase();

    try {
        $gameOldData = $db->getGameData('idGames', $gameId, 'joinedPlayers timeRanOut curRoundPlayers curPlayerIdx direction deck wasPenaltyPayed hasDrawn');
    } catch (TestException $e) {
        $db->closeConnection();
        return 'error: ' . $e->getMessage();
    } catch (Exception $e) {
        $db->closeConnection();
        return 'unknown error: ' . $e->getMessage();
    }   

    // if this user already sent request just discard it, or
    // if requst is being sent when just 1 player is left and game has not started yet:
    if (strpos($gameOldData['timeRanOut'], $player_reporting_change_idx . '_') !== false || substr_count($gameOldData['curRoundPlayers'], '_') <= 1) {
        $db->closeConnection();
        return 'success';
    }

    // increase the number of votes:
    $new_num = 1 + substr_count($gameOldData['timeRanOut'], '_');  // 1 + number of players who voted
    // check if now it makes the >= 65%:
    $consensus = ceil(0.65 * ((int) $gameOldData['joinedPlayers']));
    if ($new_num < $consensus) {
        // update the value and return since the cur players time has not run out
        $db->setGameCol($gameId, 'timeRanOut', $gameOldData['timeRanOut'] . $player_reporting_change_idx . '_');
        $db->closeConnection();
        return 'success';
    }

    // get more neccessary data:
    //$gameOldData = $db->getGameData('idGames', $gameId, '');

    $status = 'success';

    // handle all penalties the cur player might have had to pay and
    // on top of that add 2 cards since his time ran out:
    $penalty = (int) $gameOldData['wasPenaltyPayed'];  // penalty such as draw 2 or 4
    $cards_to_draw = 0 + $penalty;  // no additional penalty for running out of time
    if ($gameOldData['hasDrawn'] === '0') {  // in case current player has not drawn or played any cards until now penalise him
        $cards_to_draw += 2;
        $status = 'hasnotdrawn';
    }

    if ($cards_to_draw > 0) {
        $deck = $gameOldData['deck'];
        $drawn_cards = drawCardsFromDeck($cards_to_draw, $deck);  // modifies the deck
        $db->setGameCol($gameId, 'deck', $deck);
    
        $cur_pl_hand = $db->getGameData('idGames', $gameId, 'player' . $gameOldData['curPlayerIdx'])['player' . $gameOldData['curPlayerIdx']];
        $db->setGameCol($gameId, 'player' . $gameOldData['curPlayerIdx'], $cur_pl_hand . $drawn_cards);
    }

    // update the penalty for the next player:
    $db->setGameCol($gameId, 'wasPenaltyPayed', '0');
    
    // Update the check of taking 1 card
    $db->setGameCol($gameId, 'hasDrawn', '0');
    
    // reset the votes to 0:
    $db->setGameCol($gameId, 'timeRanOut', '');
    
    // update the cur player idx:
    $db->setGameCol($gameId, 'curPlayerIdx', strval(getNextPlayerIdx($gameOldData['curPlayerIdx'], $gameOldData['curRoundPlayers'], $gameOldData['direction'])));

    $db->closeConnection();

    return $status;
}


// add winners name text int hte right place in all playerX columns
function setWinnersName($winners_name, $gameId) {
    $db = new DataBase();
    $maxPlayers = (int) ($db->getGameData('idGames', $gameId, 'maxPlayers')['maxPlayers']);

    // set winners name in all joined players columns
    for ($i = 1, $n = $maxPlayers; $i <= $n; $i++) {
        // select only players who have been waiting
        $cur_pl_data = $db->getGameData('idGames', $gameId, 'player' . $i)['player' . $i];
        if (!$cur_pl_data || $cur_pl_data == '' || lastColonPos($cur_pl_data) === false) {
            continue;  // this player is not joined in
        }
        $winner_name_start_idx = strlen('_x_');
        $winner_name_end_idx = getStartOfUserNameIdx($cur_pl_data) - 1;  // ends with `_`
        $db->setGameCol($gameId, 'player' . $i, substr($cur_pl_data, 0, $winner_name_start_idx) . $winners_name . substr($cur_pl_data, $winner_name_end_idx));
    }

    $db->closeConnection();
}


// removes winner name from given playerX column (is called through ajax)
function removeWinnerNameFromPlayerCol($gameId, $gamePlayerCol) {
    try {
        $db = new DataBase();

        $cur_pl_hand = ($db->getGameData('idGames', $gameId, 'player' . $gamePlayerCol))['player' . $gamePlayerCol];
        // find index where winner's name ends
        $j = strlen('_x_');  // starting index, lookinf for 1st `_`
        for ($n = strlen($cur_pl_hand); $j < $n; $j++) {
            if ($cur_pl_hand[$j] === '_') {
                break;
            }
        }
        // leave out the winner's name substring
        $cur_pl_hand = substr($cur_pl_hand, 0, strlen('_x_')) . substr($cur_pl_hand, $j);
        $db->setGameCol($gameId, 'player' . $gamePlayerCol, $cur_pl_hand);

        $db->closeConnection();
        return 'success';
    } catch (TestException $e) {
        return $e->getMessage();
    }
}


// user is playing a card from his hand
function playCardFromHand($gameId, $card_to_play, $did_shout_raw, $player_idx) {  // $player_idx - session var so it is string
    $did_shout = ($did_shout_raw === 'true');  // convert to bool
    if (strlen($card_to_play) === 1) {  // careful if client side sends a card without colour
        $card_to_play .= 'R';  // be default add red colour
    }
    $card_to_play .= '_';
    if (strlen($card_to_play) > 3) {
        return 'invalidcard';
    }
    $db = new DataBase();

    try {
        $gameOldData = $db->getGameData('idGames', $gameId, 'jumpInVotes curRoundPlayers curPlayerIdx lastTwoCards wasPenaltyPayed player' . $player_idx);
    } catch (TestException $e) {
        $db->closeConnection();
        return 'error: ' . $e->getMessage();
    } catch (Exception $e) {
        return 'unknown error: ' . $e->getMessage();
    }

    $is_jump_in_enabled = false;
    if ($gameOldData['jumpInVotes'] && $gameOldData['jumpInVotes'][0] !== '?') {  // if it starts with `?` then it is not enabled
        $is_jump_in_enabled = true;
    }

    // in case it is not his turn (check the jumping in variant) do not add penalty and return
    if (!$is_jump_in_enabled && $player_idx != $gameOldData['curPlayerIdx']) {
        return 'notusersturn';
    }

    // if it is not this user's turn, but jump in is enabled do not let him play if the talon is empty,
    // since there is no jumping in on first move
    if (!strlen($gameOldData['lastTwoCards']) && $is_jump_in_enabled && $player_idx != $gameOldData['curPlayerIdx']) {
        return 'notusersturn2';
    }

    // check if the provided card to play is valid, (it exists in the user's hand)
    // if it is not valid, return, do not update anything    
    $users_hand = substr($gameOldData['player' . $player_idx], 1 + lastColonPos($gameOldData['player' . $player_idx]));

    if (strpos($users_hand, 'waiting') !== false) {  // ignore request if player is `waiting`

    }
    
    $card_to_play_raw = $card_to_play;  // it is the card user has, but if it is a black card it contains the colour, while in users data it does not
    if ($card_to_play_raw[0] === 'D' || $card_to_play_raw[0] === 'w') {
        $card_to_play_raw = $card_to_play[0] . '_';  // exclude the colour value
    }

    if (strpos($users_hand, $card_to_play_raw) === false) {
        return 'notuserscard';
    }

    // Validate the move:
    // in case it is not jumping in and invalid then add user 2 cards and update user to play idx and return
    // if it is jumping in and move is invalid simply return
    $i = -1;
    if (substr_count($gameOldData['lastTwoCards'], '_') > 1) {  // if there is more than 1 card find the last `_` just before the last card on talon
        $i = strpos($gameOldData['lastTwoCards'], '_', -5);
    }

    $penalty = (int) $gameOldData['wasPenaltyPayed'];  // penalty such as draw 2 or 4

    $did_jump_in = false;  // is true if it is not this user's turn, but he is trying to jump in
    if ($player_idx != $gameOldData['curPlayerIdx']) {
        $did_jump_in = true;
    }
    // check if talon is empty first and then validate based on the last card on the talon
    if (strlen($gameOldData['lastTwoCards']) && !isValidMove($card_to_play, $did_jump_in, substr($gameOldData['lastTwoCards'], $i + 1))) {
        if ($did_jump_in) {  // no penalty if it is not user's turn
            return 'invalidmove';
        }
        // add penalty, draw 2 cards because it is invalid move,
        // draw more if there is a debt to pay (+2 or +4) on talon
        $cards_to_draw = 2 + $penalty;
        $penalty = 0;  // it has now been payed
        $deck = $db->getGameData('idGames', $gameId, 'deck')['deck'];
        $drawn_cards = drawCardsFromDeck($cards_to_draw, $deck);  // modifies the deck
        $db->setGameCol($gameId, 'deck', $deck);
        $db->setGameCol($gameId, 'player' . $player_idx, $gameOldData['player' . $player_idx] . $drawn_cards);
        $game_direction = (int) ($db->getGameData('idGames', $gameId, 'direction')['direction']);  // neccessary to determine next player's index
        $db->setGameCol($gameId, 'curPlayerIdx', strval(getNextPlayerIdx($player_idx, $gameOldData['curRoundPlayers'], $game_direction)));
        // update the penalty for the next player:
        $db->setGameCol($gameId, 'wasPenaltyPayed', strval($penalty));
        return 'penalisedinvalidmove';
    }

    $status = 'success';  // set it since there will be no more scenario specififc returns
    $has_won = false;

    // if it is valid then:
    // remove the card from the user's hand
    $users_data_no_cards = substr($gameOldData['player' . $player_idx], 0, 1 + lastColonPos($gameOldData['player' . $player_idx]));

    $cards_to_draw = 0;
    // but first add cards if he has a penalty to pay
    // he can skip the penalty if his card matches the type of the card on talon
    if ($penalty) {  // it was not payed by previous player, which means it piled up
        // for now just add the penalty if the card user played does not defend, if it does
        // increase the penalty and pile it up , handling that later in the code
        if (substr($gameOldData['lastTwoCards'], $i + 1)[0] !== $card_to_play[0]) {  // top card on talon and played one don't match the type (+2 or +4)
            $cards_to_draw += $penalty;
            $penalty = 0;  // the debt gets reset since this user has payed it
        }
    }  // else no penalty, play user's card freely

    $cur_card_idx = strpos($users_hand, $card_to_play_raw);  // index of cur card in the user's hand
    $cards_before_played_one = substr($users_hand, 0, $cur_card_idx);
    $cards_after_played_one = substr($users_hand, strlen($cards_before_played_one) + strlen($card_to_play_raw));
    $cards_left = $cards_before_played_one . $cards_after_played_one;

    // Handle all penalties, including shouting too soon or not shouting when needed
    if ((substr_count($cards_left, '_') === 1 && !$did_shout) || (substr_count($cards_left, '_') > 1 && $did_shout)){
        $cards_to_draw += 2;
        $status = 'invalidshout';
    }

    if ($cards_to_draw) {  // add cards on top of what user has without the card he has just played
        try {
            $deck = $db->getGameData('idGames', $gameId, 'deck')['deck'];
        } catch (TestException $e) {
            return 'error getting game data: ' . $e->getMessage();
        }
        $drawn_cards = drawCardsFromDeck($cards_to_draw, $deck);  // modifies the deck
        $db->setGameCol($gameId, 'deck', $deck);
        $db->setGameCol($gameId, 'player' . $player_idx, $users_data_no_cards . $cards_left . $drawn_cards);
        $cards_left .= $drawn_cards;  // update what is now in users hand
    } else {  // just removed the played card
        $db->setGameCol($gameId, 'player' . $player_idx,  $users_data_no_cards . $cards_left);
    }

    if (!strlen($cards_left)) {  // user won the game
        $has_won = true;
    }

    // update the talon
    if (substr_count($gameOldData['lastTwoCards'], '_') < 3) {  // handle the talon being empty:
        $db->setGameCol($gameId, 'lastTwoCards', $gameOldData['lastTwoCards'] . $card_to_play);
    } else {
        $end_first_card_idx = strpos($gameOldData['lastTwoCards'], '_');
        $left_from_talon = substr($gameOldData['lastTwoCards'], 1 + $end_first_card_idx);  // what is left when first card is removed
        $db->setGameCol($gameId, 'lastTwoCards', $left_from_talon . $card_to_play);
        // add the card which left the talon to the deck
        $deck = $db->getGameData('idGames', $gameId, 'deck')['deck'];
        // check if the card to add to the deck is a black card, because then it has to
        // loose the colour which was set
        $talon_card_to_return_to_deck = substr($gameOldData['lastTwoCards'], 0, 1 + $end_first_card_idx);
        if ($talon_card_to_return_to_deck[0] === 'w' || $talon_card_to_return_to_deck[0] === 'D') {
            $talon_card_to_return_to_deck = $talon_card_to_return_to_deck[0] . '_';  // ditch the set colour
        }
        $db->setGameCol($gameId, 'deck', $deck . $talon_card_to_return_to_deck);
    }

    // if the player has won the game no need to handle the last card, just update the index of the cur player
    // and start a new game immediately
    
    $game_direction = (int) ($db->getGameData('idGames', $gameId, 'direction')['direction']);

    if ($has_won) {
        $db->setGameCol($gameId, 'curPlayerIdx', strval(getNextPlayerIdx($player_idx, $gameOldData['curRoundPlayers'], $game_direction)));
        $db->closeConnection();
        $start_of_name_idx = getStartOfUserNameIdx($users_data_no_cards);
        //return 'damn: ' . substr($users_data_no_cards, $start_of_name_idx, lastColonPos($users_data_no_cards) - $start_of_name_idx);
        setWinnersName(substr($users_data_no_cards, $start_of_name_idx, lastColonPos($users_data_no_cards) - $start_of_name_idx), $gameId);
        startNewGame($gameId);
        return 'success';
    }

    // handle the special power of the card:
    switch ($card_to_play[0]) {
        case 'r':  // handle the card which changes the direction of the game (reverse)
            $game_direction *= -1;
            $db->setGameCol($gameId, 'direction', strval($game_direction));
            $db->setGameCol($gameId, 'curPlayerIdx', strval(getNextPlayerIdx($player_idx, $gameOldData['curRoundPlayers'], $game_direction)));
        break;

        case 's':  // skip card - you can not defend from skip card if jump is not on (if you defend then techinaclly you jumped in in when it was not your turn)
            // check if the player to be skipped can defend against being skipped
            $player_to_be_skipped_idx = getNextPlayerIdx($player_idx, $gameOldData['curRoundPlayers'], $game_direction);
            $db->setGameCol($gameId, 'curPlayerIdx', strval(getNextPlayerIdx($player_to_be_skipped_idx, $gameOldData['curRoundPlayers'], $game_direction)));
        break;

        case 'd':  // draw 2
            $penalty +=2;  // setting penalty for next player
            // update next player's index
            $db->setGameCol($gameId, 'curPlayerIdx', strval(getNextPlayerIdx($player_idx, $gameOldData['curRoundPlayers'], $game_direction)));
        break;

        case 'D':  // draw 4, suit has been determined on the client side
            $penalty += 4;
            // update next player's index
            $db->setGameCol($gameId, 'curPlayerIdx', strval(getNextPlayerIdx($player_idx, $gameOldData['curRoundPlayers'], $game_direction)));
        break;

        // wildacard is treated as any other card since it contains the suit itself, only
        // difference is it has no value, other black card can be played on top of it or
        // the card of the matching suit (which are the exact same rules for normal cards)

        default:  // was just normal playable card, now it is next player's turn
            // update the index of the user to play
            $db->setGameCol($gameId, 'curPlayerIdx', strval(getNextPlayerIdx($player_idx, $gameOldData['curRoundPlayers'], $game_direction)));
        break;
    }

    // update the penalty for the next player:
    $db->setGameCol($gameId, 'wasPenaltyPayed', strval($penalty));
    
    // Update the check of taking 1 card
    $db->setGameCol($gameId, 'hasDrawn', '0');

    $db->closeConnection();
    return $status;
}


// user drawing a card volunterilly
function drawCard($gameId, $player_idx) {
    $db = new DataBase();

    try {
        $gameOldData = $db->getGameData('idGames', $gameId, 'curPlayerIdx hasDrawn');
    } catch (TestException $e) {
        $db->closeConnection();
        return 'error: ' . $e->getMessage();
    } catch (Exception $e) {
        return 'unknown error: ' . $e->getMessage();
    }

    // check if player requesting a card is the player to move:
    if ($player_idx !== $gameOldData['curPlayerIdx']) {
        return 'notusersturn';
    }

    // Check whether player has already taken 1 card
    if ($gameOldData['hasDrawn'] === '1') {
        return 'alreadydrawn';
    }

    // Deal the player 1 card
    $cards_to_draw = 1;
    $gameOldData = $db->getGameData('idGames', $gameId, 'deck player' . $player_idx);
    
    $deck = $gameOldData['deck'];
    $drawn_cards = drawCardsFromDeck($cards_to_draw, $deck);  // modifies the deck
    $db->setGameCol($gameId, 'deck', $deck);
    $db->setGameCol($gameId, 'player' . $player_idx, $gameOldData['player' . $player_idx] . $drawn_cards);

    // Update the check of taking 1 card
    $db->setGameCol($gameId, 'hasDrawn', '1');
    $db->closeConnection();

    return 'drawn:' . $cards_to_draw;
}


// pay current player's penalty
function payPenalty($gameId, $player_idx) {
    $db = new DataBase();

    try {
        $gameOldData = $db->getGameData('idGames', $gameId, 'curPlayerIdx wasPenaltyPayed');
    } catch (TestException $e) {
        $db->closeConnection();
        return 'error: ' . $e->getMessage();
    } catch (Exception $e) {
        return 'unknown error: ' . $e->getMessage();
    }

    // check if player requesting a cards is the player to move:
    if ($player_idx !== $gameOldData['curPlayerIdx']) {
        return 'notusersturn';
    }

    $penalty = (int) $gameOldData['wasPenaltyPayed'];
    // Check whether player has already taken 1 card
    if (!$penalty) {
        return 'nopenalty';  // ignore this, user should not have sent the request if there was no penalty to pay
    }

    // Deal the player 1 card
    $cards_to_draw = $penalty;
    $gameOldData = $db->getGameData('idGames', $gameId, 'deck player' . $player_idx);
    $deck = $gameOldData['deck'];
    $drawn_cards = drawCardsFromDeck($cards_to_draw, $deck);  // modifies the deck
    $db->setGameCol($gameId, 'deck', $deck);
    $db->setGameCol($gameId, 'player' . $player_idx, $gameOldData['player' . $player_idx] . $drawn_cards);

    // update the penalty for the next player:
    $db->setGameCol($gameId, 'wasPenaltyPayed', '0');
    $db->closeConnection();

    return 'drawn:' . $cards_to_draw;
}


// enable passing a turn if the player has drawn the card
function passTurn($gameId, $player_idx) {
    $db = new DataBase();

    try {
        $gameOldData = $db->getGameData('idGames', $gameId, 'curRoundPlayers curPlayerIdx direction wasPenaltyPayed hasDrawn');
    } catch (TestException $e) {
        $db->closeConnection();
        return 'error: ' . $e->getMessage();
    } catch (Exception $e) {
        return 'unknown error: ' . $e->getMessage();
    }

    // check if player requesting a card is the player to move:
    if ($player_idx !== $gameOldData['curPlayerIdx']) {
        return 'notusersturn';
    }

    // Check whether player has drawn 1 card and payed his debts, if not he may not pass
    if ($gameOldData['hasDrawn'] === '0' || (int) $gameOldData['wasPenaltyPayed']) {
        return 'hasnotdrawn';
    }

    // update the index of the player to play and reset values for next player:
    $db->setGameCol($gameId, 'hasDrawn', '0');
    $db->setGameCol($gameId, 'curPlayerIdx', strval(getNextPlayerIdx($player_idx, $gameOldData['curRoundPlayers'], $gameOldData['direction'])));
    $db->closeConnection();

    return 'success';
}
