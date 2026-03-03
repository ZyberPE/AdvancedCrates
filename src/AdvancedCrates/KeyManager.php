<?php

declare(strict_types=1);

namespace AdvancedCrates;

use pocketmine\player\Player;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\StringTag;
use pocketmine\world\Position;

class KeyManager {

    private Main $plugin;

    public function __construct(Main $plugin){
        $this->plugin = $plugin;
    }

    public function giveKey(Player $player, string $crate, int $amount): void {

        $crateData = $this->plugin
            ->getCrateConfig()
            ->getNested("crates.$crate");

        if($crateData === null) return;

        $item = VanillaItems::TORCH()->setCount($amount);
        $item->setCustomName($crateData["key-name"]);
        $item->setLore($crateData["lore"]);
        $item->getNamedTag()->setString("crate_key", $crate);

        $player->getInventory()->addItem($item);
    }

    public function tryOpenCrate(Player $player, string $crate, Position $pos): void {

        $item = $player->getInventory()->getItemInHand();

        $tag = $item->getNamedTag()->getTag("crate_key");

        if(!$tag instanceof StringTag){
            $player->sendMessage(
                $this->plugin->getMainConfig()
                    ->getNested("messages.no-key-in-hand")
            );
            return;
        }

        if($tag->getValue() !== $crate){
            $player->sendMessage(
                $this->plugin->getMainConfig()
                    ->getNested("messages.no-key-in-hand")
            );
            return;
        }

        // Remove 1 key
        $item->setCount($item->getCount() - 1);
        $player->getInventory()->setItemInHand($item);

        new AnimationTask($this->plugin, $player, $crate, $pos);
    }
}
