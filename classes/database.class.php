<?php

class DataBase {
    private $conn;  // connection with the data base
    private $sql_cmd;  // sql command
    private $stmt;  // statement to run

    private $servername = "localhost";  // change if using online companies server
    private $dBUsername = "root";
    private $dBPassword = "";  // empty in XAMPP
    private $dBName = "qcardsdatabase";  // name of database in phpadmin


    public function __construct() {
        $connection = mysqli_connect($this->servername, $this->dBUsername, $this->dBPassword, $this->dBName);
        // check if connection failed:
        if (!$connection) {
            die("Connection failed ;( ".mysqli_connect_error());  // kill connection
        } else {
            $this->conn = $connection;
        }
    }


    public function __destruct() {
        $this->clearStmt();
        $this->closeConnection();
    }


    public function closeConnection() {
        if (isset($this->conn) && $this->conn) {
            mysqli_close($this->conn);
            unset($this->conn);
        }
    }


    private function clearStmt() {
        if (isset($this->stmt) && $this->stmt) {
            mysqli_stmt_close($this->stmt);
            unset($this->stmt);
        }
    }


    // Set stuff:
    public function resetConnection() {
        $this->closeConnection();
        $connection = mysqli_connect($this->servername, $this->dBUsername, $this->dBPassword, $this->dBName);
        // check if connection failed:
        if (!$connection) {
            die("Connection failed ;( ".mysqli_connect_error());  // kill connection
        } else {
            $this->conn = $connection;
        }
    }

    public function setStmt($stmt) {
        $this->stmt = $stmt;
    }


    // sets data of given row's given col
    private function setColData($table_name, $unique_col_name, $unique_key, $col_name, $new_data) {
        $this->sql_cmd = "UPDATE " . $table_name . " SET " . $col_name . "=? WHERE " . $unique_col_name . "=?";
        $this->executeMaxTwoParameterQuery($new_data, $unique_key);
        $this->clearStmt();
    }


    // sets specified col of game with given ID
    public function setGameCol($gameId, $col_name, $new_data) {
        $this->setColData('games', 'idGames', $gameId, $col_name, $new_data);
    }


    public function deleteGame($gameId) {
        $this->sql_cmd = "DELETE FROM games WHERE idGames=?";
        $this->executeMaxTwoParameterQuery($gameId);
        $this->clearStmt();
    }


    // create a completely new game in the database
    public function insertNewGame($uid, $hashedPwd, $game_title, $max_num_players) {
        $this->sql_cmd = "INSERT INTO games (uidGames, pwdGames, titleGames, jumpInVotes, maxPlayers, joinedPlayers, curRoundPlayers, curPlayerIdx, lastTwoCards, direction, deck, wasPenaltyPayed, hasDrawn, player1, player2, player3, player4, player5, player6, player7, player8, player9, player10, player11, player12) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->stmt = mysqli_stmt_init($this->conn);

        // check if it is going to work inside database
        if (!mysqli_stmt_prepare($this->stmt, $this->sql_cmd)) {
            $this->closeConnection();
            throw new TestException('sqlerror2insertNewG');
        }

        $jumpInVotes = '?0';
        $curPlayerIdx = $joinedPlayers = $curRoundPlayers = 0;
        $direction = '1';
        $deck = $emptyPlayerData = $lastTwoCards = '';
        $wasPenaltyPayed = $hasDrawn = '0';

        mysqli_stmt_bind_param($this->stmt, "sssssssssssssssssssssssss", $uid, $hashedPwd, $game_title, $jumpInVotes, $max_num_players, $joinedPlayers, $curRoundPlayers, $curPlayerIdx, $lastTwoCards, $direction, $deck, $wasPenaltyPayed, $hasDrawn, $emptyPlayerData, $emptyPlayerData, $emptyPlayerData, $emptyPlayerData, $emptyPlayerData, $emptyPlayerData, $emptyPlayerData, $emptyPlayerData, $emptyPlayerData, $emptyPlayerData, $emptyPlayerData, $emptyPlayerData);
        mysqli_stmt_execute($this->stmt);

        $this->clearStmt();
    }


    // Get stuff:

    // assumes sql_cmd has already been defined
    // parameters are the placeholders in the sql stmt
    private function executeMaxTwoParameterQuery($placeholder_value1, $placeholder_value2 = 'nothinghere') {
        if (!$this->conn) {
            throw new TestException('conninvalid');
        }

        $this->stmt = mysqli_stmt_init($this->conn);

        // check if it is going to work inside database
        if (!mysqli_stmt_prepare($this->stmt, $this->sql_cmd)) {
            $this->clearStmt();
            $this->closeConnection();
            throw new TestException('sqlerror2');
        }

        // set the placeholder
        if ($placeholder_value2 === 'nothinghere') {
            mysqli_stmt_bind_param($this->stmt, "s", $placeholder_value1);
        } else {
            mysqli_stmt_bind_param($this->stmt, "ss", $placeholder_value1, $placeholder_value2);
        }
        
        mysqli_stmt_execute($this->stmt);
        // must not clear stmt here cause it may be used further
    }

    // returns data from all columns within the game with given value in
    // given column, takes optional parameter which specifies exact data to retrieve
    public function getGameData($unique_col, $query_data, $cols_to_get = 'nothinghere') {
        $this->sql_cmd = "SELECT * FROM games WHERE " . $unique_col . "=?";

        // set the sql_cmd in case specific columns were specified
        if ($cols_to_get != 'nothinghere') {
            $cols = explode(" ", $cols_to_get);
            $this->sql_cmd = "SELECT " . $cols[0];
            for ($i = 1, $n = count($cols); $i < $n; $i++) {
                $this->sql_cmd .= ', ' . $cols[$i];
            }
            $this->sql_cmd .= " FROM games WHERE " . $unique_col . "=?";
        }

        $this->executeMaxTwoParameterQuery($query_data);

        $result = mysqli_stmt_get_result($this->stmt);

        if ($row = mysqli_fetch_assoc($result)) {  // game found
            $this->clearStmt();
            return $row;
        } else {
            $this->clearStmt();
            throw new TestException('gamenotfound');
        }
    }

    public function isGamePrivate($gameId) {
        $password = $this->getGameData('idGames', $gameId, 'pwdGames');
        if ($password['pwdGames'] === '') {
            return false;
        }
        return true;
    }

}
