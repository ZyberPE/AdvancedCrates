<?php

declare(strict_types=1);

namespace AdvancedCrates;

use pocketmine\player\Player;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat as C;

class KeyManager{

    private Main $plugin;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    public function createKey(string $crateName, int $amount = 1): Item{

        $configData = $this->plugin->getConfig()->getAll();

        if(!isset($configData["crates"][$crateName])){
            // DO NOT crash server anymore
            $this->plugin->getLogger()->error("Crate '$crateName' not found in config.");
            return VanillaBlocks::TORCH()->asItem(); // safe fallback
        }

        $crateData = $configData["crates"][$crateName];

        $item = VanillaBlocks::TORCH()->asItem();
        $item->setCount($amount);

        // Name
        $item->setCustomName(C::colorize($crateData["key-name"] ?? "Crate Key"));

        // Lore
        $lore = [];
        foreach(($crateData["lore"] ?? []) as $line){
            $lore[] = C::colorize($line);
        }
        $item->setLore($lore);

        // NBT tag
        $item->getNamedTag()->setString("crate_key", $crateName);

        return $item;
    }

    public function giveKey(Player $player, string $crateName, int $amount = 1): void{
        $player->getInventory()->addItem(
            $this->createKey(strtolower($crateName), $amount)
        );
    }

    public function isCrateKey(Item $item, string $crateName): bool{
        return $item->getNamedTag()->getString("crate_key", "") === strtolower($crateName);
    }

    public function consumeKey(Player $player): void{
        $item = $player->getInventory()->getItemInHand();
        $item->setCount($item->getCount() - 1);
        $player->getInventory()->setItemInHand($item);
    }
}
