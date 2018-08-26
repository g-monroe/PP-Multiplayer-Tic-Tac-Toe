<?php
require_once ("assets/php/global.php");
define(DBCON_PASS, true);
require_once ("assets/php/dbcon.php");
$anon = '0';
if (isset($_GET['anon'])){
    $anon = $_GET['anon'];
}
session_start();
if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}
$token = $_SESSION['token'];
if (isset($_GET['step']) && isset($_POST['nick'])){

    $step = noHTML($_GET['step']);
    $err = "";
    if ($step == 1){

        $unsup_users = array('root', 'admin', 'administator', 'mod', 'moderator', 'nigger', 'faggot', 'nig', 'pussy', 'fuck', 'shit', 'tits', 'n1gger', 'n1gg3r', 'test');
        $nick = noHTML($_POST['nick']);
        $tok = $_POST['token'];
        //Check Token
        if (!empty($_POST['token'])) {
            if (hash_equals($_SESSION['token'], $tok)) {
            } else {
                $err = "Unoffical Request!".$_SESSION['token'].":::".$tok;
            }
        }
        //Check Username
        if (strlen($nick) >= 26){
            $err = "Username is too long!";
        }elseif (strlen($nick) < 3){
            $err ="Username is too short!";
        }
        if (in_array($nick, $unsup_users)) {
            $err = "Username can't not be that.";
        }

        //Check if user with the same IP exists
        cleanAccounts($dbc, $link);
        $existing = 0;
        try{
            $ipCount = getCountFields('players', "`IP`='".IP."'", $dbc);
            if ($ipCount > 0){
                $existing = 1;
            }
        }catch(Exception $e){
            $err = "Problem connecting to Database. Please Check Back later!";
        }

        if ($err == ""){
            if ($existing == 1){
                $newToken = grabField("players", "ID", "`IP`='".IP."'", $dbc);
                if ($newToken == "ERR"){
                    $err = "Couldn't recover old account!";
                }else{
                    if(updateDB("players", "`Name`='".$nick."', `LastMove`='".NOW."'", "`ID`='".$newToken."'", $dbc)){
                        redirect("?step=2&id=".$newToken);
                    }else{
                        $err = "Couldn't update profile!";
                    }
                }
            }else{
                $result = insertDB('`players`', "`ID`, `Name`, `Game`, `LastMove`, `IP`", "'".$_POST['token']."', '".$nick."', 'none', '".NOW."', '".IP."'", $dbc);

                if ($result){
                    redirect("?step=2&id=".$_POST['token']);
                }else{
                    $err = "Couldn't create account. Please try again another time!";
                }
            }
        }
    }
}
if (isset($_GET['step']) && isset($_GET['id'])){
    $step = noHTML($_GET['step']);
    $id = noHTML($_GET['id']);
    $err = "";
    if (!checkAccount($id, $dbc)){
        redirect("?step1&id=".$id);
    }
    if ($step == 2){
        cleanGames($dbc, $link);
    }elseif ($step == 5){
        if (isset($_POST['roomID'])){
            $pass = noHTML($_POST['gamePass']);
            $rID = noHTML($_POST['roomID']);
            $passRoom = grabField("games", "pass", "`ID`='".$rID."'", $dbc);
            $stat = grabField("games", "Status", "`ID`='".$rID."'", $dbc);
            $p2 = grabField("games", "Player2", "`ID`='".$rID."'", $dbc);

            if ($stat == "Need Player2") {
                if ($passRoom == "") {
                    addPlayerToGame($id, $rID, $dbc);
                } else {
                    if ($passRoom == $pass) {
                        addPlayerToGame($id, $rID, $dbc);
                    } else {
                        redirect("?step=2&id=".noHTML($_GET['id']));
                    }
                }
            }elseif($p2 == $id){
                redirect("?step=3&id=".noHTML($_GET['id'])."&gm=".$rID);
            }else{
                redirect("?step=2&id=".noHTML($_GET['id']));
            }
        }
    }elseif ($step == 11){
        if (isset($_GET['gm'])){
            $gm = noHTML($_GET['gm']);
            $idCount = getCountFields('games', "`ID`='".$gm."'", $dbc);
            if ($idCount == 1){
                $board = grabField("games", "Board", "`ID`='".$gm."'", $dbc);
                $stat = grabField("games", "Status", "`ID`='".$gm."'", $dbc);
                die($stat."~".$board);
            }else{
                delMyGame($id, $dbc);
                die("Dead Room");
            }
        }else{
            delMyGame($id, $dbc);
            die("Dead Room");
        }
    }elseif ($step == 13){
        if (isset($_GET['gm'])){
            $gm = noHTML($_GET['gm']);
            $spot = noHTML($_POST['spot']);
            $idCount = getCountFields('games', "`ID`='".$gm."'", $dbc);
            if ($idCount == 1){
                $stat = grabField("games", "Status", "`ID`='".$gm."'", $dbc);
                if ($stat == "Player1 Won" || $stat == "Player2 Won" || $stat == "Draw"){
                    $blank = "a1:0,a2:0,a3:0,b1:0,b2:0,b3:0,c1:0,c2:0,c3:0";
                    updateDB("games", "`Board`='".$blank."'", "`ID`='".$gm."'", $dbc);
                    updateDB("games", "`Status`='Player1 Turn'", "`ID`='".$gm."'", $dbc);
                    redirect("?step=3&id=".$id."&gm=".$gm);
                }
            }
        }
    }elseif ($step == 12){
        if (isset($_GET['gm'])){
            $gm = noHTML($_GET['gm']);
            $spot = noHTML($_POST['spot']);
            $idCount = getCountFields('games', "`ID`='".$gm."'", $dbc);
            if ($idCount == 1){
                $stat = grabField("games", "Status", "`ID`='".$gm."'", $dbc);
                $p1 = grabField("games", "Player1", "`ID`='".$gm."'", $dbc);
                $p2 = grabField("games", "Player2", "`ID`='".$gm."'", $dbc);
                $board = grabField("games", "Board", "`ID`='".$gm."'", $dbc);
                if ($stat == "Player1 Turn"){
                    if ($id == $p1){
                        $newBoard = "";
                        $arrBoard = explode(",", $board);
                        foreach ($arrBoard as &$value) {
                            $pieces = explode(":", $value);
                            if ($pieces[0] == $spot){
                                $newBoard = $newBoard.$pieces[0].":"."1".",";
                            }else{
                                $newBoard = $newBoard.$pieces[0].":".$pieces[1].",";
                            }
                        }
                        $newBoard = substr($newBoard, 0, -1);
                        updateDB("games", "`Board`='".$newBoard."'", "`ID`='".$gm."'", $dbc);
                        updateDB("games", "`Status`='Player2 Turn'", "`ID`='".$gm."'", $dbc);
                        checkWin($newBoard, $gm, $dbc);
                    }
                }else if ($stat == "Player2 Turn"){
                    $newBoard = "";
                    $arrBoard = explode(",", $board);
                    foreach ($arrBoard as &$value) {
                        $pieces = explode(":", $value);
                        if ($pieces[0] == $spot){
                            $newBoard = $newBoard.$pieces[0].":"."2".",";
                        }else{
                            $newBoard = $newBoard.$pieces[0].":".$pieces[1].",";
                        }
                    }
                    $newBoard = substr($newBoard, 0, -1);
                    updateDB("games", "`Board`='".$newBoard."'", "`ID`='".$gm."'", $dbc);
                    updateDB("games", "`Status`='Player1 Turn'", "`ID`='".$gm."'", $dbc);
                    checkWin($newBoard, $gm, $dbc);
                }
                die("Set");
            }else{
                delMyGame($id, $dbc);
                die("Dead Room");
            }
        }else{
            delMyGame($id, $dbc);
            die("Dead Room");
        }
    }elseif ($step == 10){
        die(grabField("games", "Status", "`Player1`='".$id."'", $dbc));
    }elseif ($step == 9){
        if (!checkOpenedGames($id, $dbc)){
            if (isset($_POST['delGame'])){
                if ($_POST['delGame'] == 1){
                    delMyGame($id, $dbc);
                    redirect($_SERVER["HTTP_REFERER"]);
                }
            }
        }else{
            redirect("?step=2&id=".noHTML($_GET['id']));
        }
    }elseif ($step == 4){
        if (!checkOpenedGames($id, $dbc)){
            $err = "You already have a game open! <a href='?step=6&id=".$id."'>Close it?</a>";
        }
        if ($err == "" && isset($_POST['gameName'])){
            $unsup_users = array('root', 'admin', 'administator', 'mod', 'moderator', 'nigger', 'faggot', 'nig', 'pussy', 'fuck', 'shit', 'tits', 'n1gger', 'n1gg3r', 'test');
            $nick = noHTML($_POST['gameName']);
            $pass = noHTML($_POST['gamePass']);
            $blank = "a1:0,a2:0,a3:0,b1:0,b2:0,b3:0,c1:0,c2:0,c3:0";
            $tok = substr($_POST['token'], 0, -1);
            //Check Token
            if (!empty($_POST['token'])) {
                if (hash_equals($_SESSION['token'], $tok)) {
                } else {
                    $err = "Unoffical Request!".$_SESSION['token'].":::".$tok;
                }
            }
            //Check Username
            if (strlen($nick) >= 26){
                $err = "Game Name is too long!";
            }elseif (strlen($nick) < 3){
                $err ="Game Name is too short!";
            }
            //Check Pass
            if (strlen($pass) >= 26){
                $err = "Game Pass is too long!";
            }
            if (in_array($nick, $unsup_users)) {
                $err = "Game Name can't not be that.";
            }

            //Check if user with the same IP exists
            $existing = 0;
            try{
                $gmCount = getCountFields('games', "`GameName`='".$nick."'", $dbc);
                if ($gmCount > 0){
                    $existing = 1;
                    $err = "Game with that name already exists!";
                }
            }catch(Exception $e){
                $err = "Problem connecting to Database. Please Check Back later!";
            }
            if ($err == ""){
                $idRoom = genRndStr(32);
                    $result = insertDB('`games`', "`ID`, `GameName`, `Player1`, `Status`, `pass`, `Board`", "'".$idRoom."', '".$nick."', '".noHTML($_GET['id'])."', 'Need Player2', '".$pass."', '".$blank."'", $dbc);

                    if ($result){
                        redirect("?step=9&id=".noHTML($_GET['id'])."&gm=".$idRoom);
                    }else{
                        $err = "Couldn't create game. Please try again another time!";
                    }
            }
        }
    }elseif ($step == 6){
        delMyGame($id, $dbc);
        redirect($_SERVER["HTTP_REFERER"]);
    }
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Tic Tac Toe: Open Source. Simplified</title>
    <link href="../bitnami.css" media="all" rel="Stylesheet" type="text/css" />
    <link href="assets/css/main.css" media="all" rel="Stylesheet" type="text/css" />
    <script src="assets/js/main.js"></script>
</head>
<body>
<div id="container">
    <div id="header">
        <div id="bitnami">
            <a href="/"><img alt="Bitnami" src="../img/logo.png" /></a>
        </div>
    </div>
    <div id="menu_launch_page">
        <table cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <a href="../index.html"><img src="../img/tab1_applications.png" border="0" alt="" /></a>
                </td>
                <td><img src="../img/tab2_applications.png" border="0" alt="" />
                </td>
            </tr>
        </table>
    </div>
    <div id="lowerContainer">
        <div id="content">
            <div align="center">
                <table class="tableParagraph">
                    <tr>
                        <td class="container" >
                            <p>Check out my other <a href="../projects.html">projects</a>.</p><br/>
                            <p>Go to <a href="?step=2&id=<?php echo noHTML($_GET['id']); ?>">Server Room</a>.</p><br/><br/>
                        </td>
                    </tr>
                </table>
            </div>
            <!-- START Login -->
            <?php
            $step = noHTML($_GET['step']);
       if ($step == "1" || $step == "") {

            ?>
            <div style="display:block" id="signin" align="center">
                <h3 id="pTurnBef">Welcome, please choose a name:</h3><br/>
                <form method="POST" action="?step=1">
                    <input type="text" value="" placeholder="nickname" name="nick"/>
                    <input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>" />
                    <input type="submit" value="I like my name!"/><br /><?php
            if ($err == "") {
                echo('<label id="infoPresenter" style="color:lightgray">This project was for fun and educational use.</label>');
            } else {
                echo('<label id="infoPresenter" style="color:red">' . $err . '</label>');
            }?>
            </form>
            </div>
           <?php
       }else if ($step == 9) {
           ?>
           <div style="display:block" id="createGame" align="center">
               <h3 id="pTurnBef">Waiting for play to join! <br/><br/>Time Waited: 0s</h3><br/>
               <form method="POST" action="?step=9&id=<?php echo(noHTML($_GET['id'])); ?>">
                   <input type="hidden" name="delGame" value="1" />
                   <input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>'" />
                   <input type="submit" value="Delete Game!"/><br />
               </form>
           </div>
           <script>
               var timeWaited = 0;
               time=setInterval(function(){
                   var http = new XMLHttpRequest();
                   var url = "?step=10&id=<?php echo(noHTML($_GET['id'])); ?>";
                   var params = "";
                   http.open("POST", url, true);

//Send the proper header information along with the request
                   http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

                   http.onreadystatechange = function() {//Call a function when the state changes.
                       if(http.readyState == 4 && http.status == 200) {
                           if (http.responseText == "Need Player2"){
                           }else{
                               window.location = "?step=3&id=<?php echo(noHTML($_GET['id'])); ?>&gm=<?php echo(noHTML($_GET['gm'])); ?>";
                           }
                       }
                   }
                   http.send(params);
               },2300);
               time=setInterval(function(){
                   timeWaited++;
                   document.getElementById("pTurnBef").innerHTML = "Waiting for play to join! \n\n Time Waited: " + timeWaited.toString() + "s";
               },1080);
           </script>
           <?php
       }else if ($step == 4) {
           ?>
           <div style="display:block" id="createGame" align="center">
               <h3 id="pTurnBef">Create a game:</h3><br/>
               <form method="POST" action="?step=4&id=<?php echo(noHTML($_GET['id'])); ?>">
                   <input type="text" value="" placeholder="Example's Game" name="gameName"/><br/>
                   <input type="text" value="" placeholder="Password(Leave blank = no pass)" name="gamePass"/><br/>
                   <input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>'" />
                   <input type="submit" value="Create Game!"/><br /><?php
                   if ($err == "") {
                       echo('<label id="infoPresenter" style="color:lightgray">Press the button when ready!</label>');
                   } else {
                       echo('<label id="infoPresenter" style="color:red">' . $err . '</label>');
                   }?>
               </form>
           </div>
                <?php
        }else if ($step == 2) {
            ?>
            <div style="display:block" id="signin" align="center">
                <h3 id="pTurnBef">Welcome, please choose a game or <a href="?step=4&id=<?php echo(noHTML($_GET['id']));?>">&nbsp;create&nbsp;</a> one.</h3><br/>
                <table style="width:50%">
                    <tr>
                        <th style="width:80%;background-color: black;color:white;border:solid 1px dimgrey;">Players Game</th>
                        <th style="width:20%;background-color: black;color:white;border:solid 1px dimgrey;">Actions</th>
                    </tr>
                    <?php
                        $query = "SELECT * FROM games WHERE NOT Player1 = ?";
                        if ($stmt = mysqli_prepare($link, $query)) {
                            $stmt->bind_param("s", noHTML($_GET['id']));
                            $stmt->execute();

                            $row = array();
                            stmt_bind_assoc($stmt, $row);
                            // loop through all result rows
                            while ($stmt->fetch()) {//Loop through Users Keys
                                echo('<tr>
                        <td>'.$row["GameName"].'</td>
                        <td><form method="post" action="?step=5&id='.$id.'"><input type="hidden" value="'.$row['ID'].'" name="roomID"/>');
                                if ($row['Status'] == "Need Player2"){
                                    if ($row['pass'] == ""){
                                        echo('<input value="Join Game" type="submit"/>');
                                    }else{
                                        echo('<input placeholder="password" value="" name="gamePass" type="text"/>');
                                    }
                                }else{
                                    echo('<label>'.$row['Status'].'</label>');
                                }
                                echo('</form></td>
                    </tr>');
                            }

                        }
                    ?>

                </table>
            </div>
           <?php
       }else if ($step == 3) {
           $gm = noHTML($_GET['gm']);
           $p1 = grabField("games", "Player1", "`ID`='".$gm."'", $dbc);
           $p2 = grabField("games", "Player2", "`ID`='".$gm."'", $dbc);
           $startStat = grabField("games", "Status", "`ID`='".$gm."'", $dbc);
           ?>
           <script>
               var timeWaited = 0;
               var gm = "<?php echo $gm;?>";
               var mee = "Unknown";
               var meID = "<?php echo $id;?>";
               var p1 = "<?php echo $p1;?>";
               var p1Name = "<?php echo grabField("players", "Name", "`ID`='".$p1."'", $dbc);?>";
               var p2 = "<?php echo $p2;?>";
               var p2Name = "<?php echo grabField("players", "Name", "`ID`='".$p2."'", $dbc);?>";
               var stat = "<?php echo $startStat;?>";
               window.onload = function(){
                   if (meID == p1){
                       mee = "Player1";
                   }else{
                       mee = "Player2";
                   }
               }
               time=setInterval(function(){
                   var http = new XMLHttpRequest();
                   var url = "?step=11&id=<?php echo(noHTML($_GET['id'])); ?>&gm=<?php echo(noHTML($_GET['gm'])); ?>";
                   var params = "";
                   http.open("POST", url, true);

//Send the proper header information along with the request
                   http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

                   http.onreadystatechange = function() {//Call a function when the state changes.
                       if(http.readyState == 4 && http.status == 200) {

                           var src = http.responseText;
                           var newarray = src.split("~");
                           var status = newarray[0];
                           if (status == "Dead Room"){
                               window.location = "?step=2&id=<?php echo $id; ?>";
                           }else if (status == "Player1 Won"){
                               document.getElementById("pTurnImg").style.display = "none";
                               document.getElementById("pTurnBef").innerHTML = " " + p1Name + " Won <a href='?step=13&id=<?php echo $id;?>&gm=<?php echo $gm;?>'>&nbsp;Rematch?&nbsp;</a>";
                               document.getElementById("pTurnAft").innerHTML = "";
                           }else if (status == "Player2 Won"){
                               document.getElementById("pTurnImg").style.display = "none";
                               document.getElementById("pTurnBef").innerHTML = " " + p2Name + " Won <a href='?step=13&id=<?php echo $id;?>&gm=<?php echo $gm;?>'>&nbsp;Rematch?&nbsp;</a>";
                               document.getElementById("pTurnAft").innerHTML = "";
                           }
                           if (status == "Player1 Turn"){
                               if (mee != "Player1"){
                                   paused = 1;
                                    document.getElementById("pTurnImg").style.display = "none";
                                    document.getElementById("pTurnBef").innerHTML = "It's " + p1Name + "'s Turn";
                                    document.getElementById("pTurnAft").innerHTML = "";
                               }else{
                                   paused = 0;
                                   document.getElementById("pTurnImg").style.display = "none";
                                   document.getElementById("pTurnBef").innerHTML = "Your Turn";
                                   document.getElementById("pTurnAft").innerHTML = "";
                               }
                           }else if (status == "Player2 Turn"){
                               if (mee != "Player2"){
                                   paused = 1;
                                   document.getElementById("pTurnImg").style.display = "none";
                                   document.getElementById("pTurnBef").innerHTML = "It's " + p2Name + "'s Turn";
                                   document.getElementById("pTurnAft").innerHTML = "";
                               }else{
                                   paused = 0;
                                   document.getElementById("pTurnImg").style.display = "none";
                                   document.getElementById("pTurnBef").innerHTML = "Your Turn";
                                   document.getElementById("pTurnAft").innerHTML = "";
                               }
                           }
                           var board = src.substring(src.indexOf('~') + 1, src.length);
                           var arr = board.split(',');
                           var arrLgth = arr.length;
                            console.log("recieved:" + board);
                           for (var i = 0; i < arrLgth ; i++) {
                               var val = arr[i].substring(arr[i].indexOf(':') + 1, arr[i].length);
                               var nID = arr[i].substring(0, arr[i].indexOf(':'));

                               if (val == 1){
                                   document.getElementById(nID).className = "xSpot";
                               }else if (val == 2){
                                   document.getElementById(nID).className = "oSpot";
                               }else if (val == 0) {
                                   document.getElementById(nID).className = "nSpot";
                               }
                               //Do something
                           }
                       }
                   }
                   http.send(params);
               },1080);
           </script
            <!-- START gameboard -->
            <div style="display:block" id="gameBoard" align="center">
                <h3 id="pTurnBef"></h3><img id="pTurnImg" src="assets/img/x.png"><h3 id="pTurnAft">'s Turn</h3>
                <table id="tttTable">
                    <tr>
                        <td onclick="setSpot('a1');" id="a1" class="nSpot"></td>
                        <td onclick="setSpot('a2');" id="a2" class="nSpot"></td>
                        <td onclick="setSpot('a3');" id="a3" class="nSpot"></td>
                    </tr>
                    <tr>
                        <td onclick="setSpot('b1');" id="b1" class="nSpot"></td>
                        <td onclick="setSpot('b2');" id="b2" class="nSpot"></td>
                        <td onclick="setSpot('b3');" id="b3" class="nSpot"></td>
                    </tr>
                    <tr>
                        <td onclick="setSpot('c1');" id="c1" class="nSpot"></td>
                        <td onclick="setSpot('c2');" id="c2" class="nSpot"></td>
                        <td onclick="setSpot('c3');" id="c3" class="nSpot"></td>
                    </tr>
                </table>
            </div>
           <?php
       }
            ?>
            <br/><br/>
        </div>
    </div>
</div>
</body>
</html>
