<?php
  include"connessioni.php";
    $strqry="UPDATE `statoirrigazione` SET `stato` = '1' WHERE `statoirrigazione`.`id` = 1";

if ($conn->query($strqry) === TRUE) {
    echo "Record inserito con successo";
} else {
    echo "Errore durante l'inserimento del record: " . $conn->error;
}

    mysqli_close($conn);
?>