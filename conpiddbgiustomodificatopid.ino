
#define DIGITAL_PIN 15
#define ANALOG_PIN 33

#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <NTPClient.h>   // Libreria per ottenere l'ora da un server NTP
#include <WiFiUdp.h>     // Libreria UDP per NTPClient
#include <TimeLib.h>     // Libreria per la gestione del tempo
#include <PID_v1_bc.h>


const long utcOffsetInSeconds = 3600;    // Offset dell'ora rispetto all'UTC (in questo caso +1 per l'ora italiana)
WiFiUDP ntpUDP;
NTPClient timeClient(ntpUDP, "pool.ntp.org", utcOffsetInSeconds);


// Imposta le tue credenziali WiFi
const char* password = " ";
const char* ssid = " ";
const char* jsonUrl = "dominio/irrigazione/apistato.php";
const char* jsonUrlPID = "dominio/irrigazione/apipid.php";


// Variabili per il controllo PID
double setpointin = 50.0;  // Umidità target in percentuale (40%)
double input = 0.0;      // Umidità letta dal sensore
double output = 0.0;     // Uscita del PID per il controllo del motore

// Parametri PID
double Kp = 7.0;  // Guadagno Proporzionale
double Ki = 3.0;  // Guadagno Integrale
double Kd = 0.0;  // Guadagno Derivativo

PID myPID(&input, &output, &setpointin, Kp, Ki, Kd, DIRECT);

// Stati possibili nel JSON (da 0 a 3)
const int STATE_0 = 0; //off
const int STATE_1 = 1; // inserisco dati
const int STATE_2 = 2; // irriga
const int STATE_3 = 3; //eventuali sviluppi futuri (notifica)
int sem=0;
// Tempo di attesa tra le richieste (in millisecondi)
int interval = 1 * 15 * 1000; // 15 secondi
int prminuti=40;
int h;
int stato,timer,impostatoorainizio,responsetime;
double setpoint;
int counter=0;
int st=0;
void setup() {
  Serial.begin(115200);
pinMode(16, OUTPUT);
pinMode(17, OUTPUT);
 digitalWrite(17, HIGH);
  analogReadResolution(25);
  // Connettiti alla rete WiFi
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(1000);
    Serial.println("Connessione WiFi in corso...");
  }
  Serial.println("Connesso alla rete WiFi!");

  // Avvia il timer per le richieste periodiche
  //setIntervalTimer(interval);
   timeClient.setUpdateInterval(1800000);
  pinMode(DIGITAL_PIN, INPUT);
  analogReadResolution(12);
  myPID.SetMode(AUTOMATIC);    // Impostazione del PID in modalità automatica
  myPID.SetOutputLimits(0, 255); // Limita l'uscita del PID tra 0 e 255 (per il controllo PWM)
  
}

void loop() {
 unsigned long currentMillis = millis();
 static unsigned long previousMillis = 0;
  // Verifica se è passato abbastanza tempo dall'ultima richiesta
  if (currentMillis - previousMillis >= interval) {
      digitalWrite(DIGITAL_PIN, HIGH);
      int a = analogRead(ANALOG_PIN);
      Serial.println(a);
      h=map(a,1500,4095,100,0);
      h=constrain(h,0,100);
      Serial.println(String(h)+"%"); //percentuale umidità terreno
      digitalWrite(DIGITAL_PIN, LOW);
      input = h;  // Imposta l'umidità letta come input per il PID
      myPID.Compute();  // Calcola la nuova uscita del PID
      Serial.println(String(output));
      readAndParseJson();
      counter++;
     /** if (counter % 60 == 0) {
      caricaPID();
      caricaDati();
      }*/
     
      if(stato==0){
        if (/*output >= 100 && output <= 128*/ output > 128) {
          //accendo motore
          Serial.println("debug"+String(setpointin)+"%");
          if(h<setpointin){
            //accendo motore
          digitalWrite(16, HIGH);
          digitalWrite(17, LOW);
          st=1;
          }else{
             digitalWrite(16, LOW); //INVERTIRE
            digitalWrite(17, HIGH);
            st=0;
          }
        }else{
          digitalWrite(16, LOW); //INVERTIRE
          digitalWrite(17, HIGH);
          st=0;
        }
      
      }
      caricaPID();
      caricaDati();
    previousMillis = currentMillis;
  }
  
 timeClient.update();

  int currentHour = timeClient.getHours();
  int currentMinute = timeClient.getMinutes();
  int currentSecond = timeClient.getSeconds();
  currentHour=currentHour+1;
  if (currentHour == impostatoorainizio && currentMinute == prminuti && currentSecond  == 0) {
          Serial.println("avvio programmato");
          digitalWrite(16, HIGH);
          digitalWrite(17, LOW);
          sleep(timer* 1000);
          digitalWrite(16, LOW);
          digitalWrite(17, HIGH);
  }



  
}


void readAndParseJson() {
  // Crea una connessione HTTP
  HTTPClient http;

  // Esegue la richiesta GET per ottenere il JSON
  http.begin(jsonUrl);

  int httpResponseCode = http.GET();

  // Verifica la risposta del server
  if (httpResponseCode > 0) {
    //Serial.print("Risposta del server: ");
    //Serial.println(httpResponseCode);

    // Leggi la risposta del server come stringa JSON
    String jsonResponse = http.getString();

    // Crea un oggetto JSONDocument per analizzare il JSON
    DynamicJsonDocument jsonDocument(1024);

    // Analizza il JSON
    DeserializationError error = deserializeJson(jsonDocument, jsonResponse);

    // Verifica se ci sono errori durante l'analisi del JSON
    if (error) {
      Serial.print("Errore durante l'analisi del JSON: ");
      Serial.println(error.c_str());
    } else {
      // Leggi il valore dello stato dal JSON
      stato = jsonDocument["stato"];
      timer = jsonDocument["timer"];
      impostatoorainizio = jsonDocument["impostatoorainizio"];
      setpoint = jsonDocument["setpoint"];
      responsetime=jsonDocument["responsetime"];
      String messaggio = "stato: " + String(jsonDocument["stato"].as<double>()) +
                   " timer: " + String(jsonDocument["timer"].as<double>()) +
                   " impostatoorainizio: " + String(jsonDocument["impostatoorainizio"].as<double>()) +
                   " setpoint: " + String(jsonDocument["setpoint"].as<double>()) +
                   " responsetime: " + String(jsonDocument["responsetime"].as<double>());
      Serial.println(messaggio);
        http.end();
        interval = 1 * responsetime * 1000;
      switch (stato) {
        case STATE_0:
        //Serial.println("stato 0");
        //spengo motore
        //digitalWrite(16, LOW);
        //digitalWrite(17, HIGH);
          break;

        case STATE_1:
            //Serial.println("stato 1");
            caricaDati();
          break;

        case STATE_2:
            //Serial.println("stato 2");
            //accendo motore
            digitalWrite(16, HIGH);
            digitalWrite(17, LOW);
          break;

        case STATE_3:
        Serial.print("stato 3");
            leggopid();
            break;

        default:
          Serial.println("Stato non valido");
          break;
      }
    }
  } else {
    Serial.print("Errore durante la connessione al server: ");
    Serial.println(httpResponseCode);
  }
}

void caricaDati() {
  // Valore da passare nella richiesta
  int valore = h;
  
  // Creazione dell'oggetto HttpClient
  HTTPClient http;

  // URL della richiesta
  String url = "dominio/irrigazione/inseriscidati.php";

  // Esecuzione della richiesta POST
  http.begin(url);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  // Creazione del payload con il valore come parametro
  String payload = "valore=" + String(valore)+ "&stato=" + String(st);

  // Invio del payload
  int httpCode = http.POST(payload);
  Serial.printf("Codice HTTP: %d\n", httpCode);

  // Leggere la risposta
  String response = http.getString();
  Serial.println(response);

  // Fine della richiesta
  http.end();
}

void caricaPID() {
  // Valore da passare nella richiesta
  int valore = output;
  
  // Creazione dell'oggetto HttpClient
  HTTPClient http;

  // URL della richiesta
  String urlpid = "dominio/irrigazione/Inseriscovaloripid.php";

  // Esecuzione della richiesta POST
  http.begin(urlpid);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  // Creazione del payload con il valore come parametro
  String payload = "valore=" + String(valore);

  // Invio del payload
  int httpCode = http.POST(payload);
  Serial.printf("Codice HTTP: %d\n", httpCode);

  // Leggere la risposta
  String response = http.getString();
  Serial.println(response);

  // Fine della richiesta
  http.end();
}

void leggopid() {
HTTPClient http;
            http.begin(jsonUrlPID);
            int httpResponseCode = http.GET();
            if (httpResponseCode > 0) {
              String jsonResponse = http.getString();
              DynamicJsonDocument jsonDocument(1024);
              DeserializationError error = deserializeJson(jsonDocument, jsonResponse);
              if (error) {
                Serial.print("Errore durante l'analisi del JSON: ");
                Serial.println(error.c_str());
              } else {
                // Leggi il valore dello stato dal JSON
                Kp = jsonDocument["Kp"];
                Ki = jsonDocument["Ki"];
                Kd = jsonDocument["Kd"];
                setpointin = jsonDocument["setpoint"];
                String messaggio = "Kp: " + String(jsonDocument["Kp"].as<double>()) +
                             " Ki: " + String(jsonDocument["Ki"].as<double>()) +
                             " Kd: " + String(jsonDocument["Kd"].as<double>()) +
                             " setpoint: " + String(jsonDocument["setpoint"].as<double>());
                Serial.println(messaggio);
                myPID.SetTunings(Kp, Ki, Kd);

               }
              }else {
              Serial.print("Errore durante la connessione al server: ");
              Serial.println(httpResponseCode);
            }
            // Chiudi la connessione HTTP
            http.end();
          

}
