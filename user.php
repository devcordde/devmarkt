<?php
include_once('pdo.php');
include_once('php/login.inc.php');
include_once('php/token.inc.php');
include_once('php/checklogin.php');
$mysql = new MySQL();

if (check()) {

    $token = new UserTokenHandler($_SESSION['token']);
    $login = new User($token->getDiscordID());

    if(isset($_GET['block_user'])) {

        if($login->isModerator()) {

            $blockID = testInput($_GET['block_user']);
            $blockUser = new User($blockID);

            $blockUser->switchBlockState();

            if(isset($_GET['from'])) {

                header('Location: case.php?req_id=' . $_GET['from']);
                return;

            }

        }

    }

} else {
    (isset($_GET['user_id'])) ? header('Location: login.php?redirect=' . getenv("BOT_BASE_URI") . '/user.php?user_id=' . $_GET['user_id']) : header('Location: login.php');
}

?>

    <!DOCTYPE HTML>

    <html lang="de">

    <head>

        <title>DevCord - Devmarkt</title>

        <link rel="shortcut icon" type="image/x-icon" href="assets/img/favicon.png">
        <link rel="stylesheet" href="assets/css/style.css">

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

    </head>

    <body>

    <div class="form">

        <?php

        if ($login->isModerator()) {

            if (isset($_GET['user_id'])) {

                $pdo = $mysql->getPDO();

                $bonk = testInput($_GET['user_id']);

                $stmt = 'SELECT * FROM `anfragen` WHERE `by_discord_id`=:user ORDER BY `id` DESC';
                $qry = $pdo->prepare($stmt);
                $qry->bindParam(":user", $bonk, PDO::PARAM_STR);
                $qry->execute();

                $st = $qry->fetchAll();

                foreach ($st as $s) {

                    $as = new User($s['by_discord_id']);

                    $sta = explode(":", $s['status']);

                        ?>

                        <details>

                            <summary><span class="<?php echo $sta[0]; ?>"><?php echo $sta[0]; ?></span> <span class="dettitle"><?php echo testInput($s['title']) . '</span> | ' . date("d.m.y - h:i:s",$s['date']); ?></summary>

                            <div class="detcontent">
                            <p>
                            <p>Fall-ID: <strong><?php echo testInput($s['req_id']); ?></strong></p>
                            <p>Titel: <strong><?php echo testInput($s['title']); ?></strong></p>
                            <br>
                            <p>Beschreibung: <strong><?php echo testInput($s['description']); ?></strong></p>
                            <br>
                            <?php

                            if ($s['link'] != '') {
                                ?>

                                <p>URL: <strong><?php echo testInput($s['link']); ?></strong></p>

                                <?php

                            }

                            ?>
                            <br>
                            <p>Von:
                                <strong><?php echo testInput($as->getUsername() . "#" . $as->getDiscriminator() . ' : ' . $as->getDiscordId()); ?></strong>
                            </p>
                            <br>
                            <p>Datum:
                                <strong><?php echo date("d.m.y - H:i:s", $s['date']); ?></strong>
                            </p>
                            <?php

                            if ($s['processed_by'] != 'unprocessed') {

                                ?>

                                <p>Bearbeitet am:
                                    <strong><?php echo date("d.m.y - H:i:s", $s['date_processed']); ?></strong>
                                </p>

                                <?php

                            }

                            ?>
                            <br>
                            <p>Status: <strong><?php echo testInput($sta[0]); ?></strong></p>
                            <p>Begründung: <strong><?php echo testInput($s['reason']); ?></strong></p>
                            <?php

                            if ($s['processed_by'] != 'unprocessed') {

                                $processor = new User($s['processed_by']);
                                ?>

                                <p>Bearbeitet von:
                                    <strong><?php echo $processor->getUsername() . '#' . $processor->getDiscriminator(); ?></strong>
                                </p>

                            <?php }
                            ?>
                            </p>

                            </div>
                        </details>
                        <br>
                        <br>
                        <br>

                        <?php

                    }

                }

            } else {
            header('Location: index.php');
        }

        ?>


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

    </html>
<?php
function testInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}
