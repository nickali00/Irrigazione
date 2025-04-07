<?php
define("DB_HOST", "***");
define("DB_USER", "****");
define("DB_PASSWORD", "******");
define("DB_NAME", "*****");
ini_set("max_execution_time", 300);
 
$botToken = "*****";
$website = "https://api.telegram.org/bot" . $botToken;
$update = file_get_contents("php://input");
$updateraw = $update;
$update = json_decode($update, true);
$chatID = $update["message"]["chat"]["id"];
$message = $update["message"]["text"];
$message_id = $update["message"]["message_id"];
$nome = $update["message"]["chat"]["first_name"];
$cognome = $update["message"]["chat"]["last_name"];
$username = $update["message"]["chat"]["username"];
($myfile = fopen("lastupdate.txt", "w")) or die("Unable to open file!");
fwrite($myfile, $updateraw);
fclose($myfile);
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
function InviaMessaggio($chatID, $messaggio)
{
    $url =
    	"$GLOBALS[website]/sendMessage?chat_id=$chatID&parse_mode=HTML&text=" .
    	urlencode($messaggio);
	file_get_contents($url);
}
function InviaFoto($chatID, $imagePath)
{
	$url =
        "$GLOBALS[website]/sendPhoto?chat_id=$chatID&photo=" .
    	urlencode($imagePath);
	file_get_contents($url);
}
function InviaMessaggioE($chatID, $messaggio)
{
	$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	$url =
        "$GLOBALS[website]/sendMessage?chat_id=$chatID&parse_mode=HTML&text=" .
    	urlencode($messaggio);
	$response = file_get_contents($url);
	$messageData = json_decode($response, true);
	$messageID = $messageData["result"]["message_id"];
	$query =
    	"UPDATE `settairrigazione` SET `elimina` = '" .
    	$messageID .
    	"' WHERE `settairrigazione`.`id` = 1";
	$result = mysqli_query($conn, $query);
}
 
function EliminaMessaggiodb($chatID)
{
	$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	$query = "SELECT * FROM `settairrigazione` WHERE settairrigazione.id =1";
	$result = mysqli_query($conn, $query);
	$row = mysqli_fetch_assoc($result);
	$deleteMessageUrl =
    	$GLOBALS["website"] .
    	"/deleteMessage?chat_id=" .
    	$chatID .
    	"&message_id=" .
    	$row["elimina"];
 
	file_get_contents($deleteMessageUrl);
}
function EliminaMessaggio($chatID, $messaggio)
{
	$deleteMessageUrl = "$GLOBALS[website]/deleteMessage?chat_id=$chatID&message_id=$messaggio";
	file_get_contents($deleteMessageUrl);
}
 
function tastierino($chatID, $oggetto)
{
	$keyboard = [
    	"inline_keyboard" => [],
	];
 
    array_push($keyboard["inline_keyboard"], [
    	["text" => "1", "callback_data" => "tastierino#1#" . $oggetto],
    	["text" => "2", "callback_data" => "tastierino#2#" . $oggetto],
    	["text" => "3", "callback_data" => "tastierino#3#" . $oggetto],
    	["text" => "4", "callback_data" => "tastierino#4#" . $oggetto],
    	["text" => "5", "callback_data" => "tastierino#5#" . $oggetto],
    ]);
    array_push($keyboard["inline_keyboard"], [
    	["text" => "6", "callback_data" => "tastierino#6#" . $oggetto],
    	["text" => "7", "callback_data" => "tastierino#7#" . $oggetto],
    	["text" => "8", "callback_data" => "tastierino#8#" . $oggetto],
    	["text" => "9", "callback_data" => "tastierino#9#" . $oggetto],
    	["text" => "0", "callback_data" => "tastierino#0#" . $oggetto],
    ]);
    array_push($keyboard["inline_keyboard"], [
    	["text" => ".", "callback_data" => "tastierino#punto#" . $oggetto],
    	["text" => "invia", "callback_data" => "tastierino#invia#" . $oggetto],
    	["text" => "<-", "callback_data" => "tastierino#c#" . $oggetto],
    	[
        	"text" => "cancella_tutto",
        	"callback_data" => "tastierino#ct#" . $oggetto,
    	],
    ]);
 
    $encodedKeyboard = json_encode($keyboard);
 
    $parameters = [
    	"chat_id" => $chatID,
    	"text" => "Menù tastiernino",
    	"reply_markup" => $encodedKeyboard,
    ];
 
	$query = http_build_query($parameters);
 
	$response = file_get_contents(
        "$GLOBALS[website]/sendMessage?" . http_build_query($parameters)
	);
}
 
if (isset($update["callback_query"])) {
	$callbackQuery = $update["callback_query"];
	$data = $callbackQuery["data"];
	$chatId = $callbackQuery["message"]["chat"]["id"];
	$usernames = $callbackQuery["message"]["chat"]["username"];
	$messageId = $callbackQuery["message"]["message_id"];
	$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	$query = "SELECT * FROM `settairrigazione`";
	$result = mysqli_query($conn, $query);
	$IR = mysqli_fetch_assoc($result);
	switch ($data) {
    	case "programma":
        	InviaMessaggio($chatId, "inserisci orario inizio");
        	tastierino($chatId, "programma");
        	break;
    	case "responsetime":
        	InviaMessaggio($chatId, "inserisci velocità del sistema");
        	tastierino($chatId, "responsetime");
        	break;
    	case "On":
        	InviaMessaggio($chatId, "inizio irrigazione");
        	file_get_contents(
                "https://nicolaaliuni.altervista.org/irrigazione/irriga.php"
        	);
        	break;
    	case "Off":
        	InviaMessaggio($chatId, "fine irrigazione");
        	file_get_contents(
                "https://nicolaaliuni.altervista.org/irrigazione/fine.php"
        	);
        	break;
    	case "Leggi":
        	file_get_contents(
                "https://nicolaaliuni.altervista.org/irrigazione/leggidati.php"
        	);
        	sleep(15);
        	$json = file_get_contents(
                "http://nicolaaliuni.altervista.org/irrigazione/apiumidita.php"
        	);
        	$data = json_decode($json, true);
        	InviaMessaggio($chatId, "umidità terreno:" . $data["valore"]);
        	break;
    	case "nuovo_pid":
        	InviaMessaggio($chatId, "pid creato, inserisci valori");
        	$query =
            	"INSERT INTO `PID` (`id`, `Kp`, `Ki`, `Kd`, `setpoint`) VALUES (NULL, '0', '0', '0', '0');";
        	$result = mysqli_query($conn, $query);
        	break;
    	case "PID":
        	EliminaMessaggio($chatId, $messageId);
        	PID($chatId);
        	break;
    	case "Kp":
        	InviaMessaggio($chatId, "inserisci Kp");
        	tastierino($chatId, "Kp");
        	break;
    	case "Ki":
        	InviaMessaggio($chatId, "inserisci Ki");
        	tastierino($chatId, "Ki");
        	break;
    	case "Kd":
        	InviaMessaggio($chatId, "inserisci Kd");
        	tastierino($chatId, "Kd");
        	break;
    	case "setpoint":
        	InviaMessaggio($chatId, "inserisci umidità desiderata");
        	tastierino($chatId, "setpoint");
        	break;
    	case "confermapid":
        	InviaMessaggio($chatId, "valori PID modificati");
        	file_get_contents(
                "https://nicolaaliuni.altervista.org/irrigazione/modificapid.php"
        	);
        	break;
    	case "eliminapid":
        	InviaMessaggio($chatId, "valori PID inizializzati");
        	file_get_contents(
                "https://nicolaaliuni.altervista.org/irrigazione/eliminavaloripid.php"
        	);
 
        	break;
    	case "grafico":
        	$appID = "****";
        	$url =
                "https://nicolaaliuni.altervista.org/irrigazione/apipidvalori.php";
        	$json_data = file_get_contents($url);
        	if ($json_data === false) {
            	die("Error fetching data from URL");
        	}
        	$data_array = json_decode($json_data, true);
        	if ($data_array === null) {
            	die("Error decoding JSON data");
        	}
        	foreach ($data_array as $item) {
            	$inputa .= "{" . $item["id"] . ", " . $item["valore"] . "}, ";
        	}
        	$inputa = substr($inputa, 0, -2);
        	$inputa = "ListLinePlot[{" . $inputa . "}]";
        	$inputa = rtrim($inputa, ", ");
        	$url =
                "http://api.wolframalpha.com/v1/simple?appid=" .
            	$appID .
            	"&i=" .
            	urlencode($inputa);
        	$response = file_get_contents($url);
        	InviaFoto($chatId, $url);
        	break;
    	case "umidita":
        	$appID = "****";
        	$url =
                "https://nicolaaliuni.altervista.org/irrigazione/apivaloriumidit.php";
        	$json_data = file_get_contents($url);
        	if ($json_data === false) {
            	die("Error fetching data from URL");
        	}
        	$data_array = json_decode($json_data, true);
        	if ($data_array === null) {
            	die("Error decoding JSON data");
        	}
        	foreach ($data_array as $item) {
            	$inputa .= "{" . $item["id"] . ", " . $item["valore"] . "}, ";
        	}
        	$inputa = substr($inputa, 0, -2);
        	$inputa = "ListLinePlot[{" . $inputa . "}]";
        	$inputa = rtrim($inputa, ", ");
        	$url =
                "http://api.wolframalpha.com/v1/simple?appid=" .
            	$appID .
            	"&i=" .
            	urlencode($inputa);
        	$response = file_get_contents($url);
     	   InviaFoto($chatId, $url);
        	break;
    	default:
        	$array = explode("#", $data);
        	EliminaMessaggiodb($chatId);
 
        	if (isset($array[1]) && is_numeric($array[1])) {
            	if ($IR["valore"] == null) {
                	$nuovocodice = $array[1];
            	} else {
                	$nuovocodice = $IR["valore"] . $array[1];
            	}
            	$query =
                	"UPDATE `settairrigazione` SET `valore` = '" .
                	$nuovocodice .
                	"' WHERE `settairrigazione`.`id`=1";
            	$result = mysqli_query($conn, $query);
            	InviaMessaggioE($chatId, "valore:" . $nuovocodice);
        	} else {
            	if ($array[1] == "invia") {
                	EliminaMessaggio($chatId, $messageId);
                    EliminaMessaggiodb($chatId);
                	InviaMessaggioE($chatId, "valore impostato !");
                	if ($array[2] == "programma") {
                        $query =
                        	"UPDATE `statoirrigazione` SET `impostatoorainizio` = '" .
                            $IR["valore"] .
                        	"' WHERE `statoirrigazione`.`id` = 1";
                    	$result = mysqli_query($conn, $query);
                        InviaMessaggioE($chatId, "per quanti secondi attivo");
                    	tastierino($chatId, "timer");
                	}
                	if ($array[2] == "timer") {
                    	$query =
                        	"UPDATE `statoirrigazione` SET `timer` = '" .
                            $IR["valore"] .
                        	"' WHERE `statoirrigazione`.`id` = 1";
                    	$result = mysqli_query($conn, $query);
                	}
                	if ($array[2] == "responsetime") {
                    	$query =
                        	"UPDATE `statoirrigazione` SET `responsetime` = '" .
                            $IR["valore"] .
                        	"' WHERE `statoirrigazione`.`id` = 1";
                    	$result = mysqli_query($conn, $query);
                	}
                	$query =
                    	"SELECT * FROM `PID` ORDER BY `PID`.`id` DESC LIMIT 1";
                	$result = mysqli_query($conn, $query);
                	$inc = mysqli_fetch_assoc($result);
                	if ($array[2] == "Kp") {
                    	$query =
                        	"UPDATE `PID` SET `Kp` = '" .
                            $IR["valore"] .
                        	"' WHERE `PID`.`id` =" .
                            $inc["id"];
                    	$result = mysqli_query($conn, $query);
                	}
                	if ($array[2] == "Ki") {
                    	$query =
                        	"UPDATE `PID` SET `Ki` = '" .
                            $IR["valore"] .
                        	"' WHERE `PID`.`id` = " .
                            $inc["id"];
                    	$result = mysqli_query($conn, $query);
                	}
                	if ($array[2] == "Kd") {
                    	$query =
                        	"UPDATE `PID` SET `Kd` = '" .
                        	$IR["valore"] .
                        	"' WHERE `PID`.`id` = " .
                            $inc["id"];
                    	$result = mysqli_query($conn, $query);
                	}
                	if ($array[2] == "setpoint") {
                    	$query =
                        	"UPDATE `statoirrigazione` SET `setpoint` = '" .
                        	$IR["valore"] .
                        	"' WHERE `statoirrigazione`.`id` = 1";
                    	$result = mysqli_query($conn, $query);
 
                    	$query =
                        	"UPDATE `PID` SET `setpoint` = '" .
                            $IR["valore"] .
                        	"' WHERE `PID`.`id` = " .
                        	$inc["id"];
                    	$result = mysqli_query($conn, $query);
                	}
                	$query =
                    	"UPDATE `settairrigazione` SET `valore` = NULL WHERE `settairrigazione`.`id` =1";
                	$result = mysqli_query($conn, $query);
            	} elseif ($array[1] == "c") {
                	$IR["valore"] = substr($IR["valore"], 0, -1);
                	$query =
                    	"UPDATE `settairrigazione` SET `valore` = '" .
                    	$IR["valore"] .
                    	"' WHERE `settairrigazione`.`id` =1";
                	$result = mysqli_query($conn, $query);
                	InviaMessaggioE($chatId, "codice:" . $utente["stanza"]);
            	} elseif ($array[1] == "ct") {
                	$query =
                    	"UPDATE `settairrigazione` SET `valore` = NULL WHERE `settairrigazione`.`id` =1";
                	$result = mysqli_query($conn, $query);
            	} elseif ($array[1] == "punto") {
                	$nuovocodice = $IR["valore"] . ".";
                	$query =
                    	"UPDATE `settairrigazione` SET `valore` = '" .
                    	$nuovocodice .
                    	"' WHERE `settairrigazione`.`id`=1";
                	$result = mysqli_query($conn, $query);
            	}
        	}
 
        	break;
   }
    file_get_contents(
        "https://api.telegram.org/bot" .
        	$botToken .
        	"/sendMessage?chat_id=" .
        	$chatId .
        	"&text=" .
        	urlencode($responseText)
	);
}
switch ($message) {
	case "/start":
	case "/Start":
	case "Start":
	case "start":
    	$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    	$query =
        	"INSERT INTO user (`codice`, `Nome`, `Cognome`, `stato`) SELECT '" .
        	$chatID .
        	"', '" .
        	$nome .
        	"', '" .
        	$cognome .
        	"', NULL WHERE NOT EXISTS (SELECT codice FROM user WHERE codice = '" .
        	$chatID .
        	"')";
    	InviaMessaggio(
        	$chatID,
        	"benvenuto in Irrigazione!!\n per aprire il menu digita /menu"
    	);
    	$result = mysqli_query($conn, $query);
    	break;
	case "/menu":
    	menu($chatID);
    	break;
 
	case "/ia":
    	$urlai = "https://nicolaaliuni.altervista.org/irrigazione/apiai.php";
 
    	$ch = curl_init();
 
    	curl_setopt($ch, CURLOPT_URL, $urlai);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
    	$response = curl_exec($ch);
 
    	if (curl_errno($ch)) {
        	echo "Errore cURL: " . curl_error($ch);
    	}
 
    	curl_close($ch);
 
    	$data = json_decode($response, true);
    	$stringa = json_encode($data, JSON_PRETTY_PRINT);
    	$messaggioai =
        	"sto eseguendo un progetto per irrigazione domotica puoi analizzare i seguenti parametri PID con i relativi risultati e consigliarmi come modificare i parametri PID, soprattutto vorrei che come ultima riga metti i parametri che consigli Kp,Kd,Ki. valori->: " .
        	$stringa;
    	InviaMessaggio($chatID, "elaborazione ...");
    	$apiUrlai = "https://openrouter.ai/api/v1/chat/completions";
    	$title = "Nicola prova ai";
    	$input = $messaggioai;
    	$apiKeyai ="***";
 
    	$userMessage = htmlspecialchars($_POST["user_message"]);
 
    	$data = [
        	"model" => "qwen/qwen2.5-vl-72b-instruct:free",
        	"messages" => [
            	[
                	"role" => "user",
                	"content" => [
                    	[
                        	"type" => "text",
 	                       "text" => $messaggioai,
                    	],
                	],
            	],
        	],
    	];
 
    	$jsonData = json_encode($data);
    	$options = [
        	"http" => [
            	"method" => "POST",
            	"header" => [
                	"Authorization: Bearer " . $apiKeyai,
                	"HTTP-Referer: " . $referer,
                	"X-Title: " . $title,
                	"Content-Type: application/json",
            	],
            	"content" => $jsonData,
            	"timeout" => 300,
        	],
    	];
 
    	$context = stream_context_create($options);
 
    	$response = file_get_contents($apiUrlai, false, $context);
 
    	if ($response === false) {
        	$markdownText = "Error: Request failed or timed out.";
        	InviaMessaggio($chatID, $markdownText);
    	} else {
        	$data = json_decode($response, true);
 
        	if (isset($data["choices"][0]["message"]["content"])) {
            	$markdownText = $data["choices"][0]["message"]["content"];
        	} else {
            	$markdownText = "Riprova,errore imprevisto.";
        	}
 
        	InviaMessaggio($chatID, "Risposta da qwen: \n" . $markdownText);
    	}
    	$message = "";
 
    	break;
    default:
    	$parole = explode(" ", $message);
    	if (
        	isset($parole[0], $parole[1]) &&
        	$parole[0] === "Domanda" &&
        	$parole[1] === "tesi"
    	) {
        	$sql = "SELECT * FROM `info` where nome = 'tesi'";
        	$result = mysqli_query($conn, $sql);
        	$row = mysqli_fetch_assoc($result);
      	  $messaggioai =
            	"la domanda a cui devi rispondere ti devi basare su queste informazioni: " .
            	$row["descrizione"] .
            	"ora rispondi alla seguente " .
            	$message;
        	InviaMessaggio($chatID, "Elaborazione Tesi...");
 
        	$apiUrlai = "https://openrouter.ai/api/v1/chat/completions";
        	$title = "Nicola prova ai";
        	$input = $messaggioai;
        	$apiKeyai ="***";
 
        	$userMessage = htmlspecialchars($_POST["user_message"]);
 
        	$data = [
            	"model" => "qwen/qwen2.5-vl-72b-instruct:free",
            	"messages" => [
                	[
                    	"role" => "user",
                    	"content" => [
                        	[
                                "type" => "text",
                            	"text" => $messaggioai,
                        	],
                    	],
                	],
            	],
        	];
 
            $jsonData = json_encode($data);
 
        	$options = [
            	"http" => [
                	"method" => "POST",
                	"header" => [
                    	"Authorization: Bearer " . $apiKeyai,
                    	"HTTP-Referer: " . $referer,
                    	"X-Title: " . $title,
                    	"Content-Type: application/json",
                	],
                	"content" => $jsonData,
                	"timeout" => 300,
            	],
        	];
 
        	$context = stream_context_create($options);
 
        	$response = file_get_contents($apiUrlai, false, $context);
 
        	if ($response === false) {
            	$markdownText = "Error: Request failed or timed out.";
                InviaMessaggio($chatID, $markdownText);
        	} else {
            	$data = json_decode($response, true);
 
            	if (isset($data["choices"][0]["message"]["content"])) {
                	$markdownText = $data["choices"][0]["message"]["content"];
            	} else {
                	$markdownText = "Riprova,errore imprevisto.";
            	}
 
            	InviaMessaggio($chatID, $markdownText);
        	}
 
        	$message = "";
    	}
 
    	if (
        	isset($parole[0], $parole[1]) &&
        	$parole[0] === "Domanda" &&
        	$parole[1] === "dati"
    	) {
        	$urlai =
                "https://nicolaaliuni.altervista.org/irrigazione/apiai.php";
 
        	$ch = curl_init();
 
        	curl_setopt($ch, CURLOPT_URL, $urlai);
        	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
        	$response = curl_exec($ch);
 
        	if (curl_errno($ch)) {
            	echo "Errore cURL: " . curl_error($ch);
        	}
 
        	curl_close($ch);
 
        	$data = json_decode($response, true);
        	$stringa = json_encode($data, JSON_PRETTY_PRINT);
        	$messaggioai =
            	"sto eseguendo un progetto per irrigazione domotica puoi analizzare i seguenti parametri PID con i relativi risultati e e rispondermi alla domanda con questi valori.domanda->" .
            	$message .
            	" valori->: " .
            	$stringa;
        	InviaMessaggio($chatID, "Elaborazione Dati...");
 
        	$apiUrlai = "https://openrouter.ai/api/v1/chat/completions";
        	$title = "Nicola prova ai";
        	$input = $messaggioai;
        	$apiKeyai ="*******";
 
        	$userMessage = htmlspecialchars($_POST["user_message"]); dell'utente
 
        	$data = [
            	"model" => "qwen/qwen2.5-vl-72b-instruct:free",
            	"messages" => [
                	[
                    	"role" => "user",
                    	"content" => [
                        	[
                                "type" => "text",
                            	"text" => $messaggioai,
                        	],
    	                ],
                	],
            	],
        	];
 
        	$jsonData = json_encode($data);
 
 
        	$options = [
            	"http" => [
                	"method" => "POST",
                	"header" => [
  	                  "Authorization: Bearer " . $apiKeyai,
                    	"HTTP-Referer: " . $referer,
                    	"X-Title: " . $title,
                    	"Content-Type: application/json",
                	],
                	"content" => $jsonData,
                	"timeout" => 300,
            	],
        	];
 
        	$context = stream_context_create($options);
 
        	$response = file_get_contents($apiUrlai, false, $context);
 
        	if ($response === false) {
            	$markdownText = "Error: Request failed or timed out.";
            	InviaMessaggio($chatID, $markdownText);
        	} else {
            	$data = json_decode($response, true);
 
          	  if (isset($data["choices"][0]["message"]["content"])) {
                	$markdownText = $data["choices"][0]["message"]["content"];
            	} else {
                	$markdownText = "Riprova,errore imprevisto.";
            	}
 
            	InviaMessaggio($chatID, $markdownText);
        	}
 
        	$message = "";
    	}
    	break;
}
 
function PID($chatID)
{
	$keyboard = [
    	"inline_keyboard" => [],
	];
    array_push($keyboard["inline_keyboard"], [
    	["text" => "nuovo pid", "callback_data" => "nuovo_pid"],
	]);
    array_push($keyboard["inline_keyboard"], [
    	["text" => "Kp", "callback_data" => "Kp"],
    	["text" => "Ki", "callback_data" => "Ki"],
    	["text" => "Kd", "callback_data" => "Kd"],
	]);
    array_push($keyboard["inline_keyboard"], [
    	["text" => "set point", "callback_data" => "setpoint"],
	]);
    array_push($keyboard["inline_keyboard"], [
    	["text" => "conferma", "callback_data" => "confermapid"],
	]);
    array_push($keyboard["inline_keyboard"], [
    	["text" => "visualizza grafico", "callback_data" => "grafico"],
    ]);
 
 
    $encodedKeyboard = json_encode($keyboard);
 
    $parameters = [
    	"chat_id" => $chatID,
    	"text" => "Menù",
    	"reply_markup" => $encodedKeyboard,
    ];
 
	$query = http_build_query($parameters);
 
	$response = file_get_contents(
        "$GLOBALS[website]/sendMessage?" . http_build_query($parameters)
	);
}
 
function menu($chatID)
{
	$keyboard = [
    	"inline_keyboard" => [],
	];
    array_push($keyboard["inline_keyboard"], [
    	["text" => "On", "callback_data" => "On"],
    	["text" => "Off", "callback_data" => "Off"],
	]);
	array_push($keyboard["inline_keyboard"], [
    	["text" => "Leggi dati", "callback_data" => "Leggi"],
    ]);
    array_push($keyboard["inline_keyboard"], [
    	["text" => "programma", "callback_data" => "programma"],
    ]);
    array_push($keyboard["inline_keyboard"], [
    	["text" => "PID", "callback_data" => "PID"],
	]);
    array_push($keyboard["inline_keyboard"], [
    	["text" => "response time", "callback_data" => "responsetime"],
	]);
	array_push($keyboard["inline_keyboard"], [
    	["text" => "visualizza grafico umidità", "callback_data" => "umidita"],
    ]);
 
	$encodedKeyboard = json_encode($keyboard);
 
	$parameters = [
    	"chat_id" => $chatID,
    	"text" => "Menù",
    	"reply_markup" => $encodedKeyboard,
    ];
 
	$query = http_build_query($parameters);
 
    $response = file_get_contents(
        "$GLOBALS[website]/sendMessage?" . http_build_query($parameters)
    );
}
 ?>
