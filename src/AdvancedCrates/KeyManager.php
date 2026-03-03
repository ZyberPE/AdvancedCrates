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

    public function createKey(string $crateName, int $amount = 1): Item{
        $config = $this->plugin->getConfig();
        $crates = $config->get("crates");

        if(!isset($crates[$crateName])){
            throw new \Exception("Crate '$crateName' does not exist in config.");
        }

        $crateData = $crates[$crateName];

        $item = VanillaBlocks::TORCH()->asItem();
        $item->setCount($amount);

        // Set name
        $item->setCustomName(C::colorize($crateData["key-name"]));

        // Set lore (YOUR config uses "lore")
        $lore = [];
        foreach($crateData["lore"] as $line){
            $lore[] = C::colorize($line);
        }
        $item->setLore($lore);

        // Add NBT tag to identify key
        $nbt = $item->getNamedTag();
        $nbt->setString("crate_key", $crateName);
        $item->setNamedTag($nbt);

        return $item;
    }

    public function giveKey(Player $player, string $crateName, int $amount = 1): void{
        $player->getInventory()->addItem(
            $this->createKey($crateName, $amount)
        );
    }

    public function isCrateKey(Item $item, string $crateName): bool{
        return $item->getNamedTag()->getString("crate_key", "") === $crateName;
    }

    public function consumeKey(Player $player): void{
        $item = $player->getInventory()->getItemInHand();
        $item->setCount($item->getCount() - 1);
        $player->getInventory()->setItemInHand($item);
    }
}
