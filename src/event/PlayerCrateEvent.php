<?php

declare(strict_types=1);

namespace phuongaz\crate\event;

use phuongaz\crate\crates\Crate;
use pocketmine\event\CancellableTrait;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class PlayerCrateEvent extends PlayerEvent {
    use CancellableTrait;

    private Crate $crate;

    public const SUCCESS = 1;
    public const FAIL = 2;
    private int $result;

    public function __construct(Player $player, Crate $crate, int $result) {
        $this->crate = $crate;
        $this->player = $player;
        $this->result = $result;
    }

    public function getCrate() : Crate {
        return $this->crate;
    }

    public function getResult() : int {
        return $this->result;
    }
}