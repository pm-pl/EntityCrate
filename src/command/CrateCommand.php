<?php

declare(strict_types=1);

namespace phuongaz\crate\command;

use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use labalityowo\Lcoin\Balance;
use phuongaz\crate\crates\Crate;
use phuongaz\crate\crates\CrateInventory;
use phuongaz\crate\inventory\CreateCrateInventory;
use phuongaz\crate\inventory\EditCrateInventory;
use phuongaz\crate\utils\Utils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use labalityowo\Lcoin\Main as Economy;
use pocketmine\Server;

class CrateCommand extends Command {

    public function __construct(){
        parent::__construct("crate", "Crate command", "/crate");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) :void{
        if($sender instanceof Player) {
            if(isset($args[0]) && Server::getInstance()->isOp($sender->getName())) {
                if($args[0] == "inventory") {
                    if(isset($args[1])) {
                        match ($args[1]) {
                            "create" => $this->createInventoryForm($sender),
                            "edit" => $this->editInventoryForm($sender),
                            default => null
                        };
                    }
                    return;
                }
                $this->CrateForm($sender);
                return;
            }
            $this->startForm($sender);
        }
    }

    public function CrateForm(Player $player) :void {
        $crates = Crate::getCrates();
        $form = new SimpleForm(function (Player $player, $data) use ($crates) {
            if(is_null($data)) {
                $this->startForm($player);
                return;
            }
            $crate = array_values($crates)[$data];
            if($crate instanceof Crate) {
                $crate->spawn($player->getLocation(), $player->getSkin());
                $player->sendMessage("§aSpawned ". $crate->getName() . " crate");
            }
        });
        $form->setTitle("§l§cＣＲＡＴＥ");
        $form->setContent("§7Chọn loại §cＣＲＡＴＥ §7mà bạn muốn tạo");
        foreach($crates as $name => $crate) {
            $form->addButton($name);
        }
        $player->sendForm($form);
    }

    public function startForm(Player $player) :void {
        $form = new SimpleForm(function(Player $player, ?int $data) {
            if(is_null($data)) return;
            $type = match ($data) {
                0 => Balance::LCOIN,
                1 => Balance::MONEY,
                default => null
            };
            $this->shopCrates($player, $type);
        });
        $form->setTitle("§l§cＣＲＡＴＥ");
        $form->setContent("§7Chọn loại §cbảo ngọc §7mà bạn muốn mua");
        $form->addButton("§l§f●§0 LCoin §f●§0");
        $form->addButton("§l§f●§0 Xu §f●§0");
        $player->sendForm($form);
    }

    public function shopCrates(Player $player, string $type) :void {
        $crates = Utils::getCratesWithType($type);
        $form = new SimpleForm(function (Player $player, ?int $data) use ($crates, $type) {
            if(is_null($data)) {
                $this->startForm($player);
                return;
            }
            $crate = array_values($crates)[$data];
            $confirmForm = new CustomForm(function(Player $player, ?array $dataOption) use ($crate, $data) {
                if(is_null($dataOption)) {
                    $this->startForm($player);
                    return;
                }
                if($crate instanceof Crate) {
                    $paymentMethod = $crate->getPaymentMethod();
                    $price = $crate->getPrice();
                    $eco = Economy::getInstance();
                    $count = $dataOption[1] ?? 1;
                    if(!is_numeric($count)) {
                        $player->sendMessage("§cSố lượng phải là số");
                        return;
                    }
                    if($eco->getBalance($player)->getValue($paymentMethod) >= $price * $count) {
                        $player->getInventory()->addItem($crate->getKey((int)$count));
                        $eco->getBalance($player)->reduceValue($paymentMethod, $price * $count, "Crate purchase");
                        $player->sendMessage("§aBạn đã mua §ex". $count . "§a bảo ngọc §e" . $crate->getName());
                    } else {
                        $player->sendMessage("§cBạn không đủ tiền để mua §ex". $count ."§c bảo ngọc§e ". $crate->getName());
                    }
                }
            });
            $confirmForm->setTitle("§l§cＣＲＡＴＥ");
            $confirmForm->addLabel("§fBạn có chắc chắn muốn mua §cＣＲＡＴＥ §7(§f".$crate->getName()."§7)§f với giá §e". $crate->getPrice() ." §f". $crate->getPaymentMethod() ."§7 /§e 1 không?");
            $confirmForm->addInput("§7Nhập số lượng muốn mua", "1", "1");
            $player->sendForm($confirmForm);
        });
        $form->setTitle("LOCM");
        $form->setContent("§7Chọn loại §cbảo ngọc §7mà bạn muốn mua");
        foreach($crates as $name => $crate) {
            if($crate->getPaymentMethod() == $type) {
                $form->addButton($name, 0, "textures/items/emerald");
            }
        }
        $player->sendForm($form);
    }

    public function createInventoryForm(Player $player) :void {
        $paymentMethods = [Balance::MONEY, Balance::LCOIN];
        $form = new CustomForm(function (Player $player, ?array $data) use ($paymentMethods) {
            if(is_null($data)) {
                return;
            }
            $name = $data[0];
            $price = $data[1];
            $paymentMethod = $paymentMethods[$data[2]];
            $crate = new CrateInventory($name, $paymentMethod, (int)$price);
            $inventory = new CreateCrateInventory($name, $crate);
            $inventory->createInventory($player);
        });
        $form->setTitle("§l§cＣＲＡＴＥ");

        $form->addInput("§7Nhập tên của §cＣＲＡＴＥ", "Tên");
        $form->addInput("§7Nhập giá của §cＣＲＡＴＥ", "Giá");
        $form->addDropdown("§7Chọn loại tiền", $paymentMethods);
        $player->sendForm($form);
    }

    public function editInventoryForm(Player $playr) :void {
        $crates = Crate::getCrates();
        $form = new SimpleForm(function(Player $player, ?int $data) use ($crates) {
            if(is_null($data)) {
                return;
            }
            $crate = array_values($crates)[$data];
            $inventory = new EditCrateInventory($crate->getName(), $crate);
            $inventory->createInventory($player);
        });
        $form->setTitle("§l§cＣＲＡＴＥ");
        $form->setContent("§7Chọn loại §cＣＲＡＴＥ §7mà bạn muốn sửa");
        foreach($crates as $name => $crate) {
            $form->addButton($name);
        }
        $playr->sendForm($form);
    }
}