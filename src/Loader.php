<?php

declare(strict_types=1);

namespace phuongaz\crate;

use muqsit\invmenu\InvMenuHandler;
use phuongaz\crate\command\CrateCommand;
use phuongaz\crate\entity\CrateEntity;
use phuongaz\crate\entity\emoji;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Human;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;

class Loader extends PluginBase {
    use SingletonTrait;

    public function onLoad(): void{
        self::setInstance($this);
    }

    public function onEnable() :void {
        $this->saveResource("emoji/emoji.json");
        $this->saveResource("emoji/smile.png");
        $this->saveResource("emoji/cry.png");
        $path = $this->getDataFolder() . "crates";
        if(!file_exists($path)) {
            mkdir(Loader::getInstance()->getDataFolder() . "crates", 0777, true);
        }
        if(!InvMenuHandler::isRegistered()){
            InvMenuHandler::register($this);
        }
        EntityFactory::getInstance()->register(CrateEntity::class, function(World $world, CompoundTag $nbt) :CrateEntity{
            return new CrateEntity(EntityDataHelper::parseLocation($nbt, $world), Human::parseSkinNBT($nbt), $nbt);
        }, ["CrateEntity"]);

        EntityFactory::getInstance()->register(emoji::class, function(World $world, CompoundTag $nbt) :emoji{
            return new emoji(EntityDataHelper::parseLocation($nbt, $world), Human::parseSkinNBT($nbt), $nbt);
        }, ["EmojiEntity"]);

        Server::getInstance()->getCommandMap()->register("crate", new CrateCommand());
        Server::getInstance()->getPluginManager()->registerEvents(new EventHandler(), $this);
    }
}