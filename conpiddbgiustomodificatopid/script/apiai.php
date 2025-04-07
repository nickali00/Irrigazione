<?php
  include "connessioni.php";
  // Esegui la query per ottenere gli ultimi 14 record ordinati in modo decrescente
$strqry = "SELECT datiumidita.id, PID.id AS fkpid, PID.kp, PID.kd, PID.ki, PID.setpoint, datiumidita.valore as umidita_terreno, valoripid.valore as PID, datiumidita.on_off FROM PID JOIN datiumidita ON PID.id = datiumidita.fkpid JOIN valoripid ON valoripid.id = datiumidita.id;";
$dati = mysqli_query($conn, $strqry);

   $data = [];

foreach ($dati as $row) {
    $fkpid = $row["fkpid"]; // Identificatore unico per il raggruppamento

    if (!isset($data[$fkpid])) {
        $data[$fkpid] = [
            "kp" => $row["kp"],
            "kd" => $row["kd"],
            "ki" => $row["ki"],
            "setpoint" => $row["setpoint"],
            "valori" => [] // Qui raccogliamo i valori
        ];
    }
	if($row["irrigazione"]==0)
    {
    $app="off";
    }else{
    $app="on";
    }
    $data[$fkpid]["valori"][] = [
        "umidita_terreno" => (int)$row["umidita_terreno"],
        "valore_pid" => (int)$row["PID"],
        "irrigazione" => $app
    ];
}

    // Converti l'array associativo in una lista JSON
    echo json_encode(array_values($data));
  mysqli_close($conn);
?>