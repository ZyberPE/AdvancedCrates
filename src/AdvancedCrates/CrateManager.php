<?php

declare(strict_types=1);

namespace AdvancedCrates;

use pocketmine\player\Player;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\block\Chest;
use pocketmine\world\Position;
use pocketmine\entity\Location;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Skin;
use pocketmine\entity\Human;

class CrateManager {

    private Main $plugin;
    private array $creating = [];
    private array $crates = [];
    private array $holograms = [];

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    public function loadCrates(): void {
        $this->crates = $this->plugin->getCrateConfig()->get("placed-crates", []);
        foreach($this->crates as $pos => $crate){
            $this->spawnHologramFromString($pos, $crate);
        }
    }

    public function saveCrates(): void {
        $this->plugin->getCrateConfig()->set("placed-crates", $this->crates);
        $this->plugin->getCrateConfig()->save();
    }

    public function setCreating(Player $player, string $crate): void {
        $this->creating[$player->getName()] = $crate;
    }

    public function handleInteract(PlayerInteractEvent $event): void {

        $player = $event->getPlayer();
        $block = $event->getBlock();

        if(!$block instanceof Chest) return;

        $event->cancel();

        $posKey = $block->getPosition()->getWorld()->getFolderName() . ":" .
            $block->getPosition()->getFloorX() . ":" .
            $block->getPosition()->getFloorY() . ":" .
            $block->getPosition()->getFloorZ();

        if(isset($this->creating[$player->getName()])){
            $crate = $this->creating[$player->getName()];
            unset($this->creating[$player->getName()]);
            $this->crates[$posKey] = $crate;
            $this->spawnHologram($block->getPosition(), $crate);
            $player->sendMessage("§aCrate created.");
            $this->saveCrates();
            return;
        }

        if(isset($this->crates[$posKey])){
            $crate = $this->crates[$posKey];
            $this->plugin->getKeyManager()->tryOpenCrate($player, $crate, $block->getPosition());
        }
    }

    private function spawnHologram(Position $pos, string $crate): void {

        if(!$this->plugin->getMainConfig()->getNested("hologram.enabled")) return;

        $name = $this->plugin->getCrateConfig()->getNested("crates.$crate.display-name");

        $loc = new Location(
            $pos->getX(),
            $pos->getY() + $this->plugin->getMainConfig()->getNested("hologram.height"),
            $pos->getZ(),
            $pos->getWorld(),
            0,
            0
        );

        $human = new Human($loc, new Skin("Standard_Custom", str_repeat("\x00", 8192)));
        $human->setNameTag($name);
        $human->setNameTagAlwaysVisible();
        $human->setScale(0.01);
        $human->spawnToAll();

        $this->holograms[] = $human;
    }

    private function spawnHologramFromString(string $key, string $crate): void {
        [$world,$x,$y,$z] = explode(":",$key);
        $w = $this->plugin->getServer()->getWorldManager()->getWorldByName($world);
        if($w !== null){
            $this->spawnHologram(new Position((int)$x,(int)$y,(int)$z,$w),$crate);
        }
    }
}
