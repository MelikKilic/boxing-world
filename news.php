<?php
declare(strict_types=1);

require_once 'dbconnectie.php';

$today = new DateTimeImmutable('today');

function tableExists(PDO $pdo, string $tableName): bool
{
    $stmt = $pdo->prepare('SHOW TABLES LIKE :name');
    $stmt->execute([':name' => $tableName]);
    return (bool)$stmt->fetchColumn();
}

function parseMatchDate(?string $date): ?DateTimeImmutable
{
    if (!$date) {
        return null;
    }

    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $date);
    return $dt ?: null;
}

function formatDutchDate(?string $date): string
{
    $dt = parseMatchDate($date);
    if (!$dt) {
        return 'Datum volgt';
    }

    $months = [
        1 => 'januari',
        2 => 'februari',
        3 => 'maart',
        4 => 'april',
        5 => 'mei',
        6 => 'juni',
        7 => 'juli',
        8 => 'augustus',
        9 => 'september',
        10 => 'oktober',
        11 => 'november',
        12 => 'december',
    ];

    return (int)$dt->format('j') . ' ' . $months[(int)$dt->format('n')] . ' ' . $dt->format('Y');
}

function dateTag(?string $date): string
{
    $dt = parseMatchDate($date);
    if (!$dt) {
        return '<span class="date-tbd">Datum volgt</span>';
    }

    return '<time datetime="' . $dt->format('Y-m-d') . '">' . htmlspecialchars(formatDutchDate($date)) . '</time>';
}

$newsItems = [];
$upcomingMatches = [];
$recentResults = [];

if (tableExists($pdo, 'nieuws')) {
    $newsStmt = $pdo->query("\n        SELECT\n            id,\n            titel,\n            samenvatting,\n            inhoud,\n            categorie,\n            event_datum,\n            locatie,\n            gepubliceerd_op\n        FROM nieuws\n        WHERE status = 'published'\n        ORDER BY (event_datum IS NULL) ASC, event_datum DESC, gepubliceerd_op DESC, id DESC\n        LIMIT 12\n    ");
    $newsItems = $newsStmt->fetchAll(PDO::FETCH_ASSOC);
}

if (tableExists($pdo, 'wedstrijden') && tableExists($pdo, 'vechters')) {
    $matchStmt = $pdo->query("\n        SELECT\n            w.id,\n            w.event_naam,\n            w.datum,\n            w.locatie,\n            w.gewichtsklasse,\n            w.uitslag,\n            v1.naam AS vechter1_naam,\n            v1.land AS vechter1_land,\n            v2.naam AS vechter2_naam,\n            v2.land AS vechter2_land\n        FROM wedstrijden w\n        JOIN vechters v1 ON v1.id = w.vechter1_id\n        JOIN vechters v2 ON v2.id = w.vechter2_id\n        WHERE LOWER(v1.land) = 'nederland' OR LOWER(v2.land) = 'nederland'\n        ORDER BY (w.datum IS NULL) ASC, w.datum ASC, w.id ASC\n    ");

    $dutchMatches = $matchStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dutchMatches as $match) {
        $dateObj = parseMatchDate($match['datum'] ?? null);
        $hasResult = trim((string)($match['uitslag'] ?? '')) !== '';

        if (!$hasResult && (!$dateObj || $dateObj >= $today)) {
            $upcomingMatches[] = $match;
            continue;
        }

        if ($hasResult || ($dateObj && $dateObj < $today)) {
            $recentResults[] = $match;
        }
    }

    usort($recentResults, static function (array $a, array $b): int {
        $aDate = $a['datum'] ?? null;
        $bDate = $b['datum'] ?? null;

        if ($aDate === $bDate) {
            return $b['id'] <=> $a['id'];
        }

        if ($aDate === null) {
            return 1;
        }

        if ($bDate === null) {
            return -1;
        }

        return strcmp($bDate, $aDate);
    });
}
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nieuws | Nederlandse Vechters</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="news.css">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <main class="container news-page">
        <header class="page-header news-hero">
            <div>
                <p class="news-kicker">Kickboksen & MMA NL</p>
                <h1>Nieuws over Nederlandse vechters</h1>
                <p>Volg aankomende gevechten, uitslagen en belangrijke evenementen van Nederlandse fighters.</p>
            </div>
        </header>

        <section class="news-section">
            <div class="section-head">
                <h2>Top nieuws</h2>
            </div>

            <?php if (!$newsItems): ?>
                <article class="empty-state">Nog geen nieuws in de database. Voeg nieuws toe via <strong>news_admin.</strong> (admin).</article>
            <?php else: ?>
                <div class="story-grid">
                    <?php foreach ($newsItems as $item): ?>
                        <article class="story-card">
                            <span class="chip"><?= htmlspecialchars((string)$item['categorie']) ?></span>
                            <h3><?= htmlspecialchars((string)$item['titel']) ?></h3>
                            <p><?= htmlspecialchars((string)($item['samenvatting'] ?: $item['inhoud'])) ?></p>
                            <?= dateTag($item['event_datum'] ?: null) ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="news-section">
            <div class="section-head">
                <h2>Aankomende gevechten</h2>
            </div>

            <?php if (!$upcomingMatches): ?>
                <article class="empty-state">Geen aankomende gevechten gevonden voor Nederlandse vechters.</article>
            <?php else: ?>
                <div class="event-list">
                    <?php foreach ($upcomingMatches as $match): ?>
                        <article class="event-card">
                            <div class="event-main">
                                <h3><?= htmlspecialchars($match['vechter1_naam']) ?> vs <?= htmlspecialchars($match['vechter2_naam']) ?></h3>
                                <p class="event-sub"><?= htmlspecialchars($match['event_naam'] ?: 'Event volgt') ?> • <?= htmlspecialchars($match['locatie'] ?: 'Locatie volgt') ?></p>
                            </div>
                            <div class="event-meta">
                                <span><?= htmlspecialchars(formatDutchDate($match['datum'] ?? null)) ?></span>
                                <span><?= htmlspecialchars($match['gewichtsklasse'] ?: 'Gewichtsklasse volgt') ?></span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="news-section">
            <div class="section-head">
                <h2>Recente resultaten & evenementen</h2>
            </div>

            <?php if (!$recentResults): ?>
                <article class="empty-state">Nog geen recente resultaten beschikbaar.</article>
            <?php else: ?>
                <div class="result-list">
                    <?php foreach (array_slice($recentResults, 0, 8) as $match): ?>
                        <article class="result-card">
                            <h3><?= htmlspecialchars($match['event_naam'] ?: 'Event') ?></h3>
                            <p class="fight-line"><?= htmlspecialchars($match['vechter1_naam']) ?> vs <?= htmlspecialchars($match['vechter2_naam']) ?></p>
                            <p class="result-text"><?= htmlspecialchars($match['uitslag'] ?: 'Uitslag nog niet ingevuld') ?></p>
                            <?= dateTag($match['datum'] ?? null) ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>

</html>
