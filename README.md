# Boxing World

Boxing World is een webapplicatie over kickboksen en MMA, met de focus op Nederlandse vechters. Bezoekers kunnen informatie over vechters bekijken en het laatste nieuws volgen, zoals aankomende gevechten en recente uitslagen. Admins kunnen via de website zelf vechters en nieuwsberichten toevoegen, bewerken en verwijderen.

Het project is bedoeld voor fans van vechtsport die op één plek overzichtelijk willen zien wie de vechters zijn en wat er binnenkort gebeurt.

## Functionaliteiten

- Homepagina met een overzicht en navigatie naar alle onderdelen
- Vechters-overzicht met o.a. land, vechtstand, stijl, hoogtepunten en waar de vechter bekend om staat
- Nieuwspagina met top nieuws, aankomende gevechten en recente resultaten van Nederlandse vechters
- Inloggen en uitloggen met sessies
- Profielpagina voor ingelogde gebruikers
- Admin-beheer (CRUD) voor vechters (`vechters_admin.php`)
- Admin-beheer (CRUD) voor nieuwsberichten (`news_admin.php`)
- Pagina om een nieuwe gebruiker aan te maken (`Gebruikeraanmaken.php`)

## Installatie

1. Clone deze repository of download hem als ZIP.
2. Zet de map in de webroot van je lokale server (bijvoorbeeld `htdocs` van XAMPP/MAMP), of start een PHP-server in de projectmap:

   ```bash
   php -S localhost:8000
   ```

3. Open phpMyAdmin (of een andere MySQL-client) en importeer `import.sql`. Dit maakt de database `BoxingWorld` aan met de tabellen `vechters`, `gebruikers`, `wedstrijden` en `nieuws`, inclusief voorbeelddata.
4. Controleer de databasegegevens in `dbconnectie.php` en pas ze aan als jouw MySQL-gebruiker anders is:
   - host: `localhost`
   - database: `BoxingWorld`
   - gebruiker: `bit_academy`
   - wachtwoord: `bit_academy`
5. Open `index.php` in je browser (bijvoorbeeld http://localhost:8000/index.php).

### Inloggen als admin

Na het importeren van de database kun je inloggen met:

- Gebruikersnaam: `test-user`
- Wachtwoord: `pim`

Welke gebruikers admin zijn, stel je in via `ADMIN_USERNAMES` in `config.php`.

## Belangrijke bestanden

| Bestand | Uitleg |
| --- | --- |
| `dbconnectie.php` | Verbinding met de database (PDO) |
| `config.php` | Lijst met admin-gebruikersnamen |
| `import.sql` | Database-export met tabellen en voorbeelddata |
| `navbar.php` | Navigatiebalk die op elke pagina wordt ingeladen |
| `*.css` | Styling per pagina, met `style.css` als algemene stylesheet |

## Gebruikte technieken

- PHP 8 (PDO en prepared statements)
- MySQL
- HTML5
- CSS3
