<?php
include_once 'pdo.php';
include_once 'php/login.inc.php';
include_once 'php/checklogin.php';
include_once 'php/token.inc.php';

if(check()) {

    $token = new UserTokenHandler($_SESSION['token']);
    $login = new User($token->getDiscordID());
    $login->logout();
    header('Location: index.php');

} else {
    header('Location: index.php');
}