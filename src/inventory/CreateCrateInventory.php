<?php

declare(strict_types=1);

namespace phuongaz\crate\inventory;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use phuongaz\crate\crates\Crate;
use phuongaz\crate\utils\Utils;
use pocketmine\inventory\Inventory;
use pocketmine\player\Player;

class CreateCrateInventory {

    private array $loots;

    public function __construct(private string $name, private Crate $crate) {}

    public function getName(): string {
        return $this->name;
    }

    public function getCrate(): Crate {
        return $this->crate;
    }

    public function createInventory(Player $player) :void {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
        $menu->setName($this->getName() . " Crate");
        $menu->setInventoryCloseListener($this->closeInventory());
        $menu->send($player);
    }

    public function closeInventory() :\Closure {
        return function(Player $player, Inventory $inventory) :void {
            $loots = [];
            $index = 0;
            foreach($inventory->getContents() as $item) {
                if($item->isNull()) continue;
                $loots[(string)$index++] = [$item];
            }
            $this->getCrate()->setLoots($loots);
            Utils::saveInventoryasCrate($this);
            $player->sendMessage("§aYou have successfully created a crate!");
        };
    }
}