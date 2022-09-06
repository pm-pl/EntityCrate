<?php

declare(strict_types=1);

namespace phuongaz\crate;

//use phuongaz\core\utils\Utils;
use phuongaz\crate\event\PlayerCrateEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\world\sound\BellRingSound;

class EventHandler implements Listener {

    public function onPickup(EntityItemPickupEvent $event) :void{
        $entity = $event->getEntity();
        $item = $event->getItem();
        if($entity instanceof Player) {
            if (($tag = $item->getNamedTag()->getTag("CrateLoot")) !== null) {
                if ($entity->getName() != $tag->getValue()) {
                    $event->cancel();
                    return;
                }
                $item->getNamedTag()->removeTag("CrateLoot");
            }
        }
    }

//    public function onScroll(PlayerCrateEvent $event) :void {
//        $player = $event->getPlayer();
//        $result = $event->getResult();
//        if($result === PlayerCrateEvent::SUCCESS) {
//            Utils::sound($player, new BellRingSound());
//        }
//    }
}