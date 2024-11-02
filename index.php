<?php

class Character {
    public $name;
    public $hp;
    public $level;
    public $attack;
    public $magicAttack;
    public $defense;
    public $magicDefense;
    public $tech;
    public $evasion;
    
    public function __construct($name, $hp, $level, $attack, $magicAttack, $defense, $magicDefense, $tech, $evasion) {
        $this->name = $name;
        $this->hp = $hp;
        $this->level = $level;
        $this->attack = $attack;
        $this->magicAttack = $magicAttack;
        $this->defense = $defense;
        $this->magicDefense = $magicDefense;
        $this->tech = $tech;
        $this->evasion = $evasion;
    }
    
    public function isAlive() {
        return $this->hp > 0;
    }

    public function takePhysicalDamage($damage) {
        $damage = $damage - $this->defense;
        if ($damage < 0) $damage = 0;
        $this->hp -= $damage;
        if ($this->hp < 0) {
            $this->hp = 0;
        }
    }

    public function takeMagicDamage($damage) {
        $damage = $damage - $this->magicDefense;
        if ($damage < 0) $damage = 0;
        $this->hp -= $damage;
        if ($this->hp < 0) {
            $this->hp = 0;
        }
    }
}

class Player extends Character {
    public $role; // 'Attacker', 'Tank', 'Healer'
    
    public function __construct($name, $hp, $level, $attack, $magicAttack, $defense, $magicDefense, $tech, $evasion, $role) {
        parent::__construct($name, $hp, $level, $attack, $magicAttack, $defense, $magicDefense, $tech, $evasion);
        $this->role = $role;
    }

    public function physicalAttack($target) {
        $damage = $this->attack;
        $target->takePhysicalDamage($damage);
        return $damage;
    }

    public function magicAttack($target) {
        $damage = $this->magicAttack;
        $target->takeMagicDamage($damage);
        return $damage;
    }
}

class Enemy extends Character {
    public function physicalAttack($target) {
        $damage = $this->attack;
        $target->takePhysicalDamage($damage);
        return $damage;
    }

    public function magicAttack($target) {
        $damage = $this->magicAttack;
        $target->takeMagicDamage($damage);
        return $damage;
    }
}

class Game {
    public $players;
    public $enemy;
    
    public function __construct($players, $enemy) {
        $this->players = $players;
        $this->enemy = $enemy;
    }

    public function isGameOver() {
        foreach ($this->players as $player) {
            if ($player->isAlive()) {
                return false;
            }
        }
        return true;
    }

    public function isVictory() {
        return !$this->enemy->isAlive();
    }
    
    public function playerTurn($playerIndex, $action, $targetIndex = null) {
        $player = $this->players[$playerIndex];
        if ($action == 'physical') {
            $damage = $player->physicalAttack($this->enemy);
            return "Player {$player->name} attacked Enemy for {$damage} physical damage!";
        } else if ($action == 'magic') {
            $damage = $player->magicAttack($this->enemy);
            return "Player {$player->name} attacked Enemy for {$damage} magic damage!";
        }
        return "Player {$player->name} is waiting.";
    }

    public function enemyTurn() {
        $alivePlayers = array_filter($this->players, function($player) {
            return $player->isAlive();
        });
        if (!empty($alivePlayers)) {
            $target = $alivePlayers[array_rand($alivePlayers)];
            $damageType = rand(0, 1) ? 'physical' : 'magic';
            if ($damageType == 'physical') {
                $damage = $this->enemy->physicalAttack($target);
                return "Enemy attacked Player {$target->name} for {$damage} physical damage!";
            } else {
                $damage = $this->enemy->magicAttack($target);
                return "Enemy attacked Player {$target->name} for {$damage} magic damage!";
            }
        }
    }
}

<?php
session_start();

require 'game.php';

if (!isset($_SESSION['game'])) {
    $players = [
        new Player('Attacker', 100, 1, 20, 10, 10, 5, 15, 10, 'Attacker'),
        new Player('Tank', 150, 1, 10, 5, 20, 15, 10, 5, 'Tank'),
        new Player('Healer', 80, 1, 5, 15, 10, 10, 20, 15, 'Healer')
    ];
    $enemy = new Enemy('Goblin', 200, 1, 15, 10, 10, 5, 10, 5);
    $game = new Game($players, $enemy);
    $_SESSION['game'] = $game;
} else {
    $game = $_SESSION['game'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $playerIndex = $_POST['playerIndex'];
    $message = $game->playerTurn($playerIndex, $action);
    if ($game->isVictory()) {
        $message .= "<br>Victory!";
        session_destroy();
    } else if ($game->isGameOver()) {
        $message .= "<br>Game Over!";
        session_destroy();
    } else {
        $message .= "<br>" . $game->enemyTurn();
    }
    $_SESSION['game'] = $game;
} else {
    $message = "Choose an action for the player.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Roguelike Battle Game</title>
</head>
<body>
    <h1>Roguelike Battle Game</h1>
    <p><?= $message ?></p>
    <form method="post">
        <input type="hidden" name="playerIndex" value="0">
        <button type="submit" name="action" value="physical">Physical Attack</button>
        <button type="submit" name="action" value="magic">Magic Attack</button>
        <button type="submit" name="action" value="wait">Wait</button>
    </form>
    <!-- Add more forms for other players here -->
</body>
</html>


