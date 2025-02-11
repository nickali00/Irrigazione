<?php
/*
creare bot telegram botfather creare webook
https://api.telegram.org/botCHIAVEBOT/setWebHook?url=https://DOMINIO/irrigazione/index.php

*/
define('DB_HOST', ' '); // Inserisci qui l'host del tuo database
define('DB_USER', ' '); // Inserisci qui l'utente del tuo database
define('DB_PASSWORD', ' '); // Inserisci qui la password del tuo database
define('DB_NAME', ' '); // Inserisci qui il nome del tuo database
ini_set('max_execution_time', 260);

$botToken = "CHIAVE BOT";
$website = "https://api.telegram.org/bot".$botToken;
//GetUpdate
$update = file_get_contents('php://input');
$updateraw = $update;
$update = json_decode($update, TRUE);
$chatID = $update["message"]["chat"]["id"];
$message = $update["message"]["text"];
$message_id = $update["message"]["message_id"];
$nome = $update["message"]["chat"]["first_name"];
$cognome = $update["message"]["chat"]["last_name"];
$username = $update["message"]["chat"]["username"];
$myfile = fopen("lastupdate.txt", "w") or die("Unable to open file!");
fwrite($myfile, $updateraw);
fclose($myfile);
//InviaMessaggio($chatID,"$message");
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
function InviaMessaggio($chatID,$messaggio){
    $url = "$GLOBALS[website]/sendMessage?chat_id=$chatID&parse_mode=HTML&text=".urlencode($messaggio);
    file_get_contents($url);
}
function InviaFoto($chatID, $imagePath) {
    $url = "$GLOBALS[website]/sendPhoto?chat_id=$chatID&photo=" . urlencode($imagePath);
    file_get_contents($url);
}
function InviaMessaggioE($chatID, $messaggio){
	$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    $url = "$GLOBALS[website]/sendMessage?chat_id=$chatID&parse_mode=HTML&text=".urlencode($messaggio);
    $response = file_get_contents($url);
    $messageData = json_decode($response, true);
    $messageID = $messageData['result']['message_id'];
	//InviaMessaggio($chatID, $messageID);
 	$query = "UPDATE `settairrigazione` SET `elimina` = '".$messageID."' WHERE `settairrigazione`.`id` = 1";
	$result = mysqli_query($conn, $query);
}

function EliminaMessaggiodb($chatID){
		$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
		$query ="SELECT * FROM `settairrigazione` WHERE settairrigazione.id =1";
        $result = mysqli_query($conn, $query);
        $row = mysqli_fetch_assoc($result);
          $deleteMessageUrl = $GLOBALS['website'] . "/deleteMessage?chat_id=" . $chatID . "&message_id=" . $row['elimina'];
          //InviaMessaggioE($chatID, $deleteMessageUrl);
          file_get_contents($deleteMessageUrl);
         
}
function EliminaMessaggio($chatID,$messaggio){
  $deleteMessageUrl = "$GLOBALS[website]/deleteMessage?chat_id=$chatID&message_id=$messaggio";
        file_get_contents($deleteMessageUrl);
}

function tastierino($chatID,$oggetto){
					$keyboard = [
    						'inline_keyboard' => []
						];

                      array_push($keyboard['inline_keyboard'], [
                        ['text' => '1', 'callback_data' => 'tastierino#1#'.$oggetto],
                        ['text' => '2', 'callback_data' => 'tastierino#2#'.$oggetto],
                        ['text' => '3', 'callback_data' => 'tastierino#3#'.$oggetto],
                        ['text' => '4', 'callback_data' => 'tastierino#4#'.$oggetto],
                        ['text' => '5', 'callback_data' => 'tastierino#5#'.$oggetto]
   					 ]);
 					          array_push($keyboard['inline_keyboard'], [
                        ['text' => '6', 'callback_data' => 'tastierino#6#'.$oggetto],
                        ['text' => '7', 'callback_data' => 'tastierino#7#'.$oggetto],
                        ['text' => '8', 'callback_data' => 'tastierino#8#'.$oggetto],
                        ['text' => '9', 'callback_data' => 'tastierino#9#'.$oggetto],
                        ['text' => '0', 'callback_data' => 'tastierino#0#'.$oggetto]
   					 ]);
                   	array_push($keyboard['inline_keyboard'], [
                        ['text' => 'invia', 'callback_data' => 'tastierino#invia#'.$oggetto],
                        ['text' => '<-', 'callback_data' => 'tastierino#c#'.$oggetto],
                        ['text' => 'cancella_tutto', 'callback_data' => 'tastierino#ct#'.$oggetto],
   					 ]);

					// Converti l'array della tastiera in una stringa JSON
					$encodedKeyboard = json_encode($keyboard);

					// Crea un array con i parametri da inviare alle API di Telegram
					$parameters = [
    					'chat_id' => $chatID,
    					'text' => 'Menù tastiernino',
    					'reply_markup' => $encodedKeyboard,
						];

						// Converti l'array dei parametri in una stringa da passare all'URL
						$query = http_build_query($parameters);

						// Invia la richiesta HTTP alle API di Telegram
							$response = file_get_contents("$GLOBALS[website]/sendMessage?" . http_build_query($parameters));
}



if (isset($update["callback_query"])) {
    $callbackQuery = $update["callback_query"];
    $data = $callbackQuery["data"];
    $chatId = $callbackQuery["message"]["chat"]["id"];
    $usernames = $callbackQuery["message"]["chat"]["username"];
    $messageId = $callbackQuery["message"]["message_id"];
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	$query ="SELECT * FROM `settairrigazione`";
    $result = mysqli_query($conn, $query);
	$IR = mysqli_fetch_assoc($result);
    switch ($data)
    {

    case 'programma':
    //inizio irrigazione
    InviaMessaggio($chatId,"inserisci orario inizio");
     tastierino($chatId,"programma");
    break;
    case 'responsetime':
    //inizio irrigazione
    InviaMessaggio($chatId,"inserisci velocità del sistema");
     tastierino($chatId,"responsetime");
    break;
    case 'On':
    //inizio irrigazione
    InviaMessaggio($chatId,"inizio irrigazione");
     file_get_contents("https://DOMINIO/irrigazione/irriga.php");
    break;
    case 'Off':
    //fine irrigazione
    InviaMessaggio($chatId,"fine irrigazione");
     file_get_contents("https://DOMINIO/irrigazione/fine.php");
    break;
    case 'Leggi':
    file_get_contents("https://DOMINIO/irrigazione/leggidati.php");
    sleep(15);
    $json = file_get_contents("http://DOMINIO/irrigazione/apiumidita.php");
    $data = json_decode($json, true);
    InviaMessaggio($chatId,"umidità terreno:".$data['valore']);
    break;
    //PID
        case 'PID':
    EliminaMessaggio($chatId,$messageId);
    //inizio irrigazione
    PID($chatId);
    break;
     case 'Kp':
    //inizio irrigazione
    InviaMessaggio($chatId,"inserisci Kp");
     tastierino($chatId,"Kp");
    break;
     case 'Ki':
    //inizio irrigazione
    InviaMessaggio($chatId,"inserisci Ki");
     tastierino($chatId,"Ki");
    break;
     case 'Kd':
    //inizio irrigazione
    InviaMessaggio($chatId,"inserisci Kd");
     tastierino($chatId,"Kd");
    break;
     case 'setpoint':
    //inizio irrigazione
    InviaMessaggio($chatId,"inserisci umidità desiderata");
     tastierino($chatId,"setpoint");
    break;
     case 'confermapid':
    //metto lo stato a 3
    InviaMessaggio($chatId,"valori PID modificati");
     file_get_contents("https://DOMINIO/irrigazione/modificapid.php");
    break;
     case 'eliminapid':
    //puliscovaloridb
    InviaMessaggio($chatId,"valori PID inizializzati");
     file_get_contents("https://DOMINIO/irrigazione/eliminavaloripid.php");
    
    break;
    case 'grafico':
    $appID = "CHIAVE API WOLFRAMALPHA";//apiwolframalpha
    // URL from which to fetch the JSON data
    $url = "https://DOMINIO/irrigazione/apipidvalori.php";

    // Fetch the JSON data from the URL
    $json_data = file_get_contents($url);

    // Check if the data was fetched successfully
    if ($json_data === false) {
        die("Error fetching data from URL");
    }

    // Decode the JSON data into an associative array
    $data_array = json_decode($json_data, true);

    // Check if decoding was successful
    if ($data_array === null) {
        die("Error decoding JSON data");
    }

    // Print the decoded data to verify
    //print_r($data_array);

    // Access individual values
    foreach ($data_array as $item) {
    $inputa .= "{" . $item['id'] . ", " . $item['valore'] . "}, ";
    }
    $inputa = substr($inputa, 0, -2);
	$inputa="ListLinePlot[{".$inputa."}]";
	$inputa = rtrim($inputa, ", ") ;
    //InviaMessaggio($chatId,$inputa);
	$url = "http://api.wolframalpha.com/v1/simple?appid=" . $appID . "&i=" . urlencode($inputa);
  	$response = file_get_contents($url);
    InviaFoto($chatId,$url);
    break;
    case 'umidita':
 	$appID = "CHIAVE API WOLFRAMALPHA";
    $url = "https://DOMINIO/irrigazione/apivaloriumidit.php";

    // Fetch the JSON data from the URL
    $json_data = file_get_contents($url);

    // Check if the data was fetched successfully
    if ($json_data === false) {
        die("Error fetching data from URL");
    }

    // Decode the JSON data into an associative array
    $data_array = json_decode($json_data, true);

    // Check if decoding was successful
    if ($data_array === null) {
        die("Error decoding JSON data");
    }

    // Print the decoded data to verify
    //print_r($data_array);

    // Access individual values
    foreach ($data_array as $item) {
    $inputa .= "{" . $item['id'] . ", " . $item['valore'] . "}, ";
    }
    $inputa = substr($inputa, 0, -2);
	$inputa="ListLinePlot[{".$inputa."}]";
	$inputa = rtrim($inputa, ", ") ;
    //InviaMessaggio($chatId,$inputa);
	$url = "http://api.wolframalpha.com/v1/simple?appid=" . $appID . "&i=" . urlencode($inputa);
  	$response = file_get_contents($url);
    InviaFoto($chatId,$url);
	break;
         default:
         	  $array = explode("#", $data);
       		  // InviaMessaggio($chatId,$data);
               EliminaMessaggiodb($chatId);
               
               		if (isset($array[1]) && is_numeric($array[1])) {
                  	//prendo il valore attuale
                    //InviaMessaggio($chatId,"valore".$IR["valore"]);
					if($IR["valore"]==NULL)
                  	$nuovocodice=$array[1];
                  	else
                  $nuovocodice=$IR["valore"].$array[1];
     			  $query = "UPDATE `settairrigazione` SET `valore` = '".$nuovocodice."' WHERE `settairrigazione`.`id`=1";
                  $result = mysqli_query($conn, $query);
             			  InviaMessaggioE($chatId,"valore:".$nuovocodice);
            				} else {
                        if($array[1]=="invia"){
                        EliminaMessaggio($chatId,$messageId);
                        EliminaMessaggiodb($chatId);
						InviaMessaggioE($chatId,"valore impostato !");
                        //inserisco timer o setpoint o responsetime
                        if ($array[2]=="programma"){
                        $query = "UPDATE `statoirrigazione` SET `impostatoorainizio` = '".$IR["valore"]."' WHERE `statoirrigazione`.`id` = 1";
                  		$result = mysqli_query($conn, $query);
                        InviaMessaggioE($chatId,"per quanti secondi attivo");
                        tastierino($chatId,"timer");
                        }
                        if ($array[2]=="timer"){
                        $query = "UPDATE `statoirrigazione` SET `timer` = '".$IR["valore"]."' WHERE `statoirrigazione`.`id` = 1";
                  		$result = mysqli_query($conn, $query);
                        }
                        if ($array[2]=="responsetime"){
                        $query = "UPDATE `statoirrigazione` SET `responsetime` = '".$IR["valore"]."' WHERE `statoirrigazione`.`id` = 1";
                  		$result = mysqli_query($conn, $query);
                        }
                        if ($array[2]=="Kp"){
                        $query = "UPDATE `PID` SET `Kp` = '".$IR["valore"]."' WHERE `PID`.`id` = 1";
                  		$result = mysqli_query($conn, $query);
                        }
                        if ($array[2]=="Ki"){
                        $query = "UPDATE `PID` SET `Ki` = '".$IR["valore"]."' WHERE `PID`.`id` = 1";
                  		$result = mysqli_query($conn, $query);
                        }
                        if ($array[2]=="Kd"){
                        $query = "UPDATE `PID` SET `Kd` = '".$IR["valore"]."' WHERE `PID`.`id` = 1";
                  		$result = mysqli_query($conn, $query);
                        }
						if ($array[2]=="setpoint"){
                        $query = "UPDATE `statoirrigazione` SET `setpoint` = '".$IR["valore"]."' WHERE `statoirrigazione`.`id` = 1";
                  		$result = mysqli_query($conn, $query);
                        
                        $query = "UPDATE `PID` SET `setpoint` = '".$IR["valore"]."' WHERE `PID`.`id` = 1";
                  		$result = mysqli_query($conn, $query);
                        }
                        //setto valore NULL
                        $query = "UPDATE `settairrigazione` SET `valore` = NULL WHERE `settairrigazione`.`id` =1";
                        $result = mysqli_query($conn, $query);
                        }else if($array[1]=="c"){
                        $IR["valore"] = substr($IR["valore"], 0, -1);
                        $query = "UPDATE `settairrigazione` SET `valore` = '".$IR["valore"]."' WHERE `settairrigazione`.`id` =1";
                        $result = mysqli_query($conn, $query);
     			  		InviaMessaggioE($chatId,"codice:".$utente["stanza"]);
                        }else if($array[1]=="ct"){
                        $query = "UPDATE `settairrigazione` SET `valore` = NULL WHERE `settairrigazione`.`id` =1";
                        $result = mysqli_query($conn, $query);
                        }
                    
               }

  			break;
      }
     // Invio della risposta al messaggio originale
    file_get_contents("https://api.telegram.org/bot".$botToken."/sendMessage?chat_id=" . $chatId . "&text=" . urlencode($responseText) );
}
//####################################################gestione comandi
switch ($message) {
			case '/start':
            case '/Start':
            case 'Start':
             case 'start':
            $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
					$query = "INSERT INTO user (`codice`, `Nome`, `Cognome`, `stato`) SELECT '".$chatID."', '".$nome."', '".$cognome."', NULL WHERE NOT EXISTS (SELECT codice FROM user WHERE codice = '".$chatID."')";
                      InviaMessaggio($chatID,"benvenuto in Irrigazione!!\n per aprire il menu digita /menu");
                      $result = mysqli_query($conn, $query);
					break;
                    case '/menu':
                    menu($chatID);
                    break;
                    default:
                    break;

                    
}

function PID($chatID){
					  $keyboard = [
    						'inline_keyboard' => []
						];
                         array_push($keyboard['inline_keyboard'], [
                        ['text' => 'Kp', 'callback_data' => 'Kp'],
                        ['text' => 'Ki', 'callback_data' => 'Ki'],
                        ['text' => 'Kd', 'callback_data' => 'Kd'],
   					 ]);
                     array_push($keyboard['inline_keyboard'], [
                        ['text' => 'set point', 'callback_data' => 'setpoint'],
   					 ]);
                     array_push($keyboard['inline_keyboard'], [
                        ['text' => 'conferma', 'callback_data' => 'confermapid'],
                         ['text' => 'elimina storico', 'callback_data' => 'eliminapid'],
   					 ]);
                      array_push($keyboard['inline_keyboard'], [
                        ['text' => 'visualizza grafico', 'callback_data' => 'grafico'],
   					 ]);

					// Converti l'array della tastiera in una stringa JSON
					$encodedKeyboard = json_encode($keyboard);

					// Crea un array con i parametri da inviare alle API di Telegram
					$parameters = [
    					'chat_id' => $chatID,
    					'text' => 'Menù',
    					'reply_markup' => $encodedKeyboard,
						];

						// Converti l'array dei parametri in una stringa da passare all'URL
						$query = http_build_query($parameters);

						// Invia la richiesta HTTP alle API di Telegram
							$response = file_get_contents("$GLOBALS[website]/sendMessage?" . http_build_query($parameters));
}

function menu($chatID){
					  $keyboard = [
    						'inline_keyboard' => []
						];
                         array_push($keyboard['inline_keyboard'], [
                        ['text' => 'On', 'callback_data' => 'On'],
                        ['text' => 'Off', 'callback_data' => 'Off'],
   					 ]);
                     array_push($keyboard['inline_keyboard'], [
                        ['text' => 'Leggi dati', 'callback_data' => 'Leggi'],
   					 ]);
                      array_push($keyboard['inline_keyboard'], [
                        ['text' => 'programma', 'callback_data' => 'programma'],
   					 ]);
                      array_push($keyboard['inline_keyboard'], [
                        //['text' => 'set point', 'callback_data' => 'setpoint'],
                        ['text' => 'PID', 'callback_data' => 'PID'],
   					 ]);
                      array_push($keyboard['inline_keyboard'], [
                        ['text' => 'response time', 'callback_data' => 'responsetime'],
   					 ]);
                      array_push($keyboard['inline_keyboard'], [
                        ['text' => 'visualizza grafico umidità', 'callback_data' => 'umidita'],
   					 ]);

					// Converti l'array della tastiera in una stringa JSON
					$encodedKeyboard = json_encode($keyboard);

					// Crea un array con i parametri da inviare alle API di Telegram
					$parameters = [
    					'chat_id' => $chatID,
    					'text' => 'Menù',
    					'reply_markup' => $encodedKeyboard,
						];

						// Converti l'array dei parametri in una stringa da passare all'URL
						$query = http_build_query($parameters);

						// Invia la richiesta HTTP alle API di Telegram
							$response = file_get_contents("$GLOBALS[website]/sendMessage?" . http_build_query($parameters));
}
