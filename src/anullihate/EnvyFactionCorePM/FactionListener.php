<?php
/**
 * Created by PhpStorm.
 * User: Clint Dave Luna
 * Date: 11/09/2019
 * Time: 7:08 PM
 */

namespace anullihate\EnvyFactionCorePM;

use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\utils\TextFormat;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerDeathEvent;


class FactionListener implements Listener {
    public $plugin;
    public function __construct(FactionMain $pg) {
        $this->plugin = $pg;
    }
    public function factionChat(PlayerChatEvent $PCE) {
        $player = $PCE->getPlayer()->getName();
        //MOTD Check
        if ($this->plugin->factionAPI->motdWaiting($player)) {
            if (time() - $this->plugin->factionAPI->getMOTDTime($player) > 30) {
                $PCE->getPlayer()->sendMessage($this->plugin->factionAPI->formatMessage("Timed out. Please use /f desc again."));
                $this->plugin->db->query("DELETE FROM motdrcv WHERE player='$player';");
                $PCE->setCancelled(true);
                return true;
            } else {
                $motd = $PCE->getMessage();
                $faction = $this->plugin->factionAPI->getPlayerFaction($player);
                $this->plugin->factionAPI->setMOTD($faction, $player, $motd);
                $PCE->setCancelled(true);
                $PCE->getPlayer()->sendMessage($this->plugin->factionAPI->formatMessage("Successfully updated the faction description. Type /f info.", true));
            }
            return true;
        }
        if (isset($this->plugin->factionChatActive[$player])) {
            if ($this->plugin->factionChatActive[$player]) {
                $msg = $PCE->getMessage();
                $faction = $this->plugin->factionAPI->getPlayerFaction($player);
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $fP) {
                    if ($this->plugin->factionAPI->getPlayerFaction($fP->getName()) == $faction) {
                        if ($this->plugin->getServer()->getPlayer($fP->getName())) {
                            $PCE->setCancelled(true);
                            $this->plugin->getServer()->getPlayer($fP->getName())->sendMessage(TextFormat::DARK_GREEN . "[$faction]" . TextFormat::BLUE . " $player: " . TextFormat::AQUA . $msg);
                        }
                    }
                }
            }
        }
        if (isset($this->plugin->allyChatActive[$player])) {
            if ($this->plugin->allyChatActive[$player]) {
                $msg = $PCE->getMessage();
                $faction = $this->plugin->factionAPI->getPlayerFaction($player);
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $fP) {
                    if ($this->plugin->factionAPI->areAllies($this->plugin->factionAPI->getPlayerFaction($fP->getName()), $faction)) {
                        if ($this->plugin->getServer()->getPlayer($fP->getName())) {
                            $PCE->setCancelled(true);
                            $this->plugin->getServer()->getPlayer($fP->getName())->sendMessage(TextFormat::DARK_GREEN . "[$faction]" . TextFormat::BLUE . " $player: " . TextFormat::AQUA . $msg);
                            $PCE->getPlayer()->sendMessage(TextFormat::DARK_GREEN . "[$faction]" . TextFormat::BLUE . " $player: " . TextFormat::AQUA . $msg);
                        }
                    }
                }
            }
        }
    }
    public function factionPVP(EntityDamageEvent $factionDamage) {
        if ($factionDamage instanceof EntityDamageByEntityEvent) {
            if (!($factionDamage->getEntity() instanceof Player) or !($factionDamage->getDamager() instanceof Player)) {
                return true;
            }
            if (($this->plugin->factionAPI->isInFaction($factionDamage->getEntity()->getPlayer()->getName()) == false) or ($this->plugin->isInFaction($factionDamage->getDamager()->getPlayer()->getName()) == false)) {
                return true;
            }
            if (($factionDamage->getEntity() instanceof Player) and ($factionDamage->getDamager() instanceof Player)) {
                $player1 = $factionDamage->getEntity()->getPlayer()->getName();
                $player2 = $factionDamage->getDamager()->getPlayer()->getName();
                $f1 = $this->plugin->factionAPI->getPlayerFaction($player1);
                $f2 = $this->plugin->factionAPI->getPlayerFaction($player2);
                if ((!$this->plugin->prefs->get("AllowFactionPvp") && $this->plugin->factionAPI->sameFaction($player1, $player2) == true) or (!$this->plugin->prefs->get("AllowAlliedPvp") && $this->plugin->areAllies($f1, $f2))) {
                    $factionDamage->setCancelled(true);
                }
            }
        }
    }
    public function factionBlockBreakProtect(BlockBreakEvent $event) {
        $x = $event->getBlock()->getX();
        $z = $event->getBlock()->getZ();
        $level = $event->getBlock()->getLevel()->getName();
        if ($this->plugin->factionAPI->pointIsInPlot($x, $z, $level)) {
            if ($this->plugin->factionAPI->factionFromPoint($x, $z, $level) === $this->plugin->factionAPI->getFaction($event->getPlayer()->getName())) {
                return;
            } else {
                $event->setCancelled(true);
                $event->getPlayer()->sendMessage($this->plugin->factionAPI->formatMessage("You cannot break blocks here. This is already a property of a faction. Type /f plotinfo for details."));
                return;
            }
        }
    }
    public function factionBlockPlaceProtect(BlockPlaceEvent $event) {
        $x = $event->getBlock()->getX();
        $z = $event->getBlock()->getZ();
        $level = $event->getBlock()->getLevel()->getName();
        if ($this->plugin->factionAPI->pointIsInPlot($x, $z, $level)) {
            if ($this->plugin->factionAPI->factionFromPoint($x, $z, $level) == $this->plugin->factionAPI->getFaction($event->getPlayer()->getName())) {
                return;
            } else {
                $event->setCancelled(true);
                $event->getPlayer()->sendMessage($this->plugin->factionAPI->formatMessage("You cannot place blocks here. This is already a property of a faction. Type /f plotinfo for details."));
                return;
            }
        }
    }
    public function onKill(PlayerDeathEvent $event) {
        $ent = $event->getEntity();
        $cause = $event->getEntity()->getLastDamageCause();
        if ($cause instanceof EntityDamageByEntityEvent) {
            $killer = $cause->getDamager();
            if ($killer instanceof Player) {
                $p = $killer->getPlayer()->getName();
                if ($this->plugin->factionAPI->isInFaction($p)) {
                    $f = $this->plugin->factionAPI->getPlayerFaction($p);
                    $e = $this->plugin->prefs->get("PowerGainedPerKillingAnEnemy");
                    if ($ent instanceof Player) {
                        if ($this->plugin->factionAPI->isInFaction($ent->getPlayer()->getName())) {
                            $this->plugin->factionAPI->addFactionPower($f, $e);
                        } else {
                            $this->plugin->factionAPI->addFactionPower($f, $e / 2);
                        }
                    }
                }
            }
        }
        if ($ent instanceof Player) {
            $e = $ent->getPlayer()->getName();
            if ($this->plugin->factionAPI->isInFaction($e)) {
                $f = $this->plugin->factionAPI->getPlayerFaction($e);
                $e = $this->plugin->prefs->get("PowerGainedPerKillingAnEnemy");
                if ($ent->getLastDamageCause() instanceof EntityDamageByEntityEvent && $ent->getLastDamageCause()->getDamager() instanceof Player) {
                    if ($this->plugin->factionAPI->isInFaction($ent->getLastDamageCause()->getDamager()->getPlayer()->getName())) {
                        $this->plugin->factionAPI->subtractFactionPower($f, $e * 2);
                    } else {
                        $this->plugin->factionAPI->subtractFactionPower($f, $e);
                    }
                }
            }
        }
    }
    public function onPlayerJoin(PlayerJoinEvent $event) {
        $this->plugin->factionAPI->updateTag($event->getPlayer()->getName());
    }
}
