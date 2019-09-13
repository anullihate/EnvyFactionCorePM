<?php

namespace anullihate\EnvyFactionCorePM\listeners;


use anullihate\EnvyFactionCorePM\FactionMain;
use pocketmine\event\Listener;

class MultiWorldListener implements Listener {

	private $main;

	public function __construct(FactionMain $main) {
		$this->main = $main;
	}

}
