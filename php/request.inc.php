<?php

use JetBrains\PhpStorm\Pure;

include_once('pdo.php');
require getenv('APP_PATH') . '/vendor/autoload.php';
include_once('login.inc.php');

class DevmarktRequest
{

    public string $req_id;
    public User $applicant;

    public string $applicant_id;
    public string $processor_id;

    public bool $valid;
    public string $processed;
    public string $status;
    public string $reason;

    public MySQL $mysql;
    public PDO $pdo;
    public array $request;

    public string $caseUrl;

    public string $checkEmote;
    public string $blockEmote;

    public function __construct($req_id)
    {

        $this->req_id = $req_id;
        $this->mysql = new MySQL();
        $this->pdo = $this->mysql->getPDO();
        $this->initializeRequest();

        $this->caseUrl = getenv('BOT_BASE_URI') . '/case.php?req_id=' . $this->testInput($this->req_id);

        $this->checkEmote = getenv("CHECK_EMOTE");
        $this->blockEmote = getenv("BLOCK_EMOTE");

    }

    function initializeRequest()
    {

        $requestId = $this->testInput($this->req_id);
        $stmt = 'SELECT * FROM `anfragen` WHERE `req_id`=:req_id';
        $qry = $this->pdo->prepare($stmt);
        $qry->bindParam(':req_id', $requestId);
        $qry->execute();

        if ($qry->rowCount() < 1) {
            $this->valid = false;
        } else {
            $this->valid = true;
            $this->request = $qry->fetch();
            $this->applicant_id = $this->request['by_discord_id'];
            $this->applicant = new User($this->applicant_id);

        }

    }

    #[Pure] function testInput($data): string
    {
        return htmlspecialchars(stripslashes(trim($data)));
    }

    function acceptRequest($login): bool
    {
        $applicant = $this->getApplicant();
        $dmEmbed = $this->generateDMEmbed(true, $login);
        $acceptsDMs = true;

        if (!$applicant->sendDMMessage(null, $dmEmbed, false, null)) {
            echo 'Nutzer nimmt keine DM-Nachrichten an. <a href="' . $this->caseUrl . '">Case</a>';
            $acceptsDMs = false;
        }
        $devmarktRequestEmbed = $this->generateProcessedEmbed(true, $login, $acceptsDMs);
        $messageContent = '<@' . $applicant->getDiscordId() . '>';

        if ($applicant->isModerator()) {
                $messageContent .= "\n" . ($this->pingsEveryone() ? "@everyone" : "");
        }

        $devmarktEmbed = $this->generateDevmarktEmbed();
        sendMessage(getenv('GUILD_DEVMARKT_REQUEST_CHANNEL'), null, $devmarktRequestEmbed, false);
        $res = sendMessage(getenv('GUILD_DEVMARKT_CHANNEL'), $messageContent, $devmarktEmbed, false);
        $login->deleteMessage(getenv('GUILD_DEVMARKT_REQUEST_CHANNEL'), $this->getMessageID());

        $json = json_decode($res->getBody());
        $messageId = $this->getMessageID() . ':' . $json->id;

        if (!$this->updateRequest("angenommen", $login->getDiscordID(), time(), $messageId)) {
            return false;
        }

        header('Location: ' . $this->caseUrl);
        return true;
    }

    function getApplicant(): ?User
    {
        if (!$this->valid) {
            return null;
        }

        if (isset($this->applicant)) {
            return $this->applicant;
        }

        $this->applicant = new User($this->getApplicantID());
        return $this->applicant;


    }

    function getApplicantID(): ?string
    {
        if ($this->valid) {
            $this->applicant_id = $this->request['by_discord_id'];
            return $this->applicant_id;
        } else {
            return null;
        }
    }

    function generateDMEmbed(bool $accepted, User $login): array
    {

        $embedTitle = ($accepted ? "Devmarkt-Anfrage angenommen" : "Devmarkt-Anfrage abgelehnt");
        $embedColor = ($accepted ? '3bd323' : 'f40909');
        $embedReason = ($accepted ? "Devmarkt-Anfrage angenommen." : $this->request['reason']);
        $embedCheck = ($accepted ? "Anfrage einsehen" : "Erneut einreichen und bearbeiten");
        $embedCheckValue = ($accepted ? '[**KLICK**](' . $this->caseUrl . ')' : '[**KLICK**](' . getenv("BOT_BASE_URI") . "/index.php?requestID=" . $this->getRequestId() . ')');

        return $this->generateEmbed(

            $embedTitle,
            null,
            [

                $this->generateField("Begründung", $embedReason, true),
                $this->generateField("Leitfaden", "[ **KLICK** ](https://discord.com/channels/486161636105650176/486921119513706496/489096136192163842)", true),
                $this->generateField("Bearbeitet von", $this->getUserInfo($login), false),
                $this->generateField($embedCheck, $embedCheckValue, true)

            ],
            true,
            $this->getUserAndDisc($login),
            $login->getAvatarURL(),
            $this->getColorByHex($embedColor),
            ['url' => $login->getAvatarURL()],
            date('c')
        );

    }

    function generateEmbed($title, $description, $fields, $footer, $footer_text, $footer_icon_url, $color, $thumbnail, $timestamp): array
    {

        $footer_array = null;
        if ($footer) {
            $footer_array = array("text" => $footer_text, "icon_url" => $footer_icon_url);
        }

        return array("title" => $title, "description" => $description, "thumbnail" => $thumbnail, "color" => $color, "footer" => $footer_array, "fields" => $fields, "timestamp" => $timestamp);

    }

    function generateField($name, $value, $inline): array
    {

        return [
            'name' => $name,
            'value' => $value,
            'inline' => $inline,
        ];

    }

    function getUserInfo($user): string
    {
        return $user->getUsername() . '#' . $user->getDiscriminator() . ' => ' . $user->getDiscordID();
    }

    function getUserAndDisc($user): string
    {
        return $user->getUsername() . '#' . $user->getDiscriminator();
    }

    #[Pure] function getColorByHex($color): float|int
    {
        return hexdec($color);
    }

    function generateProcessedEmbed($accepted, $login, $acceptsDMs): array
    {

        $title = ($accepted ? 'Devmarkt-Anfrage angenommen' : 'Devmarkt-Anfrage abgelehnt');
        $acceptsDMString = $acceptsDMs ? $this->checkEmote : $this->blockEmote;
        $color = ($accepted ? '3bd323' : 'f40909');

        return $this->generateEmbed($title, null, [
            $this->generateField('Bearbeitet von', $this->getUserInfo($login), false),
            $this->generateField('Anfragesteller', $this->getUserInfo($this->getApplicant()), false),
            $this->generateField('Akzeptiert DMs', $acceptsDMString, false),
            $this->generateField('Pingt @everyone', $this->pingsEveryone() ? $this->checkEmote : $this->blockEmote, false),
            $this->generateField('Case', '[**KLICK**](' . getenv('BOT_BASE_URI') . '/case.php?req_id=' . $this->testInput($this->req_id) . ')', true),
            $this->generateField('Nutzerinformationen', '[**KLICK**](' . getenv('BOT_BASE_URI') . '/user.php?user_id=' . $this->getApplicant()->getDiscordId() . ')', true),
        ],
            true,
            $this->getUserAndDisc($login),
            $login->getAvatarURL(),
            $this->getColorByHex($color),
            array('url' => $this->getApplicant()->getAvatarURL()),
            date('c')
        );

    }

    function pingsEveryone(): bool
    {

        if (!$this->valid
            || !str_contains($this->getOptions(), 'everyone')) {
            return false;
        }

        if(!$this->getApplicant()->isModerator()) {
            return false;
        }

        return true;
    }

    function getOptions(): ?string
    {

        if (!$this->valid) {
            return false;
        }
        if ($this->request['options'] == null) {
            return null;
        }

        return $this->request['options'];

    }

    function generateDevmarktEmbed(): array
    {

        $userApplicant = $this->applicant;
        $fields = $this->getURLFields();
        $requestTitle = html_entity_decode($this->getTitle());
        $requestColor = $this->getColor();
        $footerText = $this->getUserInfo($userApplicant);
        $footerIcon = $this->getApplicant()->getAvatarURL();
        $requestDescription = html_entity_decode($this->getDescription());

        return $this->generateEmbed($requestTitle, $requestDescription, $fields, true, $footerText, $footerIcon, $requestColor, null, null);
    }

    function getURLFields(): ?array
    {
        if (!$this->valid) {
            return null;
        }
        if (!$this->hasURL()) {
            return null;
        }

        return
            [$this->generateField('Link', $this->getURL(), false)];
    }

    function hasURL(): bool
    {
        if (!$this->valid) {
            return false;
        }
        if ($this->request["link"] == null) {
            return false;
        }
        return true;
    }

    function getURL()
    {

        if (!$this->valid) {
            return null;
        }
        return $this->request['link'];

    }

    function getTitle(): ?string
    {
        if (!$this->valid) {
            return null;
        }

        $this->request['title'] = html_entity_decode($this->request['title']);
        $this->request['title'] = str_replace('"', "", $this->request['title']);
        $this->request['title'] = str_replace("'", "", $this->request['title']);
        return $this->testInput($this->request['title']);

    }

    function getColor()
    {

        if (!$this->valid) {
            return null;
        }
        return $this->request['color'];
    }

    function getDescription(): ?string
    {
        if (!$this->valid) {
            return null;
        }

        $this->request['description'] = html_entity_decode($this->request['description']);
        $this->request['description'] = str_replace('"', "", $this->request['description']);
        $this->request['description'] = str_replace("'", "", $this->request['description']);
        return $this->testInput($this->request['description']);

    }

    function getMessageID()
    {
        if ($this->valid) {
            return $this->request['message_id'];
        }
        return null;
    }

    function updateRequest($status, $processedBy, $dateProcessed, $messageId): bool
    {

        $stmt = 'UPDATE `anfragen` SET `status`=:status,`date`=:datum,`date_processed`=:date_processed,`processed_by`=:processed_by,`message_id`=:messageid,`reason`=:reason WHERE `req_id`=:req_id';
        $qry = $this->pdo->prepare($stmt);
        $qry->bindValue(":status", $status);
        $qry->bindValue(":datum", $this->getDate());
        $qry->bindValue(":processed_by", $processedBy);
        $qry->bindValue(":date_processed", $dateProcessed);
        $qry->bindValue(":messageid", $messageId);
        $qry->bindValue(":req_id", $this->req_id);
        $qry->bindValue(":reason", $this->getReason());
        return $qry->execute();

    }

    function getDate()
    {
        if ($this->valid) {
            return $this->request['date'];
        }
    }

    #[Pure] function getReason(): ?string
    {

        if (!$this->valid
            && !$this->isProcessed()
            && $this->isAccepted()) {
            return null;
        }

        return $this->request['reason'];

    }

    #[Pure] function isProcessed(): bool
    {
        if (!$this->valid) {
            return false;
        }

        $processed_qry = $this->request['status'];
        if ($processed_qry == "unprocessed") {
            return false;
        }
    return true;
    }

    #[Pure] function isAccepted(): bool
    {
        if (!$this->valid
        || !($this->isProcessed())) {
            return false;
        }

        $processed_qry = $this->request['status'];
        if (!$this->startsWith($processed_qry, "angenommen")) {
            return false;
        }

        return true;
    }

    #[Pure] function startsWith($haystack, $needle): bool
    {
        $length = strlen($needle);
        return substr($haystack, 0, $length) === $needle;
    }

    function rejectRequest(User $login, $reason, $create_thread, $silent): bool
    {

        $this->request['reason'] = $this->testInput($reason);
        $at = $this->getApplicant();
        $acceptsDMs = true;
        $dateProcessed = time();

        $dmEmbed = $this->generateDMEmbed(false, $login);
        $delete = $login->deleteMessage(getenv("GUILD_DEVMARKT_REQUEST_CHANNEL"), $this->getMessageID());

        if (!$this->updateRequest("abgelehnt", $login->getDiscordID(), $dateProcessed, $this->getMessageID())) {
            return false;
        }

        if(!$silent) {

            if (!$at->sendDMMessage(null, $dmEmbed, false, null)) {
                echo 'Nutzer nimmt keine DM-Nachrichten an. Persönlich kontaktieren! <a href="' . $this->caseUrl . '">Case</a>';
                $acceptsDMs = false;
            }

            if($create_thread) {

                $thread_id = $this->getApplicant()->createRejectThread();

                try {
                    sendMessage($thread_id, null, $dmEmbed, null);
                } catch(Exception $e) {
                    $this->getApplicant()->thread = null;
                    $thread_id = $this->getApplicant()->createRejectThread();
                    sendMessage($thread_id, null, $dmEmbed, null);
                }
                try {

                    $login->addMemberToThread($thread_id, $this->getApplicantID());
                    $login->addMemberToThread($thread_id, $login->getDiscordId());
                    sendMessage($thread_id, "<@" . $this->getApplicant()->getDiscordId() .">", null, false);
                    $devmarktRequestEmbed = $this->generateProcessedEmbed(false, $login, $acceptsDMs);

                } catch(Exception $e) {

                    header('Location: ' . $this->caseUrl . "&error=" . urlencode($e->getMessage()));
                    return true;

                }

            }
        }

        $devmarktRequestEmbed = $this->generateProcessedEmbed(false, $login, $acceptsDMs);
        sendMessage(getenv("GUILD_DEVMARKT_REQUEST_CHANNEL"), null, $devmarktRequestEmbed, false);
        header('Location: ' . $this->caseUrl);
        return true;
    }

    #[Pure] function getRequestId(): string
    {
        return $this->testInput($this->req_id);
    }

    function getStatus(): ?string
    {

        if (!$this->valid) {
            return null;
        }
        $this->status = $this->request['status'];
        return $this->status;

    }

    function getProcessedDate()
    {
        if (!$this->valid) {
            return null;
        }

        return $this->request['date_processed'];
    }

    function getProcessor(): ?User
    {
        if (!$this->valid) {
            return null;
        }
        if (!$this->isProcessed()) {
            return null;
        }

        return new User($this->getProcessorID());
    }

    function getProcessorID(): ?string
    {
        if (!$this->valid) {
            return null;
        }
        if (!$this->isProcessed()) {
            return null;
        }

        $this->processor_id = $this->request['processed_by'];
        return $this->processor_id;
    }

}
