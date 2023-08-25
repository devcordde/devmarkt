<?php

include_once('pdo.php');
require getenv("APP_PATH") . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
class User
{

    public string $id;
    public string $discordId;
    public string $auth_code;
    public string $refresh_code;
    public string $rang;
    public ?string $login_token;
    public string $dmChannel;
    public string $botToken = '';

    public $thread;

    public bool $isBlocked;
    public bool $exists;

    public array $st;
    public array $guilds;

    public stdClass $user;

    public function __construct($discordId)
    {

        $this->botToken = getenv("BOT_TOKEN");
        $this->discordId = $discordId;
        $this->initializeUser();

    }

    public function initializeUser(): bool
    {

        $mysql = new MySQL();
        $pdo = $mysql->getPDO();

        if (!$mysql->inTable("dc_users", "discord_id", $this->discordId)) {
            $this->exists = false;
            return false;
        }

        $stmt = 'SELECT * FROM `dc_users` WHERE `discord_id`=:discordId';
        $qry = $pdo->prepare($stmt);
        $qry->bindParam(":discordId", $this->discordId);
        $qry->execute();

        $st = $qry->fetch();

        $this->st = $st;
        $this->auth_code = $st['auth_code'];
        $this->refresh_code = $st['refresh_code'];
        $this->rang = $st['rang'];
        $this->login_token = $st['login_token'];
        $this->isBlocked = $st['blocked'];

        if(empty($st['thread'])) {
            $this->thread = null;
        } else {
            $this->thread = $st['thread'];
        }

        $this->exists = true;
        $this->fetchUser();
        $pdo = null;
        return true;

    }

    public function fetchUser()
    {

        if (!$this->exists) {
            return false;
        }

        if (isset($this->user)) {
            return $this->user;
        }

        $client = new GuzzleHttp\Client();
        try {
            $res = $client->request('GET', 'https://discord.com/api/v8/users/' . $this->discordId, [
                'headers' => ['Authorization' => 'Bot ' . $this->botToken],
            ]);
        } catch (GuzzleException) {
            return null;
        }

        $this->user = json_decode($res->getBody());
        return json_decode($res->getBody());

    }

    public function isBlocked(): bool
    {
        if (!$this->exists) {
            return false;
        }
        return $this->isBlocked;
    }

    public function switchBlockState(): bool {

        if(!$this->exists) {
            return false;
        }

        if($this->isModerator()) {
            return false;
        }

        $mysql = new MySQL();
        $pdo = $mysql->getPDO();

        $stmt = "UPDATE `dc_users` SET `blocked`=" . ($this->isBlocked ? "0" : "1") . " WHERE `discord_id`=:id";
        $qry = $pdo->prepare($stmt);
        $qry->bindParam(":id", $this->discordId);
        $qry->execute();

        $this->isBlocked = !$this->isBlocked;

        return $this->isBlocked;

    }

    public function getLastAcceptedEntry()
    {

        $mysql = new MySQL();
        $pdo = $mysql->getPDO();

        $stmt = "SELECT req_id FROM `anfragen` WHERE `by_discord_id`=:id AND `status`='angenommen' ORDER BY `id` DESC LIMIT 1";
        $qry = $pdo->prepare($stmt);
        $qry->bindParam(":id", $this->discordId);
        $qry->execute();

        if ($qry->rowCount() == 1) {
            return $qry->fetch();
        } else {
            return null;
        }

    }

    public function isOnCoolDown(): bool
    {

        $mysql = new MySQL();
        $pdo = $mysql->getPDO();

        $stmt = "SELECT id,date_processed FROM `anfragen` WHERE `by_discord_id`=:id AND `status`='angenommen' ORDER BY `id` DESC LIMIT 1";
        $qry = $pdo->prepare($stmt);
        $qry->bindParam(":id", $this->discordId);
        $qry->execute();
        $pdo = null;

        if ($qry->rowCount() == 1) {

            $queryResult = $qry->fetch();
            $datum = $queryResult['date_processed'];
            $date = $datum;
            $now = time();

            if ($now - $date > (60 * 60 * 24 * 30)) {
                return false;
            }
            return true;

        } else {
            return false;
        }


    }

    public function login($auth_code, $refresh_token): bool
    {

        if (!$this->exists) {
            return false;
        }

        $token = sha1(time() . time());
        $_SESSION['token'] = $token;

        $mysql = new MySQL();
        $pdo = $mysql->getPDO();

        $stmt = 'UPDATE `dc_users` SET `login_token`=:token,`auth_code`=:auth_code,`refresh_code`=:refresh_code WHERE `discord_id`=:discord_id';
        $qry = $pdo->prepare($stmt);
        $qry->bindParam(":auth_code", $auth_code);
        $qry->bindParam(":refresh_code", $refresh_token);
        $qry->bindParam(":token", $token);
        $qry->bindParam(":discord_id", $this->discordId);
        return $qry->execute();

    }

    public function register($authCode, $refreshCode): bool
    {

        $mysql = new MySQL();
        $pdo = $mysql->getPDO();

        if ($mysql->inTable("dc_users", "discord_id", $this->discordId)) {
            return false;
        }

        $token = sha1(time() . md5($refreshCode));
        $stmt = 'INSERT INTO `dc_users`(`id`, `discord_id`, `auth_code`, `refresh_code`, `rang`, `login_token`,`blocked`,`thread`) VALUES (0,:discordId,:authCode,:refreshCode,"user",:loginToken,FALSE,`thread`)';
        $qry = $pdo->prepare($stmt);
        $qry->bindParam(":discordId", $this->discordId);
        $qry->bindParam(":authCode", $authCode);
        $qry->bindParam(":refreshCode", $refreshCode);
        $qry->bindParam(":loginToken", $token);

        $_SESSION['token'] = $token;
        return $qry->execute();

    }

    public function logout()
    {

        $mysql = new MySQL();
        $pdo = $mysql->getPDO();

        $_SESSION['token'] = sha1(time() . md5(time()));

        $stmt = 'UPDATE `dc_users` SET `login_token`=NULL WHERE `discord_id`="' . $this->discordId . '"';
        $qry = $pdo->prepare($stmt);
        $qry->execute();

    }

    public function getUsername()
    {
        return $this->fetchUser()->username;
    }

    public function sendDMMessage($content, $embed, $tts, $file)
    {
        $channel_id = $this->openDMChannel();
        $client = new Client();
        try {
            $res = $client->request("POST", "https://discord.com/api/v8/channels/" . $channel_id . "/messages", [
                'headers' => ['Authorization' => 'Bot ' . $this->botToken, 'Content-Type' => 'application/json'],
                'body' => json_encode([
                    'content' => $content,
                    'embed' => $embed,
                    'tts' => $tts,
                    'file' => $file
                ])
            ]);
        } catch (GuzzleException ) {
            return false;
        }
        return json_decode($res->getBody());
    }

    public function openDMChannel(): string
    {

        if (!$this->exists) {
            return "";
        }

        $client = new Client();

        $res = $client->request("POST", "https://discord.com/api/v8/users/@me/channels", [

            'headers' => ['Authorization' => 'Bot ' . $this->botToken, 'Content-Type' => 'application/json'],
            'body' => json_encode(['recipient_id' => $this->discordId]),

        ]);

        $dm_channel = json_decode($res->getBody())->id;
        $this->dmChannel = $dm_channel;

        return $dm_channel;


    }

    public function deleteMessage($channel, $message_id): bool
    {
        $client = new Client();
        try {
            $res = $client->request("DELETE", "https://discord.com/api/v8/channels/" . $channel . '/messages/' . $message_id, [
                'headers' => ['Authorization' => 'Bot ' . $this->botToken]
            ]);
            return true;
        } catch (GuzzleException) {
            return false;
        }
    }

    public function createRejectThread() {

        if($this->thread == null) {

            $mysql = new MySQL();
            $pdo = $mysql->getPDO();

            $rejectThread = $this->createThreadWithoutMessage(getenv("GUILD_DEVMARKT_CHANNEL"),"Devmarkt-Anfrage " . $this->getUsername() . "#" . $this->getDiscriminator());
            $stmt = "UPDATE `dc_users` SET `thread`=" . $rejectThread->id . " WHERE `discord_id`='" . $this->getDiscordId() . "'";
            $qry = $pdo->prepare($stmt);
            $qry->execute();

            $this->thread = $rejectThread->id;

            return $this->thread;

        } else return $this->thread;

    }

    public function createThreadWithMessage($channel, $message_id, $name) {

        $client = new Client();
        try {

            $res = $client->request("POST","https://discord.com/api/v9/channels" . $channel . '/messages/' . $message_id . '/threads', [
                'headers' => ['Authorization' => 'Bot ' . $this->botToken, 'Content-Type' => 'application/json'],
                'body'=>json_encode(["name"=>$name])
            ]);
            return json_decode($res->getBody());
        } catch(GuzzleException) {
            return null;
        }
        return false;
    }

    public function createThreadWithoutMessage($channel, $name) {

        $client = new Client();
        try {

            $res = $client->request("POST","https://discord.com/api/v9/channels/" . $channel . '/threads', [
                'headers' => ['Authorization' => 'Bot ' . $this->botToken,'Content-Type'=>'application/json'],
                'body'=>json_encode([
                    "name"=>$name,
                    "auto_archive_duration"=>1440,
                    "type"=>getenv("THREAD_TYPE"),
                    "invitable"=>0,
                ])
            ]);
            return json_decode($res->getBody());
        } catch(GuzzleException $e) {
            echo $e->getTraceAsString();
            echo $e->getMessage();
            return null;
        }
    }

    public function addMemberToThread($thread_id, $user_id) {

        $client = new Client();

        try {

            $res = $client->request("PUT","https://discord.com/api/v7/channels/" . $thread_id . "/thread-members/" . $user_id,
            ['headers'=> ['Authorization' => 'Bot ' . $this->botToken, 'Content-Type'=>'application/json']]);
            return json_decode($res->getBody());
        } catch(GuzzleException $e) {
            echo $e->getTraceAsString();
            echo $e->getMessage();
            return null;
        }

    }

    public function userThreadArchived(): bool
    {

        if($this->thread == null) {
            return false;
        }

        $client = new Client();
        try {

            $res = $client->request("GET","https://discord.com/api/v8/channels/" . $this->thread,
                ['headers'=> ['Authorization' => 'Bot ' . $this->botToken, 'Content-Type'=>'application/json']]);
            $thread = json_decode($res->getBody());

            if($thread->thread_metadata->archived) {
                return true;
            }

            return false;
        } catch(GuzzleException $e) {
            echo $e->getTraceAsString();
            echo $e->getMessage();
            echo 'Error';
        }
        return false;
    }

    public function hasRole($id, $guild)
    {
        $roles = $this->getRolesFromGuild($guild);
        if (!in_array($id, $roles)) {
            return false;
        }
        return true;
    }

    public function isModerator(): bool
    {
        if(!$this->exists) {
            return false;
        }
        if(!$this->inBotGuild()) {
            return false;
        }

        if(!$this->hasRole(getenv("GUILD_MOD_ID"),getenv("GUILD_ID"))) {
            return false;
        }

        return true;
    }

    public function getRolesFromGuild($id): array|bool
    {

        if (!$this->exists) {
            return false;
        }
        $client = new Client();
        $res = $client->request("GET", "https://discord.com/api/v8/guilds/" . $id . "/members/" . $this->discordId, [
            'headers' => ['Authorization' => 'Bot ' . $this->botToken]
        ]);
        return json_decode($res->getBody())->roles;
    }

    public function getAvatarURL(): string
    {
        if ($this->getAvatar() != null) {
            $avatar = 'https://cdn.discordapp.com/avatars/' . $this->getDiscordId() . '/' . $this->getAvatar() . '.png';
        } else {
            $discriminator = $this->getDiscriminator();
            $avatar_id = $discriminator % 5;
            $avatar = 'https://cdn.discordapp.com/embed/avatars/' . $avatar_id . '.png';
        }
        return $avatar;
    }

    public function getAvatar()
    {
        return $this->fetchUser()->avatar;
    }

    public function getDiscordId()
    {
        return $this->fetchUser()->id;

    }

    public function getDiscriminator()
    {
        return $this->fetchUser()->discriminator;
    }

    public function inBotGuild(): bool {

            $guild_id = getenv("GUILD_ID");
            $member_id = $this->getDiscordId();

        try {

            $client = new GuzzleHttp\Client();

            $res = $client->request('GET', 'https://discord.com/api/v8/guilds/' . $guild_id . '/members/' . $member_id, [
                'headers' => ['Authorization' => 'Bot ' . $this->botToken]
            ]);

            if($res->getStatusCode() != 200) return false;

            return true;

        } catch (Exception) {
            return false;
        }
    }
    public function inGuild($id): bool
    {
        try {
            $guilds = $this->getGuilds();
            foreach ($guilds as $guild) {
                if ($guild->id == $id) {
                    return true;
                }
            }
        } catch (Exception) {
          header('Location: login.php');
        }
        return false;
    }

    public function getGuilds()
    {
        return $this->fetchGuilds();
    }

    public function fetchGuilds()
    {

        if (!$this->exists) {
            return null;
        }
        if (isset($this->guilds)) {
            return $this->guilds;
        }

        $client = new GuzzleHttp\Client();

        $res = $client->request('GET', 'https://discord.com/api/v8/users/@me/guilds', [
            'headers' => ['Authorization' => 'Bearer ' . $this->auth_code]
        ]);

        $this->guilds = json_decode($res->getBody());
        return json_decode($res->getBody());

    }
}