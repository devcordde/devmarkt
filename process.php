<?php

use JetBrains\PhpStorm\Pure;

include_once('pdo.php');
require 'vendor/autoload.php';
include_once('php/checklogin.php');
include_once('php/token.inc.php');
include_once('php/login.inc.php');
include_once('php/request.inc.php');

if (isset($_GET['action'], $_GET['req_id'])) {

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

    if ($request->isProcessed()) {
        $processor = new User($st['processed_by']);
        echo 'Diese Anfrage wurde bereits von ' . $processor->getUsername() . '#' . $processor->getDiscriminator() . ' bearbeitet.';
        exit();
    }

    $at = new User($st['by_discord_id']);

    if ($status == 'angenommen') {
        $request->acceptRequest($login);
    } else if ($status == 'decline') {
        if (isset($_POST['reason'])) {
            if(isset($_POST['thread'])) {
                $request->rejectRequest($login, $_POST['reason'], true);
            } else {
                $request->rejectRequest($login, $_POST['reason'],false);
            }
        } else {
            echo file_get_contents('reason.php');
        }

    }

} else
    if (isset($_POST['access_token'], $_POST['req_id'], $_POST['action'], $_POST['moderator_id'])) {

        $access_key = getenv('BOT_ACCESS_TOKEN');
        $req_id = $_POST['req_id'];
        $action = $_POST['action'];
        $moderator = $_POST['moderator_id'];

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
                    $request->rejectRequest($login, $_POST['reason']);
                }
            }
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

    return $client->request("POST", "https://discordapp.com/api/v6/channels/" . $channel . "/messages", [
            'headers' => [
                'Authorization' => 'Bot ' . getenv("BOT_TOKEN"),
                'Content-Type' => 'application/json'
            ],
            'body' => $body,]
    );

}
