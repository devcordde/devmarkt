<!DOCTYPE HTML>

<html lang="de">

<head>

    <title>Fehler - Devmarkt</title>
    <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.png">
    <link rel="stylesheet" href="assets/css/styles.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta charset="utf-8"/>
    <meta name="description" content="Interface des Devmarktes für den DevCord-Discord. Hier kannst du Anfragen in den Devmarkt schicken, die vor Veröffentlichung geprüft werden."/>
    <meta name="author" content="T1Il"/>
    <meta name="copyright" content="T1Il"/>

    <meta property="og:title" content="DevCord Devmarkt für Developer und Serverbetreiber"/>
    <meta property="og:description" content="Interface des Devmarktes für den DevCord-Discord. Hier kannst du Anfragen in den Devmarkt schicken, die vor Veröffentlichung geprüft werden."/>
    <meta property="og:site_name" content="DevCord Devmarkt"/>

</head>

<body>

<div class="status-page">
    <div class="status-card fade-in">
        <div class="status-icon">&#10060;</div>
        <p class="status-text">Da ist was schiefgelaufen...</p>

        <?php if(isset($_GET['error'])) { ?>
        <p class="status-detail">Bitte melde folgenden Fehler an Till#6638: <strong><code><?php echo testInput($_GET['error']); ?></code></strong></p>
        <?php } ?>
    </div>
</div>

</body>

</html>

<?php

function testInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

?>
