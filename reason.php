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

$req = $client->request("GET", "https://discord.com/api/v8/guilds/" . getenv("GUILD_ID"),[
    "headers"=>["Authorization"=>"Bot " . getenv("BOT_TOKEN")]
]);

if(json_decode($req->getBody())->premium_tier >= 2 || in_array("PARTNERED", json_decode($req->getBody())->features)) {
    $thread = true;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/styles.css">
    <title>DevCord - Devmarkt</title>
    <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.png">

    <meta charset="utf-8"/>
    <meta name="description" content="Interface des Devmarktes für den DevCord-Discord. Hier kannst du Anfragen in den Devmarkt schicken, die vor Veröffentlichung geprüft werden."/>
    <meta name="author" content="T1Il"/>
    <meta name="copyright" content="T1Il"/>

    <meta property="og:title" content="DevCord Devmarkt für Developer und Serverbetreiber"/>
    <meta property="og:description" content="Interface des Devmarktes für den DevCord-Discord. Hier kannst du Anfragen in den Devmarkt schicken, die vor Veröffentlichung geprüft werden."/>
    <meta property="og:site_name" content="DevCord Devmarkt"/>
    <meta property="og:image" content="<?php echo getenv("BOT_BASE_URI"); ?>/assets/img/favicon.png">
</head>
<body>
    <form method="POST">
    <div class="input-box">
        <div class="box-title">Begründung</div>
        <input type="text" class="reason-input" name="reason" minlength="10" maxlength="500" placeholder="Gib hier deine Begründung ein...">
        <div class="thread-checkbox">
            <input type="checkbox" id="thread-checkbox" name="thread" <?php if($thread == false) echo 'disabled'; ?> checked>
            <label for="thread-checkbox" class="checkbox-label">Thread erstellen</label>
        </div>
        <div class="button-container">
            <button type="submit" class="submit-button">Absenden</button>
        </div>
</div>
    </form>
</body>
</html>
