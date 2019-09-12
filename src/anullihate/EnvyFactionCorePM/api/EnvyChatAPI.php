<?php
/**
 * Created by PhpStorm.
 * User: trd-staff
 * Date: 9/12/19
 * Time: 4:51 PM
 */

namespace anullihate\EnvyFactionCorePM\api;


use _64FF00\PurePerms\PPGroup;
use anullihate\EnvyFactionCorePM\FactionMain;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class EnvyChatAPI {

	private $main;

	public function __construct(FactionMain $main) {
		$this->main = $main;
	}

	/**
	 * @param $string
	 * @return mixed
	 */
	public function applyColors($string) {
		$string = str_replace("&0", TextFormat::BLACK, $string);
		$string = str_replace("&1", TextFormat::DARK_BLUE, $string);
		$string = str_replace("&2", TextFormat::DARK_GREEN, $string);
		$string = str_replace("&3", TextFormat::DARK_AQUA, $string);
		$string = str_replace("&4", TextFormat::DARK_RED, $string);
		$string = str_replace("&5", TextFormat::DARK_PURPLE, $string);
		$string = str_replace("&6", TextFormat::GOLD, $string);
		$string = str_replace("&7", TextFormat::GRAY, $string);
		$string = str_replace("&8", TextFormat::DARK_GRAY, $string);
		$string = str_replace("&9", TextFormat::BLUE, $string);
		$string = str_replace("&a", TextFormat::GREEN, $string);
		$string = str_replace("&b", TextFormat::AQUA, $string);
		$string = str_replace("&c", TextFormat::RED, $string);
		$string = str_replace("&d", TextFormat::LIGHT_PURPLE, $string);
		$string = str_replace("&e", TextFormat::YELLOW, $string);
		$string = str_replace("&f", TextFormat::WHITE, $string);
		$string = str_replace("&k", TextFormat::OBFUSCATED, $string);
		$string = str_replace("&l", TextFormat::BOLD, $string);
		$string = str_replace("&m", TextFormat::STRIKETHROUGH, $string);
		$string = str_replace("&n", TextFormat::UNDERLINE, $string);
		$string = str_replace("&o", TextFormat::ITALIC, $string);
		$string = str_replace("&r", TextFormat::RESET, $string);
		return $string;
	}

	/**
	 * @param $string
	 * @param Player $player
	 * @param $message
	 * @param null $levelName
	 * @return mixed
	 */
	public function applyPCTags($string, Player $player, $message, $levelName)
	{
		// TODO
		$string = str_replace("{display_name}", $player->getDisplayName(), $string);
		if($message === null)
			$message = "";
		if($player->hasPermission("pchat.coloredMessages"))
		{
			$string = str_replace("{msg}", $this->applyColors($message), $string);
		}
		else
		{
			$string = str_replace("{msg}", $this->stripColors($message), $string);
		}
		if($this->main->factionsAPI !== null)
		{
			$string = str_replace("{fac_name}", $this->main->factionsAPI->getPlayerFaction($player), $string);
			$string = str_replace("{fac_rank}", $this->main->factionsAPI->getPlayerRank($player), $string);
		}
		else
		{
			$string = str_replace("{fac_name}", '', $string);
			$string = str_replace("{fac_rank}", '', $string);
		}
		$string = str_replace("{world}", ($levelName === null ? "" : $levelName), $string);
		$string = str_replace("{prefix}", $this->getPrefix($player, $levelName), $string);
		$string = str_replace("{suffix}", $this->getSuffix($player, $levelName), $string);
		return $string;
	}

	/**
	 * @param Player $player
	 * @param $message
	 * @param null $levelName
	 * @return mixed
	 */
	public function getChatFormat(Player $player, $message, $levelName = null)
	{
		$originalChatFormat = $this->getOriginalChatFormat($player, $levelName);
		$chatFormat = $this->applyColors($originalChatFormat);
		$chatFormat = $this->applyPCTags($chatFormat, $player, $message, $levelName);
		return $chatFormat;
	}

	/**
	 * @param Player $player
	 * @param null $levelName
	 * @return mixed
	 */
	public function getNametag(Player $player, $levelName = null) {
		$originalNametag = $this->getOriginalNametag($player, $levelName);
		$nameTag = $this->applyColors($originalNametag);
		$nameTag = $this->applyPCTags($nameTag, $player, null, $levelName);
		return $nameTag;
	}

	/**
	 * @param Player $player
	 * @param null $levelName
	 * @return mixed
	 */
	public function getOriginalChatFormat(Player $player, $levelName = null)
	{
		/** @var \_64FF00\PurePerms\PPGroup $group */
		$group = $this->main->purePerms->getUserDataMgr()->getGroup($player, $levelName);
		if($levelName === null)
		{
			if($this->main->config->getNested("groups." . $group->getName() . ".chat") === null)
			{
				$this->getLogger()->critical("Invalid chat format found in config.yml (Group: " . $group->getName() . ") / Setting it to default value.");
				$this->main->config->setNested("groups." . $group->getName() . ".chat", "&8&l[" . $group->getName() . "]&f&r {display_name} &7> {msg}");
				$this->main->config->save();
				$this->main->config->reload();
			}
			return $this->main->config->getNested("groups." . $group->getName() . ".chat");
		}
		else
		{
			if($this->main->config->getNested("groups." . $group->getName() . "worlds.$levelName.chat") === null)
			{
				$this->getLogger()->critical("Invalid chat format found in config.yml (Group: " . $group->getName() . ", WorldName = $levelName) / Setting it to default value.");
				$this->main->config->setNested("groups." . $group->getName() . "worlds.$levelName.chat", "&8&l[" . $group->getName() . "]&f&r {display_name} &7> {msg}");
				$this->main->config->save();
				$this->main->config->reload();
			}
			return $this->main->config->getNested("groups." . $group->getName() . "worlds.$levelName.chat");
		}
	}

	public function getOriginalNametag(Player $player, $levelName = null)
	{
		/** @var \_64FF00\PurePerms\PPGroup $group */
		$group = $this->main->purePerms->getUserDataMgr()->getGroup($player, $levelName);
		if($levelName === null)
		{
			if($this->main->config->getNested("groups." . $group->getName() . ".nametag") === null)
			{
				$this->main->getLogger()->critical("Invalid nametag found in config.yml (Group: " . $group->getName() . ") / Setting it to default value.");
				$this->main->config->setNested("groups." . $group->getName() . ".nametag", "&8&l[" . $group->getName() . "]&f&r {display_name}");
				$this->main->config->save();
				$this->main->config->reload();
			}
			return $this->main->config->getNested("groups." . $group->getName() . ".nametag");
		}
		else
		{
			if($this->main->config->getNested("groups." . $group->getName() . "worlds.$levelName.nametag") === null)
			{
				$this->main->getLogger()->critical("Invalid nametag found in config.yml (Group: " . $group->getName() . ", WorldName = $levelName) / Setting it to default value.");
				$this->main->config->setNested("groups." . $group->getName() . "worlds.$levelName.nametag", "&8&l[" . $group->getName() . "]&f&r {display_name}");
				$this->main->config->save();
				$this->main->config->reload();
			}
			return $this->main->config->getNested("groups." . $group->getName() . "worlds.$levelName.nametag");
		}
	}

	/**
	 * @param Player $player
	 * @param null $levelName
	 * @return mixed|null|string
	 */
	public function getPrefix(Player $player, $levelName = null)
	{
		if($levelName === null)
		{
			return $this->main->purePerms->getUserDataMgr()->getNode($player, "prefix");
		}
		else
		{
			$worldData = $this->main->purePerms->getUserDataMgr()->getWorldData($player, $levelName);
			if(!isset($worldData["prefix"]) || $worldData["prefix"] === null)
				return "";
			return $worldData["prefix"];
		}
	}

	/**
	 * @param Player $player
	 * @param null $levelName
	 * @return mixed|null|string
	 */
	public function getSuffix(Player $player, $levelName = null)
	{
		if($levelName === null)
		{
			return $this->main->purePerms->getUserDataMgr()->getNode($player, "suffix");
		}
		else
		{
			$worldData = $this->main->purePerms->getUserDataMgr()->getWorldData($player, $levelName);
			if(!isset($worldData["suffix"]) || $worldData["suffix"] === null)
				return "";
			return $worldData["suffix"];
		}
	}

	/**
	 * @param PPGroup $group
	 * @param $chatFormat
	 * @param null $levelName
	 * @return bool
	 */
	public function setOriginalChatFormat(PPGroup $group, $chatFormat, $levelName = null)
	{
		if($levelName === null)
		{
			$this->main->config->setNested("groups." . $group->getName() . ".chat", $chatFormat);
		}
		else
		{
			$this->main->config->setNested("groups." . $group->getName() . "worlds.$levelName.chat", $chatFormat);
		}
		$this->main->config->save();
		$this->main->config->reload();
		return true;
	}

	/**
	 * @param PPGroup $group
	 * @param $nameTag
	 * @param null $levelName
	 * @return bool
	 */
	public function setOriginalNametag(PPGroup $group, $nameTag, $levelName = null)
	{
		if($levelName === null)
		{
			$this->main->config->setNested("groups." . $group->getName() . ".nametag", $nameTag);
		}
		else
		{
			$this->main->config->setNested("groups." . $group->getName() . "worlds.$levelName.nametag", $nameTag);
		}
		$this->main->config->save();
		$this->main->config->reload();
		return true;
	}

	/**
	 * @param $prefix
	 * @param Player $player
	 * @param null $levelName
	 * @return bool
	 */
	public function setPrefix($prefix, Player $player, $levelName = null)
	{
		if($levelName === null)
		{
			$this->main->purePerms->getUserDataMgr()->setNode($player, "prefix", $prefix);
		}
		else
		{
			$worldData = $this->main->purePerms->getUserDataMgr()->getWorldData($player, $levelName);
			$worldData["prefix"] = $prefix;
			$this->main->purePerms->getUserDataMgr()->setWorldData($player, $levelName, $worldData);
		}
		return true;
	}

	/**
	 * @param $suffix
	 * @param Player $player
	 * @param null $levelName
	 * @return bool
	 */
	public function setSuffix($suffix, Player $player, $levelName = null)
	{
		if($levelName === null)
		{
			$this->main->purePerms->getUserDataMgr()->setNode($player, "suffix", $suffix);
		}
		else
		{
			$worldData = $this->main->purePerms->getUserDataMgr()->getWorldData($player, $levelName);
			$worldData["suffix"] = $suffix;
			$this->main->purePerms->getUserDataMgr()->setWorldData($player, $levelName, $worldData);
		}
		return true;
	}

	/**
	 * @param $string
	 * @return mixed
	 */
	public function stripColors($string)
	{
		$string = str_replace(TextFormat::BLACK, '', $string);
		$string = str_replace(TextFormat::DARK_BLUE, '', $string);
		$string = str_replace(TextFormat::DARK_GREEN, '', $string);
		$string = str_replace(TextFormat::DARK_AQUA, '', $string);
		$string = str_replace(TextFormat::DARK_RED, '', $string);
		$string = str_replace(TextFormat::DARK_PURPLE, '', $string);
		$string = str_replace(TextFormat::GOLD, '', $string);
		$string = str_replace(TextFormat::GRAY, '', $string);
		$string = str_replace(TextFormat::DARK_GRAY, '', $string);
		$string = str_replace(TextFormat::BLUE, '', $string);
		$string = str_replace(TextFormat::GREEN, '', $string);
		$string = str_replace(TextFormat::AQUA, '', $string);
		$string = str_replace(TextFormat::RED, '', $string);
		$string = str_replace(TextFormat::LIGHT_PURPLE, '', $string);
		$string = str_replace(TextFormat::YELLOW, '', $string);
		$string = str_replace(TextFormat::WHITE, '', $string);
		$string = str_replace(TextFormat::OBFUSCATED, '', $string);
		$string = str_replace(TextFormat::BOLD, '', $string);
		$string = str_replace(TextFormat::STRIKETHROUGH, '', $string);
		$string = str_replace(TextFormat::UNDERLINE, '', $string);
		$string = str_replace(TextFormat::ITALIC, '', $string);
		$string = str_replace(TextFormat::RESET, '', $string);
		return $string;
	}
}
