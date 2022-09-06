<?php

declare(strict_types=1);

namespace phuongaz\crate\crates;

use labalityowo\Lcoin\Balance;
use phuongaz\crate\entity\CrateEntity;
use phuongaz\crate\utils\Utils;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;

abstract class Crate {

    public const LCOIN = Balance::LCOIN;
    public const MONEY = Balance::MONEY;

    public bool $isInventoryCrate = false;

    protected array $loots;
    protected string $name;
    protected string $paymentMethod;
    protected int $price;

    abstract public function getName() :string;

    abstract public function getPrice() :int;

    abstract public function getPaymentMethod() :string;

    public function getLoots() :array{
        return $this->loots;
    }

    public function isInventoryCrate() :bool {
        return $this->isInventoryCrate;
    }

    public static function getCrates() :array {
        $crates = [];
        foreach(self::getInventoryCrates() as $crate) {
            $crates[$crate->getName()] = $crate;
        }
        return $crates;
    }

    public static function getInventoryCrates() :array {
        $crateInventory = Utils::getCratesFromConfig();
        $crates = [];
        foreach($crateInventory as $name => $crate) {
            $crates[] = new CrateInventory($name, $crate["payment-method"], $crate["price"], $crate["loots"]);
        }
        return $crates;
    }

    public function spawn(Location $location, Skin $skin) :void {
        $nbt = CompoundTag::create();
        $nbt->setString("Crate", $this->getName());
        $nbt->setString("CrateLoots", json_encode($this->getLoots()));
        $crateEntity = new CrateEntity($location, $skin, $nbt);
        $crateEntity->setNameTagAlwaysVisible();
        $crateEntity->setNameTag("§l§cＣＲＡＴＥ\n§f" . $this->getName() . "§f\nCầm bảo ngọc trên tay để mở\nClick để xem vật phẩm!");
        $crateEntity->spawnToAll();
    }

    public function getKey(int $count = 1) :Item {
        $item = VanillaItems::EMERALD();
        $item->setCustomName("§l§cＢảo ngọc §7(§e". $this->getName(). "§7)");
        $nbt = $item->getNamedTag();
        $nbt->setString("key", $this->getName());
        $item->setNamedTag($nbt);
        $item->setCount($count);
        return $item;
    }

    public function setLoots(array $loots) :void {
        $this->loots = $loots;
    }

    public function getSerializeLoots() :string {
        return json_encode($this->getLoots());
    }

}