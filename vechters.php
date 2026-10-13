<?php
require_once 'dbconnectie.php';

$stmt = $pdo->query("
    SELECT
        id,
        naam,
        Bekend_om,
        Hoogtepunt,
        Stijl,
        Info,
        land,
        vecht_stand
    FROM vechters
    ORDER BY id ASC
");
$vechters = $stmt->fetchAll(PDO::FETCH_ASSOC);

function initials(string $name): string
{
    $name = trim($name);
    if ($name === '') return '';
    $parts = preg_split('/\s+/', $name);
    $first = $parts[0][0] ?? '';
    $last = (count($parts) > 1) ? ($parts[count($parts) - 1][0] ?? '') : '';
    return strtoupper($first . $last);
}
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vechters</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <main class="container">
        <header class="page-header">
            <h1>Vechters</h1>
            <p>Kies je favoriet en bekijk direct de belangrijkste info.</p>
        </header>

        <section class="fighters-grid" aria-label="Overzicht van vechters">
            <?php if (empty($vechters)): ?>
                <p>Er zijn nog geen vechters gevonden in de database.</p>
            <?php else: ?>
                <?php foreach ($vechters as $v): ?>
                    <article class="fighter-card">
                        <header class="fighter-top">
                            <div class="fighter-avatar" aria-hidden="true">
                                <?= htmlspecialchars(initials((string)$v['naam'])) ?>
                            </div>
                            <div>
                                <h2 class="fighter-name"><?= htmlspecialchars((string)$v['naam']) ?></h2>
                                <p class="fighter-subtitle">
                                    <?= htmlspecialchars((string)$v['land']) ?> • <?= htmlspecialchars((string)$v['vecht_stand']) ?>
                                </p>
                            </div>
                        </header>

                        <div class="fighter-meta">
                            <span class="badge"><?= htmlspecialchars((string)$v['land']) ?></span>
                            <span class="badge badge-outline"><?= htmlspecialchars((string)$v['vecht_stand']) ?></span>
                        </div>

                        <dl class="fighter-stats">
                            <div class="stat">
                                <dt>Bekend om</dt>
                                <dd><?= htmlspecialchars((string)$v['Bekend_om']) ?></dd>
                            </div>
                            <div class="stat">
                                <dt>Hoogtepunt</dt>
                                <dd><?= htmlspecialchars((string)$v['Hoogtepunt']) ?></dd>
                            </div>
                            <div class="stat">
                                <dt>Stijl</dt>
                                <dd><?= htmlspecialchars((string)$v['Stijl']) ?></dd>
                            </div>
                            <div class="stat">
                                <dt>Info</dt>
                                <dd><?= htmlspecialchars((string)$v['Info']) ?></dd>
                            </div>
                        </dl>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
</body>

</html>