<?php
include_once('pdo.php');
require 'inc/vendor/autoload.php';
include_once('php/checklogin.php');
include_once('php/token.inc.php');
include_once('php/login.inc.php');
include_once('php/request.inc.php');

if (!isset($_GET['req_id'])) {
    header('Location: index.php?error=nothing_set');
}

if (!check()) {
    header('Location: login.php?redirect=' . getenv("BOT_BASE_URI") . '/case.php?req_id=' . $_GET['req_id']);
}


$token = new UserTokenHandler($_SESSION['token']);
$login = new User($token->getDiscordID());

$request = new DevmarktRequest($_GET['req_id']);
if (!$login->inGuild(getenv("GUILD_ID"))) {
    header('Location: ' . getenv("GUILD_INVITE"));
}

if (!$login->isModerator() && ($login->getDiscordId() != $request->getApplicant()->getDiscordId())) {
    header('Location: index.php?error=no_permission');
}


function testInput($data)
{
    return htmlspecialchars(stripslashes(trim($data)));
}

?>

<!DOCTYPE html>

<html lang="de">

<head>
    <title>Case <?php echo testInput($_GET['req_id']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="form">

    <?php

    if ($request->valid) {
        $as = $request->getApplicant();
        $sta = explode(":", $request->getStatus());
        ?>
        <p>Fall-ID: <strong><?php echo $request->getRequestId(); ?></strong></p>
        <p>Titel: <strong><?php echo $request->getTitle(); ?></strong></p>
        <br>
        <p>Beschreibung: <strong><?php echo $request->getDescription(); ?></strong></p>
        <br>
        <?php

        if ($request->hasURL()) {
            ?>
            <p>URL: <strong><?php echo testInput($request->getURL()); ?></strong></p>
            <?php
        }
        ?>
        <br>
        <p>Von:
            <strong><?php echo testInput($as->getUsername() . "#" . $as->getDiscriminator() . ' : ' . $as->getDiscordId()); ?></strong>
        </p>
        <br>
        <p>Datum: <strong><?php echo date("d.m.y - H:i:s", $request->getDate()); ?></strong></p>
        <?php
        if ($request->isProcessed()) {
            ?>
            <p>Bearbeitet am: <strong><?php echo date("d.m.y - H:i:s", $request->getProcessedDate()); ?></strong></p>
            <?php
        }
        ?>
        <br>
        <p>Pingt @everyone: <strong><?php echo $request->pingsEveryone() ? "Ja" : "Nein"; ?></strong></p>
        <p>Status: <strong><?php echo testInput($sta[0]); ?></strong></p>
        <p>Begründung: <strong><?php echo $request->getReason(); ?></strong></p>
        <?php

        if ($request->isProcessed()) {
            $processor = $request->getProcessor();
            ?>

            <p>Bearbeitet von: <strong><?php echo $processor->getUsername() . '#' . $processor->getDiscriminator(); ?></strong></p>

            <a class="resend" href="index.php?requestID=<?php echo $request->getRequestId(); ?>">Erneut einsenden</a>

            <?php
        }
    }
    ?>

</div>

</body>

</html>
