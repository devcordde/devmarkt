<?php
include_once('pdo.php');

function check(): bool
{

    if (!isset($_SESSION['token'])) {
        return false;
    }

    $mysql = new MySQL();

    if (!$mysql->inTable('dc_users', 'login_token', $_SESSION['token'])) {
        return false;
    }

    return true;


}
