<?php
/**
 * Created by PhpStorm.
 * User: trd-staff
 * Date: 9/12/19
 * Time: 5:33 PM
 */

namespace anullihate\EnvyFactionCorePM\listeners;


use _64FF00\PurePerms\event\PPGroupChangedEvent;
use anullihate\EnvyFactionCorePM\FactionMain;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;

class EnvyChatListener implements Listener {

	private $main;
	public function __construct(FactionMain $main) {
		$this->main = $main;
	}

	public function onGroupChanged(PPGroupChangedEvent $event)
	{
		/** @var \pocketmine\IPlayer $player */
		$player = $event->getPlayer();

		if($player instanceof Player)
		{
			$levelName = $this->main->getConfig()->get("enable-multiworld-chat") ? $player->getLevel()->getName() : null;
			$nameTag = $this->main->eChatAPI->getNametag($player, $levelName);
			$player->setNameTag($nameTag);
		}
	}

	/**
	 * @param PlayerJoinEvent $event
	 * @priority HIGH
	 */
	public function onPlayerJoin(PlayerJoinEvent $event) {
		/** @var \pocketmine\Player $player */
		$player = $event->getPlayer();
		$levelName = $this->main->getConfig()->get("enable-multiworld-chat") ? $player->getLevel()->getName() : null;
		$nameTag = $this->main->eChatAPI->getNametag($player, $levelName);
		$player->setNameTag($nameTag);
	}

	/**
	 * @param PlayerChatEvent $event
	 * @priority HIGH
	 */
	public function onPlayerChat(PlayerChatEvent $event) {
		if ($event->isCancelled()) return;
		$player = $event->getPlayer();
		$message = $event->getMessage();
		$levelName = $this->main->getConfig()->get("enable-multiworld-chat") ? $player->getLevel()->getName() : null;
		$chatFormat = $this->main->eChatAPI->getChatFormat($player, $message, $levelName);
		$event->setFormat($chatFormat);
	}
}
