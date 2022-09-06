<?php

declare(strict_types=1);

namespace phuongaz\crate\entity;

use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\math\Vector2;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

class emoji extends Human {

    private int $baseTimeFlag = 10;

    private Player $player;

    public function __construct(Location $location, Skin $skin, ?CompoundTag $nbt = null) {
        parent::__construct($location, $skin, $nbt);
    }

    public function setBaseTimeFlag(int $baseTimeFlag) :void {
        $this->baseTimeFlag = $baseTimeFlag;
    }

    public function setOwner(Player $player) :void {
        $this->player = $player;
    }

    public function entityBaseTick(int $tickDiff = 1): bool{
        if(!$this->isAlive() and !$this->closed) {
            $this->flagForDespawn();
            return false;
        }
        if($this->baseTimeFlag == 0) {
            $this->flagForDespawn();
        }
        $this->lookAtLocation($this->player?->getLocation());
        $this->location->yaw = $this->location->yaw + 25;
        $this->location->y = $this->location->y + 0.2;
        $this->baseTimeFlag--;
        return parent::entityBaseTick($tickDiff);
    }

    protected function lookAtLocation(Location $location): array{
        $angle = atan2($location->z - $this->getLocation()->z, $location->x - $this->getLocation()->x);
        $yaw = (($angle * 180) / M_PI) - 90;
        $angle = atan2((new Vector2($this->getLocation()->x, $this->getLocation()->z))->distance(new Vector2($location->x, $location->z)), $location->y - $this->getLocation()->y);
        $pitch = (($angle * 180) / M_PI) - 90;
        $this->setRotation($yaw, $pitch);
        return [$yaw, $pitch];
    }
}