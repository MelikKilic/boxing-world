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

$errors = [];
$success = null;
$editingItem = null;

if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action');

    if ($action === 'delete') {
        $id = (int)post('id');
        if ($id <= 0) {
            $errors[] = 'Ongeldig vechter-ID.';
        } else {
            try {
                $stmt = $pdo->prepare('DELETE FROM vechters WHERE id = :id');
                $stmt->execute([':id' => $id]);
                $success = 'Vechter verwijderd.';
            } catch (PDOException $e) {
                $errors[] = 'Verwijderen is mislukt. Controleer gekoppelde data in andere tabellen.';
            }
        }
    }

    if ($action === 'create' || $action === 'update') {
        $id = (int)post('id');
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
                $errors[] = "$label is verplicht.";
            }
        }

        if ($action === 'update' && $id <= 0) {
            $errors[] = 'Ongeldig vechter-ID voor update.';
        }

        if (!$errors) {
            if ($action === 'create') {
                $stmt = $pdo->prepare(
                    'INSERT INTO vechters (naam, Bekend_om, Hoogtepunt, Stijl, Info, land, vecht_stand)
                     VALUES (:naam, :bekend, :hoogtepunt, :stijl, :info, :land, :vecht_stand)'
                );
                $stmt->execute([
                    ':naam' => $naam,
                    ':bekend' => $bekendOm,
                    ':hoogtepunt' => $hoogtepunt,
                    ':stijl' => $stijl,
                    ':info' => $info,
                    ':land' => $land,
                    ':vecht_stand' => $vechtStand,
                ]);
                $success = 'Vechter toegevoegd.';
                $_POST = [];
            }

            if ($action === 'update') {
                $stmt = $pdo->prepare(
                    'UPDATE vechters
                     SET naam = :naam,
                         Bekend_om = :bekend,
                         Hoogtepunt = :hoogtepunt,
                         Stijl = :stijl,
                         Info = :info,
                         land = :land,
                         vecht_stand = :vecht_stand
                     WHERE id = :id'
                );
                $stmt->execute([
                    ':naam' => $naam,
                    ':bekend' => $bekendOm,
                    ':hoogtepunt' => $hoogtepunt,
                    ':stijl' => $stijl,
                    ':info' => $info,
                    ':land' => $land,
                    ':vecht_stand' => $vechtStand,
                    ':id' => $id,
                ]);

                header('Location: vechters_admin.php?updated=1');
                exit;
            }
        }
    }
}

if ($isAdmin && isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    if ($editId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM vechters WHERE id = :id');
        $stmt->execute([':id' => $editId]);
        $editingItem = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

if (isset($_GET['updated']) && $_GET['updated'] === '1') {
    $success = 'Vechter bijgewerkt.';
}

$stmt = $pdo->query('SELECT id, naam, land, vecht_stand, Bekend_om, Hoogtepunt FROM vechters ORDER BY id ASC');
$fighters = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vechters Beheer</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="vechters_admin.css">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <main class="container vechters-admin-page">
        <header class="page-header">
            <h1>Vechters beheer</h1>
            <p>Voeg vechters toe, bewerk gegevens en verwijder fouten.</p>
        </header>

        <?php if (!$isAdmin): ?>
            <section class="panel">
                <h2>Geen toegang</h2>
                <p>Alleen admins mogen vechters beheren.</p>
            </section>
        <?php else: ?>
            <section class="panel">
                <h2><?= $editingItem ? 'Vechter bewerken' : 'Vechter toevoegen' ?></h2>

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

                <?php
                $formData = [
                    'id' => (string)($editingItem['id'] ?? ''),
                    'naam' => (string)($editingItem['naam'] ?? ''),
                    'Bekend_om' => (string)($editingItem['Bekend_om'] ?? ''),
                    'Hoogtepunt' => (string)($editingItem['Hoogtepunt'] ?? ''),
                    'Stijl' => (string)($editingItem['Stijl'] ?? ''),
                    'Info' => (string)($editingItem['Info'] ?? ''),
                    'land' => (string)($editingItem['land'] ?? ''),
                    'vecht_stand' => (string)($editingItem['vecht_stand'] ?? 'Orthodox'),
                ];

                if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($errors || !$success)) {
                    $formData['id'] = post('id');
                    $formData['naam'] = post('naam');
                    $formData['Bekend_om'] = post('Bekend_om');
                    $formData['Hoogtepunt'] = post('Hoogtepunt');
                    $formData['Stijl'] = post('Stijl');
                    $formData['Info'] = post('Info');
                    $formData['land'] = post('land');
                    $formData['vecht_stand'] = post('vecht_stand');
                }
                ?>

                <form method="post" class="form-grid" autocomplete="off">
                    <input type="hidden" name="action" value="<?= $editingItem ? 'update' : 'create' ?>">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($formData['id']) ?>">

                    <label class="field">
                        <span>Naam</span>
                        <input type="text" name="naam" required value="<?= htmlspecialchars($formData['naam']) ?>">
                    </label>

                    <label class="field">
                        <span>Land</span>
                        <input type="text" name="land" required value="<?= htmlspecialchars($formData['land']) ?>">
                    </label>

                    <label class="field">
                        <span>Vecht-stand</span>
                        <select name="vecht_stand" required>
                            <option value="" disabled>Maak een keuze</option>
                            <?php foreach (['Orthodox', 'Southpaw', 'Switch', 'Onbekend'] as $opt): ?>
                                <option value="<?= htmlspecialchars($opt) ?>" <?= $formData['vecht_stand'] === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="field field-full">
                        <span>Bekend om</span>
                        <input type="text" name="Bekend_om" required value="<?= htmlspecialchars($formData['Bekend_om']) ?>">
                    </label>

                    <label class="field field-full">
                        <span>Hoogtepunt</span>
                        <input type="text" name="Hoogtepunt" required value="<?= htmlspecialchars($formData['Hoogtepunt']) ?>">
                    </label>

                    <label class="field field-full">
                        <span>Stijl</span>
                        <input type="text" name="Stijl" required value="<?= htmlspecialchars($formData['Stijl']) ?>">
                    </label>

                    <label class="field field-full">
                        <span>Info</span>
                        <textarea name="Info" rows="5" required><?= htmlspecialchars($formData['Info']) ?></textarea>
                    </label>

                    <div class="field-actions field-full">
                        <button class="btn" type="submit"><?= $editingItem ? 'Opslaan wijzigingen' : 'Vechter opslaan' ?></button>
                        <?php if ($editingItem): ?>
                            <a class="btn btn-secondary" href="vechters_admin.php">Nieuwe invoer</a>
                        <?php endif; ?>
                        <a class="btn btn-secondary" href="vechters.php">Naar vechters</a>
                    </div>
                </form>
            </section>

            <section class="panel">
                <h2>Bestaande vechters</h2>

                <?php if (!$fighters): ?>
                    <p>Er zijn nog geen vechters opgeslagen.</p>
                <?php else: ?>
                    <div class="vechter-list">
                        <?php foreach ($fighters as $fighter): ?>
                            <article class="vechter-item">
                                <div class="vechter-item-main">
                                    <h3><?= htmlspecialchars((string)$fighter['naam']) ?></h3>
                                    <p>
                                        <strong>Land:</strong> <?= htmlspecialchars((string)$fighter['land']) ?> |
                                        <strong>Stand:</strong> <?= htmlspecialchars((string)$fighter['vecht_stand']) ?>
                                    </p>
                                    <p><strong>Bekend om:</strong> <?= htmlspecialchars((string)$fighter['Bekend_om']) ?></p>
                                    <p><strong>Hoogtepunt:</strong> <?= htmlspecialchars((string)$fighter['Hoogtepunt']) ?></p>
                                </div>
                                <div class="vechter-item-actions">
                                    <a class="btn" href="vechters_admin.php?edit=<?= (int)$fighter['id'] ?>">Bewerken</a>
                                    <form method="post" onsubmit="return confirm('Weet je zeker dat je deze vechter wilt verwijderen?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$fighter['id'] ?>">
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
