<?php
  include"connessioni.php";
    $strqry="TRUNCATE TABLE valoripid;";

if ($conn->query($strqry) === TRUE) {
    echo "Record inserito con successo";
} else {
    echo "Errore durante l'inserimento del record: " . $conn->error;
}

    mysqli_close($conn);
?>
