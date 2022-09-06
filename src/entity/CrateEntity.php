<?php

declare(strict_types=1);

namespace phuongaz\crate\entity;

use phuongaz\crate\crates\Crate;
use phuongaz\crate\event\PlayerCrateEvent;
use phuongaz\crate\inventory\EditCrateInventory;
use phuongaz\crate\inventory\ReviewInventory;
use phuongaz\crate\utils\Utils;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector2;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\Server;

class CrateEntity extends Human {

    private CONST EMOJI_SMILE = "smile";
    private CONST EMOJI_CRY = "cry";

    private ?Player $crateOwner = null;

    private string $keyType;
    private array $crateItems = [];
    private array $lootTable = [];

    public function __construct(Location $location, Skin $skin, ?CompoundTag $nbt = null) {
        parent::__construct($location, $skin, $nbt);
    }

    public function initEntity(CompoundTag $nbt): void{
        $this->keyType = $nbt->getString("Crate");
        $this->crateItems = json_decode($nbt->getString("CrateLoots"), true);
        parent::initEntity($nbt);
    }

    public function saveNBT(): CompoundTag{
        $nbt = parent::saveNBT();
        $nbt->setString("Crate", $this->keyType);
        $nbt->setString("CrateLoots", json_encode($this->crateItems));
        return $nbt;
    }

    public function entityBaseTick(int $tickDiff = 1): bool{
        if(!$this->isAlive() and !$this->closed) {
            $this->flagForDespawn();
            return false;
        }
        if(count($this->lootTable) > 0 && $this->crateOwner != null) {
            $this->setSneaking();
            if(Server::getInstance()->getTick() % 10 == 0) {
                foreach($this->lootTable[0] as $item) {
                    $this->dropItem($item);
                }
                array_shift($this->lootTable);
            }
        }else{
            if($this->isSneaking()) {
                $this->setSneaking(false);
                $this->crateOwner = null;
            }
        }
        return parent::entityBaseTick($tickDiff);
    }

    public function attack(EntityDamageEvent $source): void{
        if($source instanceof EntityDamageByEntityEvent){
            $damager = $source->getDamager();
            if($damager instanceof Player){
                if($this->crateOwner != null) {
                    $damager->sendPopup("§l§cĐã có người quay rồi, vui lòng chờ trong giây lát!");
                    $source->cancel();
                    return;
                }
                $key = $damager->getInventory()->getItemInHand();
                if($damager->isSneaking() && Server::getInstance()->isOp($damager->getName())) {
                    if($damager->getInventory()->getItemInHand()->isNull()) {
                        $this->flagForDespawn();
                        return;
                    }
                    if($damager->getInventory()->getItemInHand()->getId() == ItemIds::STICK) {
                        (new EditCrateInventory($this->keyType, Utils::getCrateByName($this->keyType), $this))->createInventory($damager);
                        return;
                    }
                }
                if(Utils::isKeyFitWithCrate($key, $this->keyType)) {

                    $this->crateOwner = $damager;
                    $key = $key->getCount() > 1 ? $key->setCount($key->getCount() - 1) : VanillaItems::AIR();
                    $this->lookAtLocation($this->crateOwner->getLocation());
                    $this->crateOwner->getInventory()->setItemInHand($key);
                    $items = Utils::getRandomItems(Utils::getCrateByName($this->keyType)->getLoots(), $this->crateOwner);
                    if ($items == null || count($items) == 0) {
                        $event = new PlayerCrateEvent($this->crateOwner, Utils::getCrateByName($this->keyType), PlayerCrateEvent::FAIL);
                        $event->call();
                        if($event->isCancelled()) {
                            $this->crateOwner = null;
                            return;
                        }
                        $this->crateOwner->sendPopup("§l§cChúc bạn may mắn lần sau!!");
                        $this->sendEmoji(self::EMOJI_CRY, 15);
                        $this->crateOwner = null;
                        return;
                    }
                    $event = new PlayerCrateEvent($this->crateOwner, Utils::getCrateByName($this->keyType), PlayerCrateEvent::SUCCESS);
                    $event->call();
                    if ($event->isCancelled()) {
                        $this->crateOwner = null;
                        return;
                    }
                    $this->crateOwner->sendPopup("§l§aChúc mừng bạn đã trúng thưởng!!");
                    $this->sendEmoji(self::EMOJI_SMILE, 15);
                    $this->lootTable = $items;
                    return;
                }
                (new ReviewInventory(Utils::getCrateByName($this->keyType), $damager))->createInventory();
            }
        }
        $source->cancel();
        parent::attack($source);
    }

    public function getLoots() :array{
        return $this->crateItems;
    }

    public function syncCrate(Crate $crate) :void {
        $this->keyType = $crate->getName();
        $this->crateItems = json_decode($crate->getSerializeLoots(), true);
    }

    public function dropItem(Item $item) : void{
        $nbt = $item->getNamedTag()->setString("CrateLoot", $this->crateOwner->getName());
        $item->setNamedTag($nbt);
        $this->lookAtLocation($this->crateOwner?->getLocation());
        $this->broadcastAnimation(new ArmSwingAnimation($this), $this->getViewers());
        $this->getWorld()->dropItem($this->location->add(0, 1.3, 0), $item, $this->getDirectionVector()->multiply(0.4), 30);
    }

    protected function lookAtLocation(Location $location): array{
        $angle = atan2($location->z - $this->getLocation()->z, $location->x - $this->getLocation()->x);
        $yaw = (($angle * 180) / M_PI) - 90;
        $angle = atan2((new Vector2($this->getLocation()->x, $this->getLocation()->z))->distance(new Vector2($location->x, $location->z)), $location->y - $this->getLocation()->y);
        $pitch = (($angle * 180) / M_PI) - 90;
        $this->setRotation($yaw, $pitch);
        return [$yaw, $pitch];
    }

    private function sendEmoji(string $type, int $timeflag) :void {
        $emoji = new emoji($this->getLocation(), Utils::getEmojiSkin($type));
        $emoji->setScale(0.2);
        $emoji->setBaseTimeFlag($timeflag);
        $emoji->setOwner($this->crateOwner);
        $emoji->spawnToAll();
    }

}