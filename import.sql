DROP DATABASE IF EXISTS `BoxingWorld`;
CREATE DATABASE `BoxingWorld`;
USE `BoxingWorld`;

CREATE TABLE IF NOT EXISTS vechters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    naam VARCHAR(100) NOT NULL,
    Bekend_om VARCHAR(150) NOT NULL,
    Hoogtepunt VARCHAR(150) NOT NULL,
    Stijl VARCHAR(150) NOT NULL,
    Info VARCHAR(255) NOT NULL,
    land VARCHAR(100) NOT NULL,
    vecht_stand VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS gebruikers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    wachtwoord VARCHAR(255) NOT NULL
);

CREATE TABLE IF NOT EXISTS wedstrijden (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_naam VARCHAR(150) NOT NULL,
    datum DATE NULL,
    locatie VARCHAR(150) NULL,
    gewichtsklasse VARCHAR(100) NULL,
    ronde_info VARCHAR(100) NULL,
    uitslag VARCHAR(255) NULL,
    vechter1_id INT NOT NULL,
    vechter2_id INT NOT NULL,
    CONSTRAINT fk_wedstrijd_vechter1 FOREIGN KEY (vechter1_id) REFERENCES vechters(id) ON DELETE CASCADE,
    CONSTRAINT fk_wedstrijd_vechter2 FOREIGN KEY (vechter2_id) REFERENCES vechters(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS nieuws (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titel VARCHAR(180) NOT NULL,
    samenvatting VARCHAR(255) NOT NULL,
    inhoud TEXT NOT NULL,
    categorie VARCHAR(40) NOT NULL,
    event_datum DATE NULL,
    locatie VARCHAR(150) NULL,
    vechter1_id INT NULL,
    vechter2_id INT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'published',
    gepubliceerd_op DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    aangemaakt_door VARCHAR(255) NULL,
    CONSTRAINT fk_nieuws_vechter1 FOREIGN KEY (vechter1_id) REFERENCES vechters(id) ON DELETE SET NULL,
    CONSTRAINT fk_nieuws_vechter2 FOREIGN KEY (vechter2_id) REFERENCES vechters(id) ON DELETE SET NULL
);

INSERT INTO vechters (naam, Bekend_om, Hoogtepunt, Stijl, Info, land, vecht_stand)
VALUES
('Gokhan Saki', 'Combinaties en lowkicks', 'Topniveau in GLORY en K-1', 'Druk zetten, KO-gericht', 'Korte, explosieve aanvallen met veel kracht.', 'Turkije', 'Orthodox'),
('Badr Hari', 'Snelheid en knock-outs', 'Iconische K-1 en GLORY fights', 'Aanvallend, risico nemen', 'Spektakel met druk, explosies en harde counters.', 'Marokko', 'Orthodox'),
('Rico Verhoeven', 'Cardio en gameplan', 'Langdurig kampioenschap', 'Controle, volume, timing', 'Constante druk met slimme combinaties en defensie.', 'Nederland', 'Orthodox'),
('Melvin Manhoef', 'Boksen en druk', 'Veel finishes in MMA en kickboksen', 'Vooruit, combineren, afmaken', 'Gevaarlijk in de pocket met zware handen.', 'Nederland', 'Orthodox'),
('Melik Kilic', 'Combinaties en lowkicks', 'Veel finishes in kickboksen', 'Vooruit, combineren, afmaken', 'Gevaarlijk in de pocket met zware handen.', 'Nederland', 'Orthodox');

INSERT INTO gebruikers (username, wachtwoord)
VALUES ('test-user', 'pim');

INSERT INTO wedstrijden (event_naam, datum, locatie, gewichtsklasse, ronde_info, uitslag, vechter1_id, vechter2_id)
VALUES
('GLORY: Rotterdam', '2026-05-18', 'Rotterdam, Nederland', 'Heavyweight', NULL, NULL, 3, 2),
('Enfusion NL', '2026-06-09', 'Amsterdam, Nederland', 'Middleweight', NULL, NULL, 5, 1),
('GLORY Collision', '2026-03-01', 'Arnhem, Nederland', 'Light Heavyweight', 'R3 TKO', 'Melvin Manhoef wint via TKO in ronde 3', 4, 2);

CREATE OR REPLACE VIEW wedstrijden_overzicht AS
SELECT
    w.id,
    w.event_naam,
    w.datum,
    w.locatie,
    w.gewichtsklasse,
    w.ronde_info,
    w.uitslag,
    w.vechter1_id,
    v1.naam AS vechter1_naam,
    w.vechter2_id,
    v2.naam AS vechter2_naam
FROM wedstrijden w
JOIN vechters v1 ON v1.id = w.vechter1_id
JOIN vechters v2 ON v2.id = w.vechter2_id;

INSERT INTO nieuws (titel, samenvatting, inhoud, categorie, event_datum, locatie, vechter1_id, vechter2_id, status, aangemaakt_door)
VALUES
('Rico Verhoeven bevestigt volgende partij', 'De Nederlandse kampioen maakt zich op voor een nieuw hoofdevenement.', 'Rico Verhoeven heeft via het team bevestigd dat de voorbereiding voor het volgende titelgevecht is gestart. Meer details over de tegenstander volgen binnenkort.', 'Aankondiging', '2026-05-18', 'Rotterdam, Nederland', 3, 2, 'published', 'test-user'),
('Nederlandse vechters op sterk card in Amsterdam', 'Meerdere Nederlandse namen staan op het programma van Enfusion NL.', 'Het event in Amsterdam krijgt een sterk Nederlands blok met talenten uit verschillende gewichtsklassen. Organisatie verwacht veel publiek en een volle arena.', 'Evenement', '2026-06-09', 'Amsterdam, Nederland', 5, 1, 'published', 'test-user');
