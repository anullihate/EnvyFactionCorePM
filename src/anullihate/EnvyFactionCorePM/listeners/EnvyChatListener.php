<?php
/**
 * Created by PhpStorm.
 * User: trd-staff
 * Date: 9/12/19
 * Time: 5:33 PM
 */

namespace anullihate\EnvyFactionCorePM\listeners;


use anullihate\EnvyFactionCorePM\FactionMain;
use pocketmine\event\Listener;

class EnvyChatListener implements Listener {

	private $main;
	public function __construct(FactionMain $main) {
		$this->main = $main;
	}
}
