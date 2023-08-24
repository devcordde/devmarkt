<?php

use Wohali\OAuth2\Client\Provider\Discord;

require_once 'pdo.php';
require_once 'vendor/autoload.php';
require_once 'php/login.inc.php';

try {
    $provider = new Discord([
        'clientId' => getenv("BOT_CLIENT_ID"),
        'clientSecret' => getenv("BOT_CLIENT_SECRET"),
        'redirectUri' => getenv("BOT_REDIRECT_URI"),
    ]);

    $options = [
        'scope' => ['guilds', 'identify'],
    ];

    if (empty($_GET['code'])) {
        $authUrl = $provider->getAuthorizationUrl($options);
        $_SESSION['oauth2state'] = $provider->getState();

        if (isset($_GET['action'], $_GET['req_id'])) {
            $_SESSION['action'] = $_GET['action'];
            $_SESSION['req_id'] = $_GET['req_id'];
        } elseif (isset($_GET['redirect'])) {
            $_SESSION['redirect'] = $_GET['redirect'];
        }

        header('Location: ' . $authUrl);
        exit;
    }

    if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
        unset($_SESSION['oauth2state']);
        exit('Invalid state');
    }

    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code'],
    ]);

    $mysql = new MySQL();
    $pdo = $mysql->getPDO();

    $user = $provider->getResourceOwner($token);
    $login = new User($user->getId());

    if ($login->exists) {
        $login->login($token->getToken(), $token->getRefreshToken());

        if (isset($_SESSION['action'], $_SESSION['req_id'])) {
            $url = 'process.php?action=' . $_SESSION['action'] . '&req_id=' . $_SESSION['req_id'] . '&from=login';
            unset($_SESSION['action'], $_SESSION['req_id']);
        } elseif (isset($_SESSION['redirect'])) {
            $url = $_SESSION['redirect'];
            unset($_SESSION['redirect']);
        } else {
            $url = 'index.php';
        }

        header('Location: ' . $url);
        exit(1);
    }

    $login->register($token->getToken(), $token->getRefreshToken());
    header('Location: index.php');
    exit(1);

} catch(Exception $e) {
    header('Location: login.php');
    exit;
}
