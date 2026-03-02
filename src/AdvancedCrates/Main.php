<?php

declare(strict_types=1);

namespace AdvancedCrates;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Main extends PluginBase implements Listener {

    private Config $config;
    private Config $crates;

    private CrateManager $crateManager;
    private KeyManager $keyManager;

    public function onEnable(): void {

        @mkdir($this->getDataFolder());
        $this->saveResource("config.yml");
        $this->saveResource("crates.yml");

        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->crates = new Config($this->getDataFolder() . "crates.yml", Config::YAML);

        $this->crateManager = new CrateManager($this);
        $this->keyManager = new KeyManager($this);

        $this->crateManager->loadCrates();

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onDisable(): void {
        $this->crateManager->saveCrates();
    }

    public function getMainConfig(): Config { return $this->config; }
    public function getCrateConfig(): Config { return $this->crates; }
    public function getCrateManager(): CrateManager { return $this->crateManager; }
    public function getKeyManager(): KeyManager { return $this->keyManager; }

    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool {

        if($cmd->getName() === "crates" && $sender instanceof Player){
            $tp = $this->config->get("crates-teleport");
            $world = $this->getServer()->getWorldManager()->getWorldByName($tp["world"]);
            if($world !== null){
                $sender->teleport($world->getSpawnLocation()->add($tp["x"],$tp["y"],$tp["z"]));
            }
            return true;
        }

        if($cmd->getName() === "crate" && isset($args[0])){

            if($args[0] === "create" && $sender instanceof Player){
                if(!isset($args[1])) return false;
                $this->crateManager->setCreating($sender, strtolower($args[1]));
                $sender->sendMessage("§aClick a chest to register crate.");
                return true;
            }

            if($args[0] === "give"){
                if(count($args) < 4) return false;
                $player = $this->getServer()->getPlayerExact($args[1]);
                if($player !== null){
                    $this->keyManager->giveKey($player, strtolower($args[2]), (int)$args[3]);
                }
                return true;
            }
        }
        return false;
    }

    public function onBreak(BlockBreakEvent $event): void {
        if(!$this->config->getNested("mining.enabled")) return;

        foreach(["common","uncommon","epic","legendary"] as $tier){
            if(mt_rand(1,10) <= $this->config->getNested("mining.$tier")){
                $this->keyManager->giveKey($event->getPlayer(), $tier, 1);
            }
        }
    }

    public function onInteract(PlayerInteractEvent $event): void {
        $this->crateManager->handleInteract($event);
    }
}
