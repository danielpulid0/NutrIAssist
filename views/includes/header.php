<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $page_title ?? 'NutrIAssist' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/global.css?v=<?= time() ?>">
    
    <!-- PWA Config -->
    <link rel="manifest" href="/nutriassist/manifest.json">
    <meta name="theme-color" content="#15B85E">
    <link rel="apple-touch-icon" href="/nutriassist/assets/img/icon-192.png">

    <script src="../assets/js/theme.js?v=<?= time() ?>"></script>
    <?php if (isset($extra_css)): ?>
        <link rel="stylesheet" href="<?= $extra_css ?>?v=<?= time() ?>">
    <?php endif; ?>
</head>
<body>
