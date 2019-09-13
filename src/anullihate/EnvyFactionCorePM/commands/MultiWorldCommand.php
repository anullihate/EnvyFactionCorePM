<?php

namespace anullihate\EnvyFactionCorePM\commands;


use anullihate\EnvyFactionCorePM\commands\subcommands\SubCommand;
use anullihate\EnvyFactionCorePM\commands\subcommands\TeleportSubcommand;
use anullihate\EnvyFactionCorePM\FactionMain;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;

class MultiWorldCommand extends Command implements PluginIdentifiableCommand {

	/** @var  FactionMain $plugin */
	public $plugin;
	/** @var SubCommand[] $subcommands */
	public $subcommands = [];

	public function __construct() {
		parent::__construct("envymultiworld", "EnvyMultiWorld commands", null, ["envymw"]);
		$this->plugin = FactionMain::getInstance();
		$this->registerSubcommands();
	}

	public function registerSubcommands() {
//		$this->subcommands["help"] = new HelpSubcommand;
//		$this->subcommands["create"] = new CreateSubcommand;
		$this->subcommands["teleport"] = new TeleportSubcommand;
//		$this->subcommands["list"] = new ListSubcommand;
//		$this->subcommands["load"] = new LoadSubcommand;
//		$this->subcommands["unload"] = new UnloadSubcommand;
//		$this->subcommands["delete"] = new DeleteSubcommand;
//		$this->subcommands["update"] = new UpdateSubcommand;
//		$this->subcommands["info"] = new InfoSubcommand;
//		$this->subcommands["gamerule"] = new GameruleSubcommand;
//		$this->subcommands["manage"] = new ManageSubcommand;
//		$this->subcommands["rename"] = new RenameSubcommand;
	}

	/**
	 * @param CommandSender $sender
	 * @param string $commandLabel
	 * @param array $args
	 *
	 * @return mixed|void
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		if(!isset($args[0])) {
			if($sender->hasPermission("mw.cmd")) {
				$sender->sendMessage('usage');
				return;
			}
			$sender->sendMessage('no perms');
			return;
		}
		if($this->getSubcommand($args[0]) === null) {
			$sender->sendMessage('usage');
			return;
		}
		if(!$this->checkPerms($sender, $args[0])) {
			$sender->sendMessage('no perms');
			return;
		}
		$name = $args[0];
		array_shift($args);
		/** @var SubCommand $subCommand */
		$subCommand = $this->subcommands[$this->getSubcommand($name)];
		$subCommand->executeSub($sender, $args, $this->getSubcommand($name));
	}

	/**
	 * @param string $name
	 *
	 * @return string|null $name
	 */
	public function getSubcommand(string $name) {
		switch ($name) {
//			case "help":
//			case "?":
//				return "help";
//			case "create":
//			case "generate":
//			case "new":
//				return "create";
			case "tp":
			case "teleport":
			case "move":
				return "teleport";
//			case "list":
//			case "ls":
//				return "list";
//			case "load":
//			case "ld":
//				return "load";
//			case "unload":
//			case "unld":
//				return "unload";
//			case "remove":
//			case "delete":
//			case "rm":
//			case "del":
//			case "dl":
//				return "delete";
//			case "update":
//			case "ue":
//				return "update";
//			case "info":
//			case "i":
//				return "info";
//			case "gamerule":
//			case "gr":
//			case "gamer":
//			case "grule":
//				return "gamerule";
//			case "manage":
//			case "mng":
//			case "mg":
//				return "manage";
//			case "rename":
//			case "rnm":
//			case "re":
//				return "rename";
		}
		return null;
	}

	/**
	 * @param CommandSender $sender
	 * @param string $command
	 * @return bool
	 */
	public function checkPerms(CommandSender $sender, string $command):bool {
		if($sender instanceof Player) {
			if(!$sender->hasPermission("envymw.cmd." . $this->getSubcommand($command))) {
				$sender->sendMessage('no perms');
				return false;
			} else {
				return true;
			}
		} else {
			return true;
		}
	}

	/**
	 * @return Server
	 */
	public function getServer(): Server {
		return Server::getInstance();
	}

	/**
	 * @return Plugin|FactionMain $multiWorld
	 */
	public function getPlugin(): Plugin {
		return FactionMain::getInstance();
	}
}
