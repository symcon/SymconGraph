# WebGraph
Das Modul dient dazu, bereits vorhandene Diagramme via WebHook zur Verfügung zu stellen.
Der WebHook ist dann sowohl lokal als auch via Connect Service aufrufbar.
Auch stilistische Konfigurationen sind über Parameter in der URL möglich.

### Inhaltverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

* Erstellt einen eigenen Webhook
* Hinzufügen der erlaubten ObjektIDs für Diagramme via Liste
* Möglichkeit zur Sicherung durch Benutzername und Passwort
* Bereitstellung via WebHook sowohl lokal oder via Connect Service
* Konfiguration der Darstellung via URL Parameter

### 2. Voraussetzungen

- IP-Symcon ab Version 4.3
- Aktive Subskription bei Nutzung des Connect Services

### 3. Software-Installation

Über das Modul-Control folgende URL hinzufügen.  
`git://github.com/symcon/SymconGraph.git`  

### 4. Einrichten der Instanzen in IP-Symcon

- Unter "Instanz hinzufügen" ist das 'Web Graph'-Modul unter dem Hersteller '(Kern)' aufgeführt.  

__Konfigurationsseite__:

Name                            | Beschreibung
------------------------------- | ---------------------------------
Access List                     | Die Liste beinhaltet die ObjektIDs von Diagrammmedien, welche via WebHook bereitgestellt werden dürfen
Benutzername/Passwort (Experte) | Begrenzt den Zugang mithilfe eines Benutzernamens und Passwort
Testumgebung                    | Hier können verschiedene Einstellung ausprobiert werden und die passende URL ausgelesen werden

Nach Eintragung in die "Access List" stehen die Graphen mit ihrer ID zur Verfügung.

#### Beispiel

Diagrammmedia mit der ID 12345 wird in die "Access List" eingetragen.
Daraufhin ist diese sichtbar unter:

Lokal:  
http://127.0.0.1:3777/hook/webgraph/?id=12345

Symcon Connect Service:
https://xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx.ipmagic.de/hook/webgraph/?id=12345

Weitere Einstellungen können an die URL angehangen werden.  
/hook/webgraph/?id=12345&startTime=&timeSpan=3&isHighDensity=1&isExtrema=&isDynamic=1&isContinuous=&width=0&height=0&showTitle=&showLegend=

### 5. Statusvariablen und Profile

Es werden keine zusätzlichen Statusvariablen oder Profile erstellt.
Es wird lediglich der WebHook "/hook/webgraph/" eingetragen

### 6. WebFront

Über das WebFront ist keine weitere Konfiguration ode Anzeige möglich.

### 7. PHP-Befehlsreferenz

Es stehen keine weiteren Befehle zur Verfügung. 