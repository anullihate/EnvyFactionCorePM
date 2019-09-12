<?php

namespace anullihate\EnvyFactionCorePM;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\level\Position;


class FactionCommands {
    public $plugin;
    public function __construct(FactionMain $pg) {
        $this->plugin = $pg;
    }
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (strtolower($command->getName()) !== "f" || empty($args)) {
            $sender->sendMessage($this->plugin->factionAPI->formatMessage("Please use /f help for a list of commands"));
            return true;
        }
        if (strtolower($args[0]) == "help") {
            $sender->sendMessage(TextFormat::RED . "\n/f about\n/f accept\n/f overclaim [Takeover the plot of the requested faction]\n/f claim\n/f create <name>\n/f del\n/f demote <player>\n/f deny");
            $sender->sendMessage(TextFormat::RED . "\n/f home\n/f help <page>\n/f info\n/f info <faction>\n/f invite <player>\n/f kick <player>\n/f leader <player>\n/f leave");
            $sender->sendMessage(TextFormat::RED . "\n/f sethome\n/f unclaim\n/f unsethome\n/f ourmembers - {Members + Statuses}\n/f ourofficers - {Officers + Statuses}\n/f ourleader - {Leader + Status}\n/f allies - {The allies of your faction");
            $sender->sendMessage(TextFormat::RED . "\n/f desc\n/f promote <player>\n/f allywith <faction>\n/f breakalliancewith <faction>\n\n/f allyok [Accept a request for alliance]\n/f allyno [Deny a request for alliance]\n/f allies <faction> - {The allies of your chosen faction}");
            $sender->sendMessage(TextFormat::RED . "\n/f membersof <faction>\n/f officersof <faction>\n/f leaderof <faction>\n/f say <send message to everyone in your faction>\n/f pf <player>\n/f topfactions");
            $sender->sendMessage(TextFormat::RED . "\n/f forceunclaim <faction> [Unclaim a faction plot by force - OP]\n\n/f forcedelete <faction> [Delete a faction by force - OP]");
            return true;
        }
        if (!$sender instanceof Player || ($sender->isOp() && $this->plugin->prefs->get("AllowOpToChangeFactionPower"))) {
            if (strtolower($args[0]) == "addpower") {
                if (!isset($args[1]) || !isset($args[2]) || !$this->alphanum($args[1]) || !is_numeric($args[2])) {
                    $sender->sendMessage($this->plugin->factionAPI->formatMessage("Usage: /f addpower <faction name> <power>"));
                    return true;
                }
                if ($this->plugin->factionAPI->factionExists($args[1])) {
                    $this->plugin->factionAPI->addFactionPower($args[1], $args[2]);
                    $sender->sendMessage($this->plugin->factionAPI->formatMessage("Power " . $args[2] . " added to Faction " . $args[1]));
                } else {
                    $sender->sendMessage($this->plugin->factionAPI->formatMessage("Faction " . $args[1] . " does not exist"));
                }
            }
            if (strtolower($args[0]) == "setpower") {
                if (!isset($args[1]) || !isset($args[2]) || !$this->alphanum($args[1]) || !is_numeric($args[2])) {
                    $sender->sendMessage($this->plugin->factionAPI->formatMessage("Usage: /f setpower <faction name> <power>"));
                    return true;
                }
                if ($this->plugin->factionAPI->factionExists($args[1])) {
                    $this->plugin->factionAPI->setFactionPower($args[1], $args[2]);
                    $sender->sendMessage($this->plugin->factionAPI->formatMessage("Faction " . $args[1] . " set to Power " . $args[2]));
                } else {
                    $sender->sendMessage($this->plugin->factionAPI->formatMessage("Faction " . $args[1] . " does not exist"));
                }
            }
            if (!$sender instanceof Player) return true;
        }
        $playerName = $sender->getPlayer()->getName();
        ///////////////////////////////// WAR /////////////////////////////////
        if ($args[0] == "war") {
            if (!isset($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Usage: /f war <faction name:tp>"));
                return true;
            }
            if (strtolower($args[1]) == "tp") {
                foreach ($this->plugin->wars as $r => $f) {
                    $fac = $this->plugin->factionAPI->getPlayerFaction($playerName);
                    if ($r == $fac) {
                        $x = mt_rand(0, $this->plugin->factionAPI->getNumberOfPlayers($fac) - 1);
                        $tper = $this->plugin->war_players[$f][$x];
                        $sender->teleport($this->plugin->getServer()->getPlayer($tper));
                        return true;
                    }
                    if ($f == $fac) {
                        $x = mt_rand(0, $this->plugin->factionAPI->getNumberOfPlayers($fac) - 1);
                        $tper = $this->plugin->war_players[$r][$x];
                        $sender->teleport($this->plugin->getServer()->getPlayer($tper));
                        return true;
                    }
                }
                $sender->sendMessage("You must be in a war to do that");
                return true;
            }
            if (!($this->alphanum($args[1]))) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You may only use letters and numbers"));
                return true;
            }
            if (!$this->plugin->factionAPI->factionExists($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Faction does not exist"));
                return true;
            }
            if (!$this->plugin->factionAPI->isInFaction($sender->getName())) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be in a faction to do this"));
                return true;
            }
            if (!$this->plugin->factionAPI->isLeader($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Only your faction leader may start wars"));
                return true;
            }
            if (!$this->plugin->factionAPI->areEnemies($this->plugin->factionAPI->getPlayerFaction($playerName), $args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Your faction is not an enemy of $args[1]"));
                return true;
            } else {
                $factionName = $args[1];
                $sFaction = $this->plugin->factionAPI->getPlayerFaction($playerName);
                foreach ($this->plugin->war_req as $r => $f) {
                    if ($r == $args[1] && $f == $sFaction) {
                        foreach ($this->plugin->getServer()->getOnlinePlayers() as $p) {
                            $task = new FactionWar($this->plugin, $r);
                            $handler = $this->plugin->getScheduler()->scheduleDelayedTask($task, 20 * 60 * 2);
                            $task->setHandler($handler);
                            $p->sendMessage("The war against $factionName and $sFaction has started!");
                            if ($this->plugin->factionAPI->getPlayerFaction($p->getName()) == $sFaction) {
                                $this->plugin->war_players[$sFaction][] = $p->getName();
                            }
                            if ($this->plugin->factionAPI->getPlayerFaction($p->getName()) == $factionName) {
                                $this->plugin->war_players[$factionName][] = $p->getName();
                            }
                        }
                        $this->plugin->wars[$factionName] = $sFaction;
                        unset($this->plugin->war_req[strtolower($args[1])]);
                        return true;
                    }
                }
                $this->plugin->war_req[$sFaction] = $factionName;
                foreach ($this->plugin->getServer()->getOnlinePlayers() as $p) {
                    if ($this->plugin->factionAPI->getPlayerFaction($p->getName()) == $factionName) {
                        if ($this->plugin->factionAPI->getLeader($factionName) == $p->getName()) {
                            $p->sendMessage("$sFaction wants to start a war, '/f war $sFaction' to start!");
                            $sender->sendMessage("Faction war requested");
                            return true;
                        }
                    }
                }
                $sender->sendMessage("Faction leader is not online.");
                return true;
            }
        }
        /////////////////////////////// CREATE ///////////////////////////////
        if ($args[0] == "create") {
            if (!isset($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Usage: /f create <faction name>"));
                return true;
            }
            if (!($this->alphanum($args[1]))) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You may only use letters and numbers"));
                return true;
            }
            if ($this->plugin->factionAPI->isNameBanned($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("This name is not allowed"));
                return true;
            }
            if ($this->plugin->factionAPI->factionExists($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("The Faction already exists"));
                return true;
            }
            if (strlen($args[1]) > $this->plugin->prefs->get("MaxFactionNameLength")) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("That name is too long, please try again"));
                return true;
            }
            if ($this->plugin->factionAPI->isInFaction($sender->getName())) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must leave the faction first"));
                return true;
            } else {
                $factionName = $args[1];
                $rank = "Leader";
                $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                $stmt->bindValue(":player", $playerName);
                $stmt->bindValue(":faction", $factionName);
                $stmt->bindValue(":rank", $rank);
                $result = $stmt->execute();
                $this->plugin->factionAPI->updateAllies($factionName);
                $this->plugin->factionAPI->setFactionPower($factionName, $this->plugin->prefs->get("TheDefaultPowerEveryFactionStartsWith"));
                $this->plugin->factionAPI->updateTag($sender->getName());
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Faction created", true));
                return true;
            }
        }
        /////////////////////////////// INVITE ///////////////////////////////
        if ($args[0] == "invite") {
            if (!isset($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Usage: /f invite <player>"));
                return true;
            }
            if ($this->plugin->factionAPI->isFactionFull($this->plugin->factionAPI->getPlayerFaction($playerName))) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Faction is full, please kick players to make room"));
                return true;
            }
            $invited = $this->plugin->getServer()->getPlayerExact($args[1]);
            if (!($invited instanceof Player)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Player not online"));
                return true;
            }
            if ($this->plugin->factionAPI->isInFaction($invited->getName()) == true) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Player is currently in a faction"));
                return true;
            }
            if ($this->plugin->prefs->get("OnlyLeadersAndOfficersCanInvite")) {
                if (!($this->plugin->factionAPI->isOfficer($playerName) || $this->plugin->factionAPI->isLeader($playerName))) {
                    $sender->sendMessage($this->plugin->factionAPI->formatMessage("Only your faction leader/officers can invite"));
                    return true;
                }
            }
            if ($invited->getName() == $playerName) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You can't invite yourself to your own faction"));
                return true;
            }
            $factionName = $this->plugin->factionAPI->getPlayerFaction($playerName);
            $invitedName = $invited->getName();
            $rank = "Member";
            $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO confirm (player, faction, invitedby, timestamp) VALUES (:player, :faction, :invitedby, :timestamp);");
            $stmt->bindValue(":player", $invitedName);
            $stmt->bindValue(":faction", $factionName);
            $stmt->bindValue(":invitedby", $sender->getName());
            $stmt->bindValue(":timestamp", time());
            $result = $stmt->execute();
            $sender->sendMessage($this->plugin->factionAPI->formatMessage("$invitedName has been invited", true));
            $invited->sendMessage($this->plugin->factionAPI->formatMessage("You have been invited to $factionName. Type '/f accept' or '/f deny' into chat to accept or deny!", true));
        }
        /////////////////////////////// LEADER ///////////////////////////////
        if ($args[0] == "leader") {
            if (!isset($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Usage: /f leader <player>"));
                return true;
            }
            if (!$this->plugin->factionAPI->isInFaction($sender->getName())) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be in a faction to use this"));
                return true;
            }
            if (!$this->plugin->factionAPI->isLeader($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be leader to use this"));
                return true;
            }
            if ($this->plugin->factionAPI->getPlayerFaction($playerName) != $this->plugin->factionAPI->getPlayerFaction($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Add player to faction first"));
                return true;
            }
            if (!($this->plugin->getServer()->getPlayerExact($args[1]) instanceof Player)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Player not online"));
                return true;
            }
            if ($args[1] == $sender->getName()) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You can't transfer the leadership to yourself"));
                return true;
            }
            $factionName = $this->plugin->factionAPI->getPlayerFaction($playerName);
            $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
            $stmt->bindValue(":player", $playerName);
            $stmt->bindValue(":faction", $factionName);
            $stmt->bindValue(":rank", "Member");
            $result = $stmt->execute();
            $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
            $stmt->bindValue(":player", $args[1]);
            $stmt->bindValue(":faction", $factionName);
            $stmt->bindValue(":rank", "Leader");
            $result = $stmt->execute();
            $sender->sendMessage($this->plugin->factionAPI->formatMessage("You are no longer leader", true));
            $this->plugin->getServer()->getPlayerExact($args[1])->sendMessage($this->plugin->formatMessage("You are now leader \nof $factionName!", true));
            $this->plugin->factionAPI->updateTag($sender->getName());
            $this->plugin->factionAPI->updateTag($this->plugin->getServer()->getPlayerExact($args[1])->getName());
        }
        /////////////////////////////// PROMOTE ///////////////////////////////
        if ($args[0] == "promote") {
            if (!isset($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Usage: /f promote <player>"));
                return true;
            }
            if (!$this->plugin->factionAPI->isInFaction($sender->getName())) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be in a faction to use this"));
                return true;
            }
            if (!$this->plugin->factionAPI->isLeader($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be leader to use this"));
                return true;
            }
            if ($this->plugin->factionAPI->getPlayerFaction($playerName) != $this->plugin->factionAPI->getPlayerFaction($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Player is not in this faction"));
                return true;
            }
            $promotee = $this->plugin->getServer()->getPlayerExact($args[1]);
            if ($promotee instanceof Player && $promotee->getName() == $sender->getName()) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You can't promote yourself"));
                return true;
            }
            if ($this->plugin->factionAPI->isOfficer($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Player is already Officer"));
                return true;
            }
            $factionName = $this->plugin->factionAPI->getPlayerFaction($playerName);
            $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
            $stmt->bindValue(":player", $args[1]);
            $stmt->bindValue(":faction", $factionName);
            $stmt->bindValue(":rank", "Officer");
            $result = $stmt->execute();
            $sender->sendMessage($this->plugin->factionAPI->formatMessage("$args[1] has been promoted to Officer", true));
            if ($promotee instanceof Player) {
                $promotee->sendMessage($this->plugin->factionAPI->formatMessage("You were promoted to officer of $factionName!", true));
                $this->plugin->factionAPI->updateTag($promotee->getName());
                return true;
            }
        }
        /////////////////////////////// DEMOTE ///////////////////////////////
        if ($args[0] == "demote") {
            if (!isset($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Usage: /f demote <player>"));
                return true;
            }
            if ($this->plugin->factionAPI->isInFaction($sender->getName()) == false) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be in a faction to use this"));
                return true;
            }
            if ($this->plugin->factionAPI->isLeader($playerName) == false) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be leader to use this"));
                return true;
            }
            if ($this->plugin->factionAPI->getPlayerFaction($playerName) != $this->plugin->factionAPI->getPlayerFaction($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Player is not in this faction"));
                return true;
            }
            if ($args[1] == $sender->getName()) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You can't demote yourself"));
                return true;
            }
            if (!$this->plugin->factionAPI->isOfficer($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Player is already Member"));
                return true;
            }
            $factionName = $this->plugin->factionAPI->getPlayerFaction($playerName);
            $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
            $stmt->bindValue(":player", $args[1]);
            $stmt->bindValue(":faction", $factionName);
            $stmt->bindValue(":rank", "Member");
            $result = $stmt->execute();
            $demotee = $this->plugin->getServer()->getPlayerExact($args[1]);
            $sender->sendMessage($this->plugin->factionAPI->formatMessage("$args[1] has been demoted to Member", true));
            if ($demotee instanceof Player) {
                $demotee->sendMessage($this->plugin->factionAPI->formatMessage("You were demoted to member of $factionName!", true));
                $this->plugin->factionAPI->updateTag($this->plugin->getServer()->getPlayerExact($args[1])->getName());
                return true;
            }
        }
        /////////////////////////////// KICK ///////////////////////////////
        if ($args[0] == "kick") {
            if (!isset($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Usage: /f kick <player>"));
                return true;
            }
            if ($this->plugin->factionAPI->isInFaction($sender->getName()) == false) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be in a faction to use this"));
                return true;
            }
            if ($this->plugin->factionAPI->isLeader($playerName) == false) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be leader to use this"));
                return true;
            }
            if ($this->plugin->factionAPI->getPlayerFaction($playerName) != $this->plugin->factionAPI->getPlayerFaction($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Player is not in this faction"));
                return true;
            }
            if ($args[1] == $sender->getName()) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You can't kick yourself"));
                return true;
            }
            $kicked = $this->plugin->getServer()->getPlayerExact($args[1]);
            $factionName = $this->plugin->factionAPI->getPlayerFaction($playerName);
            $stmt = $this->plugin->db->prepare("DELETE FROM master WHERE player = :playername;");
            $stmt->bindvalue(":playername", $args[1]);
            $stmt->execute();
            $sender->sendMessage($this->plugin->factionAPI->formatMessage("You successfully kicked $args[1]", true));
            $this->plugin->factionAPI->subtractFactionPower($factionName, $this->plugin->prefs->get("PowerGainedPerPlayerInFaction"));
            if ($kicked instanceof Player) {
                $kicked->sendMessage($this->plugin->factionAPI->formatMessage("You have been kicked from \n $factionName", true));
                $this->plugin->factionAPI->updateTag($this->plugin->getServer()->getPlayerExact($args[1])->getName());
                return true;
            }
        }
        /////////////////////////////// CLAIM ///////////////////////////////
        if (strtolower($args[0]) == 'claim') {
            if (!$this->plugin->factionAPI->isInFaction($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be in a faction."));
                return true;
            }
            if (!$this->plugin->factionAPI->isLeader($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be leader to use this."));
                return true;
            }
            if (!in_array($sender->getPlayer()->getLevel()->getName(), $this->plugin->prefs->get("ClaimWorlds"))) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You can only claim in Faction Worlds: " . implode(" ", $this->plugin->prefs->get("ClaimWorlds"))));
                return true;
            }
            if ($this->plugin->factionAPI->inOwnPlot($sender)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Your faction has already claimed this area."));
                return true;
            }
            $faction = $this->plugin->factionAPI->getPlayerFaction($sender->getPlayer()->getName());
            if ($this->plugin->factionAPI->getNumberOfPlayers($faction) < $this->plugin->prefs->get("PlayersNeededInFactionToClaimAPlot")) {
                $needed_players = $this->plugin->prefs->get("PlayersNeededInFactionToClaimAPlot") -
                    $this->plugin->factionAPI->getNumberOfPlayers($faction);
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You need $needed_players more players in your faction to claim a faction plot"));
                return true;
            }
            if ($this->plugin->factionAPI->getFactionPower($faction) < $this->plugin->prefs->get("PowerNeededToClaimAPlot")) {
                $needed_power = $this->plugin->prefs->get("PowerNeededToClaimAPlot");
                $faction_power = $this->plugin->factionAPI->getFactionPower($faction);
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Your faction doesn't have enough STR to claim a land."));
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("$needed_power STR is required but your faction has only $faction_power STR."));
                return true;
            }
            $x = floor($sender->getX());
            $y = floor($sender->getY());
            $z = floor($sender->getZ());
            if ($this->plugin->factionAPI->drawPlot($sender, $faction, $x, $y, $z, $sender->getPlayer()->getLevel(), $this->plugin->prefs->get("PlotSize")) == false) {
                return true;
            }
            $sender->sendMessage($this->plugin->factionAPI->formatMessage("Getting your coordinates...", true));
            $plot_size = $this->plugin->prefs->get("PlotSize");
            $faction_power = $this->plugin->factionAPI->getFactionPower($faction);
            $sender->sendMessage($this->plugin->factionAPI->formatMessage("Your land has been claimed.", true));
        }
        if (strtolower($args[0]) == 'plotinfo') {
            $x = floor($sender->getX());
            $y = floor($sender->getY());
            $z = floor($sender->getZ());
            if (!$this->plugin->factionAPI->isInPlot($sender)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("This plot is not claimed by anyone. You can claim it by typing /f claim", true));
                return true;
            }
            $fac = $this->plugin->factionAPI->factionFromPoint($x, $z, $sender->getPlayer()->getLevel()->getName());
            $power = $this->plugin->factionAPI->getFactionPower($fac);
            $sender->sendMessage($this->plugin->factionAPI->formatMessage("This plot is claimed by $fac with $power STR"));
        }
        if (strtolower($args[0]) == 'topfactions') {
            $this->plugin->factionAPI->sendListOfTop10FactionsTo($sender);
        }
        if (strtolower($args[0]) == 'forcedelete') {
            if (!isset($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Usage: /f forcedelete <faction>"));
                return true;
            }
            if (!$this->plugin->factionAPI->factionExists($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("The requested faction doesn't exist."));
                return true;
            }
            if (!($sender->isOp())) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be OP to do this."));
                return true;
            }
            $this->plugin->db->query("DELETE FROM master WHERE faction='$args[1]';");
            $this->plugin->db->query("DELETE FROM plots WHERE faction='$args[1]';");
            $this->plugin->db->query("DELETE FROM allies WHERE faction1='$args[1]';");
            $this->plugin->db->query("DELETE FROM allies WHERE faction2='$args[1]';");
            $this->plugin->db->query("DELETE FROM strength WHERE faction='$args[1]';");
            $this->plugin->db->query("DELETE FROM motd WHERE faction='$args[1]';");
            $this->plugin->db->query("DELETE FROM home WHERE faction='$args[1]';");
            $sender->sendMessage($this->plugin->factionAPI->formatMessage("Unwanted faction was successfully deleted and their faction plot was unclaimed!", true));
        }
        if (strtolower($args[0]) == 'addstrto') {
            if (!isset($args[1]) or !isset($args[2])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Usage: /f addstrto <faction> <STR>"));
                return true;
            }
            if (!$this->plugin->factionAPI->factionExists($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("The requested faction doesn't exist."));
                return true;
            }
            if (!($sender->isOp())) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be OP to do this."));
                return true;
            }
            $this->plugin->factionAPI->addFactionPower($args[1], $args[2]);
            $sender->sendMessage($this->plugin->factionAPI->formatMessage("Successfully added $args[2] STR to $args[1]", true));
        }
        if (strtolower($args[0]) == 'pf') {
            if (!isset($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Usage: /f pf <player>"));
                return true;
            }
            if (!$this->plugin->factionAPI->isInFaction($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("The selected player is not in a faction or doesn't exist."));
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Make sure the name of the selected player is spelled EXACTLY."));
                return true;
            }
            $faction = $this->plugin->factionAPI->getPlayerFaction($args[1]);
            $sender->sendMessage($this->plugin->factionAPI->formatMessage("-$args[1] is in $faction-", true));
        }
        if (strtolower($args[0]) == 'overclaim') {
            if (!$this->plugin->factionAPI->isInFaction($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be in a faction."));
                return true;
            }
            if (!$this->plugin->factionAPI->isLeader($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be leader to use this."));
                return true;
            }
            $faction = $this->plugin->factionAPI->getPlayerFaction($playerName);
            if ($this->plugin->factionAPI->getNumberOfPlayers($faction) < $this->plugin->prefs->get("PlayersNeededInFactionToClaimAPlot")) {
                $needed_players = $this->plugin->prefs->get("PlayersNeededInFactionToClaimAPlot") -
                    $this->plugin->factionAPI->getNumberOfPlayers($faction);
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You need $needed_players more players in your faction to overclaim a faction plot"));
                return true;
            }
            if ($this->plugin->factionAPI->etFactionPower($faction) < $this->plugin->prefs->get("PowerNeededToClaimAPlot")) {
                $needed_power = $this->plugin->prefs->get("PowerNeededToClaimAPlot");
                $faction_power = $this->plugin->factionAPI->getFactionPower($faction);
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Your faction doesn't have enough STR to claim a land."));
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("$needed_power STR is required but your faction has only $faction_power STR."));
                return true;
            }
            $sender->sendMessage($this->plugin->factionAPI->formatMessage("Getting your coordinates...", true));
            $x = floor($sender->getX());
            $z = floor($sender->getZ());
            $level = $sender->getLevel()->getName();
            if ($this->plugin->prefs->get("EnableOverClaim")) {
                if ($this->plugin->factionAPI->isInPlot($sender)) {
                    $faction_victim = $this->plugin->factionAPI->factionFromPoint($x, $z, $sender->getPlayer()->getLevel()->getName());
                    $faction_victim_power = $this->plugin->factionAPI->getFactionPower($faction_victim);
                    $faction_ours = $this->plugin->factionAPI->getPlayerFaction($playerName);
                    $faction_ours_power = $this->plugin->factionAPI->getFactionPower($faction_ours);
                    if ($this->plugin->factionAPI->inOwnPlot($sender)) {
                        $sender->sendMessage($this->plugin->factionAPI->formatMessage("You can't overclaim your own plot."));
                        return true;
                    } else {
                        if ($faction_ours_power < $faction_victim_power) {
                            $sender->sendMessage($this->plugin->factionAPI->formatMessage("You can't overclaim the plot of $faction_victim because your STR is lower than theirs."));
                            return true;
                        } else {
                            $this->plugin->db->query("DELETE FROM plots WHERE faction='$faction_ours';");
                            $this->plugin->db->query("DELETE FROM plots WHERE faction='$faction_victim';");
                            $arm = (($this->plugin->prefs->get("PlotSize")) - 1) / 2;
                            $this->plugin->factionAPI->newPlot($faction_ours, $x + $arm, $z + $arm, $x - $arm, $z - $arm, $level);
                            $sender->sendMessage($this->plugin->factionAPI->formatMessage("The land of $faction_victim has been claimed. It is now yours.", true));
                            if ($this->plugin->prefs->get("OverClaimCostsPower")) {
                                $this->plugin->factionAPI->setFactionPower($faction_ours, $faction_ours_power - $faction_victim_power);
                                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Your faction has used $faction_victim_power STR overclaiming $faction_victim", true));
                            }
                            return true;
                        }
                    }
                } else {
                    $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be in a faction plot."));
                    return true;
                }
            } else {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Overclaiming is disabled."));
                return true;
            }
        }
        /////////////////////////////// UNCLAIM ///////////////////////////////
        if (strtolower($args[0]) == "unclaim") {
            if (!$this->plugin->factionAPI->isInFaction($sender->getName())) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be in a faction"));
                return true;
            }
            if (!$this->plugin->factionAPI->isLeader($sender->getName())) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be leader to use this"));
                return true;
            }
            $faction = $this->plugin->factionAPI->getPlayerFaction($sender->getName());
            $this->plugin->db->query("DELETE FROM plots WHERE faction='$faction';");
            $sender->sendMessage($this->plugin->factionAPI->formatMessage("Your land has been unclaimed", true));
        }
        /////////////////////////////// DESCRIPTION ///////////////////////////////
        if (strtolower($args[0]) == "desc") {
            if ($this->plugin->factionAPI->isInFaction($sender->getName()) == false) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be in a faction to use this!"));
                return true;
            }
            if ($this->plugin->factionAPI->isLeader($playerName) == false) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be leader to use this"));
                return true;
            }
            $sender->sendMessage($this->plugin->factionAPI->formatMessage("Type your message in chat. It will not be visible to other players", true));
            $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO motdrcv (player, timestamp) VALUES (:player, :timestamp);");
            $stmt->bindValue(":player", $sender->getName());
            $stmt->bindValue(":timestamp", time());
            $result = $stmt->execute();
        }
        /////////////////////////////// ACCEPT ///////////////////////////////
        if (strtolower($args[0]) == "accept") {
            $lowercaseName = strtolower($playerName);
            $result = $this->plugin->db->query("SELECT * FROM confirm WHERE player='$lowercaseName';");
            $array = $result->fetchArray(SQLITE3_ASSOC);
            if (empty($array) == true) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You have not been invited to any factions"));
                return true;
            }
            $invitedTime = $array["timestamp"];
            $currentTime = time();
            if (($currentTime - $invitedTime) <= 60) { //This should be configurable
                $faction = $array["faction"];
                $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
                $stmt->bindValue(":player", ($playerName));
                $stmt->bindValue(":faction", $faction);
                $stmt->bindValue(":rank", "Member");
                $result = $stmt->execute();
                $this->plugin->db->query("DELETE FROM confirm WHERE player='$lowercaseName';");
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You successfully joined $faction", true));
                $this->plugin->factionAPI->addFactionPower($faction, $this->plugin->prefs->get("PowerGainedPerPlayerInFaction"));
                $inviter = $this->plugin->getServer()->getPlayerExact($array["invitedby"]);
                if ($inviter !== null) $inviter->sendMessage($this->plugin->factionAPI->formatMessage("$playerName joined the faction", true));
                $this->plugin->factionAPI->updateTag($sender->getName());
            } else {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Invite has timed out"));
                $this->plugin->db->query("DELETE FROM confirm WHERE player='$playerName';");
            }
        }
        /////////////////////////////// DENY ///////////////////////////////
        if (strtolower($args[0]) == "deny") {
            $lowercaseName = strtolower($playerName);
            $result = $this->plugin->db->query("SELECT * FROM confirm WHERE player='$lowercaseName';");
            $array = $result->fetchArray(SQLITE3_ASSOC);
            if (empty($array) == true) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You have not been invited to any factions"));
                return true;
            }
            $invitedTime = $array["timestamp"];
            $currentTime = time();
            if (($currentTime - $invitedTime) <= 60) { //This should be configurable
                $this->plugin->db->query("DELETE FROM confirm WHERE player='$lowercaseName';");
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Invite declined", true));
                $this->plugin->getServer()->getPlayerExact($array["invitedby"])->sendMessage($this->plugin->formatMessage("$playerName declined the invitation"));
            } else {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Invite has timed out"));
                $this->plugin->db->query("DELETE FROM confirm WHERE player='$lowercaseName';");
            }
        }
        /////////////////////////////// DELETE ///////////////////////////////
        if (strtolower($args[0]) == "del") {
            if ($this->plugin->factionAPI->isInFaction($playerName) == true) {
                if ($this->plugin->factionAPI->isLeader($playerName)) {
                    $faction = $this->plugin->factionAPI->getPlayerFaction($playerName);
                    $this->plugin->db->query("DELETE FROM plots WHERE faction='$faction';");
                    $this->plugin->db->query("DELETE FROM master WHERE faction='$faction';");
                    $this->plugin->db->query("DELETE FROM allies WHERE faction1='$faction';");
                    $this->plugin->db->query("DELETE FROM allies WHERE faction2='$faction';");
                    $this->plugin->db->query("DELETE FROM strength WHERE faction='$faction';");
                    $this->plugin->db->query("DELETE FROM motd WHERE faction='$faction';");
                    $this->plugin->db->query("DELETE FROM home WHERE faction='$faction';");
                    $sender->sendMessage($this->plugin->formatMessage("Faction successfully disbanded and the faction plot was unclaimed", true));
                    $this->plugin->factionAPI->updateTag($sender->getName());
                    unset($this->plugin->factionChatActive[$playerName]);
                    unset($this->plugin->allyChatActive[$playerName]);
                } else {
                    $sender->sendMessage($this->plugin->factionAPI->formatMessage("You are not leader!"));
                }
            } else {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You are not in a faction!"));
            }
        }
        /////////////////////////////// LEAVE ///////////////////////////////
        if (strtolower($args[0] == "leave")) {
            if ($this->plugin->factionAPI->isLeader($playerName) == false) {
                $remove = $sender->getPlayer()->getNameTag();
                $faction = $this->plugin->factionAPI->getPlayerFaction($playerName);
                $name = $sender->getName();
                $this->plugin->db->query("DELETE FROM master WHERE player='$name';");
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You successfully left $faction", true));
                $this->plugin->factionAPI->subtractFactionPower($faction, $this->plugin->prefs->get("PowerGainedPerPlayerInFaction"));
                $this->plugin->factionAPI->updateTag($sender->getName());
                unset($this->plugin->factionChatActive[$playerName]);
                unset($this->plugin->allyChatActive[$playerName]);
            } else {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must delete the faction or give\nleadership to someone else first"));
            }
        }
        /////////////////////////////// SETHOME ///////////////////////////////
        if (strtolower($args[0] == "sethome")) {
            if (!$this->plugin->factionAPI->isInFaction($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be in a faction to do this"));
                return true;
            }
            if (!$this->plugin->factionAPI->isLeader($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be leader to set home"));
                return true;
            }
            $factionName = $this->plugin->factionAPI->getPlayerFaction($sender->getName());
            $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO home (faction, x, y, z, world) VALUES (:faction, :x, :y, :z, :world);");
            $stmt->bindValue(":faction", $factionName);
            $stmt->bindValue(":x", $sender->getX());
            $stmt->bindValue(":y", $sender->getY());
            $stmt->bindValue(":z", $sender->getZ());
            $stmt->bindValue(":world", $sender->getLevel()->getName());
            $result = $stmt->execute();
            $sender->sendMessage($this->plugin->factionAPI->formatMessage("Home set", true));
        }
        /////////////////////////////// UNSETHOME ///////////////////////////////
        if (strtolower($args[0] == "unsethome")) {
            if (!$this->plugin->factionAPI->isInFaction($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be in a faction to do this"));
                return true;
            }
            if (!$this->plugin->factionAPI->isLeader($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be leader to unset home"));
                return true;
            }
            $faction = $this->plugin->factionAPI->getPlayerFaction($sender->getName());
            $this->plugin->db->query("DELETE FROM home WHERE faction = '$faction';");
            $sender->sendMessage($this->plugin->factionAPI->formatMessage("Home unset", true));
        }
        /////////////////////////////// HOME ///////////////////////////////
        if (strtolower($args[0] == "home")) {
            if (!$this->plugin->factionAPI->isInFaction($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be in a faction to do this"));
                return true;
            }
            $faction = $this->plugin->factionAPI->getPlayerFaction($sender->getName());
            $result = $this->plugin->db->query("SELECT * FROM home WHERE faction = '$faction';");
            $array = $result->fetchArray(SQLITE3_ASSOC);
            if (!empty($array)) {
                if ($array['world'] === null || $array['world'] === "") {
                    $sender->sendMessage($this->plugin->factionAPI->formatMessage("Home is missing world name, please delete and make it again"));
                    return true;
                }
                if (Server::getInstance()->loadLevel($array['world']) === false) {
                    $sender->sendMessage($this->plugin->factionAPI->formatMessage("The world '" . $array['world'] . "'' could not be found"));
                    return true;
                }
                $level = Server::getInstance()->getLevelByName($array['world']);
                $sender->getPlayer()->teleport(new Position($array['x'], $array['y'], $array['z'], $level));
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Teleported home", true));
            } else {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Home is not set"));
            }
        }
        /////////////////////////////// MEMBERS/OFFICERS/LEADER AND THEIR STATUSES ///////////////////////////////
        if (strtolower($args[0] == "ourmembers")) {
            if (!$this->plugin->factionAPI->isInFaction($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be in a faction to do this"));
                return true;
            }
            $this->plugin->factionAPI->getPlayersInFactionByRank($sender, $this->plugin->factionAPI->getPlayerFaction($playerName), "Member");
        }
        if (strtolower($args[0] == "membersof")) {
            if (!isset($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Usage: /f membersof <faction>"));
                return true;
            }
            if (!$this->plugin->factionAPI->factionExists($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("The requested faction doesn't exist"));
                return true;
            }
            $this->plugin->factionAPI->getPlayersInFactionByRank($sender, $args[1], "Member");
        }
        if (strtolower($args[0] == "ourofficers")) {
            if (!$this->plugin->factionAPI->isInFaction($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be in a faction to do this"));
                return true;
            }
            $this->plugin->factionAPI->getPlayersInFactionByRank($sender, $this->plugin->factionAPI->getPlayerFaction($playerName), "Officer");
        }
        if (strtolower($args[0] == "officersof")) {
            if (!isset($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Usage: /f officersof <faction>"));
                return true;
            }
            if (!$this->plugin->factionAPI->factionExists($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("The requested faction doesn't exist"));
                return true;
            }
            $this->plugin->factionAPI->getPlayersInFactionByRank($sender, $args[1], "Officer");
        }
        if (strtolower($args[0] == "ourleader")) {
            if (!$this->plugin->factionAPI->isInFaction($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be in a faction to do this"));
                return true;
            }
            $this->plugin->factionAPI->getPlayersInFactionByRank($sender, $this->plugin->factionAPI->getPlayerFaction($playerName), "Leader");
        }
        if (strtolower($args[0] == "leaderof")) {
            if (!isset($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Usage: /f leaderof <faction>"));
                return true;
            }
            if (!$this->plugin->factionAPI->factionExists($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("The requested faction doesn't exist"));
                return true;
            }
            $this->plugin->factionAPI->getPlayersInFactionByRank($sender, $args[1], "Leader");
        }
        if (strtolower($args[0] == "say")) {
            if (true) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("/f say is disabled"));
                return true;
            }
            if (!($this->plugin->factionAPI->isInFaction($playerName))) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be in a faction to send faction messages"));
                return true;
            }
            $r = count($args);
            $row = array();
            $rank = "";
            $f = $this->plugin->factionAPI->getPlayerFaction($playerName);
            if ($this->plugin->factionAPI->isOfficer($playerName)) {
                $rank = "*";
            } else if ($this->plugin->factionAPI->isLeader($playerName)) {
                $rank = "**";
            }
            $message = "-> ";
            for ($i = 0; $i < $r - 1; $i = $i + 1) {
                $message = $message . $args[$i + 1] . " ";
            }
            $result = $this->plugin->db->query("SELECT * FROM master WHERE faction='$f';");
            for ($i = 0; $resultArr = $result->fetchArray(SQLITE3_ASSOC); $i = $i + 1) {
                $row[$i]['player'] = $resultArr['player'];
                $p = $this->plugin->getServer()->getPlayerExact($row[$i]['player']);
                if ($p instanceof Player) {
                    $p->sendMessage(TextFormat::ITALIC . TextFormat::RED . "<FM>" . TextFormat::AQUA . " <$rank$f> " . TextFormat::GREEN . "<$playerName> " . ": " . TextFormat::RESET);
                    $p->sendMessage(TextFormat::ITALIC . TextFormat::DARK_AQUA . $message . TextFormat::RESET);
                }
            }
        }
        ////////////////////////////// ALLY SYSTEM ////////////////////////////////
        if (strtolower($args[0] == "enemywith")) {
            if (!isset($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Usage: /f enemywith <faction>"));
                return true;
            }
            if (!$this->plugin->factionAPI->isInFaction($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be in a faction to do this"));
                return true;
            }
            if (!$this->plugin->factionAPI->isLeader($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be the leader to do this"));
                return true;
            }
            if (!$this->plugin->factionAPI->factionExists($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("The requested faction doesn't exist"));
                return true;
            }
            if ($this->plugin->factionAPI->getPlayerFaction($playerName) == $args[1]) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("A faction can not be an enemy of itself"));
                return true;
            }
            if ($this->plugin->factionAPI->areAllies($this->plugin->factionAPI->getPlayerFaction($playerName), $args[1])) {
                $sender->sendMessage($this->plugin->formatMessage("Your faction is an ally of $args[1]"));
                return true;
            }
            if ($this->plugin->areEnemies($this->plugin->getPlayerFaction($playerName), $args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Your faction is already an enemy of $args[1]"));
                return true;
            }
            $fac = $this->plugin->factionAPI->getPlayerFaction($playerName);
            $leader = $this->plugin->getServer()->getPlayerExact($this->plugin->factionAPI->getLeader($args[1]));
            if (!($leader instanceof Player)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("The leader of the requested faction is offline"));
            } else {
                $leader->sendMessage($this->plugin->factionAPI->formatMessage("The leader of $fac has declared your factions are enemies", true));
            }
            $this->plugin->factionAPI->setEnemies($fac, $args[1]);
            $sender->sendMessage($this->plugin->factionAPI->formatMessage("You are now enemies with $args[1]!", true));
        }
        if (strtolower($args[0] == "notenemy")) {
            if (!isset($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Usage: /f notenemy <faction>"));
                return true;
            }
            if (!$this->plugin->factionAPI->isInFaction($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be in a faction to do this"));
                return true;
            }
            if (!$this->plugin->factionAPI->isLeader($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be the leader to do this"));
                return true;
            }
            if (!$this->plugin->factionAPI->factionExists($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("The requested faction doesn't exist"));
                return true;
            }
            $fac = $this->plugin->factionAPI->getPlayerFaction($playerName);
            $leader = $this->plugin->getServer()->getPlayerExact($this->plugin->factionAPI->getLeader($args[1]));
            $this->plugin->factionAPI->unsetEnemies($fac, $args[1]);
            if (!($leader instanceof Player)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("The leader of the requested faction is offline"));
            } else {
                $leader->sendMessage($this->plugin->factionAPI->formatMessage("The leader of $fac has declared your factions are no longer enemies", true));
            }
            $sender->sendMessage($this->plugin->factionAPI->formatMessage("You are no longer enemies with $args[1]!", true));
        }
        if (strtolower($args[0] == "allywith")) {
            if (!isset($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Usage: /f allywith <faction>"));
                return true;
            }
            if (!$this->plugin->factionAPI->isInFaction($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be in a faction to do this"));
                return true;
            }
            if (!$this->plugin->factionAPI->isLeader($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be the leader to do this"));
                return true;
            }
            if (!$this->plugin->factionAPI->factionExists($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("The requested faction doesn't exist"));
                return true;
            }
            if ($this->plugin->factionAPI->getPlayerFaction($playerName) == $args[1]) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Your faction can not ally with itself"));
                return true;
            }
            if ($this->plugin->factionAPI->areAllies($this->plugin->factionAPI->getPlayerFaction($playerName), $args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Your faction is already allied with $args[1]"));
                return true;
            }
            $fac = $this->plugin->factionAPI->getPlayerFaction($playerName);
            $leaderName = $this->plugin->factionAPI->getLeader($args[1]);
            if (!isset($fac) || !isset($leaderName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Faction not found"));
                return true;
            }
            $leader = $this->plugin->getServer()->getPlayerExact($leaderName);
            $this->plugin->factionAPI->updateAllies($fac);
            $this->plugin->factionAPI->updateAllies($args[1]);
            if (!($leader instanceof Player)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("The leader of the requested faction is offline"));
                return true;
            }
            if ($this->plugin->factionAPI->getAlliesCount($args[1]) >= $this->plugin->factionAPI->getAlliesLimit()) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("The requested faction has the maximum amount of allies", false));
                return true;
            }
            if ($this->plugin->factionAPI->getAlliesCount($fac) >= $this->plugin->factionAPI->getAlliesLimit()) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Your faction has the maximum amount of allies", false));
                return true;
            }
            $stmt = $this->plugin->db->prepare("INSERT OR REPLACE INTO alliance (player, faction, requestedby, timestamp) VALUES (:player, :faction, :requestedby, :timestamp);");
            $stmt->bindValue(":player", $leader->getName());
            $stmt->bindValue(":faction", $args[1]);
            $stmt->bindValue(":requestedby", $sender->getName());
            $stmt->bindValue(":timestamp", time());
            $result = $stmt->execute();
            $sender->sendMessage($this->plugin->factionAPI->formatMessage("You requested to ally with $args[1]!\nWait for the leader's response...", true));
            $leader->sendMessage($this->plugin->factionAPI->formatMessage("The leader of $fac requested an alliance.\nType /f allyok to accept or /f allyno to deny.", true));
        }
        if (strtolower($args[0] == "breakalliancewith") or strtolower($args[0] == "notally")) {
            if (!isset($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Usage: /f breakalliancewith <faction>"));
                return true;
            }
            if (!$this->plugin->factionAPI->isInFaction($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be in a faction to do this"));
                return true;
            }
            if (!$this->plugin->factionAPI->isLeader($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be the leader to do this"));
                return true;
            }
            if (!$this->plugin->factionAPI->factionExists($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("The requested faction doesn't exist"));
                return true;
            }
            if ($this->plugin->factionAPI->getPlayerFaction($playerName) == $args[1]) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Your faction can not break alliance with itself"));
                return true;
            }
            if (!$this->plugin->factionAPI->areAllies($this->plugin->factionAPI->getPlayerFaction($playerName), $args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Your faction is not allied with $args[1]"));
                return true;
            }
            $fac = $this->plugin->factionAPI->getPlayerFaction($playerName);
            $leader = $this->plugin->getServer()->getPlayerExact($this->plugin->factionAPI->getLeader($args[1]));
            $this->plugin->factionAPI->deleteAllies($fac, $args[1]);
            $this->plugin->factionAPI->subtractFactionPower($fac, $this->plugin->prefs->get("PowerGainedPerAlly"));
            $this->plugin->factionAPI->subtractFactionPower($args[1], $this->plugin->prefs->get("PowerGainedPerAlly"));
            $this->plugin->factionAPI->updateAllies($fac);
            $this->plugin->factionAPI->updateAllies($args[1]);
            $sender->sendMessage($this->plugin->factionAPI->formatMessage("Your faction $fac is no longer allied with $args[1]", true));
            if ($leader instanceof Player) {
                $leader->sendMessage($this->plugin->factionAPI->formatMessage("The leader of $fac broke the alliance with your faction $args[1]", false));
            }
        }
        if (strtolower($args[0] == "forceunclaim")) {
            if (!isset($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Usage: /f forceunclaim <faction>"));
                return true;
            }
            if (!$this->plugin->factionAPI->factionExists($args[1])) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("The requested faction doesn't exist"));
                return true;
            }
            if (!($sender->isOp())) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be OP to do this."));
                return true;
            }
            $sender->sendMessage($this->plugin->factionAPI->formatMessage("Successfully unclaimed the unwanted plot of $args[1]"));
            $this->plugin->db->query("DELETE FROM plots WHERE faction='$args[1]';");
        }
        if (strtolower($args[0] == "allies")) {
            if (!isset($args[1])) {
                if (!$this->plugin->factionAPI->isInFaction($playerName)) {
                    $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be in a faction to do this"));
                    return true;
                }
                $this->plugin->factionAPI->updateAllies($this->plugin->factionAPI->getPlayerFaction($playerName));
                $this->plugin->factionAPI->getAllAllies($sender, $this->plugin->factionAPI->getPlayerFaction($playerName));
            } else {
                if (!$this->plugin->factionAPI->factionExists($args[1])) {
                    $sender->sendMessage($this->plugin->factionAPI->formatMessage("The requested faction doesn't exist"));
                    return true;
                }
                $this->plugin->factionAPI->updateAllies($args[1]);
                $this->plugin->factionAPI->getAllAllies($sender, $args[1]);
            }
        }
        if (strtolower($args[0] == "allyok")) {
            if (!$this->plugin->factionAPI->isInFaction($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be in a faction to do this"));
                return true;
            }
            if (!$this->plugin->factionAPI->isLeader($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be a leader to do this"));
                return true;
            }
            $lowercaseName = strtolower($playerName);
            $result = $this->plugin->db->query("SELECT * FROM alliance WHERE player='$lowercaseName';");
            $array = $result->fetchArray(SQLITE3_ASSOC);
            if (empty($array) == true) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Your faction has not been requested to ally with any factions"));
                return true;
            }
            $allyTime = $array["timestamp"];
            $currentTime = time();
            if (($currentTime - $allyTime) <= 60) { //This should be configurable
                $requested_fac = $this->plugin->factionAPI->getPlayerFaction($array["requestedby"]);
                $sender_fac = $this->plugin->factionAPI->getPlayerFaction($playerName);
                $this->plugin->factionAPI->setAllies($requested_fac, $sender_fac);
                $this->plugin->factionAPI->addFactionPower($sender_fac, $this->plugin->prefs->get("PowerGainedPerAlly"));
                $this->plugin->factionAPI->addFactionPower($requested_fac, $this->plugin->prefs->get("PowerGainedPerAlly"));
                $this->plugin->db->query("DELETE FROM alliance WHERE player='$lowercaseName';");
                $this->plugin->factionAPI->updateAllies($requested_fac);
                $this->plugin->factionAPI->updateAllies($sender_fac);
                $this->plugin->factionAPI->unsetEnemies($requested_fac, $sender_fac);
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Your faction has successfully allied with $requested_fac", true));
                $this->plugin->getServer()->getPlayerExact($array["requestedby"])->sendMessage($this->plugin->formatMessage("$playerName from $sender_fac has accepted the alliance!", true));
            } else {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Request has timed out"));
                $this->plugin->db->query("DELETE FROM alliance WHERE player='$lowercaseName';");
            }
        }
        if (strtolower($args[0]) == "allyno") {
            if (!$this->plugin->factionAPI->isInFaction($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be in a faction to do this"));
                return true;
            }
            if (!$this->plugin->factionAPI->isLeader($playerName)) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be a leader to do this"));
                return true;
            }
            $lowercaseName = strtolower($playerName);
            $result = $this->plugin->db->query("SELECT * FROM alliance WHERE player='$lowercaseName';");
            $array = $result->fetchArray(SQLITE3_ASSOC);
            if (empty($array) == true) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Your faction has not been requested to ally with any factions"));
                return true;
            }
            $allyTime = $array["timestamp"];
            $currentTime = time();
            if (($currentTime - $allyTime) <= 60) { //This should be configurable
                $requested_fac = $this->plugin->factionAPI->getPlayerFaction($array["requestedby"]);
                $sender_fac = $this->plugin->factionAPI->getPlayerFaction($playerName);
                $this->plugin->db->query("DELETE FROM alliance WHERE player='$lowercaseName';");
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Your faction has successfully declined the alliance request.", true));
                $this->plugin->getServer()->getPlayerExact($array["requestedby"])->sendMessage($this->plugin->factionAPI->formatMessage("$playerName from $sender_fac has declined the alliance!"));
            } else {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("Request has timed out"));
                $this->plugin->db->query("DELETE FROM alliance WHERE player='$lowercaseName';");
            }
        }
        /////////////////////////////// ABOUT ///////////////////////////////
        if (strtolower($args[0] == 'about')) {
            $sender->sendMessage(TextFormat::GREEN . "[ORIGINAL] FactionsPro v1.3.2 by " . TextFormat::BOLD . "Tethered_");
            $sender->sendMessage(TextFormat::GOLD . "[MODDED] This version by MPE and " . TextFormat::BOLD . "Awzaw");
        }
        ////////////////////////////// CHAT ////////////////////////////////
        if (strtolower($args[0]) == "chat" or strtolower($args[0]) == "c") {
            if (!$this->plugin->prefs->get("AllowChat")) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("All Faction chat is disabled", false));
                return true;
            }
            if ($this->plugin->factionAPI->isInFaction($playerName)) {
                if (isset($this->plugin->factionChatActive[$playerName])) {
                    unset($this->plugin->factionChatActive[$playerName]);
                    $sender->sendMessage($this->plugin->factionAPI->formatMessage("Faction chat disabled", false));
                    return true;
                } else {
                    $this->plugin->factionChatActive[$playerName] = 1;
                    $sender->sendMessage($this->plugin->factionAPI->formatMessage("§aFaction chat enabled", false));
                    return true;
                }
            } else {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You are not in a faction"));
                return true;
            }
        }
        if (strtolower($args[0]) == "allychat" or strtolower($args[0]) == "ac") {
            if (!$this->plugin->prefs->get("AllowChat")) {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("All Faction chat is disabled", false));
                return true;
            }
            if ($this->plugin->factionAPI->isInFaction($playerName)) {
                if (isset($this->plugin->allyChatActive[$playerName])) {
                    unset($this->plugin->allyChatActive[$playerName]);
                    $sender->sendMessage($this->plugin->factionAPI->formatMessage("Ally chat disabled", false));
                    return true;
                } else {
                    $this->plugin->allyChatActive[$playerName] = 1;
                    $sender->sendMessage($this->plugin->factionAPI->formatMessage("§aAlly chat enabled", false));
                    return true;
                }
            } else {
                $sender->sendMessage($this->plugin->factionAPI->formatMessage("You are not in a faction"));
                return true;
            }
        }
        /////////////////////////////// INFO ///////////////////////////////
        if (strtolower($args[0]) == 'info') {
            if (isset($args[1])) {
                if (!(ctype_alnum($args[1])) or !($this->plugin->factionAPI->factionExists($args[1]))) {
                    $sender->sendMessage($this->plugin->factionAPI->formatMessage("Faction does not exist"));
                    $sender->sendMessage($this->plugin->factionAPI->formatMessage("Make sure the name of the selected faction is ABSOLUTELY EXACT."));
                    return true;
                }
                $faction = $args[1];
                $result = $this->plugin->db->query("SELECT * FROM motd WHERE faction='$faction';");
                $array = $result->fetchArray(SQLITE3_ASSOC);
                $power = $this->plugin->factionAPI->getFactionPower($faction);
                $message = $array["message"];
                $leader = $this->plugin->factionAPI->getLeader($faction);
                $numPlayers = $this->plugin->factionAPI->getNumberOfPlayers($faction);
                $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "-------INFORMATION-------" . TextFormat::RESET);
                $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "|[Faction]| : " . TextFormat::GREEN . "$faction" . TextFormat::RESET);
                $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "|(Leader)| : " . TextFormat::YELLOW . "$leader" . TextFormat::RESET);
                $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "|^Players^| : " . TextFormat::LIGHT_PURPLE . "$numPlayers" . TextFormat::RESET);
                $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "|&Strength&| : " . TextFormat::RED . "$power" . " STR" . TextFormat::RESET);
                $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "|*Description*| : " . TextFormat::AQUA . TextFormat::UNDERLINE . "$message" . TextFormat::RESET);
                $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "-------INFORMATION-------" . TextFormat::RESET);
            } else {
                if (!$this->plugin->factionAPI->isInFaction($playerName)) {
                    $sender->sendMessage($this->plugin->factionAPI->formatMessage("You must be in a faction to use this!"));
                    return true;
                }
                $faction = $this->plugin->getPlayerFaction(($sender->getName()));
                $result = $this->plugin->db->query("SELECT * FROM motd WHERE faction='$faction';");
                $array = $result->fetchArray(SQLITE3_ASSOC);
                $power = $this->plugin->factionAPI->getFactionPower($faction);
                $message = $array["message"];
                $leader = $this->plugin->factionAPI->getLeader($faction);
                $numPlayers = $this->plugin->factionAPI->getNumberOfPlayers($faction);
                $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "-------INFORMATION-------" . TextFormat::RESET);
                $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "|[Faction]| : " . TextFormat::GREEN . "$faction" . TextFormat::RESET);
                $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "|(Leader)| : " . TextFormat::YELLOW . "$leader" . TextFormat::RESET);
                $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "|^Players^| : " . TextFormat::LIGHT_PURPLE . "$numPlayers" . TextFormat::RESET);
                $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "|&Strength&| : " . TextFormat::RED . "$power" . " STR" . TextFormat::RESET);
                $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "|*Description*| : " . TextFormat::AQUA . TextFormat::UNDERLINE . "$message" . TextFormat::RESET);
                $sender->sendMessage(TextFormat::GOLD . TextFormat::ITALIC . "-------INFORMATION-------" . TextFormat::RESET);
            }
            return true;
        }
        if ($this->plugin->prefs->get("EnableMap") && (strtolower($args[0]) == "map" or strtolower($args[0]) == "m")) {
            $factionPlots = $this->plugin->factionAPI->getNearbyPlots($sender);
            if ($factionPlots == null) {
                $sender->sendMessage(TextFormat::RED . "No nearby factions found");
                return true;
            }
            $playerFaction = $this->plugin->factionAPI->getPlayerFaction(($sender->getName()));
            $found = false;
            foreach ($factionPlots as $key => $faction) {
                $plotFaction = $factionPlots[$key]['faction'];
                if ($plotFaction == $playerFaction) {
                    continue;
                }
                if ($this->plugin->factionAPI->isInPlot($sender)) {
                    $inWhichPlot = $this->plugin->factionAPI->factionFromPoint($sender->getX(), $sender->getZ(), $sender->getLevel()->getName());
                    if ($inWhichPlot == $plotFaction) {
                        $sender->sendMessage(TextFormat::GREEN . "You are in faction " . $plotFaction . "'s plot");
                        $found = true;
                        continue;
                    }
                }
                $found = true;
                $x1 = $factionPlots[$key]['x1'];
                $x2 = $factionPlots[$key]['x2'];
                $z1 = $factionPlots[$key]['z1'];
                $z2 = $factionPlots[$key]['z2'];
                $plotX = $x1 + ($x2 - $x1) / 2;
                $plotZ = $z1 + ($z2 - $z1) / 2;
                $deltaX = $plotX - $sender->getX();
                $deltaZ = $plotZ - $sender->getZ();
                $bearing = rad2deg(atan2($deltaZ, $deltaX));
                if ($bearing >= -22.5 && $bearing < 22.5) $direction = "south";
                else if ($bearing >= 22.5 && $bearing < 67.5) $direction = "southwest";
                else if ($bearing >= 67.5 && $bearing < 112.5) $direction = "west";
                else if ($bearing >= 112.5 && $bearing < 157.5) $direction = "northwest";
                else if ($bearing >= 157.5) $direction = "north";
                else if ($bearing < -22.5 && $bearing > -67.5) $direction = "southeast";
                else if ($bearing <= -67.5 && $bearing > -112.5) $direction = "east";
                else if ($bearing <= -112.5 && $bearing > -157.5) $direction = "northeast";
                else if ($bearing <= -157.5) $direction = "north";
                $distance = floor(sqrt(pow($deltaX, 2) + pow($deltaZ, 2)));
                $sender->sendMessage(TextFormat::GREEN . $plotFaction . "'s plot is " . $distance . " blocks " . $direction);
            }
            if (!$found) {
                $sender->sendMessage(TextFormat::RED . "No nearby factions found");
            } else {
                $points = ["south", "west", "north", "east"];
                $sender->sendMessage(TextFormat::YELLOW . "You are facing " . $points[$sender->getDirection()]);
            }
        }
        return true;
    }
    public function alphanum($string) {
        if (function_exists('ctype_alnum')) {
            $return = ctype_alnum($string);
        } else {
            $return = preg_match('/^[a-z0-9]+$/i', $string) > 0;
        }
        return $return;
    }
}
