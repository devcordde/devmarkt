<?php
include_once('../pdo.php');
include_once('login.inc.php');
include_once('token.inc.php');
include_once('request.inc.php');
include_once('checklogin.php');

$mysql = new MySQL();
$pdo = $mysql->getPDO();
$base_url = getenv('BOT_BASE_URI');
$location = 'Location: ' . $base_url;

if (!check()) {
    header($location . '/login.php');
}

$token = new UserTokenHandler($_SESSION['token']);
$login = new User($token->getDiscordID());

$devmarktRequestChannel = getenv('GUILD_DEVMARKT_REQUEST_CHANNEL');
$devmarktChannel = getenv('GUILD_DEVMARKT_CHANNEL');

if ($login->isBlocked()) {
    header('Location: index.php');
}

if ($login->isOnCoolDown()
    && !($login->isModerator())) {
    header('Location: index.php');
}

if (!(isset($_POST['titel'], $_POST['type'], $_POST['beschreibung'], $_POST['color']))) {
    header($location . '/error=fields');
}

if($_POST['titel'] == '' || $_POST['beschreibung'] == '') {
    header($location . '/index.php?error=fields');
}


$requestTitle = testInput($_POST['titel']);
$requestType = testInput($_POST['type']);
$requestDescription = testInput($_POST['beschreibung']);
$requestDescription = str_replace('@everyone', '', $requestDescription);
$requestId = substr(sha1(sha1($requestTitle . sha1(md5($requestType))) . time()), 30);
$requestColor = hexdec(testInput(str_replace('#','',$_POST['color'])));
$everyonePing = false;

if (!(strlen($requestTitle) < 51
    && strlen($requestType) < 21
    && strlen($requestDescription) < 1024
    && strlen($requestDescription) > 100)) {
    header($location . '/?error=size');
}

$request = new DevmarktRequest($requestId);
$url_field = null;
$url = '';

if (isset($_POST['additional_link']) && !empty($_POST['additional_link'])) {
    $url = testInput($_POST['additional_link']);
    $url_field = $request->generateField('URL', $url, false);
}

$avatar = $login->getAvatarURL();

if(isset($_POST['everyone'])
&& $_POST['everyone'] == 'on') {
    $everyonePing = true;
}

$clickTemplate = '[**KLICK**]';

if ($url_field != null) {
    $fields = [
        $request->generateField('Titel', html_entity_decode($requestTitle), true),
        $request->generateField('Beschreibung', $requestDescription, true),
        $request->generateField('Type', $requestType, true),
        $url_field,
        $request->generateField('Request-ID', $requestId, true),
        $request->generateField("Pingt @everyone", $everyonePing ? getenv("CHECK_EMOTE") : getenv("BLOCK_EMOTE"), false),
        $request->generateField('Nutzerinformationen', '[**Einsehen**](' . getenv('BOT_BASE_URI') . '/user.php?user_id=' . $login->getDiscordId() . ')', false),
        $request->generateField('Annehmen', "$clickTemplate($base_url/process.php?action=accept&req_id=$requestId)", false),
        $request->generateField('Ablehnen', "$clickTemplate($base_url/process.php?action=decline&req_id=$requestId)", false),
    ];
} else {
    $fields = [
        $request->generateField('Titel', html_entity_decode($requestTitle), true),
        $request->generateField('Beschreibung', $requestDescription, true),
        $request->generateField('Type', $requestType, true),
        $request->generateField('Request-ID', $requestId, true),
        $request->generateField("Pingt @everyone", $everyonePing ? getenv("CHECK_EMOTE") : getenv("BLOCK_EMOTE"), false),
        $request->generateField('Nutzerinformationen', '[**Einsehen**](' . getenv('BOT_BASE_URI') . '/user.php?user_id=' . $login->getDiscordId() . ')', false),
        $request->generateField('Annehmen', '[**KLICK**](' . $base_url . '/process.php?action=accept&req_id=' . $requestId . ')', false),
        $request->generateField('Ablehnen', '[**KLICK**](' . $base_url . '/process.php?action=decline&req_id=' . $requestId . ')', false),
    ];
}
$embed = $request->generateEmbed('Neue Devmarkt-Anfrage',
    '**Neue Anfrage von ' . $login->getUsername() . '#' . $login->getDiscriminator() . ' : ' . $login->getDiscordId() . '**',
    $fields,
    true,
    $login->getUsername() . '#' . $login->getDiscriminator() . ' : ' . $login->getDiscordId(),
    $avatar,
    $requestColor,
    null,
    date('c'));

$stmt = 'INSERT INTO `anfragen`(`id`, `by_discord_id`, `title`, `type`, `description`, `link`, `req_id`, `status`, `processed_by`,`message_id`,`date`,`date_processed`,`color`,`reason`,`options`) VALUES 
         (0,:discordId,:requestTitle,:requestType,:requestDescription,:requestUrl,:requestId,"unprocessed","unprocessed",NULL,:time,NULL,:requestColor,NULL,:options)';
$qry = $pdo->prepare($stmt);
$discordID = $login->getDiscordId();
$time = time();
$requestOptions = $everyonePing ? "everyone" : "NULL";
$pdoOptions = $everyonePing ? PDO::PARAM_STR : PDO::PARAM_NULL;
$qry->bindParam(':discordId', $discordID);
$qry->bindParam(':requestTitle', $requestTitle);
$qry->bindParam(':requestType', $requestType);
$qry->bindParam(':requestDescription', $requestDescription);
$qry->bindParam(':requestUrl', $url);
$qry->bindParam(':requestId', $requestId);
$qry->bindParam(':requestColor', $requestColor);
$qry->bindParam(":time", $time);
$qry->bindParam(":options",$requestOptions,$pdoOptions);
$res = $qry->execute();

if ($res == 1) {

    $res = sendMessage($devmarktRequestChannel, '<@&' . getenv("GUILD_MOD_ID") .'>', $embed, false);

    $json = json_decode($res->getBody());
    $id = $json->id;

    $stmt2 = 'UPDATE `anfragen` SET `message_id`=:messageId WHERE `req_id`=:requestId';
    $qry2 = $pdo->prepare($stmt2);
    $qry2->bindParam(':messageId', $id);
    $qry2->bindParam(':requestId', $requestId);
    $res = $qry2->execute();

    if ($res == 1) {
        header($location . '/success.php');
    } else {
        header($location . '/error.php?error=' . print_r($qry2->errorInfo()));
    }

} else {
    header($location . '/error.php?error=' . print_r($qry->errorInfo()));
}

function testInput($data): string
{

    return htmlspecialchars(stripslashes(trim($data)));
}

function sendMessage($channel, $content, $embed, $tts)
{

    $client = new GuzzleHttp\Client();

    $body = json_encode([
        'content' => $content,
        'embed' => $embed,
        'tts' => $tts,
    ]);

    return $client->request('POST', 'https://discordapp.com/api/v6/channels/' . $channel . '/messages', [
            'headers' => [
                'Authorization' => 'Bot ' . getenv('BOT_TOKEN'),
                'Content-Type' => 'application/json'
            ],
            'body' => $body,
        ]
    );

}