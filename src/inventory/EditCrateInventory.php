<?php

declare(strict_types=1);

namespace phuongaz\crate\inventory;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use phuongaz\crate\crates\Crate;
use phuongaz\crate\entity\CrateEntity;
use phuongaz\crate\utils\Utils;
use pocketmine\inventory\Inventory;
use pocketmine\player\Player;

class EditCrateInventory {

    private array $loots;

    public function __construct(private string $name, private Crate $crate, private ?CrateEntity $crateEntity = null) {}

    public function getName(): string {
        return $this->name;
    }

    public function getCrate(): Crate {
        return $this->crate;
    }

    public function getCrateEntity(): ?CrateEntity {
        return $this->crateEntity;
    }

    public function createInventory(Player $player) :void {
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
        $menu->setName($this->getName() . " Crate Edit");
        foreach($this->getCrate()->getLoots() as $index => $items) {

            $menu->getInventory()->setItem($index, $items[0]);
        }
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
            Utils::saveEditCrate($this->getCrate());
            if($this->getCrateEntity() !== null) {
                $this->getCrateEntity()->syncCrate($this->getCrate());
                $player->sendMessage("Auto sync crate entity");
            }
            $player->sendMessage("§aYou have successfully edited a crate!");
        };
    }
}