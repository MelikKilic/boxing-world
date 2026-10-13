<?php
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
if (!$isAdmin) {
    http_response_code(403);
}

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

function columnExists(PDO $pdo, string $tableName, string $columnName): bool
{
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$tableName` LIKE :column");
    $stmt->execute([':column' => $columnName]);
    return (bool)$stmt->fetchColumn();
}

function validDateOrNull(string $date): ?string
{
    if ($date === '') {
        return null;
    }

    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $date);
    if (!$dt || $dt->format('Y-m-d') !== $date) {
        return null;
    }

    return $date;
}

$errors = [];
$success = null;
$categories = ['Aankondiging', 'Resultaat', 'Evenement'];
$statuses = ['published', 'draft'];
$newsTableExists = tableExists($pdo, 'nieuws');
$fightersTableExists = tableExists($pdo, 'vechters');
$newsHasFightColumns = $newsTableExists
    && columnExists($pdo, 'nieuws', 'vechter1_id')
    && columnExists($pdo, 'nieuws', 'vechter2_id');
$fighters = [];
$editingItem = null;

if ($fightersTableExists) {
    $fighterStmt = $pdo->query('SELECT id, naam FROM vechters ORDER BY naam ASC');
    $fighters = $fighterStmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$newsTableExists) {
        $errors[] = 'Tabel `nieuws` bestaat nog niet. Draai eerst `import.sql`.';
    } else {
        $action = post('action');

        if ($action === 'delete') {
            $id = (int)post('id');
            if ($id <= 0) {
                $errors[] = 'Ongeldig nieuws-ID.';
            } else {
                $stmt = $pdo->prepare('DELETE FROM nieuws WHERE id = :id');
                $stmt->execute([':id' => $id]);
                $success = 'Nieuwsbericht verwijderd.';
            }
        }

        if ($action === 'create' || $action === 'update') {
            $id = (int)post('id');
            $titel = post('titel');
            $samenvatting = post('samenvatting');
            $inhoud = post('inhoud');
            $categorie = post('categorie');
            $eventDatumInput = post('event_datum');
            $locatie = post('locatie');
            $status = post('status');
            $vechter1Id = $newsHasFightColumns ? (int)post('vechter1_id') : 0;
            $vechter2Id = $newsHasFightColumns ? (int)post('vechter2_id') : 0;

            foreach ([
                'Titel' => $titel,
                'Samenvatting' => $samenvatting,
                'Inhoud' => $inhoud,
                'Categorie' => $categorie,
                'Status' => $status,
            ] as $label => $value) {
                if ($value === '') {
                    $errors[] = "$label is verplicht.";
                }
            }

            if ($categorie !== '' && !in_array($categorie, $categories, true)) {
                $errors[] = 'Ongeldige categorie.';
            }

            if ($status !== '' && !in_array($status, $statuses, true)) {
                $errors[] = 'Ongeldige status.';
            }

            $eventDatum = validDateOrNull($eventDatumInput);
            if ($eventDatumInput !== '' && $eventDatum === null) {
                $errors[] = 'Event datum is ongeldig.';
            }

            if ($newsHasFightColumns) {
                if (($vechter1Id > 0 && $vechter2Id === 0) || ($vechter2Id > 0 && $vechter1Id === 0)) {
                    $errors[] = 'Kies beide vechters of laat beide velden leeg.';
                }

                if ($vechter1Id > 0 && $vechter1Id === $vechter2Id) {
                    $errors[] = 'Vechter 1 en Vechter 2 mogen niet hetzelfde zijn.';
                }
            }

            if ($action === 'update' && $id <= 0) {
                $errors[] = 'Ongeldig nieuws-ID voor update.';
            }

            if (!$errors) {
                if ($action === 'create') {
                    if ($newsHasFightColumns) {
                        $stmt = $pdo->prepare(
                            'INSERT INTO nieuws (titel, samenvatting, inhoud, categorie, event_datum, locatie, vechter1_id, vechter2_id, status, gepubliceerd_op, aangemaakt_door)
                             VALUES (:titel, :samenvatting, :inhoud, :categorie, :event_datum, :locatie, :vechter1_id, :vechter2_id, :status, NOW(), :aangemaakt_door)'
                        );
                        $stmt->execute([
                            ':titel' => $titel,
                            ':samenvatting' => $samenvatting,
                            ':inhoud' => $inhoud,
                            ':categorie' => $categorie,
                            ':event_datum' => $eventDatum,
                            ':locatie' => $locatie !== '' ? $locatie : null,
                            ':vechter1_id' => $vechter1Id > 0 ? $vechter1Id : null,
                            ':vechter2_id' => $vechter2Id > 0 ? $vechter2Id : null,
                            ':status' => $status,
                            ':aangemaakt_door' => $username,
                        ]);
                    } else {
                        $stmt = $pdo->prepare(
                            'INSERT INTO nieuws (titel, samenvatting, inhoud, categorie, event_datum, locatie, status, gepubliceerd_op, aangemaakt_door)
                             VALUES (:titel, :samenvatting, :inhoud, :categorie, :event_datum, :locatie, :status, NOW(), :aangemaakt_door)'
                        );
                        $stmt->execute([
                            ':titel' => $titel,
                            ':samenvatting' => $samenvatting,
                            ':inhoud' => $inhoud,
                            ':categorie' => $categorie,
                            ':event_datum' => $eventDatum,
                            ':locatie' => $locatie !== '' ? $locatie : null,
                            ':status' => $status,
                            ':aangemaakt_door' => $username,
                        ]);
                    }
                    $success = 'Nieuwsbericht toegevoegd.';
                    $_POST = [];
                }

                if ($action === 'update') {
                    if ($newsHasFightColumns) {
                        $stmt = $pdo->prepare(
                            'UPDATE nieuws
                             SET titel = :titel,
                                 samenvatting = :samenvatting,
                                 inhoud = :inhoud,
                                 categorie = :categorie,
                                 event_datum = :event_datum,
                                 locatie = :locatie,
                                 vechter1_id = :vechter1_id,
                                 vechter2_id = :vechter2_id,
                                 status = :status
                             WHERE id = :id'
                        );
                        $stmt->execute([
                            ':titel' => $titel,
                            ':samenvatting' => $samenvatting,
                            ':inhoud' => $inhoud,
                            ':categorie' => $categorie,
                            ':event_datum' => $eventDatum,
                            ':locatie' => $locatie !== '' ? $locatie : null,
                            ':vechter1_id' => $vechter1Id > 0 ? $vechter1Id : null,
                            ':vechter2_id' => $vechter2Id > 0 ? $vechter2Id : null,
                            ':status' => $status,
                            ':id' => $id,
                        ]);
                    } else {
                        $stmt = $pdo->prepare(
                            'UPDATE nieuws
                             SET titel = :titel,
                                 samenvatting = :samenvatting,
                                 inhoud = :inhoud,
                                 categorie = :categorie,
                                 event_datum = :event_datum,
                                 locatie = :locatie,
                                 status = :status
                             WHERE id = :id'
                        );
                        $stmt->execute([
                            ':titel' => $titel,
                            ':samenvatting' => $samenvatting,
                            ':inhoud' => $inhoud,
                            ':categorie' => $categorie,
                            ':event_datum' => $eventDatum,
                            ':locatie' => $locatie !== '' ? $locatie : null,
                            ':status' => $status,
                            ':id' => $id,
                        ]);
                    }
                    $success = 'Nieuwsbericht bijgewerkt.';
                    header('Location: news_admin.php?updated=1');
                    exit;
                }
            }
        }
    }
}

if ($isAdmin && $newsTableExists && isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    if ($editId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM nieuws WHERE id = :id');
        $stmt->execute([':id' => $editId]);
        $editingItem = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

if (isset($_GET['updated']) && $_GET['updated'] === '1') {
    $success = 'Nieuwsbericht bijgewerkt.';
}

$newsItems = [];
if ($newsTableExists) {
    if ($newsHasFightColumns && $fightersTableExists) {
        $stmt = $pdo->query(
            'SELECT n.id, n.titel, n.categorie, n.status, n.event_datum, n.locatie, n.gepubliceerd_op,
                    n.vechter1_id, n.vechter2_id,
                    v1.naam AS vechter1_naam, v2.naam AS vechter2_naam
             FROM nieuws n
             LEFT JOIN vechters v1 ON v1.id = n.vechter1_id
             LEFT JOIN vechters v2 ON v2.id = n.vechter2_id
             ORDER BY n.gepubliceerd_op DESC, n.id DESC'
        );
    } else {
        $stmt = $pdo->query(
            'SELECT id, titel, categorie, status, event_datum, locatie, gepubliceerd_op
             FROM nieuws
             ORDER BY gepubliceerd_op DESC, id DESC'
        );
    }
    $newsItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nieuws Beheer</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="news_admin.css">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <main class="container news-admin-page">
        <header class="page-header">
            <h1>Nieuws beheer</h1>
            <p>Voeg nieuws toe, bewerk bestaande berichten en verwijder oude items.</p>
        </header>

        <?php if (!$isAdmin): ?>
            <section class="panel">
                <h2>Geen toegang</h2>
                <p>Alleen admins mogen nieuws beheren.</p>
            </section>
        <?php else: ?>
            <section class="panel">
                <h2><?= $editingItem ? 'Nieuws bewerken' : 'Nieuws toevoegen' ?></h2>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <?php if ($errors): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!$newsTableExists): ?>
                    <div class="alert alert-error">Tabel <code>nieuws</code> ontbreekt. Gebruik eerst <code>import.sql</code>.</div>
                <?php else: ?>
                    <?php if (!$newsHasFightColumns): ?>
                        <div class="alert alert-error">Deze database mist kolommen voor vechters in nieuws. Importeer <code>import.sql</code> opnieuw om <code>vechter1_id</code> en <code>vechter2_id</code> toe te voegen.</div>
                    <?php endif; ?>
                    <?php
                    $formData = [
                        'id' => (string)($editingItem['id'] ?? ''),
                        'titel' => (string)($editingItem['titel'] ?? ''),
                        'samenvatting' => (string)($editingItem['samenvatting'] ?? ''),
                        'inhoud' => (string)($editingItem['inhoud'] ?? ''),
                        'categorie' => (string)($editingItem['categorie'] ?? 'Aankondiging'),
                        'event_datum' => (string)($editingItem['event_datum'] ?? ''),
                        'locatie' => (string)($editingItem['locatie'] ?? ''),
                        'vechter1_id' => (string)($editingItem['vechter1_id'] ?? ''),
                        'vechter2_id' => (string)($editingItem['vechter2_id'] ?? ''),
                        'status' => (string)($editingItem['status'] ?? 'published'),
                    ];

                    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($errors || !$success)) {
                        $formData['id'] = post('id');
                        $formData['titel'] = post('titel');
                        $formData['samenvatting'] = post('samenvatting');
                        $formData['inhoud'] = post('inhoud');
                        $formData['categorie'] = post('categorie');
                        $formData['event_datum'] = post('event_datum');
                        $formData['locatie'] = post('locatie');
                        $formData['vechter1_id'] = post('vechter1_id');
                        $formData['vechter2_id'] = post('vechter2_id');
                        $formData['status'] = post('status');
                    }
                    ?>

                    <form method="post" class="form-grid" autocomplete="off">
                        <input type="hidden" name="action" value="<?= $editingItem ? 'update' : 'create' ?>">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($formData['id']) ?>">

                        <label class="field field-full">
                            <span>Titel</span>
                            <input type="text" name="titel" required value="<?= htmlspecialchars($formData['titel']) ?>">
                        </label>

                        <label class="field field-full">
                            <span>Samenvatting</span>
                            <input type="text" name="samenvatting" required value="<?= htmlspecialchars($formData['samenvatting']) ?>">
                        </label>

                        <label class="field field-full">
                            <span>Inhoud</span>
                            <textarea name="inhoud" rows="6" required><?= htmlspecialchars($formData['inhoud']) ?></textarea>
                        </label>

                        <label class="field">
                            <span>Categorie</span>
                            <select name="categorie" required>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category) ?>" <?= $formData['categorie'] === $category ? 'selected' : '' ?>><?= htmlspecialchars($category) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="field">
                            <span>Status</span>
                            <select name="status" required>
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?= htmlspecialchars($status) ?>" <?= $formData['status'] === $status ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>

                        <label class="field">
                            <span>Event datum (optioneel)</span>
                            <input type="date" name="event_datum" value="<?= htmlspecialchars($formData['event_datum']) ?>">
                        </label>

                        <label class="field">
                            <span>Locatie (optioneel)</span>
                            <input type="text" name="locatie" value="<?= htmlspecialchars($formData['locatie']) ?>">
                        </label>

                        <?php if ($newsHasFightColumns && $fightersTableExists): ?>
                            <label class="field">
                                <span>Vechter 1 (optioneel)</span>
                                <select name="vechter1_id">
                                    <option value="">Geen keuze</option>
                                    <?php foreach ($fighters as $fighter): ?>
                                        <?php $fighterId = (string)$fighter['id']; ?>
                                        <option value="<?= htmlspecialchars($fighterId) ?>" <?= $formData['vechter1_id'] === $fighterId ? 'selected' : '' ?>>
                                            <?= htmlspecialchars((string)$fighter['naam']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>

                            <label class="field">
                                <span>Vechter 2 (optioneel)</span>
                                <select name="vechter2_id">
                                    <option value="">Geen keuze</option>
                                    <?php foreach ($fighters as $fighter): ?>
                                        <?php $fighterId = (string)$fighter['id']; ?>
                                        <option value="<?= htmlspecialchars($fighterId) ?>" <?= $formData['vechter2_id'] === $fighterId ? 'selected' : '' ?>>
                                            <?= htmlspecialchars((string)$fighter['naam']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        <?php endif; ?>

                        <div class="field-actions field-full">
                            <button class="btn" type="submit"><?= $editingItem ? 'Opslaan wijzigingen' : 'Nieuws opslaan' ?></button>
                            <?php if ($editingItem): ?>
                                <a class="btn btn-secondary" href="news_admin.php">Nieuwe invoer</a>
                            <?php endif; ?>
                            <a class="btn btn-secondary" href="news.php">Naar nieuws</a>
                        </div>
                    </form>
                <?php endif; ?>
            </section>

            <section class="panel">
                <h2>Bestaande nieuwsberichten</h2>

                <?php if (!$newsTableExists): ?>
                    <p>Geen tabel gevonden.</p>
                <?php elseif (!$newsItems): ?>
                    <p>Er zijn nog geen nieuwsberichten opgeslagen.</p>
                <?php else: ?>
                    <div class="news-list">
                        <?php foreach ($newsItems as $item): ?>
                            <article class="news-item">
                                <div class="news-item-main">
                                    <h3><?= htmlspecialchars((string)$item['titel']) ?></h3>
                                    <p>
                                        <strong>Categorie:</strong> <?= htmlspecialchars((string)$item['categorie']) ?> |
                                        <strong>Status:</strong> <?= htmlspecialchars((string)$item['status']) ?> |
                                        <strong>Event datum:</strong> <?= htmlspecialchars((string)($item['event_datum'] ?: 'Geen')) ?>
                                    </p>
                                    <?php if (!empty($item['vechter1_naam']) && !empty($item['vechter2_naam'])): ?>
                                        <p><strong>Gevecht:</strong> <?= htmlspecialchars((string)$item['vechter1_naam']) ?> vs <?= htmlspecialchars((string)$item['vechter2_naam']) ?></p>
                                    <?php endif; ?>
                                    <p><strong>Locatie:</strong> <?= htmlspecialchars((string)($item['locatie'] ?: 'Onbekend')) ?></p>
                                </div>
                                <div class="news-item-actions">
                                    <a class="btn" href="news_admin.php?edit=<?= (int)$item['id'] ?>">Bewerken</a>
                                    <form method="post" onsubmit="return confirm('Weet je zeker dat je dit nieuwsbericht wilt verwijderen?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                        <button type="submit" class="btn btn-danger">Verwijderen</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</body>

</html>
