<?php

declare(strict_types=1);

namespace AdvancedCrates;

use pocketmine\scheduler\Task;
use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\item\StringToItemParser;

class AnimationTask extends Task {

    private Main $plugin;
    private Player $player;
    private string $crate;
    private Position $pos;
    private int $ticks = 0;

    public function __construct(
        Main $plugin,
        Player $player,
        string $crate,
        Position $pos
    ){
        $this->plugin = $plugin;
        $this->player = $player;
        $this->crate = $crate;
        $this->pos = $pos;

        $plugin->getScheduler()->scheduleRepeatingTask($this, 5);
    }

    public function onRun(): void {

        $this->ticks++;

        if($this->ticks >= 20){
            $this->giveReward();
            $this->getHandler()?->cancel();
        }
    }

    private function giveReward(): void {

        $rewards = $this->plugin
            ->getCrateConfig()
            ->getNested("crates.$this->crate.rewards");

        if($rewards === null) return;

        $total = 0;
        foreach($rewards as $reward){
            $total += $reward["chance"];
        }

        $rand = mt_rand(1, $total);
        $current = 0;

        foreach($rewards as $reward){
            $current += $reward["chance"];

            if($rand <= $current){

                $item = StringToItemParser::getInstance()
                    ->parse($reward["item"]);

                if($item !== null){
                    $item->setCount($reward["amount"]);
                    $this->player->getInventory()->addItem($item);

                    if($this->crate === "legendary"){
                        $msg = str_replace(
                            ["{player}", "{reward}", "{crate}"],
                            [
                                $this->player->getName(),
                                $item->getName(),
                                ucfirst($this->crate)
                            ],
                            $this->plugin->getMainConfig()
                                ->getNested("messages.reward-broadcast")
                        );

                        $this->plugin->getServer()
                            ->broadcastMessage($msg);
                    }
                }
                return;
            }
        }
    }
}
