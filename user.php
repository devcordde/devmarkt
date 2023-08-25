<?php

include_once('pdo.php');
include_once('php/login.inc.php');
include_once('php/token.inc.php');
include_once('php/checklogin.php');
$mysql = new MySQL();

if (!check()) {
    (isset($_GET['user_id'])) ? header('Location: login.php?redirect=' . getenv("BOT_BASE_URI") . '/user.php?user_id=' . $_GET['user_id']) : header('Location: login.php');
}
///
$token = new UserTokenHandler($_SESSION['token']);
$login = new User($token->getDiscordID());

if (isset($_GET['block_user'])) {

    if ($login->isModerator()) {

        $blockID = testInput($_GET['block_user']);
        $blockUser = new User($blockID);

        $blockUser->switchBlockState();

        if (isset($_GET['from'])) {

            header('Location: case.php?req_id=' . $_GET['from']);
            return;

        }

        header('Location: user.php?user_id=' . $_GET['block_user']);
        return;

    }

}

if (!isset($_GET['user_id']) || !$login->isModerator()) {
    header('Location: index.php');
}

$idUser = new User($_GET['user_id']);

$accent_color = getAverage($idUser->getAvatarURL());

?>

<!DOCTYPE html>
<html lang="de">
<head>

    <title>DevCord - Devmarkt</title>

    <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.png">
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
    <meta property="og:image" content="<?php echo getenv("BOT_BASE_URI"); ?>/assets/img/favicon.png">

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
<div class="container">
    <div class="user-info-box" style="width: 100%;">
        <div class="user-avatar">
            <img src="<?php echo $idUser->getAvatarURL(); ?>" alt="User Avatar">
        </div>
        <div class="user-details">
            <div class="user-name accent"><?php echo $idUser->getUsername();
                if ($idUser->isModerator()) { ?> <span class="angenommen">(Moderator)</span> <?php } ?> </div>
            <br>
            <div class="user-discord-id"><span class="accent">Discord-ID:</span> <?php $discordId = $idUser->getDiscordId();
                echo $discordId; ?>
            </div>
            <div class="user-status" <?php echo $idUser->isBlocked() ? "abgelehnt" : "angenommen"; ?>><span
                        class="accent">Nutzer-Status: </span><?php echo $idUser->isBlocked() ? "blockiert " : "nicht blockiert "; ?>
                <button onclick="window.location.href='user.php?block_user=<?php echo $discordId; ?>';"
                        class="<?php echo $idUser->isBlocked() ? "reject" : "accept"; ?>-button offset"><?php echo $idUser->isBlocked() ? "Freigeben" : "Blockieren"; ?>
                </button>
            </div>
        </div>
    </div>
    <div class="user-requests">

        <?php

        $pdo = $mysql->getPDO();
        $stmt = 'SELECT * FROM `anfragen` WHERE `by_discord_id`=:user ORDER BY `id` DESC';
        $qry = $pdo->prepare($stmt);
        $qry->bindParam(":user", $discordId, PDO::PARAM_STR);
        $qry->execute();

        $st = $qry->fetchAll();

        foreach ($st as $request) {

            ?>

            <div class="request">
                <div class="request-info">
                    <div class="request-date">Datum: <?php echo  date("d.m.y - h:i:s",$request['date']); ?></div>
                    <div class="request-status <?php echo testInput($request['status']); ?>">Status: <?php echo testInput($request['status']); ?></div>
                </div>
                <div class="request-title"><?php echo testInput($request['title']); ?>
                    <button onclick="window.location.href='case.php?req_id=<?php echo $request['req_id']; ?>';"
                           class="neutral-button offset">Details
                    </button></div>
            </div>

        <?php

        }

        ?>
    </div>
</div>
</body>
</html>

<?php
function testInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

function getAverage($sourceURL){

    $image = imagecreatefrompng($sourceURL);
    $scaled = imagescale($image, 1, 1, IMG_BICUBIC);
    $index = imagecolorat($scaled, 0, 0);
    $rgb = imagecolorsforindex($scaled, $index);
    $red = round(round(($rgb['red'] / 0x33)) * 0x33);
    $green = round(round(($rgb['green'] / 0x33)) * 0x33);
    $blue = round(round(($rgb['blue'] / 0x33)) * 0x33);
    return sprintf('%02X%02X%02X', $red, $green, $blue);
}

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