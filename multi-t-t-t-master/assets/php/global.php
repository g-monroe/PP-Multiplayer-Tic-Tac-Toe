<?php
//ERROR checking/Reporting
$errFile = "index.php";
if ($errFile == basename($_SERVER['REQUEST_URI'], '?' . $_SERVER['QUERY_STRING'])){
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}else{
    ini_set("display_errors","0");
}
//DB Connection Security Password
define('DBCON_PASS', 'auth_SECRETKEY_ForbiddenCheck');
//DB Connection info
define('DB_SERVER', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB', 'ttt');

//Global
define('IP', $_SERVER['REMOTE_ADDR']);
define('NOW', date("Y-m-d H:i:s"));

//■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
//■■■■■    GLOBAL FUCNTIONS    ■■■■■■
//■■■■■    8/23/2017 -Gavin    ■■■■■■
//■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■

//Alert User with a Pop-Up Message
function alert($msg){
    echo '<script language="javascript">';
    echo 'alert("'.$msg.'")';
    echo '</script>';
}//End Function
//No Html Special Characters
function noHTML($input, $encoding = 'UTF-8'){
    $input = strip_tags($input); // Strip tags
    return htmlentities($input, ENT_QUOTES | ENT_HTML5, $encoding); //Strip all special chars
}//End Function
//Redirect
function redirect($url){
    header("Location: ".$url);
}//End Function
//Generate RND String
function genRndStr($num){//Generate String
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charsLength = strlen($chars);
    $code = '';
    for ($i = 0; $i < $num; $i++) {
        $code .= $chars[rand(0, $charsLength - 1)];
    }//End(1)
    return $code;
}//End If
//Set Cookie
function setCook($key, $value, $seconds){
    try{
        setcookie($key, $value, time()+$seconds, '/');  /* expire in 1 hour */
        return true;
    }catch(Exception $e){
        return false;
    }//End Try
}//End Fucntion
//Delete Cookies
function deleteCooks(){
    if (isset($_SERVER['HTTP_COOKIE'])) {
        $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
        foreach($cookies as $cookie) {
            $parts = explode('=', $cookie);
            $name = trim($parts[0]);
            setcookie($name, '', time()-1000);
            setcookie($name, '', time()-1000, '/');
        }
    }
}
//Unset Cookie
function unsetCook($key){
    try{
        unset($_COOKIE[$key]);
        return true;
    }catch(Exception $e){
        return false;
    }//End Try
}//End Fucntion
//Get Global Link
function urlOut(){
    $url = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $url = strtok($url, '?');
    $url = str_replace('index.php', '', $url);
    return $url;
}//End Function
//Check Data
function validDate($date){
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}
function checkAccount($id, $dbc){
    $id = noHTML($id);
    $idCount = getCountFields('players', "`ID`='".$id."'", $dbc);
    if ($idCount == 1){
        $lastDate = new DateTime(grabField("players", "LastMove", "`ID`='".$id."'", $dbc));
        $hourLate = new DateTime();
        $hourLate->modify('-1 hour');
        //die($lastDate->format('Y-m-d H:i:s').":::".$hourLate->format('Y-m-d H:i:s'));
        if ($lastDate > $hourLate) {
            updateDB("players", "`LastMove`='".NOW."'", "`ID`='".$id."'", $dbc);
            return true;
        }else {
            try{
                $qryDEL = $dbc->prepare("DELETE FROM players WHERE ID='".$id."'");
                $qryDEL->execute();
                return false;
            }catch(Exception $e){
                return false;
            }
        }
    }else{
        redirect("");
    }
}
function cleanAccounts($dbc, $link){
    $query = "SELECT * FROM players WHERE 1";
    if ($stmt = mysqli_prepare($link, $query)) {
        $stmt->execute();

        $row = array();
        stmt_bind_assoc($stmt, $row);
        // loop through all result rows
        while ($stmt->fetch()) {//Loop through Users Keys
            $lastDate = new DateTime($row["LastMove"]);
            $hourLate = new DateTime();
            $hourLate->modify('-1 hour');
            //die($lastDate->format('Y-m-d H:i:s').":::".$hourLate->format('Y-m-d H:i:s'));
            if ($lastDate > $hourLate) {
            }else {
                try{
                    $qryDEL = $dbc->prepare("DELETE FROM players WHERE ID='".$row["ID"]."'");
                    $qryDEL->execute();
                }catch(Exception $e){
                }
            }

        }
    }
}
function cleanGames($dbc, $link){
    $query = "SELECT * FROM games WHERE 1";
    if ($stmt = mysqli_prepare($link, $query)) {
        $stmt->execute();

        $row = array();
        stmt_bind_assoc($stmt, $row);
        // loop through all result rows
        while ($stmt->fetch()) {//Loop through Users Keys
            $lastDate = new DateTime($row["DateAdded"]);
            $hourLate = new DateTime();
            $hourLate->modify('-1 hour');
            //die($lastDate->format('Y-m-d H:i:s').":::".$hourLate->format('Y-m-d H:i:s'));
            if ($lastDate > $hourLate) {
            }else {
                try{
                    $qryDEL = $dbc->prepare("DELETE FROM games WHERE ID='".$row["ID"]."'");
                    $qryDEL->execute();
                }catch(Exception $e){
                }
            }

        }
    }
}
function checkWin($arr, $gm, $dbc){
    $arrBoard = explode(",", $arr);
    $arrFill = array( 0 );
    $Filled = 1;
    foreach ($arrBoard as &$value) {
        $pieces = explode(":", $value);
        array_push($arrFill,$pieces[1]);
        if ($pieces[1] == 0){
            $Filled = 0;
        }
    }
    if ($Filled == 1){
        updateDB("games", "`Status`='Draw'", "`ID`='".$gm."'", $dbc);
    }
//Check Rows
    $top = rowCheck($arrFill[1], $arrFill[2], $arrFill[3]);//Top
    $mid = rowCheck($arrFill[4], $arrFill[5], $arrFill[6]);//Middle
    $bot = rowCheck($arrFill[7], $arrFill[8], $arrFill[9]);//Bottom
    //Check Cols
    $lef = rowCheck($arrFill[1], $arrFill[4], $arrFill[7]);//Left
    $cen = rowCheck($arrFill[2], $arrFill[5], $arrFill[8]);//Center
    $rig = rowCheck($arrFill[3], $arrFill[6], $arrFill[9]);//Right
    //Check Diagonals
    $dg1 = rowCheck($arrFill[1], $arrFill[5], $arrFill[9]);//Left Diagonal
    $dg2 = rowCheck($arrFill[3], $arrFill[5], $arrFill[7]);//Right Diagonal
    if ($top == 1 || $mid == 1 || $bot == 1 || $lef == 1 || $cen == 1 || $rig == 1 || $dg1 == 1 || $dg2 == 1){
        updateDB("games", "`Status`='Player1 Won'", "`ID`='".$gm."'", $dbc);
    }
    //check for o
    if ($top == 2 || $mid == 2 || $bot == 2 || $lef == 2 || $cen == 2 || $rig == 2 || $dg1 == 2 || $dg2 == 2){
        updateDB("games", "`Status`='Player2 Won'", "`ID`='".$gm."'", $dbc);
    }
}
function rowCheck($sp1, $sp2, $sp3){
    //Return 0 = Blank, 1 = Cross, 2 = Circle
    if ($sp1 == 1 && $sp2 == 1 && $sp3 == 1){
        return 1;
    }
    if ($sp1 == 2 && $sp2 == 2 && $sp3 == 2){
        return 2;
    }
    return 0;
}
function addPlayerToGame($id, $rID, $dbc){
    if (updateDB("games", "`Player2`='".$id."'", "`ID`='".$rID."'", $dbc)){
        if (updateDB("games", "`Status`='Player1 Turn'", "`ID`='".$rID."'", $dbc)){
            redirect("?step=3&id=".noHTML($_GET['id'])."&gm=".$rID);
        }
    }
}
function checkOpenedGames($id, $dbc){
    $id = noHTML($id);
    $idCount = getCountFields('games', "`Player1`='".$id."'", $dbc);
    if ($idCount == 1){
        $lastDate = new DateTime(grabField("games", "DateAdded", "`Player1`='".$id."'", $dbc));
        $hourLate = new DateTime();
        $hourLate->modify('-1 hour');
        //die($lastDate->format('Y-m-d H:i:s').":::".$hourLate->format('Y-m-d H:i:s'));
        if ($lastDate > $hourLate) {
            return false;
        }else {
            try{
                $qryDEL = $dbc->prepare("DELETE FROM games WHERE Player1='".$id."'");
                $qryDEL->execute();
                return true;
            }catch(Exception $e){
                return false;
            }
        }
    }else{
        return true;
    }
}
function getCreatedGame($id, $dbc){
    $idCount = getCountFields('games', "`Player1`='".$id."'", $dbc);
    if ($idCount == 1){
        return grabField("games", "ID", "`Player1`='".$id."'", $dbc);
    }else{
        return "NULL";
    }
}
function delMyGame($id, $dbc){
    $idCount = getCountFields('games', "`Player1`='".$id."'", $dbc);
    if ($idCount == 1){
        try{
            $qryDEL = $dbc->prepare("DELETE FROM games WHERE Player1='".$id."'");
            $qryDEL->execute();
            return true;
        }catch(Exception $e){
            return false;
        }
    }else{
        return "NULL";
    }
}
//■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
//■■■■■    DBC FUCNTIONS       ■■■■■■
//■■■■■    8/23/2017 -Gavin    ■■■■■■
//■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
function getCount($db, $dbc){
    $count = 0;
    try{
        $qryCount = $dbc->prepare("SELECT count(*) FROM ".$db);
        $qryCount->execute();
        $count = $qryCount->fetchColumn();
    }catch (Exception $e){
        $count =0;
    }
    return $count;
}
//Get Count of a certain Row in a Table
function getCountFields($db, $fields, $dbc){
    $count = 0;
    try{
        $qryCount = $dbc->prepare("SELECT count(*) FROM ".$db." WHERE ".$fields);
        $qryCount->execute();
        $count = $qryCount->fetchColumn();
    }catch (Exception $e){
        $count =0;
    }
    return $count;
}
//Update Row in table
function updateDB($db, $field, $input, $dbc){
    try{
        $qryCount = $dbc->prepare("UPDATE ".$db." SET ".$field." WHERE ".$input);
        $qryCount->execute();
        return true;
    }catch (Exception $e){
        return false;
    }
}
function insertDB($db, $field, $input, $dbc){
    //die("INSERT INTO ".$db." (".$field.") VALUES (".$input.")");
    try{
        $qryCount = $dbc->prepare("INSERT INTO ".$db." (".$field.") VALUES (".$input.")");
        $qryCount->execute();
        return true;
    }catch (Exception $e){
        return false;
    }
}
//Grab Field in Row from a Table
function grabField($db, $field, $input, $dbc){
    try{
        $qryGrab = $dbc->prepare("SELECT ".$field." FROM ".$db." WHERE ".$input);
        $qryGrab->execute();
        return $qryGrab->fetchColumn();
    }catch (Exception $e){
        return "ERR";
    }
}
function stmt_bind_assoc (&$stmt, &$out) {
    $data = mysqli_stmt_result_metadata($stmt);
    $fields = array();
    $out = array();

    $fields[0] = $stmt;
    $count = 1;

    while($field = mysqli_fetch_field($data)) {
        $fields[$count] = &$out[$field->name];
        $count++;
    }

    call_user_func_array('mysqli_stmt_bind_result', $fields);
}//End Function
?>