<?php

declare(strict_types=1);

namespace AdvancedCrates;

use pocketmine\player\Player;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat as C;
use pocketmine\nbt\tag\StringTag;

class KeyManager{

    private Main $plugin;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    /**
     * Create a crate key item
     */
    public function createKey(string $crateName, int $amount = 1): Item{
        $config = $this->plugin->getConfig();
        $crateData = $config->get("crates")[$crateName] ?? null;

        if($crateData === null){
            throw new \Exception("Crate '$crateName' does not exist in config.");
        }

        $item = VanillaBlocks::TORCH()->asItem();
        $item->setCount($amount);

        // Custom name
        $item->setCustomName(C::colorize($crateData["key-name"]));

        // Lore
        $lore = [];
        foreach($crateData["key-lore"] as $line){
            $lore[] = C::colorize($line);
        }
        $item->setLore($lore);

        // Add hidden NBT tag so fake torches can't be used
        $nbt = $item->getNamedTag();
        $nbt->setTag("crate_key", new StringTag($crateName));
        $item->setNamedTag($nbt);

        return $item;
    }

    /**
     * Give key to player
     */
    public function giveKey(Player $player, string $crateName, int $amount = 1): void{
        $item = $this->createKey($crateName, $amount);
        $player->getInventory()->addItem($item);
    }

    /**
     * Check if item is a crate key
     */
    public function isCrateKey(Item $item, string $crateName): bool{
        $nbt = $item->getNamedTag();

        if(!$nbt->getTag("crate_key") instanceof StringTag){
            return false;
        }

        return $nbt->getString("crate_key") === $crateName;
    }

    /**
     * Remove one key from player hand
     */
    public function consumeKey(Player $player): void{
        $item = $player->getInventory()->getItemInHand();
        $item->setCount($item->getCount() - 1);
        $player->getInventory()->setItemInHand($item);
    }
}
