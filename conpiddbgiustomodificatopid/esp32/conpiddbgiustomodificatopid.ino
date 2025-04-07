#define DIGITAL_PIN 15
#define ANALOG_PIN 33
#define mA 4
#define mB 2
#define flusso 17

int pwmChannel = 0; 
int pwmFreq = 5000;  
int pwmResolution = 8;  


#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <NTPClient.h>   
#include <WiFiUdp.h>    
#include <TimeLib.h>     
#include <PID_v1_bc.h>
#include <PubSubClient.h>


const long utcOffsetInSeconds = 3600;    
WiFiUDP ntpUDP;
NTPClient timeClient(ntpUDP, "pool.ntp.org", utcOffsetInSeconds);

const char* ssid = "***";
const char* password = "***";

const char* mqtt_server = "***";
const int mqtt_port = 1883; 
const char* mqtt_user = "***";    
const char* mqtt_pass = "***"; 
const char* mqtt_topic_pub = "esp32/valori";     
const char* mqtt_topic_sub = "esp32/valori";
WiFiClient espClient;
PubSubClient client(espClient);


const char* jsonUrl = "http://nicolaaliuni.altervista.org/irrigazione/apistato.php";
const char* jsonUrlPID = "http://nicolaaliuni.altervista.org/irrigazione/apipid.php";

double setpointin = 50.0;
double input = 0.0;
double output = 0.0;   
double Kp = 7.0; 
double Ki = 3.0;
double Kd = 0.0;

PID myPID(&input, &output, &setpointin, Kp, Ki, Kd, DIRECT);
const int STATE_0 = 0;
const int STATE_1 = 1;
const int STATE_2 = 2;
const int STATE_3 = 3;
int sem = 0;
int interval = 1 * 15 * 1000;
int prminuti = 40;
int h;
int stato, timer, impostatoorainizio, responsetime;
double setpoint;
int counter = 0;
int st = 0;

void callback(char* topic, byte* payload, unsigned int length) {
  Serial.print("Messaggio ricevuto su topic: ");
  Serial.print(topic);
  Serial.print(" con payload: ");
  for (unsigned int i = 0; i < length; i++) {
    Serial.print((char)payload[i]);
  }
  Serial.println();
}

void setup() {
  Serial.begin(115200);
  pinMode(16, OUTPUT);
  pinMode(flusso, OUTPUT);
  pinMode(mA, OUTPUT); 
  pinMode(mB, OUTPUT); 
  analogReadResolution(25);
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(1000);
    Serial.println("Connessione WiFi in corso...");
  }
  Serial.println("Connesso alla rete WiFi!");
  timeClient.setUpdateInterval(1800000);
  pinMode(DIGITAL_PIN, INPUT);
  analogReadResolution(12);
  myPID.SetMode(AUTOMATIC);
  myPID.SetOutputLimits(0, 255); 
  ledcSetup(pwmChannel, pwmFreq, pwmResolution);
  ledcAttachPin(flusso, pwmChannel);
  digitalWrite(mA, LOW);
  digitalWrite(mB, HIGH);
  client.setServer(mqtt_server, mqtt_port);
  client.setCallback(callback);

}
void reconnect() {
  while (!client.connected()) {
    Serial.print("Connessione al broker MQTT...");
    if (client.connect("ESP32Client", mqtt_user, mqtt_pass)) {
      Serial.println("Connesso!");
      client.subscribe(mqtt_topic_sub);
    } else {
      Serial.print("Fallito con codice di errore: ");
      Serial.print(client.state());
      Serial.println(". Riprovo tra 5 secondi...");
      delay(5000);
    }
  }
}

void setup_wifi() {
  delay(10);
  Serial.println();
  Serial.print("Connessione alla rete Wi-Fi: ");
  Serial.println(ssid);
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println(" Connesso!");
  Serial.print("Indirizzo IP: ");
  Serial.println(WiFi.localIP());
}

void loop() {

  if (!client.connected()) {
    reconnect();
  }
  client.loop();

  unsigned long currentMillis = millis();
  static unsigned long previousMillis = 0;
  if (currentMillis - previousMillis >= interval) {
    digitalWrite(DIGITAL_PIN, HIGH);
    int a = analogRead(ANALOG_PIN);
    h = map(a, 1500, 4095, 100, 0);
    h = constrain(h, 0, 100); 
    Serial.println(String(h) + "%");
    digitalWrite(DIGITAL_PIN, LOW);
    input = h;  
    myPID.Compute();
    Serial.println(String(output)); 
    readAndParseJson();
    counter++;
    if (counter % 10 == 0) {
      Serial.println("carico valori");
      caricaPID();
      caricaDati();
      StaticJsonDocument<200> doc;
      doc["umidita"] = h;
      doc["pid"] = output;
      char jsonBuffer[512];
      serializeJson(doc, jsonBuffer);
      client.publish(mqtt_topic_pub, jsonBuffer);
    }
    if (stato == 0) {
      ledcWrite(pwmChannel, output);
    }
    previousMillis = currentMillis;
  }


}

void readAndParseJson() {
  HTTPClient http;
  http.begin(jsonUrl);

  int httpResponseCode = http.GET();
  if (httpResponseCode > 0) {
    String jsonResponse = http.getString();

    DynamicJsonDocument jsonDocument(1024);

    DeserializationError error = deserializeJson(jsonDocument, jsonResponse);

    if (error) {
      Serial.print("Errore durante l'analisi del JSON: ");
      Serial.println(error.c_str());
    } else {
      stato = jsonDocument["stato"];
      timer = jsonDocument["timer"];
      impostatoorainizio = jsonDocument["impostatoorainizio"];
      setpoint = jsonDocument["setpoint"];
      responsetime = jsonDocument["responsetime"];
      String messaggio = "stato: " + String(jsonDocument["stato"].as<double>()) +
                         " timer: " + String(jsonDocument["timer"].as<double>()) +
                         " impostatoorainizio: " + String(jsonDocument["impostatoorainizio"].as<double>()) +
                         " setpoint: " + String(jsonDocument["setpoint"].as<double>()) +
                         " responsetime: " + String(jsonDocument["responsetime"].as<double>());
      Serial.println(messaggio);
      http.end();
      interval = 1 * responsetime * 1000;
      switch (stato) {

        case STATE_1:
          caricaDati();
          break;

        case STATE_2:
          digitalWrite(16, HIGH);
          digitalWrite(17, LOW);
          break;

        case STATE_3:
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
  int valore = h;
  HTTPClient http;
  String url = "http://nicolaaliuni.altervista.org/irrigazione/inseriscidati.php";
  http.begin(url);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  String payload = "valore=" + String(valore) + "&stato=" + String(st);
  int httpCode = http.POST(payload);
  Serial.printf("Codice HTTP: %d\n", httpCode);
  String response = http.getString();
  Serial.println(response);
  http.end();
}

void caricaPID() {
  int valore = output;
  HTTPClient http;
  String urlpid = "http://nicolaaliuni.altervista.org/irrigazione/Inseriscovaloripid.php";
  http.begin(urlpid);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  String payload = "valore=" + String(valore);
  int httpCode = http.POST(payload);
  Serial.printf("Codice HTTP: %d\n", httpCode);
  String response = http.getString();
  Serial.println(response);
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
  } else {
    Serial.print("Errore durante la connessione al server: ");
    Serial.println(httpResponseCode);
  }
  http.end();


}
