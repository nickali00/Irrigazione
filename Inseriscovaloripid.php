<?php
include"connessioni.php";
if (isset($_POST['valore'])) {
    $valore = $_POST['valore'];
      $query = "INSERT INTO `valoripid` (`id`, `valore`) VALUES (NULL,".$valore.")";

if ($conn->query($query) === TRUE) {
    echo "Record inserito con successo";
} else {
    echo "Errore durante l'inserimento del record: " . $conn->error;
}

    mysqli_close($conn);
  }

?>