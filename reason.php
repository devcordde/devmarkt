<?php
require getenv("APP_PATH") . '/vendor/autoload.php';
include_once('pdo.php');
set_error_handler("var_dump");
include_once('php/login.inc.php');
include_once('php/token.inc.php');
include_once('php/checklogin.php');
$mysql = new MySQL();
$client = new \GuzzleHttp\Client();

$thread = false;

if(getenv("THREAD_TYPE") == 12) {

    $req = $client->request("GET", "https://discordapp.com/api/v8/guilds/" . getenv("GUILD_ID"),[
            "headers"=>["Authorization"=>"Bot " . getenv("BOT_TOKEN")]
    ]);

    if(json_decode($req->getBody())->premium_tier >= 2) {
        $thread = true;
    }

}

?>

<!DOCTYPE HTML>

<html lang="de">

<head>

    <title>DevCord - Devmarkt</title>

    <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.png">
    <link rel="stylesheet" href="assets/css/style.css">

    <meta charset="utf-8"/>
    <meta name="description" content="Interface des Devmarktes für den DevCord-Discord. Hier kannst du Anfragen in den Devmarkt schicken, die vor Veröffentlichung geprüft werden."/>
    <meta name="author" content="T1Il"/>
    <meta name="copyright" content="T1Il"/>

    <meta property="og:title" content="DevCord Devmarkt für Developer und Serverbetreiber"/>
    <meta property="og:description" content="Interface des Devmarktes für den DevCord-Discord. Hier kannst du Anfragen in den Devmarkt schicken, die vor Veröffentlichung geprüft werden."/>
    <meta property="og:site_name" content="DevCord Devmarkt"/>
    <meta property="og:image" content="<?php echo $base_url; ?>/assets/img/favicon.png">

</head>

<body>

<div class="form">

    <form method="POST">

        <input type="text" name="reason" placeholder="Grund" minlength="10" maxlength="500">
        <br><br>
        <input type="checkbox" name="thread" placeholder="Thread erstellen" <?php if($thread == false) echo 'disabled'; ?> checked>  <b>Thread erstellen</b>
        <br><br>
        <input type="submit" value="Grund absenden">

    </form>

</div>

<br>
<br>
<br>
<br>
<br>
<br>
<br>

</body>

<script
    src="https://code.jquery.com/jquery-3.3.1.min.js"
    integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
    crossorigin="anonymous"></script>

<script>

    function checkInput() {

        var desc = document.getElementById('desc');
        var xhttp = new XMLHttpRequest();

        $.post("<?php echo getenv("BOT_BASE_URI") . '/strlen.php'; ?> ",{text:desc.value}, function(data,status) {

            $("#length").html(data + "/1000");
            if(desc.value.length >= 1000) {

                alert('Text zu lang!');
                return false;

            } else return true;

        });

    }

    function changeColor() {

        color = document.getElementById("color");

        color.style.backgroundColor = color.value;

    }

</script>

</html>
