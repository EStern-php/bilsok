# Eriks BilSök

Ett PHP-projekt som scrapar bilannonser och låter användaren söka efter dem via AJAX.

## Funktioner
- Hämtar bilannonser från bilweb.se och sparar i MySQL
- Sök efter märke, årsmodell eller regnummer via AJAX
- Enkelt Bootstrap-gränssnitt

## Krav
- PHP 8.1+
- MySQL 5.7+ eller MariaDB 10+
- cURL och DOM-extension aktiverade

## Installation
1. Klona repot
2. Skapa en databas enligt uppgifterna i databaseModel.php
(eller ändra koden så att den läser från en .env-fil i stället)
3. Importera `schema.sql` i din databas
4. Starta webbservern och öppna sidan



## Saker man hade kunnat göra annorlunda
1. Just nu kan man hämta annonser genom en knapp. I vanliga fall hade jag lagt det i ett cron job istället.
2. Har gjort det väldigt simpelt med MVC-upplägget just nu. Hade man byggt vidare på det här så hade jag troligtvis ändrat om i strukturen lite. Kanske lite mer hjälpfunktioner i databaseModel t.ex. 