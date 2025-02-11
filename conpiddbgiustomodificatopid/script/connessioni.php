 <?php

$dbhost=" ";
$dbuser=" ";
$dbpassword="";
$dbname=" ";
$conn=mysqli_connect($dbhost,$dbuser,$dbpassword) or 
die("connessione fallita");
mysqli_select_db($conn,$dbname) or
die("selezione del DB fallita");


//echo "conn e selezione del database eseguita con sucesso! <br>";
?>
