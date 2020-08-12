#include <GSMSimTime.h>
#include <GSMSimHTTP.h>
#include <SoftwareSerial.h>
#include <EEPROM.h>
#include "LowPower.h"
#define RESET_PIN 6 
#define ON 0
#define OFF 1 

SoftwareSerial GPRSserial(7, 8);
GSMSimHTTP gprs(GPRSserial, RESET_PIN);
int relayState = 0;
int ciclosSleep = 0;
int currentCiclo = 0;
boolean connectionFail=false;

const int openPin = 2;
const int closePin = 3;

void setup() {  
  Serial.begin(115200);
  initGprs();
  pinMode(openPin, OUTPUT);
  pinMode(closePin, OUTPUT);
  digitalWrite(openPin, OFF);
  digitalWrite(closePin, OFF);
  relayState=EEPROM.read(0);
  Serial.print("Estado relay leído: ");
  Serial.println(relayState);
}

void loop() {
  if(currentCiclo<ciclosSleep){
    currentCiclo++;
    LowPower.powerDown(SLEEP_8S, ADC_OFF, BOD_OFF);
    return;
  }
  if (connectionFail) initGprs();
  String s = syncServerData();
  int newState = s.charAt(0) - '0';
  ciclosSleep = s.substring(2).toInt();
  Serial.print("Nuevos datos: ");
  Serial.println(s);
  Serial.print("Estado actual:");
  Serial.println(relayState);
  if(newState!=relayState) {
    changeState();
    return;
  }
  currentCiclo=0;
  Serial.println("A dormir!");
  delay(100);
}

void initGprs(){
  GPRSserial.begin(57600); //init serial comm with the device
  while(!GPRSserial) {
      ; // wait for module to connect.
  }
  gprs.reset();
  gprs.init();
  //gprs.setPhoneFunc(1)
  Serial.println(gprs.setPhoneFunc(1));
  if(!gprs.isConnected()){
    //gprs.connect();
    Serial.println(gprs.connect()); 
  }else{
    connectionFail=false;
  }
}

String syncServerData(){
  String response= gprs.post("example.com/blabla.php", String("voltage=")+getVoltage()+"&state="+relayState+"&temp="+getTemp(), "application/x-www-form-urlencoded", true).substring(21);
  Serial.println(String("v=")+getVoltage()+"&e="+relayState+"&t="+getTemp());
  //Serial.print("Datos descargados: ");
  //Serial.println(response);
  if(response.substring(0,3).equals("200")){
    response=response.substring(18);
    return response;
  }else{
    Serial.print("ConnErr:");
    Serial.println(response);
    connectionFail=true;
    return "0|10"; //sin conexion, apagar grifo y dormir 1min
  }
}

void changeState(){
  if(relayState==0){//if tap is closed, we open it
    Serial.println("Opening...");
    digitalWrite(openPin, ON);
    delay(10000);
    digitalWrite(openPin, OFF);    
    relayState=1;
  }else{ //tap open, we close it
    Serial.println("Closing...");
    digitalWrite(closePin, ON);
    delay(10000);
    digitalWrite(closePin, OFF);    
    relayState=0;
  }
  EEPROM.write(0, relayState); //write to EEPROM address 0 the new state
}

float getVoltage(){
  int value=analogRead(A0);
  for(int i=0;i<9;i++){
    value+=analogRead(A0);
  }
  value/=10;
//  Serial.print("Divisor voltaje: ");
//  Serial.println(lectura*5.0/1023.0);
//  Serial.print("Batería: ");
//  Serial.println((lectura*5.0/1023.0)*0.3269550749);
    return (value*5.0/1023.0)/0.3269550749;
}

float getTemp(){
  int value = analogRead(A1);
  float millivolts = (value / 1023.0) * 5000;
  float celsius = millivolts / 10; 
  return celsius;
}
