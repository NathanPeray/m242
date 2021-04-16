# m242 LB2
## Projekt

Unser Ziel war es ein kleines Tool zur Zeiterfassung mittels NFC Chips / Karten zu realisieren. Das Tool verwendet im Grunde nur einen Sensor, den RFID Reader. Dazu kommt noch ein Aktor, das Wifi Interface.
Wird ein NFC Tag an das Gerät gehalten, wird die UID mittels HTTP an den Server übermittelt.

---
## Komponenten

Das Programm besteht im Wesentlichen aus den folgenen Komponenten.

#### RFID Reader

Ein RFID Reader ist in der Lage NFC Tags zu lesen. Obwohl der Name "Reader" sagt, wäre es theoretisch auch möglich NFC Tags zu schreiben. Das heisst man kann seine eigene Daten auf den Tag schreiben.
So könnte man z.B. die Zugangsdaten zum eigenen Wifi auf einem Tag speichern, hät man das Tag nun an sein Smartphone - welches NFC aktiviert hat - wird man direkt mit dem Netzwerk verbunden, wenn man die aktion bestätigt. Damit das Gerät auch eine neue Karte erkennen kann, wird die Suche nach einer Karte in einer endlosen Schleife ausgeführt:

```cpp
while (1) {
    if (rfidReader.PICC_IsNewCardPresent()) {
        if (rfidReader.PICC_ReadCardSerial()) {
                // Code here
        }
    }
    thread_sleep_for(200);
}

```

Dabei wird die ausführung nach jedem Durchlauf für 200 Millisekunden pausiert. Wird eine neuer NFC Tag gefunden, wird die UID des TAGS ausgelesen:

```cpp
int uid[rfidReader.uid.size];
for ( int i = 0; i < rfidReader.uid.size; i++ ) {
    uid[i] = rfidReader.uid.uidByte[i];
}
```

Diese UID wird dann an der URL des Servers angehängt und dan den Server übermittelt.

```cpp
char buffer[128];
int size = sprintf(buffer, "http://m242.n-peray.ch/api?uid=%02X:%02X:%02X:%02X", uid[0], uid[1], uid[2], uid[3]);
```
Diese URL wird dann später für die HTTP anfrage verwendet.

#### WifiInterface

Das WifiInterface ist ein kleines Modul direkt auf dem IOTKit, dieses ermöglicht es eine Verbindung mit einem Drahtlosem Netzwerk herzustellen. Folgender Code stellt eine Wifi Verbindung her:
```cpp
bool connectWifi() {
    WifiInterface wifi = WiFiInterface::get_default_instance();
    if (!wifi) {
        return false;
    }
    int status = wifi->connect("unicorn-island", "1234abcd", NSAPI_SECURITY_WPA_WPA2);
    if (status != 0) {
        return false;
    }
    return true;
}
```
Die Wifi Verbindung ist für unsere Applikation unerlässlich. In diesem Fall ist das WifiInterface global deklariert, das heisst ausserhalb jeder Funktion, so können wir von allen Funktionen aus darauf zugreifen.

```cpp
WiFiInterface* wifi;

int main() {
    //
}
```

#### Http Request

Der HTTP Request überträgt die Daten an den Server, und zeigt die Antwort auf dem Oled Display an. Für diese Aktion haben wir wieder eine Funktion geschrieben.

```cpp
bool storeData(char url[]) {
    // Code here...
}
```
Die URL inklusive Daten erhalten wir als Parameter beim Aufruf der Funktion. Mittels HttpRequest können wir den die HTTP Anfrage starten. Dafür geben wir dem Konstruktor unser WifiInterface, und die gewünschte Übertragungsart sowie die URL des empfängers an.

Mit `request->send()` wird die Anfrage ausgeführt, die Antwort des Servers wird sogleich als `HttpResponse` gespeichert.

```cpp
    HttpRequest* request = new HttpRequest(wifi, HTTP_GET, url);
    HttpResponse* result = request->send();
```
Unser server ist so konfiguriert, dass er uns ein JSON String sendet, welches nur ein Attribut enthält: `message`. Folgende Zeilen parsen das JSON Object in einen cpp string.

```cpp
MbedJSONValue parser;
parse(parser, result->get_body_as_string().c_str())
std::string answer;
answer = parser["message"].get<std::string>();
```
Diesen String können wir so weiterverarbeiten und auf dem Oled Display ausgeben.

---
#### PHP Server

Um die Daten auf dem Server zu verarbeiten, haben wir eine kleine API in PHP geschrieben.
Die Api sucht als erstes nach der UID in der Datenbank. Wenn diese UID noch nicht in der Datenbank vorhanden ist, wird ein Neuer Eintrag erstellt:

```php
<?php
$card = Card::where([["uid", $_GET['uid']]]);
if (sizeof($card) < 1) {
    $card = new Card(['uid' => $_GET['uid'], 'user_FK' => NULL]);
} else {
    $card = $card[0];
}

```
Mit dem Karten Objekt aus der Datenbank, sucht die API als nächstes nach einer Zeiterfassung für diese Karte, die nur über einen Starzeitpunkt verfügt. Sollte keine Zeiterfassung mit einem leeren Endzeitpunkt gefunden werden, wird eine neue Zeiterfassung erfasst.

```php
<?php
$stamp = Stamp::where([["endtime", null], ["card_FK", $card->id]]);
$now = new DateTime();
if (sizeof($stamp) < 1) {
    $starttime = $now->format('Y-m-d H:i:s');
    $endtime = null;
    echo json_encode([
        "message" => "Welcome \nback"
    ]);
} else {
    $starttime = $stamp[0]->starttime;
    $stamp[0]->delete();
    $endtime =  $now->format('Y-m-d H:i:s');
    echo json_encode([
        'message' => "Bye"
    ]);
}
new Stamp(['starttime' => $starttime, 'endtime' => $endtime, 'card_FK' => $card->id]);
```

In jedem fall wird eine Neue Zeiterfassung in der Datenbank eingetragen, Die bereits ohne Endzeitpunkt erfasste Zeiterfassung, wird aus der Datenbank gelöscht.

---
## Installation

Das Tool besteht im Wesentlichen aus zwei Teilen:

- Das Programm für das IoTKitV3
- Die PHP API für für den Server

Für das IoTKitV3 benötigen wir noch MbedStudio, dieses können wir [hier](https://os.mbed.com/studio/) herunterladen. Ist die Installation abgeschlossen müssen wir uns nun Anmelden oder für den Service registrieren. Im MbedStudio angekommen, können wir das Programm für den Kit direkt importieren. Dafür klicken wir oben links in der Toolbar auf `File` und dann auf `Import Programm...`. Dort könen wir nun einfach die URL dieses Repositories (`https://github.com/NathanPeray/M122_peray`) einfügen. Und den korrekten Branch (`master`) auswählen. Das importieren kann einige Momente dauern.

Die PHP API befindet sich im API branch des selben Repositories. Die API können wir einfach in unserem HTDOCS Ordner von XAMPP abspeichern. Wenn der Ordnername des Projekts beibehalten wurde, könenen wir die `conf.json` Datei ignorieren. Ansonsten müssen wir die base_url so ändern dass statt `localhost/m242backend/public`, `localhost/neuerName/public` steht.
```json
{
    "base_url" : "localhost/m242backend/public",
    "protocol" : "http",
    "db" : {
        "host" : "localhost",
        "user" : "root",
        "pw" : "",
        "db" : "m242_backend"
    },
    "pepper" : "@pepper@"
}
```
Wenn die Standard Daten der Datenbank verändert wurden, müssen die `host` `user` und `pw` Variablen im `conf.json` anpassen.
Als letztes müssen wir noch die Datenbank erstellen, dazu suchen wir in der API nach dem `db` Ordner, darin finden wir zwei Dateien, 'db_create.bat' funktioniert nur wenn die `mysql.exe` den Systemumgebungsvariablen hinzugefügt wurde. Ansonsten muss das 'ddl.sql' script manuel in der Datenbank importiert werden.
