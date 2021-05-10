<?php
include_once 'pdo.php';
require 'vendor/autoload.php';
include_once 'php/login.inc.php';

try {

$provider = new \Wohali\OAuth2\Client\Provider\Discord([
    'clientId' => getenv("BOT_CLIENT_ID"),
    'clientSecret' => getenv("BOT_CLIENT_SECRET"),
    'redirectUri' => getenv("BOT_REDIRECT_URI"),
]);

$options = [

  'scope'=> [

      'guilds',
      'identify',

    ],

];

if (!isset($_GET['code'])) {

    $authUrl = $provider->getAuthorizationUrl($options);
    $_SESSION['oauth2state'] = $provider->getState();

    if(isset($_GET['action'],$_GET['req_id'])) {

        $_SESSION['action'] = $_GET['action'];
        $_SESSION['req_id'] = $_GET['req_id'];

    } else if(isset($_GET['redirect'])) {

        $_SESSION['redirect'] = $_GET['redirect'];

    }

    header('Location: ' . $authUrl);

} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {

    try {

        $token = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        $mysql = new MySQL();
        $pdo = $mysql->getPDO();

        $user = $provider->getResourceOwner($token);
        $login = new User($user->getId());

        if ($login->exists) {

            $login->login($token->getToken(), $token->getRefreshToken());

            if(isset($_SESSION['action'],$_SESSION['req_id'])) {

                header('Location: process.php?action=' .$_SESSION['action'] . '&req_id=' . $_SESSION['req_id'] . '&from=login');
                unset($_SESSION['action'],$_SESSION['req_id']);
                exit(1);

            } else if(isset($_SESSION['redirect'])) {

                header('Location: ' . $_SESSION['redirect']);
                unset($_SESSION['redirect']);
                exit(1);

            }

            header('Location: index.php');

        } else {

            $login->register($token->getToken(), $token->getRefreshToken());
            header('Location: index.php');

        }

    } catch (Exception $e) {
        echo $e->getMessage();
        header('Location: login.php');
    }

}

} catch(Exception $e) {

    header('Location: login.php');

}