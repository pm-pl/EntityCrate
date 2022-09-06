<?php

declare(strict_types=1);

namespace phuongaz\crate\utils;

use JsonException;
use phuongaz\crate\crates\Crate;
use phuongaz\crate\crates\CrateInventory;
use phuongaz\crate\inventory\CreateCrateInventory;
use phuongaz\crate\Loader;
use pocketmine\entity\Skin;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\Item;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\ToastRequestPacket;
use pocketmine\player\Player;
use pocketmine\utils\Config;

class Utils {

    public static function getRandomItems(array $dropLoot, ?Player $player = null) :?array{
        $chance = mt_rand(0, 54);
        if(count($dropLoot) - 1 < $chance) return null;
        $loots = [];
        $buff = ($player->hasPermission("crate.loot.buff") ? mt_rand(1,4) : mt_rand(1,3));
        foreach($dropLoot as $index => $items) {
            $chance = mt_rand(0, 100);
            if($index >= $chance) {
                if(count($loots) < $buff) {
                    $loots[] = $items;
                }
            }
        }
        return $loots;
    }

    public static function isKeyFitWithCrate(Item $item, string $type) :bool{
        if(($tag = $item->getNamedTag()->getTag("key")) !== null && $tag instanceof StringTag){
            return $tag->getValue() === $type;
        }
        return false;
    }

    public static function enchantment(Item $item, string $enchantString, int $level) :Item{
        $enchantment = StringToEnchantmentParser::getInstance()->parse($enchantString);
        $enchantInstance = new EnchantmentInstance($enchantment, $level);
        $item->addEnchantment($enchantInstance);
        return $item;
    }

    public static function sendToast(Player $player, string $message) :void{
        $packet = ToastRequestPacket::create("§l§cＣＲＡＴＥ", $message);
        $player->getNetworkSession()->sendDataPacket($packet);
    }

    /**
     * @throws JsonException
     */
    public static function saveInventoryasCrate(CreateCrateInventory $crateInventory) :void{
        self::extracted($crateInventory->getCrate());
    }

    /**
     * @throws JsonException
     */
    public static function saveEditCrate(Crate $crate) :void{
        self::extracted($crate);
    }

    public static function getCratesFromConfig() :array{
        $crates = [];
        foreach(glob(Loader::getInstance()->getDataFolder() . "crates/*.yml") as $file){
            $config = new Config($file, Config::YAML);
            $crate = [];
            $crate["payment-method"] = $config->get("payment-method");
            $crate["price"] = $config->get("price");
            $crate["loots"] = $config->get("loots");
            $crates[$config->get("crate")] = $crate;
        }
        return $crates;
    }

    /**
     * @param Crate $crate
     * @return void
     * @throws JsonException
     */
    public static function extracted(Crate $crate): void
    {
        $path = Loader::getInstance()->getDataFolder() . "crates/" . $crate->getName() . ".yml";
        $config = new Config($path, Config::YAML);
        $config->set("crate", $crate->getName());
        $config->set("payment-method", $crate->getPaymentMethod());
        $config->set("price", $crate->getPrice());
        $config->set("loots", json_encode($crate->getLoots()));
        $config->save();
    }

    public static function getCrateByName(string $name) :?Crate{
        $path = Loader::getInstance()->getDataFolder() . "crates/" . $name . ".yml";
        if(file_exists($path)){

            $config = new Config($path, Config::YAML);
            return new CrateInventory($config->get("crate"), $config->get("payment-method"), $config->get("price"), $config->get("loots"));
        }
        return null;
    }

    public function parseCratesToClass(array $crates) :array{
        $crateClasses = [];
        foreach($crates as $name => $crate){
            $crateClasses[$name] = new CrateInventory($name, $crate["payment-method"], $crate["price"], $crate["loots"]);
        }
        return $crateClasses;
    }

    public static function getCratesWithType(string $type) :array {
        $crateWithType = [];
        foreach(Utils::getCratesFromConfig() as $name => $crate){
            if($crate["payment-method"] === $type){
                $crateWithType[$name] = Utils::getCrateByName($name);
            }
        }
        return $crateWithType;
    }

    public static function getEmojiSkin(string $type) :Skin {
        $path = Loader::getInstance()->getDataFolder() . "emoji" . DIRECTORY_SEPARATOR;
        $img = @imagecreatefrompng($path .$type. ".png");
        $bytes = '';
        $l = (int)@getimagesize($path .$type. ".png")[1];

        for ($y = 0; $y < $l; $y++) {
            for ($x = 0; $x < 64; $x++) {
                $rgba = @imagecolorat($img, $x, $y);
                $a = ((~((int)($rgba >> 24))) << 1) & 0xff;
                $r = ($rgba >> 16) & 0xff;
                $g = ($rgba >> 8) & 0xff;
                $b = $rgba & 0xff;
                $bytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }

        @imagedestroy($img);
        $geometryPath = $path . "emoji.json";
        return new Skin("Standard_CustomSlim", $bytes, "", "geometry.emoji", file_get_contents($geometryPath));
    }

}