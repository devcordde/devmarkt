<?php

use JetBrains\PhpStorm\Pure;

include_once('pdo.php');
require_once 'vendor/autoload.php';
include_once('php/checklogin.php');
include_once('php/token.inc.php');
include_once('php/login.inc.php');
include_once('php/request.inc.php');
$client = new \GuzzleHttp\Client();

if (isset($_POST['access_token'], $_POST['req_id'], $_POST['action'], $_POST['moderator_id'])) {

    $access_key = getenv('BOT_ACCESS_TOKEN');
    $req_id = $_POST['req_id'];
    $action = $_POST['action'];
    $moderator = $_POST['moderator_id'];

    $req_id = substr($req_id,1,10);

    if ($_POST['access_token'] != $access_key) {
        echo 'wrong access key';
        exit();
    }

    $request = new DevmarktRequest($req_id);

    if (!$request->valid
        || $request->isProcessed()) {
        exit();
    }
    $login = new User($moderator);
    if ($login->isModerator()) {
        if ($action == "accept") {
            $request->acceptRequest($login);
        } else if ($action == "decline") {
            if (isset($_POST['reason'])) {
                $request->rejectRequest($login, $_POST['reason'], true, false);
            }
        } else if($action == "silent-decline") {
                $request->rejectRequest($login, "Nutzer nicht mehr auf dem DevCord" ,false, true);
        }
    }

} else if (isset($_GET['action'], $_GET['req_id'])) {

    if (!check()) {
        header('Location: login.php?req_id=' . testInput($_GET['req_id']) . '&action=' . testInput($_GET['action']));
    }

    if (isset($_GET['from'])) {
        unset($_SESSION['req_id']);
        unset($_SESSION['action']);
    }

    $token = new UserTokenHandler($_SESSION['token']);
    $login = new User($token->getDiscordID());

    $req_id = testInput($_GET['req_id']);
    $request = new DevmarktRequest($req_id);

    if (!$login->isModerator()) {
        header('Location: index.php?error=perm');
    }

    $status = testInput($_GET['action']);
    $status = str_replace('accept', 'angenommen', $status);

    $mysql = new MySQL();
    $pdo = $mysql->getPDO();

    $stmt = 'SELECT * FROM `anfragen` WHERE `req_id`="' . testInput($_GET['req_id']) . '"';
    $qry = $pdo->prepare($stmt);
    $qry->execute();

    $st = $qry->fetch();

    if($qry->rowCount() <= 0) {

        echo "Keine Anfrage mit dieser ID gefunden.";
        exit();

    }

    if ($request->isProcessed()) {
        $processor = new User($st['processed_by']);
        echo 'Diese Anfrage wurde bereits von ' . $processor->getUsername() . '#' . $processor->getDiscriminator() . ' bearbeitet.';
        exit();
    }

    $at = new User($st['by_discord_id']);

    if(!$at->inBotGuild() && $status == "angenommen") {
        header('Location: case.php?req_id=' . $req_id . "&msg=left");
    }

    if ($status == 'angenommen') {
        $request->acceptRequest($login);
    } else if ($status == 'decline') {
        if (isset($_POST['reason'])) {
            if(isset($_POST['thread'])) {
                    $req = $client->request("GET", "https://discord.com/api/v8/guilds/" . getenv("GUILD_ID"),[
                        "headers"=>["Authorization"=>"Bot " . getenv("BOT_TOKEN")]
                    ]);

                    if(json_decode($req->getBody())->premium_tier >= 2 || in_array("PARTNERED", json_decode($req->getBody())->features)) {
                        $request->rejectRequest($login, $_POST['reason'], true, false);
                        return;
                    }
                    $request->rejectRequest($login, $_POST['reason'], false, false);
            } else {
                $request->rejectRequest($login, $_POST['reason'],false, false);
            }
        } else {
            include('reason.php');
        }

    } else if($status == 'silent-decline') {
        $request->rejectRequest($login, "Nutzer nicht mehr auf dem Discord", false, true);
    }

}

#[Pure] function testInput($data): string
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

    return $client->request("POST", "https://discord.com/api/v8/channels/" . $channel . "/messages", [
            'headers' => [
                'Authorization' => 'Bot ' . getenv("BOT_TOKEN"),
                'Content-Type' => 'application/json'
            ],
            'body' => $body,]
    );

}
