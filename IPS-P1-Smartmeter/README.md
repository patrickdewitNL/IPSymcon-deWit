# IPS-PI-Smartmeter
A sensor platform for Dutch Smart Meters which comply to DSMR (Dutch Smart Meter Requirements), also known as 'Slimme meter' or 'P1 poort'.

- Currently support DSMR V4
- For official information about DSMR refer to: [DSMR Document](https://www.netbeheernederland.nl/dossiers/slimme-meter-15)
- For official information about the P1 port refer to: <https://www.wijhebbenzon.nl/media/kunena/attachments/3055/DSMRv5.0FinalP1.pdf>
- For unofficial hardware connection examples refer to: [Domoticx](http://domoticx.com/p1-poort-slimme-meter-hardware/)

### Contents

1. [Functional scope](#1-functionalscope)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Functional scope
* Auswählbares Quellbild und auswählbare Auslöservariable
* Einstellbare Anzahl von Bildern, welche gespeichert werden sollen
* Erstellt ein Ereignis, welches auf Variablenaktualisierung der Auslöservariable reagiert
* Das Ereignis kann manuell auf persönliche Bedürfnisse angepasst werden und wird nicht überschrieben
* Bilder kriegen den Zeitpunkt des Auslösens als Namen
* Bilder werden chronologisch von alt -> neu in der Kategorie Bilder angezeigt
* Automatisches Löschen der ältesten Bilder, beim Erreichen der eingestellten maximalen Bilderanzahl

### 2. Requirements

- IP-Symcon from Version 5.0

### 3. Software-Installation

Über das Modul-Control folgende URL hinzufügen.  
`git://github.com/symcon/SymconMisc.git`  

### 4. Setup in IP-Symcon

- Unter "Instanz hinzufügen" ist das 'Bildarchiv'-Modul unter dem Hersteller '(Sonstige)' aufgeführt.  

__Konfigurationsseite__:

Name            | Beschreibung
--------------- | ---------------------------------
Bild            | Quellbild, welches kopiert werden soll
Anzahl          | Maximale Anzahl an Bildern, die gespeichert werden sollen
Auslösevariable | Variable bei dessen Änderung das Quellbild ins Archiv kopiert werden soll 

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

##### Statusvariablen

Name     | Typ       | Beschreibung
-------- | --------- | ----------------
Bilder   | Kategorie | Hier werden die Quellbilder hineinkopiert
AddImage | Ereignis  | Wird durch die Auslösevariable ausgelöst und stößt das Kopieren des Quellbildes an

##### Profile:

Es werden keine zusätzlichen Profile hinzugefügt

### 6. WebFront

Über das WebFront wird die Variable angezeigt. Es ist keine weitere Steuerung oder gesonderte Darstellung integriert.

Zur Darstellung der Bilder in der Kategorie "Bilderarchiv" kann ein Inhaltsteiler im WebFront hinzugefügt werden oder ein Link der Kategorie gemacht werden.  
Achtung: Es ist nicht nützlich direkt die Bilder aus dem Archiv zu verlinken, da sich durch die Aktualisierungen die ID's ändern.


### 7. PHP-Befehlsreferenz

`boolean BA_AddImage(integer $InstanzID);`  
Kopiert das aktuelle Quellbild in die Kategorie "Bilder" des Moduls "BildArchiv" mit der InstanzID $InstanzID.
Die Funktion liefert keinerlei Rückgabewert.  
Beispiel:  
`BA_AddImage(12345);`
