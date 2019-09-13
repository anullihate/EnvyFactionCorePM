<?php

declare(strict_types=1);

namespace anullihate\EnvyFactionCorePM;

use anullihate\EnvyFactionCorePM\api\EnvyChatAPI;
use anullihate\EnvyFactionCorePM\api\FactionAPI;
use anullihate\EnvyFactionCorePM\commands\MultiWorldCommand;
use anullihate\EnvyFactionCorePM\listeners\EnvyChatListener;
use anullihate\EnvyFactionCorePM\listeners\MultiWorldListener;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\block\Snow;
use pocketmine\math\Vector3;

class FactionMain extends PluginBase implements Listener {

	private static $instance;

	public $config;

	// API
	public $eChatAPI;
	public $factionAPI;

	public $purePerms;

    public $db;
    public $prefs;
    public $war_req = [];
    public $wars = [];
    public $war_players = [];
    public $antispam;
    public $purechat;
    public $factionChatActive = [];
    public $allyChatActive = [];

    public function onLoad() {
    	self::$instance = $this;
    	$this->saveDefaultConfig();
    	$this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);

    	$this->purePerms = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
		$this->loadAPI();
	}

	public function onEnable() {
        @mkdir($this->getDataFolder());
        if (!file_exists($this->getDataFolder() . "BannedNames.txt")) {
            $file = fopen($this->getDataFolder() . "BannedNames.txt", "w");
            $txt = "Admin:admin:Staff:staff:Owner:owner:Builder:builder:Op:OP:op";
            fwrite($file, $txt);
        }

        $this->getServer()->getCommandMap()->register("envymw", new MultiWorldCommand());

        $this->getServer()->getPluginManager()->registerEvents(new FactionListener($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EnvyChatListener($this), $this);
        $this->getServer()->getPluginManager()->registerEvents(new MultiWorldListener($this), $this);
        $this->antispam = $this->getServer()->getPluginManager()->getPlugin("AntiSpamPro");
        if (!$this->antispam) {
            $this->getLogger()->info("Add AntiSpamPro to ban rude Faction names");
        }
        $this->fCommand = new FactionCommands($this);
        $this->prefs = new Config($this->getDataFolder() . "prefs.yml", CONFIG::YAML, array(
            "MaxFactionNameLength" => 15,
            "MaxPlayersPerFaction" => 30,
            "OnlyLeadersAndOfficersCanInvite" => true,
            "OfficersCanClaim" => false,
            "PlotSize" => 25,
            "PlayersNeededInFactionToClaimAPlot" => 5,
            "PowerNeededToClaimAPlot" => 1000,
            "PowerNeededToSetOrUpdateAHome" => 250,
            "PowerGainedPerPlayerInFaction" => 50,
            "PowerGainedPerKillingAnEnemy" => 10,
            "PowerGainedPerAlly" => 100,
            "AllyLimitPerFaction" => 5,
            "TheDefaultPowerEveryFactionStartsWith" => 0,
            "EnableOverClaim" => true,
            "OverClaimCostsPower" => false,
            "ClaimWorlds" => [],
            "AllowChat" => true,
            "AllowFactionPvp" => false,
            "AllowAlliedPvp" => false,
            "EnableMap" => false,
            "MaxMapDistance" => 500
        ));
        $this->db = new \SQLite3($this->getDataFolder() . "FactionsPro.db");
        $this->db->exec("CREATE TABLE IF NOT EXISTS master (player TEXT PRIMARY KEY COLLATE NOCASE, faction TEXT, rank TEXT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS confirm (player TEXT PRIMARY KEY COLLATE NOCASE, faction TEXT, invitedby TEXT, timestamp INT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS motdrcv (player TEXT PRIMARY KEY, timestamp INT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS motd (faction TEXT PRIMARY KEY, message TEXT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS plots(faction TEXT PRIMARY KEY, x1 INT, z1 INT, x2 INT, z2 INT, world TEXT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS home(faction TEXT PRIMARY KEY, x INT, y INT, z INT, world VARCHAR);");
        $this->db = new \SQLite3($this->getDataFolder() . "FactionsPro.db");
        $this->db->exec("CREATE TABLE IF NOT EXISTS master (player TEXT PRIMARY KEY COLLATE NOCASE, faction TEXT, rank TEXT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS confirm (player TEXT PRIMARY KEY COLLATE NOCASE, faction TEXT, invitedby TEXT, timestamp INT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS alliance (player TEXT PRIMARY KEY COLLATE NOCASE, faction TEXT, requestedby TEXT, timestamp INT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS motdrcv (player TEXT PRIMARY KEY, timestamp INT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS motd (faction TEXT PRIMARY KEY, message TEXT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS plots(faction TEXT PRIMARY KEY, x1 INT, z1 INT, x2 INT, z2 INT, world TEXT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS home(faction TEXT PRIMARY KEY, x INT, y INT, z INT, world TEXT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS strength(faction TEXT PRIMARY KEY, power INT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS allies(ID INT PRIMARY KEY,faction1 TEXT, faction2 TEXT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS enemies(ID INT PRIMARY KEY,faction1 TEXT, faction2 TEXT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS alliescountlimit(faction TEXT PRIMARY KEY, count INT);");
        try {
            $this->db->exec("ALTER TABLE plots ADD COLUMN world TEXT default null");
            Server::getInstance()->getLogger()->info(TextFormat::GREEN . "FactionPro: Added 'world' column to plots");
        } catch (\ErrorException $ex) {
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        switch ($command->getName()) {
			case 'f':
				return $this->fCommand->onCommand($sender, $command, $label, $args);
			case 'shop':
				$sender->sendMessage('opening shop');
				return true;
			default:
				return false;
		}
    }

    public function onDisable() {
        if (isset($this->db)) $this->db->close();
    }

    private function loadAPI() {
		$this->eChatAPI = new EnvyChatAPI($this);
		$this->factionAPI = new FactionAPI($this);
	}

	public static function getInstance() : FactionMain {
    	return self::$instance;
	}
}
