<?php
session_start();
error_reporting(E_ALL ^ E_DEPRECATED);
$base_url = getenv("BOT_BASE_URI");
$devmarkt_anfragen = getenv("GUILD_DEVMARKT_REQUEST_CHANNEL");
$devmarkt = getenv("GUILD_DEVMARKT_CHANNEL");
$guild_id = getenv("GUILD_ID");
$moderator_id = getenv("GUILD_MOD_ID");
$redirect_uri = getenv("BOT_REDIRECT_URI");

class MySQL
{

    private $host, $database, $username, $password;

    private $pdo;

    public function __construct()
    {

        $this->host = getenv("MYSQL_HOST");
        $this->database = getenv("MYSQL_DATABASE");
        $this->username = getenv("MYSQL_USER");
        $this->password = getenv("MYSQL_PASSWORD");
        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
            PDO::ATTR_PERSISTENT => false,
        );
        $this->pdo = new PDO('mysql:host=' . $this->host . ';dbname=' . $this->database, $this->username, $this->password, $options);
        $this->loadTables();

    }

    public function loadTables()
    {

        $pdoConnection = $this->getPDO();

        $stmt = 'CREATE TABLE IF NOT EXISTS `dc_users` ( `id` INT NOT NULL AUTO_INCREMENT , `discord_id` VARCHAR(255) NOT NULL , `auth_code` VARCHAR(255) NOT NULL , `refresh_code` VARCHAR(255) NOT NULL , `rang` VARCHAR(50) NOT NULL , `login_token` VARCHAR(255),`blocked` BOOLEAN, PRIMARY KEY (`id`)) ENGINE = InnoDB;';
        $qry = $pdoConnection->prepare($stmt);
        $qry->execute();

        $stmt1 = 'CREATE TABLE `anfragen` ( `id` INT NOT NULL AUTO_INCREMENT , `by_discord_id` VARCHAR(255) NOT NULL , `title` VARCHAR(100) NOT NULL , `type` VARCHAR(20) NOT NULL , `description` VARCHAR(1200) NOT NULL , `link` VARCHAR(100) NOT NULL , `req_id` VARCHAR(100) NOT NULL , `status` VARCHAR(100) NOT NULL , `processed_by` VARCHAR(100) NOT NULL ,`message_id` VARCHAR(100),`date` VARCHAR(100),`date_processed` VARCHAR(100),`color` VARCHAR(100),`reason` VARCHAR(500),`options` VARCHAR(200), PRIMARY KEY (`id`)) ENGINE = InnoDB;';
        $qry1 = $pdoConnection->prepare($stmt1);
        $qry1->execute();

    }

    public function getPDO()
    {

        return $this->pdo;

    }

    public function close()
    {

        $this->pdo->close();

    }

    public function query($qry)
    {
        return $this->pdo->query($qry);

    }

    public function inTable($table, $column, $user)
    {

        $pdoConnection = $this->pdo;

        $stmt = "SELECT * FROM `" . $table . "` WHERE `" . $column . "`=:user";
        $qry = $pdoConnection->prepare($stmt);
        $qry->bindParam(":user", $user);
        $qry->execute();

        if ($qry->fetchColumn() == 0) {
            return false;
        }
        return true;
    }

}

?>
