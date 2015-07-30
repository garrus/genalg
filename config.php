<?php
/**
 * Created by PhpStorm.
 * User: garrus
 * Date: 15-7-20
 * Time: 上午2:17
 */

return [
	'sys.seeds' => 6,
	'sys.seedLength' => 300,
    'sys.exchangePtg' => 70,
    'sys.mutantPtg' => 15,
	'sys.mutantRange' => 5,
    'sys.generations' => 10000,

    'game.time' => 3000 * 3600,
    'game.buildRate' => 1000 / 3600,
    'game.startGold' => 1000,
    'game.baseGoldProduct' => 10 / 3600,

	// metal mine
	'game.building.metalMine.cost.metal' => function($level){
		return 60 * $level * pow($level - 1, 1.5);
	},
	'game.building.metalMine.cost.crystal' => function($level){
		return 15 * $level * pow($level - 1, 1.5);
	},
	'game.building.metalMine.product.metal' => function($level){
		return 30 * $level * pow($level - 1, 1.1);
	},
	'game.building.metalMine.consume' => function($level){
		return 10 * $level * pow($level - 1, 1.1);
	},

	// crystal mine
	'game.building.crystalMine.cost.metal' => function($level){
		return 45 * $level * pow($level - 1, 1.5);
	},
	'game.building.crystalMine.cost.crystal' => function($level){
		return 24 * $level * pow($level - 1, 1.5);
	},
	'game.building.crystalMine.product.crystal' => function($level){
		return 15 * $level * pow($level - 1, 1.1);
	},
	'game.building.crystalMine.consume' => function($level){
		return 12 * $level * pow($level - 1, 1.1);
	},

	// deuterium
	'game.building.deuteriumSynthesizer.cost.metal' => function($level){
		return 45 * $level * pow($level - 1, 1.5);
	},
	'game.building.deuteriumSynthesizer.cost.crystal' => function($level){
		return 24 * $level * pow($level - 1, 1.5);
	},
	'game.building.deuteriumSynthesizer.product.deuterium' => function($level){
		return 15 * $level * pow($level - 1, 1.1);
	},
	'game.building.deuteriumSynthesizer.consume' => function($level){
		return 12 * $level * pow($level - 1, 1.1);
	},

	// solar plant
    'game.building.solar.cost.metal' => function($level){
        return 75 * pow($level - 1, 1.6);
    },
	'game.building.solar.cost.crystal' => function($level){
		return 15 * pow($level - 1, 1.6);
	},
    'game.building.solar.product.energy' => function($level) {
        return 20 * $level * pow($level - 1, 1.1);
    },

	// robot
    'game.building.robot.cost.metal' => function($level){
        return 100 * pow($level - 1, 2);
    },
	'game.building.robot.cost.crystal' => function($level){
		return 15 * pow($level - 1, 2);
	},

	// nano
    'game.building.nano.cost.metal' => function($level){
        return 1000000 * pow($level - 1, 2);
    },
	'game.building.nano.cost.crystal' => function($level){
		return 500000 * pow($level - 1, 2);
	},

];