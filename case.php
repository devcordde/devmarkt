<?php
include_once('pdo.php');
require 'vendor/autoload.php';
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
if (!$login->inBotGuild()) {
    header('Location: ' . getenv("GUILD_INVITE"));
}

if (!$login->isModerator() && ($login->getDiscordId() != $request->getApplicant()->getDiscordId())) {
    header('Location: index.php?error=no_permission');
}

function testInput($data)
{
    return htmlspecialchars(trim($data));
}

if (!$request->valid) {

    header('Location: index.php?error=wrong_req_id');

}
$as = $request->getApplicant();
$active = true;

if (!$as->inBotGuild()) {
    echo '<p><b>Nutzer nicht mehr auf dem Discord!</b></p><br>';
    $active = false;
}
$sta = explode(":", $request->getStatus());

$accent_color = dechex($request->getColor());


function getContrastColor($hexColor)
{
    // hexColor RGB
    $R1 = hexdec(substr($hexColor, 1, 2));
    $G1 = hexdec(substr($hexColor, 3, 2));
    $B1 = hexdec(substr($hexColor, 5, 2));

    // Black RGB
    $blackColor = "#000000";
    $R2BlackColor = hexdec(substr($blackColor, 1, 2));
    $G2BlackColor = hexdec(substr($blackColor, 3, 2));
    $B2BlackColor = hexdec(substr($blackColor, 5, 2));

    // Calc contrast ratio
    $L1 = 0.2126 * pow($R1 / 255, 2.2) +
        0.7152 * pow($G1 / 255, 2.2) +
        0.0722 * pow($B1 / 255, 2.2);

    $L2 = 0.2126 * pow($R2BlackColor / 255, 2.2) +
        0.7152 * pow($G2BlackColor / 255, 2.2) +
        0.0722 * pow($B2BlackColor / 255, 2.2);

    $contrastRatio = 0;
    if ($L1 > $L2) {
        $contrastRatio = (int)(($L1 + 0.05) / ($L2 + 0.05));
    } else {
        $contrastRatio = (int)(($L2 + 0.05) / ($L1 + 0.05));
    }

    // If contrast is more than 5, return black color
    if ($contrastRatio > 5) {
        return '#000000';
    } else {
        // if not, return white color.
        return '#FFFFFF';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/styles.css">
    <meta charset="utf-8"/>
    <meta name="description"
          content="Interface des Devmarktes für den DevCord-Discord. Hier kannst du Anfragen in den Devmarkt schicken, die vor Veröffentlichung geprüft werden."/>
    <meta name="author" content="T1Il"/>
    <meta name="copyright" content="T1Il"/>

    <meta property="og:title" content="DevCord Devmarkt für Developer und Serverbetreiber"/>
    <meta property="og:description"
          content="Interface des Devmarktes für den DevCord-Discord. Hier kannst du Anfragen in den Devmarkt schicken, die vor Veröffentlichung geprüft werden."/>
    <meta property="og:site_name" content="DevCord Devmarkt"/>
    <meta property="og:image" content="assets/img/favicon.png">
    <title>Case <?php echo testInput($_GET['req_id']); ?></title>

    <?php
    $contrast_color = getContrastColor("#" . $accent_color)
    ?>

    <style>
        .accent {
            color: <?php echo '#' . $accent_color; ?>;
        }

        .divider {
            background-color: <?php echo '#' . $accent_color; ?>;
        }

        .user-name {
            border-color: <?php echo '#' . $accent_color; ?>;
        }

        .user-info-box,
        .big-box,
        .box-title,
        .box-text,
        .box-url {
            background-color: <?php echo $contrast_color; ?>;
            color: <?php echo getContrastColor($contrast_color); ?>
        }

        .user-info-box,
        .big-box {
            border-color: <?php echo '#' . $accent_color; ?>;
        }

    </style>

</head>
<body>

<?php

if ($request->valid) {

    ?>

    <div class="container">
    <div class="user-info-box">
        <div class="user-avatar">
            <img src="<?php echo $as->getAvatarURL(); ?>" alt="User Avatar">
        </div>
        <div class="user-details">
            <div class="user-name accent"><?php echo $as->getUsername(); if($as->isModerator()) { ?> <span class="angenommen">(Moderator)</span> <?php } ?> </div>
            <br>
            <div class="user-discord-id"><span class="accent">Discord-ID:</span> <?php echo $as->getDiscordId(); ?>
            </div>
            <div class="user-request-id"><span class="accent">Request-ID:</span> <?php echo $request->getRequestId(); ?>
            </div>
            <div class="user-submitted-date"><span class="accent">Eingesendet am:</span>
                <strong><?php echo date("d.m.y - H:i:s", $request->getDate()); ?></strong></div>
            <div class="user-everyone-ping"><span
                        class="accent">Everyone-Ping:</span> <span class="<?php echo $request->pingsEveryone() ? "warning-ping" : "normal-ping"; ?>"><?php echo $request->pingsEveryone() ? "Ja" : "Nein"; ?></span>
            </div>
            <div class="user-status <?php echo testInput($sta[0]); ?>"><span
                        class="accent">Status: </span><span class="unprocessed"><?php echo testInput($sta[0]); ?></span> </div>
            <div class="user-status" <?php echo testInput($sta[0]); ?>><span class="accent">Nutzer-Status: </span><?php echo $as->isBlocked() ? "blockiert " : "nicht blockiert "; ?>
            <?php if(!$as->isModerator()) { ?><button onclick="window.location.href='user.php?block_user=<?php echo $as->getDiscordId(); ?>&from=<?php echo $request->getRequestId();?>';"
                    class="<?php echo $as->isBlocked() ? "reject" : "accept"; ?>-button offset"><?php echo $as->isBlocked() ? "Freigeben" : "Blockieren"; ?>
            </button>
                <?php } ?>
            </div>
        </div>
    </div>
    <div class="big-box">
    <div class="box-title"><?php echo $request->getTitle(); ?></div>
    <div class="divider accent"></div>
    <div class="box-text"><?php echo nl2br($request->getDescription()); ?></div>
    <?php
    if ($request->hasURL()) {
        ?>
        <div class="divider accent"></div>
        <div class="box-url"><?php echo testInput($request->getURL()); ?></div>
        <?php
    }
    ?>

    <?php

    if(!$request->isProcessed() && !$as->inBotGuild()) {

        ?>

        <div class="button-container offset">
            <button onclick="window.location.href='process.php?action=silent-decline&req_id=<?php echo $request->getRequestId(); ?>';"
                    class="reject-button offset">Ablehnen (ohne Benachrichtigung)
            </button>
        </div>

        <?php

    } else if (!$request->isProcessed() && $as->inBotGuild()) { ?>

        <div class="button-container">
            <button onclick="window.location.href='process.php?action=accept&req_id=<?php echo $request->getRequestId(); ?>';"
                    class="accept-button offset">Annehmen
            </button>
            <button onclick="window.location.href='process.php?action=decline&req_id=<?php echo $request->getRequestId(); ?>';"
                    class="reject-button offset">Ablehnen
            </button>
        </div>
        </div>

    <?php } else {

        $mod = $request->getProcessor();

        ?>



        </div>
        <br>
        <div class="user-info-box dist">
            <div class="user-avatar">
                <img src="<?php echo $mod->getAvatarURL(); ?>" alt="User Avatar">
            </div>
            <div class="user-details">
                <div class="user-name accent"><?php echo $mod->getUsername(); if($as->isModerator()) { ?> <span class="angenommen">(Moderator)</span> <?php } ?></div>
                <br>
                <div class="user-discord-id"><span class="accent">Discord-ID:</span> <?php echo $mod->getDiscordId(); ?>
                </div>
                <div class="user-submitted-date"><span class="accent">Bearbeitet am:</span>
                    <strong><?php echo date("d.m.y - H:i:s", $request->getProcessedDate()); ?></strong></div>
            </div>
        </div>

        <?php if (!$request->isAccepted() && $request->isProcessed()) { ?>

            <div class="user-info-box dist">
                <div class="user-reason"><span class="accent">Begründung:</span> <?php echo $request->getReason(); ?>
                </div>
            </div>

        <?php } ?>

        </div>

        <?php
    } ?>

    </div>

<?php } ?>

</body>
</html>
