<?php
  include "connessioni.php";
  // Esegui la query per ottenere gli ultimi 14 record ordinati in modo decrescente
$strqry = "SELECT * FROM `valoripid` ORDER BY `id` DESC LIMIT 14";
$dati = mysqli_query($conn, $strqry);
$js1 = array();

// Leggi i dati e aggiungili all'array $js1
while ($res = mysqli_fetch_array($dati)) {
    $js = array(
        'id' => $res["id"],
        'valore' => $res["valore"],
    );
    $js1[] = $js; // Aggiungi l'array $js all'array $js1
}

// Inverti l'ordine dell'array per visualizzare i risultati in modo crescente
$js1 = array_reverse($js1);

// Ora $js1 contiene i dati in ordine crescente
echo json_encode($js1);
  mysqli_close($conn);
?>
