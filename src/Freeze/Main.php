<?php

namespace Freeze;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener
{
    private $frozen = array();
    private $config;

    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this,$this);
        @mkdir($this->getDataFolder());
        $config = new Config($this->getDataFolder() . "config.yml" , Config::YAML, array(
            "codeInvalid" => "§b[Freeze] §cVous devez indiquer un code de vérification valide !",
            "freezeOn" => "§b[Freeze] §cLe serveur a ete freeze !",
            "freezeOff" => "§b[Freeze] §aLe serveur a ete unfreeze !",
            "freezePlayerOn" => "§b[Freeze] §aVous venez d'être gelé(e) !",
            "freezePlayerOff" => "§b[Freeze] §aVous venez d'être dégelé(e) !",
            "codeOn" => "codeOn",
            "codeOff" => "codeOff",
        ));
        $this->saveResource("config.yml");
    }

    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        if(in_array($player->getName(), $this->frozen)){
            $player->setImmobile(true);
        }
    }

    public function onQuit(PlayerQuitEvent $e){
        $player = $e->getPlayer();
        $pName = $e->getPlayer()->getDisplayName();
        if(in_array($player->getName(), $this->frozen)){
            $this->getServer()->broadcastMessage("§b[Freeze] §c$pName a quitter pendant le freeze !");
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if ($sender instanceof Player) {
            if ($command == "freezeall") {
                if (isset($args[0])) {
                    if (isset($args[1])) {
                        if ($args[0] == "on") {
                            if ($args[1] == $this->getConfig()->get("codeOn")) {
                                foreach ($this->getServer()->getOnlinePlayers() as $all) {
                                    $all->setImmobile(true);
                                    array_push($this->frozen, $all->getName());
                                    $all->sendPopup($this->getConfig()->get("freezeOn"));

                                    // Retirer le freezeur de la liste
                                    $sender->setImmobile(false);
                                    unset($this->frozen[array_search($sender->getName(),$this->frozen)]);
                                }
                            }
                        } elseif ($args[0] == "off"){
                            if($args[1] == $this->getConfig()->get("codeOn")){
                                foreach ($this->getServer()->getOnlinePlayers() as $all2) {
                                    $all2->setImmobile(false);
                                    array_splice($this->frozen, array_search($all2->getName(), $this->frozen));
                                    $all2->sendPopup($this->getConfig()->get("freezeOff"));
                                }
                            }
                        }
                    } else {
                        $sender->sendMessage($this->getConfig()->get("codeInvalide"));
                    }
                } else {
                    $sender->sendMessage("§b[Freeze] §cVous devez indiquer on/off");
                }
            }else if($command == "freeze"){
                if(isset($args[0])){
                    $player = Server::getInstance()->getPlayer($args[0]);
                    if($player instanceof Player){
                        if(isset($args[1])){
                            if($args[1] == $this->getConfig()->get("codeOn")){
                                array_push($this->frozen, $player->getName());
                                $player->setImmobile(true);
                                $player->sendPopup($this->getConfig()->get("freezePlayerOn"));
                            }else{
                                unset($this->frozen[array_search($player->getName(),$this->frozen)]);
                                $player->setImmobile(false);
                                $player->sendPopup($this->getConfig()->get("freezePlayerOff"));
                            }
                        }else{
                            $player->sendMessage("");
                        }
                    }else{
                        $sender->sendMessage("§b[Freeze] §cLa personne n'est pas connecté(e)");
                    }
                }else{
                    $sender->sendMessage("§b[Freeze] §cVous n'avez pas indiquer le joueur à freeeze");
                }
            }
        } else {
            $this->getLogger()->info("Vous n'etes pas un joueur !");
        }
        return true;
    }
}
