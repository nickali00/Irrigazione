<?php
$botToken = "***";
$website = "https://api.telegram.org/bot".$botToken;
$chatID=442527310;
  include"connessioni.php";
   $id = $_GET['id'];
   $messagge= "programmazione modifica pid ".$id;
   InviaMessaggio($chatID, $messagge);

    
    //il primo deve fare una media degli ultimi 3 valori del PID
    if($id==1)
    {
     $strqry = "SELECT * FROM `PID` ORDER BY `PID`.`id` DESC limit 3";// ultimi 3 valori della giornata
	    $dati = mysqli_query($conn, $strqry);
     $first_row = mysqli_fetch_assoc($dati);
	 // Prendi il valore di setpoint dal primo record
     $setpoint = $first_row["setpoint"];
     $TP=0.0;
     $TI=0.0;
     $TD=0.0;
	foreach ($dati as $row) {
            $TP=$TP+ $row["Kp"];
            $Ti=$TI+ $row["Ki"];
            $TD=$TD+ $row["Kd"];
      }
      $TP=$TP/3;
      $TI=$TI/3;
      $TD=$TD/3;
      $messagge= "i valori medi della giornata di ieri sono\n kp: ".$TP." ki: ".$Ti." kd: ".$TD;
      //aggiungo PID
      InviaMessaggio($chatID, $messagge);
       $query ="INSERT INTO `PID` (`id`, `Kp`, `Ki`, `Kd`, `setpoint`) VALUES (NULL, '".$TP."', '".$Ti."', '".$TD."', '".$setpoint."');";
    	$result = mysqli_query($conn, $query);
        InviaMessaggio($chatID,"valori PID modificati");
     file_get_contents("https://nicolaaliuni.altervista.org/irrigazione/modificapid.php");
    }else{
     do{
    //quando entra qui significa che i dati vanno fatti interagire con ia
    // giusta
    //$strqry = "SELECT datiumidita.id, PID.id AS fkpid, PID.kp, PID.kd, PID.ki, PID.setpoint, datiumidita.valore AS umidita_terreno, valoripid.valore AS PID, datiumidita.on_off FROM PID JOIN datiumidita ON PID.id = datiumidita.fkpid JOIN valoripid ON valoripid.id = datiumidita.id WHERE DATE(datiumidita.data) = CURDATE() ORDER BY datiumidita.id ASC";
	$data_oggi = date('Y-m-d');
    $strqry = "SELECT datiumidita.id, PID.id AS fkpid, PID.kp, PID.kd, PID.ki, PID.setpoint, datiumidita.valore AS umidita_terreno, valoripid.valore AS PID, datiumidita.on_off FROM PID JOIN datiumidita ON PID.id = datiumidita.fkpid JOIN valoripid ON valoripid.id = datiumidita.id WHERE DATE(datiumidita.data) = '".$data_oggi."' ORDER BY datiumidita.id DESC";
    echo($strqry);
    $dati = mysqli_query($conn, $strqry);
     $first_row = mysqli_fetch_assoc($dati);
	 // Prendi il valore di setpoint dal primo record
     $setpoint = $first_row["setpoint"];
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
            "Ut" => (int)$row["umidita_terreno"],
            "Vp" => (int)$row["PID"]//,
            //"F" => $app
        ];
    }
        // Converti l'array associativo in una lista JSON
   	$js =json_encode(array_values($data));
    echo($js);
    $messaggioai="sto eseguendo un progetto per irrigazione domotica puoi analizzare i seguenti parametri PID con i relativi risultati e consigliarmi come modificare i parametri PID (Ut-> umidità terreno VP-> è il valore in uscita del PID che va da  0 a 255 e definisce anche il flusso d'acqua ho visto che sopra i 146 il motore irriga, soprattutto vorrei che come ultima riga metti i parametri che consigli Kp,Kd,Ki. valori->: ".$js; 
                    echo($messaggioai);
                    InviaMessaggio($chatID,"elaborazione ...");
                    //InviaMessaggio($chatID,$messaggioai);
                    //qwen
                    $apiUrlai = 'https://openrouter.ai/api/v1/chat/completions';
                    $title = 'Nicola prova ai';
                    $input = $messaggioai; // Sostituire con l'input dell'utente
                    $apiKeyai = 'sk-or-v1-***';

                    $userMessage = htmlspecialchars($_POST['user_message']); // Salva il messaggio dell'utente

                    // Crea l'array dei dati da inviare
                    $data = [
                      "model" => "qwen/qwen2.5-vl-72b-instruct:free",
                      "messages" => [
                        [
                          "role" => "user",
                          "content" => [
                            [
                              "type" => "text",
                              "text" => $messaggioai
                            ]
                          ]
                        ]
                      ]
                    ];

                    $jsonData = json_encode($data);

                    // Configura il contesto di stream per inviare i dati tramite POST
                    $options = [
                      'http' => [
                        'method'  => 'POST',
                        'header'  => [
                          'Authorization: Bearer ' . $apiKeyai,
                          'HTTP-Referer: ' . $referer,
                          'X-Title: ' . $title,
                          'Content-Type: application/json'
                        ],
                        'content' => $jsonData,
                        'timeout' => 300 // Imposta un timeout di 120 secondi
                      ]
                    ];



                    $context = stream_context_create($options);

                    // Esegui la richiesta HTTP
                    $response = file_get_contents($apiUrlai, false, $context);

                    // Verifica la risposta
                    if ($response === FALSE) {
                      $markdownText = 'Error: Request failed or timed out.';
                      InviaMessaggio($chatID, $markdownText);
                    } else {
                      $data = json_decode($response, true);

                      if (isset($data['choices'][0]['message']['content'])) {
                        $markdownText = $data['choices'][0]['message']['content'];
                      } else {
                        $markdownText = 'Riprova,errore imprevisto.';
                      }
					  $normalizedText = strtolower(preg_replace(['/[*]/', '/:/'], ['', '='], $markdownText));
                      InviaMessaggio($chatID,$normalizedText);
                       $kp = $ki = $kd = null;

					$pattern = '/(?:-?\s*(kp|ki|kd)\s*=\s*([\d\.]+))/i';

                  if (preg_match_all($pattern, $normalizedText, $matches, PREG_SET_ORDER)) {
                      foreach ($matches as $match) {
                          ${$match[1]} = $match[2]; // Salva il valore direttamente in minuscolo
                      }
                  }

                        $found = !empty($kp) || !empty($ki) || !empty($kd);
						$TP=$kp;
                        $TI=$ki;
                        $TD=$kd;
                        $messagge= "i valori calcolati dalla AI sono\n kp: ".$TP." ki: ".$TI." kd: ".$TD;
                        //aggiungo PID
                        InviaMessaggio($chatID, $messagge);
                         $query ="INSERT INTO `PID` (`id`, `Kp`, `Ki`, `Kd`, `setpoint`) VALUES (NULL, '".$TP."', '".$TI."', '".$TD."', '".$setpoint."');";
                          InviaMessaggio($chatID, "test".$messagge);
                          $result = mysqli_query($conn, $query);
                         // InviaMessaggio($chatID,"valori PID modificati");
                       file_get_contents("https://nicolaaliuni.altervista.org/irrigazione/modificapid.php");
                        echo "Ultimi valori:\n";
                        echo "Kp = $lastKp\n";
                        echo "Ki = $lastKi\n";
                        echo "Kd = $lastKd\n";
                       // InviaMessaggio($chatID,$markdownText);
                    }
                  
          } while (!$found);
}
    
function InviaMessaggio($chatID,$messaggio){
    $url = "$GLOBALS[website]/sendMessage?chat_id=$chatID&parse_mode=HTML&text=".urlencode($messaggio);
    file_get_contents($url);
}
?>
