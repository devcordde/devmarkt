<?php

include_once('pdo.php');
require getenv('APP_PATH') . '/vendor/autoload.php';
include_once('login.inc.php');

class Devmarkt
{

    private MySQL $mysql;
    private PDO $pdo;

    private User $moderator;

    public function __construct($moderator)
    {

        $this->mysql = new MySQL();
        $this->pdo = $this->mysql->getPDO();

        $this->moderator = $moderator;

    }

    public function getUnresolvedRequests()
    {

        $pdo = $this->pdo;

        $stmt = "SELECT id,by_discord_id,title,req_id,date FROM `anfragen` WHERE `status`='unprocessed' ORDER BY `date`";
        $qry = $pdo->prepare($stmt);
        $qry->execute();

        return $qry->fetchAll();

    }

}

