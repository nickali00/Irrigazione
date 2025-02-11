<?php
include"connessioni.php";
if (isset($_POST['valore'])) {
    $valore = $_POST['valore'];
    $stato = $_POST['stato'];
    $dataOdierna = date("Y-m-d");
    $oraCorrente = date("H:i");
    
      $query = "INSERT INTO `datiumidita` (`id`, `valore`, `data`, `ora`,`on/off`) VALUES (NULL,".$valore.", '".$dataOdierna."', '".$oraCorrente."', '".$stato."')";

if ($conn->query($query) === TRUE) {
    echo "Record inserito con successo";
} else {
    echo "Errore durante l'inserimento del record: ".$query." " . $conn->error;
}

    mysqli_close($conn);
    file_get_contents("https://DOMINIO/irrigazione/fine.php");
  }

?>
