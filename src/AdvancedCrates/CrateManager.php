<?php

declare(strict_types=1);

namespace AdvancedCrates;

use pocketmine\player\Player;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\block\Chest;
use pocketmine\world\Position;
use pocketmine\entity\Location;
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
        foreach($this->crates as $posKey => $crate){
            $this->spawnHologramFromString($posKey, $crate);
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

        $pos = $block->getPosition();
        $world = $pos->getWorld()->getFolderName();

        $posKey = $world . ":" .
            $pos->getFloorX() . ":" .
            $pos->getFloorY() . ":" .
            $pos->getFloorZ();

        // Creating new crate
        if(isset($this->creating[$player->getName()])){
            $crate = $this->creating[$player->getName()];
            unset($this->creating[$player->getName()]);

            $this->crates[$posKey] = $crate;
            $this->spawnHologram($pos, $crate);

            $player->sendMessage(
                $this->plugin->getMainConfig()->getNested("messages.crate-created")
            );

            $this->saveCrates();
            return;
        }

        // Opening existing crate
        if(isset($this->crates[$posKey])){
            $crate = $this->crates[$posKey];
            $this->plugin->getKeyManager()->tryOpenCrate($player, $crate, $pos);
        }
    }

    private function spawnHologram(Position $pos, string $crate): void {

        if(!$this->plugin->getMainConfig()->getNested("hologram.enabled")) return;

        $name = $this->plugin->getCrateConfig()
            ->getNested("crates.$crate.display-name");

        $height = $this->plugin->getMainConfig()
            ->getNested("hologram.height");

        $location = new Location(
            $pos->getX(),
            $pos->getY() + $height,
            $pos->getZ(),
            $pos->getWorld(),
            0,
            0
        );

        $skin = new Skin("crate_hologram", str_repeat("\x00", 8192));

        $human = new Human($location, $skin);
        $human->setNameTag($name);
        $human->setNameTagAlwaysVisible(true);
        $human->setNameTagVisible(true);
        $human->setScale(0.01);
        $human->spawnToAll();

        $this->holograms[] = $human;
    }

    private function spawnHologramFromString(string $key, string $crate): void {

        [$worldName,$x,$y,$z] = explode(":", $key);

        $world = $this->plugin->getServer()
            ->getWorldManager()
            ->getWorldByName($worldName);

        if($world === null) return;

        $this->spawnHologram(
            new Position((int)$x,(int)$y,(int)$z,$world),
            $crate
        );
    }
}
