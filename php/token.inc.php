<?php
include_once('pdo.php');
include_once('login.inc.php');

class UserTokenHandler
{

    public string $token;
    public array $st;
    public bool $exists = false;
    private string $discord_id;
    private string $auth_code;
    private string $refresh_code;
    private string $rang;
    private string $login_token;

    public function __construct($token)
    {

        $mysql = new MySQL();
        $pdo = $mysql->getPDO();

        if (!$mysql->inTable("dc_users", "login_token", $token)) {
            $this->exists = false;
        }

        $this->token = $token;

        $stmt = 'SELECT * FROM `dc_users` WHERE `login_token`=:token';
        $qry = $pdo->prepare($stmt);
        $qry->bindParam(":token", $token);
        $qry->execute();

        $st = $qry->fetch();

        $this->auth_code = $st['auth_code'];
        $this->refresh_code = $st['refresh_code'];
        $this->rang = $st['rang'];
        $this->login_token = $st['login_token'];
        $this->discord_id = $st['discord_id'];

        $this->exists = true;


    }

    public function getUser() : ?User {
        return ($this->exists) ? new User($this->getDiscordID()) : null;
    }

    public function getDiscordID()
    {
        if (!$this->exists) {
            return null;
        }
        return $this->discord_id;
    }

}