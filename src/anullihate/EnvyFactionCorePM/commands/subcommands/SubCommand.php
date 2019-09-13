<?php

namespace anullihate\EnvyFactionCorePM\commands\subcommands;


use pocketmine\command\CommandSender;

interface SubCommand {

	/**
	 * @api
	 *
	 * @param CommandSender $sender
	 * @param array $args
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function executeSub(CommandSender $sender, array $args, string $name);
}
