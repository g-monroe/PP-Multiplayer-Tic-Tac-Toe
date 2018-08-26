<?php
//Initial Connection File; Very important to establish a secure connection befo ->
// -re any work is done.
if (!defined(DBCON_PASS)) {
    die("Failed to connect to database!");
}
$link = mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB);
//Declare dbc as a mysql Database connection to the database named "kevin_gate" ->
// to overall perform required requests
try{
    $dbc = new PDO('mysql:host='.DB_SERVER.';dbname='.DB,DB_USER, DB_PASS);
    $dbc->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}catch(PDOException $e){
    die('Error:Failed to Establish Connection!');
}
//End of Document.
?>
