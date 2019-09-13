<?php

namespace anullihate\EnvyFactionCorePM\commands\subcommands;


use anullihate\EnvyFactionCorePM\FactionMain;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;

class TeleportSubcommand implements SubCommand {

	/**
	 * @param CommandSender $sender
	 * @param array $args
	 * @param string $name
	 * @return mixed|void
	 */
	public function executeSub(CommandSender $sender, array $args, string $name) {
		try {
			if(!isset($args[0])) {
//				LanguageManager::getMsg($sender, "teleport-usage");
				return;
			}
			if(!$this->getServer()->isLevelGenerated($args[0])) {
//				$sender->sendMessage(LanguageManager::getMsg($sender, "teleport-levelnotexists", [$args[0]]));
				return;
			}
			if(!$this->getServer()->isLevelLoaded($args[0])) {
				$this->getServer()->loadLevel($args[0]);
			}
			$level = $this->getServer()->getLevelByName($args[0]);
			if(!isset($args[1])) {
				if(!$sender instanceof Player) {
//					$sender->sendMessage(MultiWorld::getPrefix().LanguageManager::getMsg($sender, "teleport-usage"));
					return;
				}
				$sender->teleport($level->getSafeSpawn());
//				$sender->sendMessage(MultiWorld::getPrefix().LanguageManager::getMsg($sender, "teleport-done-1", [$level->getName()]));
				return;
			}
			$player = $this->getServer()->getPlayer($args[1]);
			if((!$player instanceof Player) || !$player->isOnline()) {
//				$sender->sendMessage(MultiWorld::getPrefix().LanguageManager::getMsg($sender, "teleport-playernotexists"));
				return;
			}
			$player->teleport($level->getSafeSpawn());
//			$player->sendMessage(MultiWorld::getPrefix() . LanguageManager::getMsg($sender, "teleport-done-1", [$level->getName()]));
//			$sender->sendMessage(LanguageManager::getMsg($sender, "teleport-done-2", [$level->getName(), $player->getName()]));
			return;
		}
		catch (\Exception $exception) {
			FactionMain::getInstance()->getLogger()->error("An error occurred while teleporting player between worlds: " . $exception->getMessage() . " (at line: " . $exception->getLine() . " , file: ". $exception->getFile() .")");
		}
	}
	/**
	 * @return Server $server
	 */
	private function getServer(): Server {
		return Server::getInstance();
	}
}
