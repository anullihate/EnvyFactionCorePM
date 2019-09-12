<?php
/**
 * Created by PhpStorm.
 * User: trd-staff
 * Date: 9/12/19
 * Time: 6:54 PM
 */

namespace anullihate\EnvyFactionCorePM\api;


use jojoe77777\FormAPI\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\Config;

class EnvyShop {
	public function shopCategory($cfg, Player $player, Config $msg, $ans) {
		$form = new SimpleForm(function (Player $player, int $data = null) use ($cfg, $msg, $ans) : void {
			$categories = $data;
			if ($categories === null) {
				$player->sendMessage('ty');
			}
		});
	}
}
