<?php
/**
 * Created by PhpStorm.
 * User: garrus
 * Date: 15-7-20
 * Time: 上午2:17
 */

return [
    'time' => 30 * 24 * 3600,
    'buildRate' => 1000 / 3600,
    'startMetal' => 1000,
	'startCrystal' => 500,
    'baseMetalProduct' => 60 / 3600,
	'baseCrystalProduct' => 30 / 3600,

	// metal mine
	'building.metalMine.cost.metal' => function($level){
		return 60 * $level * pow($level - 1, 1.5);
	},
	'building.metalMine.cost.crystal' => function($level){
		return 15 * $level * pow($level - 1, 1.5);
	},
	'building.metalMine.product.metal' => function($level){
		return 90 * $level * pow($level - 1, 1.1);
	},
	'building.metalMine.consume' => function($level){
		return 10 * $level * pow($level - 1, 1.1);
	},

	// crystal mine
	'building.crystalMine.cost.metal' => function($level){
		return 45 * $level * pow($level - 1, 1.5);
	},
	'building.crystalMine.cost.crystal' => function($level){
		return 24 * $level * pow($level - 1, 1.5);
	},
	'building.crystalMine.product.crystal' => function($level){
		return 45 * $level * pow($level - 1, 1.1);
	},
	'building.crystalMine.consume' => function($level){
		return 12 * $level * pow($level - 1, 1.1);
	},

	// deuterium
	'building.deuteriumSynthesizer.cost.metal' => function($level){
		return 45 * $level * pow($level - 1, 1.5);
	},
	'building.deuteriumSynthesizer.cost.crystal' => function($level){
		return 24 * $level * pow($level - 1, 1.5);
	},
	'building.deuteriumSynthesizer.product.deuterium' => function($level){
		return 15 * $level * pow($level - 1, 1.1);
	},
	'building.deuteriumSynthesizer.consume' => function($level){
		return 12 * $level * pow($level - 1, 1.1);
	},

	// solar plant
    'building.solar.cost.metal' => function($level){
        return 75 * pow($level - 1, 1.6);
    },
	'building.solar.cost.crystal' => function($level){
		return 15 * pow($level - 1, 1.6);
	},
    'building.solar.product.energy' => function($level) {
        return 20 * $level * pow($level - 1, 1.1);
    },

	// robot
    'building.robot.cost.metal' => function($level){
        return 100 * pow($level - 1, 2);
    },
	'building.robot.cost.crystal' => function($level){
		return 15 * pow($level - 1, 2);
	},

	// nano
    'building.nano.cost.metal' => function($level){
        return 1000000 * pow($level - 1, 2);
    },
	'building.nano.cost.crystal' => function($level){
		return 500000 * pow($level - 1, 2);
	},

];