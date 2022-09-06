<?php

declare(strict_types=1);

namespace phuongaz\crate\crates;

use pocketmine\item\Item;

class CrateInventory extends Crate {

    public function __construct(string $name, string $paymentMethod, int $price, string $loots = "") {
        $this->name = $name;
        $this->paymentMethod = $paymentMethod;
        $arrayLoots = [];
        if($loots != "") {
            foreach(json_decode($loots, true) as $chance => $items) {
                $arrayLoots[$chance] = array_map(function($item) {
                    return Item::jsonDeserialize($item);
                }, $items);
            }
        }
        $this->loots = $arrayLoots;
        $this->price = $price;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getPaymentMethod(): string {
        return $this->paymentMethod;
    }

    public function getPrice(): int {
        return $this->price;
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function isInventoryCrate() :bool {
        return true;
    }

}