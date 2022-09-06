<?php

declare(strict_types=1);

namespace phuongaz\crate\inventory;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use phuongaz\crate\crates\Crate;
use pocketmine\player\Player;

class ReviewInventory {

    private Player $player;
    private Crate $crate;

    public function __construct(Crate $crate, Player $player){
        $this->crate = $crate;
        $this->player = $player;
    }


    public function createInventory(): void{
        $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
        $menu->setName("Các vật phẩm có thể nhận được");
        foreach($this->crate->getLoots() as $index => $item){
            $previewItem = $item[0];
            $previewItem->setLore(["§l§fMua bảo ngọc tại §d/crate"]);
            $menu->getInventory()->setItem($index, $item[0]);
        }
        $menu->setListener(function(InvMenuTransaction $transaction) :InvMenuTransactionResult{
            return $transaction->discard();
        });
        $menu->send($this->player);
    }

}