/** Beispiel Abfrage Cloud Dienst Sunrise / Sunset
 */
#include "mbed.h"
#include <string>
#include "OLEDDisplay.h"
#include "http_request.h"
#include "MbedJSONValue.h"
#include "MFRC522.h"


char message[6000];

OLEDDisplay oled( MBED_CONF_IOTKIT_OLED_RST, MBED_CONF_IOTKIT_OLED_SDA, MBED_CONF_IOTKIT_OLED_SCL );
MFRC522    rfidReader( MBED_CONF_IOTKIT_RFID_MOSI, MBED_CONF_IOTKIT_RFID_MISO, MBED_CONF_IOTKIT_RFID_SCLK, MBED_CONF_IOTKIT_RFID_SS, MBED_CONF_IOTKIT_RFID_RST ); 




WiFiInterface* wifi;

bool connectWifi() {
    wifi = WiFiInterface::get_default_instance();
    if (!wifi) {
        return false;
    }
    int status = wifi->connect("unicorn-island", "1234abcd", NSAPI_SECURITY_WPA_WPA2);
    if (status != 0) {
        return false;
    }
    return true;
}
bool storeData(char url[]) {

    HttpRequest* request = new HttpRequest(wifi, HTTP_GET, url);
    HttpResponse* result = request->send();

    if(result) {
        oled.clear();
        oled.cursor( 1, 0 );   
        MbedJSONValue parser;
        parse(parser, result->get_body_as_string().c_str());
        std::string answer;
        answer = parser["message"].get<std::string>();
        oled.printf("%s", answer.c_str());
        delete result;
        return true;
    } else {
        printf("Result fucked up \n");
        return false;
    }
}
int main()
{   
    if (!connectWifi()) {
        printf("Could not connect to WIFI, system shutting down");
        return -1;
    }
    printf("---------------------------------------------------\n");
    printf("---------------| Connected to Wifi |---------------\n");
    printf("---------------------------------------------------\n");
    
           
    printf("RFID Reader MFRC522 Test V3\n");
    rfidReader.PCD_Init();

    printf("----------------| PCD Initialized |----------------\n");
    printf("---------------------------------------------------\n");
    while   ( 1 ) 
    {
        oled.clear();
        oled.cursor(1, 0);
        oled.printf("Ready");
        // RFID Reader
        if ( rfidReader.PICC_IsNewCardPresent()) {
            if ( rfidReader.PICC_ReadCardSerial()) 
            {
                int uid[rfidReader.uid.size];
                for ( int i = 0; i < rfidReader.uid.size; i++ ) {
                    uid[i] = rfidReader.uid.uidByte[i];
                }

                char buffer[128];
                int size = sprintf(buffer, "http://m242.n-peray.ch/api?uid=%02X:%02X:%02X:%02X", uid[0], uid[1], uid[2], uid[3]);
                if (storeData(buffer)) {
                    thread_sleep_for(5000);
                }
            }
        }
        thread_sleep_for( 200 );
    }
}
