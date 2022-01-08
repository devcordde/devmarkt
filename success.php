<?php
include_once('pdo.php');

?>

<!DOCTYPE HTML>

<html lang="de">

<head>

    <title>Anfrage erfolgreich gestellt - Devmarkt</title>
    <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.png">
    <link rel="stylesheet" href="assets/css/style.css">

    <meta charset="utf-8"/>
    <meta name="description" content="Interface des Devmarktes für den DevCord-Discord. Hier kannst du Anfragen in den Devmarkt schicken, die vor Veröffentlichung geprüft werden."/>
    <meta name="author" content="T1Il"/>
    <meta name="copyright" content="T1Il"/>

    <meta property="og:title" content="DevCord Devmarkt für Developer und Serverbetreiber"/>
    <meta property="og:description" content="Interface des Devmarktes für den DevCord-Discord. Hier kannst du Anfragen in den Devmarkt schicken, die vor Veröffentlichung geprüft werden."/>
    <meta property="og:site_name" content="DevCord Devmarkt"/>
    <link rel="stylesheet" href="assets/css/style.css">

</head>

<body>

<div class="form">

    <div id="s">

<h3 id="success" style="opacity: 0;left: 100%;">&#9989;</h3>
    <h3 id="text" class="success" style="opacity: 0;left: 90%;">Anfrage erfolgreich gestellt.(5s)</h3>

    </div>

</div>

</body>

<script
    src="https://code.jquery.com/jquery-3.3.1.min.js"
    integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
    crossorigin="anonymous"></script>

<script>

    $(window).ready(function() {

        $("#success").animate({left: '25px',width: '25%',height:'150px',opacity:"1"},500);
        $("#text").animate({left: '30px',opacity:"1",'margin-right':'40%','margin-top':'11%'},500);
        $("#text").html("Anfrage erfolgreich gestellt.");

    });

</script>

</html>