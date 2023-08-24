<?php
include_once('pdo.php');
include_once('php/login.inc.php');
include_once('php/token.inc.php');
include_once('php/checklogin.php');
include_once('php/request.inc.php');
include_once('php/devmarkt.class.php');
$mysql = new MySQL();
$base_url = getenv("BOT_BASE_URI");
$discordInvite = getenv('GUILD_INVITE');
?><!DOCTYPE HTML>
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

    <script src="assets/js/index.js"></script>

</head>

<body>

<div class="form">

<?php if(!check()) { ?>

        <h3>Logge<br> dich<br> ein</h3>
        <a href="login.php"><button>Login</button></a>

<?php } else {

    $token = new UserTokenHandler($_SESSION['token']);
    $login = new User($token->getDiscordID());

    $request_template = array("Title"=>"","Description"=>"","Color"=>"","Link"=>"");

    if(isset($_GET['requestID'])) {

        $request = new DevmarktRequest(htmlentities($_GET['requestID']));
        if($request->getApplicant()->getDiscordId() == $login->getDiscordId() || $login->isModerator()) {

            $request_template["Title"] = $request->getTitle();
            $request_template["Description"] = $request->getDescription();
            $request_template["Color"] = $request->getColor();
            $request_template["Link"] = $request->getURL();

        }

    }

    if($login->isModerator()) {

        $devmarkt = new Devmarkt($login);
        $unresolvedRequests = $devmarkt->getUnresolvedRequests();
        $uRS = "";

        if(sizeof($unresolvedRequests) > 0) {
            ?>
            <h5>Nicht bearbeitete Anfragen:</h5>
            <?php
        }

        ?>

    <?php

        foreach($unresolvedRequests as $request) {

            $request = new DevmarktRequest($request['req_id']);
            if($request->getApplicant()->inBotGuild()) {
               $uRS .= "<a href=case.php?req_id=" . htmlentities($request->getRequestId()) . ">" . htmlentities($request->getTitle()) . "</a><br> ";
            } else {
                $uRS .= "<a style='color: grey;' href=case.php?req_id=" . htmlentities($request->getRequestId()) . ">" . htmlentities($request->getTitle()) . "</a><br> ";
            }
        }

        echo $uRS;

    }

    if (!$login->inBotGuild()) {

         ?>

         <h4><a href="<?php echo $discordInvite; ?>">Du musst dem Discord beitreten, um diesen Service zu nutzen.</a>
         </h4>

         <?php

     } else if ($login->isBlocked()) {

         ?>

    <h4>Der Devmarkt ist zurzeit leider nicht für dich verfügbar. Wende dich bei Fragen oder Problemen im Discord an das Administrations-Team oder schreibe uns eine <a href="impressum.html">E-Mail.</a></h4>

         <?php


     } else if ($login->isOnCoolDown() && !($login->isModerator())) {

         $request = new DevmarktRequest($login->getLastAcceptedEntry()[0]);
         $datum = date("d.m.y", $request->getProcessedDate());
         $cooldowndate = $request->getProcessedDate() + 86400 * 30;
         $cdd = date("d.m.y - H:i", $cooldowndate);

         ?>

         <h4>Deine Anfrage wurde zuletzt am <?php echo $datum; ?> bearbeitet. Warte bitte bis zum <?php echo $cdd; ?>
             (<a href="case.php?req_id=<?php echo $request->req_id; ?>">Letzter Eintrag</a>)<br>
             Wenn du glaubst, dass dies ein Fehler ist, wende dich bitte an das Devmarkt-Moderatoren-Team.
         </h4>

         <?php

     } else {

    ?>

    <form id="form" method="POST" action="php/devmarkt.inc.php">

        <?php

        $login->isOnCoolDown();

        ?>


        <h3 class="dv">Devmarkt-Anfrage einreichen</h3>
        <br>
        <h4>Hallo <?php echo htmlentities($login->getUsername()); ?>,</h4>
        <p><strong>bitte achte bei deiner Anfrage auf eine angemessene sprachliche Richtigkeit. <br>Als Hilfe kannst du das <a href="https://languagetool.org/">LanguageTool</a> nutzen.</strong></p>
		<br>
        <label>
            <input type="text" min="10" name="titel" minlength="5" maxlength="50" placeholder="Titel deiner Einreichung" value="<?php echo $request_template["Title"]; ?>">
        </label>
        <br>
        <br>
        <div style="display:flex">

            <label for="color"><?php
            $color = random_color();
            ?></label><input id="color" type="color" onchange="changeColor()" name="color" value="#<?php if($request_template["Color"] != "") { echo $request_template["Color"]; } else echo $color; ?>" style="background-color: #<?php echo $color; ?>">
        <select name="type">

            <option value="Suche">Suche</option>
            <option value="Gebot">Gebot</option>
            <option value="Sonstiges">Sonstiges</option>

        </select>
        </div>

        <br>
        <br>

        <textarea oninput="checkInput('<?php echo getenv("BOT_BASE_URI") . '/strlen.php'; ?>',<?php echo getenv("MAX_DESCRIPTION_SIZE") - 96; ?>)" id="desc" name="beschreibung" class="beschreibung" minlength="100" maxlength="<?php echo getenv("MAX_DESCRIPTION_SIZE")-96; ?>" required><?php echo $request_template['Description']; ?></textarea>

        <p id="length"></p>

        <br>
        <br>

        <input oninput="checkAdditionalURL()" id="additional_url" type="url" name="additional_link" placeholder="Link mit weiteren Informationen">
        <br><br>

        <?php

        if($login->isModerator()) {
            ?>
            <input type="checkbox" name="everyone" placeholder="Everyone" > @Everyone-Ping</input>
        <?php
        }

        ?>

        <br>
        <br>

        <button type="submit" class="send">Anfrage einreichen</button>

    </form>

</div>

<?php }
} ?>

<footer>
    <p><a href="https://github.com/T1Il/devcord_devmarkt/">T1Il</a> 20<?php echo date('y'); ?> (v1.5)</p>
<br>
<a href="impressum.html">Impressum</a>/<a href="datenschutz.html">Datenschutzerklärung</a>
</footer>


</body>

</html>
<?php

function random_color_part(): string
{
    return str_pad( dechex( mt_rand( 0, 255 ) ), 2, '0', STR_PAD_LEFT);
}

function random_color(): string
{
    return random_color_part() . random_color_part() . random_color_part();
}

?>