<?php
include_once('../php/pdo.php');
include_once('../php/checklogin.php');
include_once('../php/token.inc.php');
include_once('../php/login.inc.php');
include_once('../php/request.inc.php');

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
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/styles.css">
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

    <style>
        .case-card { border-left: 4px solid <?php echo '#' . $accent_color; ?>; }
    </style>

</head>
<body>

<?php

if ($request->valid) {

    ?>

    <div class="case-page">

    <a href="index.php" class="case-back">&larr; Zurück zur Übersicht</a>

    <?php if (!$as->inBotGuild()) { ?>
        <div class="case-warning">Nutzer nicht mehr auf dem Discord!</div>
    <?php } ?>

    <!-- Member Info -->
    <div class="case-card case-member">
        <div class="case-member-header">
            <img class="case-avatar" src="<?php echo $as->getAvatarURL(); ?>" alt="Avatar">
            <div class="case-member-name">
                <?php echo $as->getUsername(); ?>
                <?php if($as->isModerator()) { ?><span class="angenommen">(Moderator)</span><?php } ?>
            </div>
        </div>
        <div class="case-meta">
            <div><span class="case-accent">Discord-ID:</span> <?php echo $as->getDiscordId(); ?></div>
            <div><span class="case-accent">Request-ID:</span> <?php echo $request->getRequestId(); ?></div>
            <div><span class="case-accent">Eingesendet am:</span> <strong><?php echo date("d.m.y - H:i:s", $request->getDate()); ?></strong></div>
            <div><span class="case-accent">Everyone-Ping:</span> <span class="<?php echo $request->pingsEveryone() ? "warning-ping" : ""; ?>"><?php echo $request->pingsEveryone() ? "Ja" : "Nein"; ?></span></div>
            <div><span class="case-accent">Status:</span> <span class="case-status-<?php echo testInput($sta[0]); ?>"><?php echo testInput($sta[0]); ?></span></div>
            <div>
                <span class="case-accent">Nutzer-Status:</span> <?php echo $as->isBlocked() ? "blockiert" : "nicht blockiert"; ?>
                <?php if(!$as->isModerator()) { ?>
                <button onclick="window.location.href='user.php?block_user=<?php echo $as->getDiscordId(); ?>&from=<?php echo $request->getRequestId();?>';"
                        class="<?php echo $as->isBlocked() ? "reject" : "accept"; ?>-button offset"><?php echo $as->isBlocked() ? "Freigeben" : "Blockieren"; ?></button>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- Request Content -->
    <div class="case-card">
        <div class="case-title"><?php echo testInput($request->getTitle()); ?></div>

        <?php if ($request->hasURL()) { ?>
            <div class="case-url"><span class="case-accent">Link:</span> <a href="<?php echo testInput($request->getURL()); ?>" target="_blank" rel="noopener"><?php echo testInput($request->getURL()); ?></a></div>
        <?php } ?>

        <div class="case-content-grid">
            <div class="case-content-panel">
                <div class="case-panel-label">Unformatiert</div>
                <div class="case-content-raw"><?php echo nl2br($request->getDescription()); ?></div>
            </div>
            <div class="case-content-panel">
                <div class="case-panel-label">Vorschau</div>
                <div class="case-content-preview md-preview"></div>
            </div>
        </div>
    </div>

    <?php

    if(!$request->isProcessed() && !$as->inBotGuild()) {

        ?>

        <div class="case-actions">
            <button type="button" onclick="window.location.href='process.php?action=silent-decline&req_id=<?php echo $request->getRequestId(); ?>';"
                    class="reject-button offset">Ablehnen (ohne Benachrichtigung)</button>
        </div>

        <?php

    } else if (!$request->isProcessed() && $as->inBotGuild()) { ?>

        <div class="case-actions">
            <button type="button" onclick="window.location.href='process.php?action=accept&req_id=<?php echo $request->getRequestId(); ?>';"
                    class="accept-button offset">Annehmen</button>
            <button type="button" onclick="window.location.href='process.php?action=decline&req_id=<?php echo $request->getRequestId(); ?>';"
                    class="reject-button offset">Ablehnen</button>
        </div>

    <?php } else {

        $mod = $request->getProcessor();

        ?>

        <!-- Processor Info -->
        <div class="case-card case-member">
            <div class="case-member-header">
                <img class="case-avatar" src="<?php echo $mod->getAvatarURL(); ?>" alt="Avatar">
                <div class="case-member-name">
                    <?php echo $mod->getUsername(); ?>
                    <?php if($mod->isModerator()) { ?><span class="angenommen">(Moderator)</span><?php } ?>
                </div>
            </div>
            <div class="case-meta">
                <div><span class="case-accent">Discord-ID:</span> <?php echo $mod->getDiscordId(); ?></div>
                <div><span class="case-accent">Bearbeitet am:</span> <strong><?php echo date("d.m.y - H:i:s", $request->getProcessedDate()); ?></strong></div>
            </div>
        </div>

        <?php if (!$request->isAccepted() && $request->isProcessed()) { ?>

            <div class="case-card">
                <div class="case-panel-label">Begründung</div>
                <p><?php echo testInput($request->getReason()); ?></p>
            </div>

        <?php } ?>

        <?php
    } ?>

    </div>

    <script>
    // Render Discord markdown in the preview panel
    (function() {
        var preview = document.querySelector('.case-content-preview');
        if (!preview) return;
        var text = <?php echo json_encode(html_entity_decode($request->getDescription(), ENT_QUOTES, 'UTF-8')); ?>;
        preview.innerHTML = renderDiscordMarkdown(text);

        function escapeHtml(t) {
            var d = document.createElement('div');
            d.appendChild(document.createTextNode(t));
            return d.innerHTML;
        }

        function renderDiscordMarkdown(text) {
            text = escapeHtml(text);
            text = text.replace(/```(\w*)\n?([\s\S]*?)```/g, function(_, l, c) { return '<pre><code>' + c + '</code></pre>'; });
            text = text.replace(/`([^`]+)`/g, '<code>$1</code>');
            text = text.replace(/\|\|(.+?)\|\|/g, '<span class="md-spoiler">$1</span>');
            text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
            text = text.replace(/\*(.+?)\*/g, '<em>$1</em>');
            text = text.replace(/__(.+?)__/g, '<u>$1</u>');
            text = text.replace(/~~(.+?)~~/g, '<del>$1</del>');
            text = text.replace(/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');
            text = text.replace(/^### (.+)$/gm, '<h5>$1</h5>');
            text = text.replace(/^## (.+)$/gm, '<h4>$1</h4>');
            text = text.replace(/^# (.+)$/gm, '<h3>$1</h3>');
            text = text.replace(/^-# (.+)$/gm, '<sub>$1</sub>');
            text = text.replace(/^&gt;&gt;&gt; ([\s\S]+)$/gm, '<blockquote>$1</blockquote>');
            text = text.replace(/^&gt; (.+)$/gm, '<blockquote>$1</blockquote>');
            text = text.replace(/^- (.+)$/gm, '<li>$1</li>');
            text = text.replace(/^\d+\. (.+)$/gm, '<li class="ol">$1</li>');
            text = text.replace(/((?:<li>.*<\/li>\n?)+)/g, '<ul>$1</ul>');
            text = text.replace(/((?:<li class="ol">.*<\/li>\n?)+)/g, function(m) { return '<ol>' + m.replace(/ class="ol"/g, '') + '</ol>'; });
            text = text.replace(/\n/g, '<br>');
            text = text.replace(/(<\/h[345]>)<br>/g, '$1');
            text = text.replace(/(<\/ul>)<br>/g, '$1');
            text = text.replace(/(<\/ol>)<br>/g, '$1');
            text = text.replace(/(<\/pre>)<br>/g, '$1');
            text = text.replace(/(<\/blockquote>)<br>/g, '$1');
            text = text.replace(/(<\/sub>)<br>/g, '$1');
            text = text.replace(/<br>(<ul>)/g, '$1');
            text = text.replace(/<br>(<ol>)/g, '$1');
            text = text.replace(/<\/blockquote><br><blockquote>/g, '<br>');
            return text;
        }
    })();
    </script>

<?php } ?>

</body>
</html>
