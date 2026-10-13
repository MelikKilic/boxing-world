<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? "Mijn Eindproject" ?></title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <header class="hero">
        <div class="hero-content">
            <h1 id="Boxing-wolrd">Welkom bij boxing world</h1>
            <p id="favoriete-vechters">Ontdek meer over je favoriete vechters.</p>
        </div>
    </header>

    <main>
        <section class="categories">
            <h2>Verken je favoriete vechters</h2>
            <p class="category-note">Klik op de knop hieronder om direct naar het overzicht van alle vechters te gaan.</p>
            <div class="category-list">
                <a class="category-item" href="vechters.php">Vechters</a>
            </div>
        </section>
    </main>
</body>

</html>
