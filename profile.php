<?php
// filepath: /Users/melikkilic/Documents/Nexed/eindproject/profile.php
declare(strict_types=1);

session_start();
require_once 'dbconnectie.php';
require_once 'config.php';

$userId = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;

if (!$userId || !$username) {
    header('Location: login.php');
    exit;
}

$isAdmin = in_array($username, ADMIN_USERNAMES, true);

$fighterErrors = [];
$fighterSuccess = null;
$newsErrors = [];
$newsSuccess = null;
$newsCategorieen = ['Aankondiging', 'Resultaat', 'Evenement'];

function post(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}

function tableExists(PDO $pdo, string $tableName): bool
{
    $stmt = $pdo->prepare('SHOW TABLES LIKE :name');
    $stmt->execute([':name' => $tableName]);
    return (bool)$stmt->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('form_action');

    if (!$isAdmin) {
        $fighterErrors[] = 'Je bent geen admin.';
    } elseif ($action === 'add_fighter') {
        $naam = post('naam');
        $bekendOm = post('Bekend_om');
        $hoogtepunt = post('Hoogtepunt');
        $stijl = post('Stijl');
        $info = post('Info');
        $land = post('land');
        $vechtStand = post('vecht_stand');

        foreach ([
            'Naam' => $naam,
            'Bekend om' => $bekendOm,
            'Hoogtepunt' => $hoogtepunt,
            'Stijl' => $stijl,
            'Info' => $info,
            'Land' => $land,
            'Vecht-stand' => $vechtStand,
        ] as $label => $value) {
            if ($value === '') {
                $fighterErrors[] = "$label is verplicht.";
            }
        }

        if (!$fighterErrors) {
            $stmt = $pdo->prepare(
                'INSERT INTO vechters (naam, Bekend_om, Hoogtepunt, Stijl, Info, land, vecht_stand)
                 VALUES (:naam, :bekend, :hoogte, :stijl, :info, :land, :stand)'
            );
            $stmt->execute([
                ':naam' => $naam,
                ':bekend' => $bekendOm,
                ':hoogte' => $hoogtepunt,
                ':stijl' => $stijl,
                ':info' => $info,
                ':land' => $land,
                ':stand' => $vechtStand,
            ]);

            $fighterSuccess = 'Toegevoegd aan Vechters.';
            $_POST = [];
        }
    } elseif ($action === 'add_news') {
        if (!tableExists($pdo, 'nieuws')) {
            $newsErrors[] = 'Tabel `nieuws` bestaat nog niet. Importeer eerst de nieuwe import.sql.';
        }

        $titel = post('news_titel');
        $samenvatting = post('news_samenvatting');
        $inhoud = post('news_inhoud');
        $categorie = post('news_categorie');
        $eventDatum = post('news_event_datum');
        $locatie = post('news_locatie');

        foreach ([
            'Titel' => $titel,
            'Samenvatting' => $samenvatting,
            'Inhoud' => $inhoud,
            'Categorie' => $categorie,
        ] as $label => $value) {
            if ($value === '') {
                $newsErrors[] = "$label is verplicht.";
            }
        }

        if ($categorie !== '' && !in_array($categorie, $newsCategorieen, true)) {
            $newsErrors[] = 'Ongeldige categorie gekozen.';
        }

        if ($eventDatum !== '') {
            $dt = DateTimeImmutable::createFromFormat('Y-m-d', $eventDatum);
            if (!$dt || $dt->format('Y-m-d') !== $eventDatum) {
                $newsErrors[] = 'Event datum is ongeldig.';
            }
        }

        if (!$newsErrors) {
            $stmt = $pdo->prepare(
                'INSERT INTO nieuws (titel, samenvatting, inhoud, categorie, event_datum, locatie, status, gepubliceerd_op, aangemaakt_door)
                 VALUES (:titel, :samenvatting, :inhoud, :categorie, :event_datum, :locatie, :status, NOW(), :aangemaakt_door)'
            );

            $stmt->execute([
                ':titel' => $titel,
                ':samenvatting' => $samenvatting,
                ':inhoud' => $inhoud,
                ':categorie' => $categorie,
                ':event_datum' => $eventDatum !== '' ? $eventDatum : null,
                ':locatie' => $locatie !== '' ? $locatie : null,
                ':status' => 'published',
                ':aangemaakt_door' => $username,
            ]);

            $newsSuccess = 'Nieuwsbericht toegevoegd';
            $_POST = [];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profiel</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="profile.css">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <main class="container profile-page">
        <header class="page-header">
            <h1>Profiel</h1>
            <p>Ingelogd als: <strong><?= htmlspecialchars($username) ?></strong></p>
            <p><a class="btn" href="logout.php">Uitloggen</a></p>
        </header>

        <section class="panel">
            <h2>Jouw gegevens</h2>
            <p><strong>Username:</strong> <?= htmlspecialchars($username) ?></p>
            <p><strong>User ID:</strong> <?= (int)$userId ?></p>
        </section>

        <?php if ($isAdmin): ?>
            <section class="panel">
                <h2>Admin: vechter toevoegen</h2>
                <p><a class="btn" href="vechters_admin.php">Vechters beheren (CRUD)</a></p>

                <?php if ($fighterSuccess): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($fighterSuccess) ?></div>
                <?php endif; ?>

                <?php if ($fighterErrors): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($fighterErrors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" class="form-grid" autocomplete="off">
                    <input type="hidden" name="form_action" value="add_fighter">

                    <label class="field">
                        <span>Naam</span>
                        <input type="text" name="naam" required value="<?= htmlspecialchars((string)($_POST['naam'] ?? '')) ?>">
                    </label>

                    <label class="field">
                        <span>Land</span>
                        <input type="text" name="land" required value="<?= htmlspecialchars((string)($_POST['land'] ?? '')) ?>">
                    </label>

                    <label class="field">
                        <span>Vecht-stand</span>
                        <select name="vecht_stand" required>
                            <option value="" disabled>Maak een keuze</option>
                            <?php
                            $current = (string)($_POST['vecht_stand'] ?? 'Orthodox');
                            foreach (['Orthodox', 'Southpaw', 'Switch', 'Onbekend'] as $opt) {
                                $sel = ($current === $opt) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($opt) . '" ' . $sel . '>' . htmlspecialchars($opt) . '</option>';
                            }
                            ?>
                        </select>
                    </label>

                    <label class="field field-full">
                        <span>Bekend om</span>
                        <input type="text" name="Bekend_om" required value="<?= htmlspecialchars((string)($_POST['Bekend_om'] ?? '')) ?>">
                    </label>

                    <label class="field field-full">
                        <span>Hoogtepunt</span>
                        <input type="text" name="Hoogtepunt" required value="<?= htmlspecialchars((string)($_POST['Hoogtepunt'] ?? '')) ?>">
                    </label>

                    <label class="field field-full">
                        <span>Stijl</span>
                        <input type="text" name="Stijl" required value="<?= htmlspecialchars((string)($_POST['Stijl'] ?? '')) ?>">
                    </label>

                    <label class="field field-full">
                        <span>Info</span>
                        <textarea name="Info" rows="4" required><?= htmlspecialchars((string)($_POST['Info'] ?? '')) ?></textarea>
                    </label>

                    <div class="field-actions field-full">
                        <button class="btn" type="submit">Vechter opslaan</button>
                        <a class="btn" href="vechters.php">Naar vechters</a>
                    </div>
                </form>
            </section>

            <section class="panel">
                <h2>Admin: nieuws toevoegen</h2>
                <p><a class="btn" href="news_admin.php">Nieuws beheren (CRUD)</a></p>

                <?php if ($newsSuccess): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($newsSuccess) ?></div>
                <?php endif; ?>

                <?php if ($newsErrors): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($newsErrors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" class="form-grid" autocomplete="off">
                    <input type="hidden" name="form_action" value="add_news">

                    <label class="field field-full">
                        <span>Titel</span>
                        <input type="text" name="news_titel" required value="<?= htmlspecialchars((string)($_POST['news_titel'] ?? '')) ?>">
                    </label>

                    <label class="field field-full">
                        <span>Samenvatting</span>
                        <input type="text" name="news_samenvatting" required value="<?= htmlspecialchars((string)($_POST['news_samenvatting'] ?? '')) ?>">
                    </label>

                    <label class="field field-full">
                        <span>Inhoud</span>
                        <textarea name="news_inhoud" rows="5" required><?= htmlspecialchars((string)($_POST['news_inhoud'] ?? '')) ?></textarea>
                    </label>

                    <label class="field">
                        <span>Categorie</span>
                        <select name="news_categorie" required>
                            <option value="" disabled>Maak een keuze</option>
                            <?php
                            $selectedCategorie = (string)($_POST['news_categorie'] ?? 'Aankondiging');
                            foreach ($newsCategorieen as $categorie) {
                                $sel = ($selectedCategorie === $categorie) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($categorie) . '" ' . $sel . '>' . htmlspecialchars($categorie) . '</option>';
                            }
                            ?>
                        </select>
                    </label>

                    <label class="field">
                        <span>Event datum (optioneel)</span>
                        <input type="date" name="news_event_datum" value="<?= htmlspecialchars((string)($_POST['news_event_datum'] ?? '')) ?>">
                    </label>

                    <label class="field field-full">
                        <span>Locatie (optioneel)</span>
                        <input type="text" name="news_locatie" value="<?= htmlspecialchars((string)($_POST['news_locatie'] ?? '')) ?>">
                    </label>

                    <div class="field-actions field-full">
                        <button class="btn" type="submit">Nieuws opslaan</button>
                        <a class="btn" href="news.php">Naar nieuws</a>
                    </div>
                </form>
            </section>
        <?php else: ?>
            <section class="panel">
                <h2>Geen admin</h2>
                <p>Je kunt je gegevens zien, maar geen vechters of nieuws toevoegen.</p>
            </section>
        <?php endif; ?>
    </main>
</body>

</html>
