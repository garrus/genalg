<?php
/**
 * Created by PhpStorm.
 * User: garrus
 * Date: 15-7-20
 * Time: 上午2:17
 */

return [
	'time'                                  => 60 * 24 * 3600,
	'buildRate'                             => 1000 / 3600,

	'startResource'                         => [
		'metal'     => 2000,
		'crystal'   => 1000,
		'deuterium' => 500,
	],
	'baseProduct'                           => [
		'metal'     => 60 / 3600,
		'crystal'   => 30 / 3600,
		'deuterium' => 0,
	],

	// metal mine
	'building.metalMine.cost'               => function ($level){

		return [
			'metal'   => 60 * $level * pow(1.5, $level - 1),
			'crystal' => 15 * $level * pow(1.5, $level - 1),
		];
	},
	'building.metalMine.product'            => function ($level){

		return [
			'metal' => 90 * $level * pow(1.1, $level - 1) / 3600,
		];
	},
	'building.metalMine.consume'            => function ($level){

		return 10 * $level * pow(1.1, $level - 1);
	},

	// crystal mine
	'building.crystalMine.cost'             => function ($level){

		return [
			'metal'   => 48 * $level * pow(1.6, $level - 1),
			'crystal' => 24 * $level * pow(1.6, $level - 1),
		];
	},
	'building.crystalMine.product'          => function ($level){

		return [
			'crystal' => 45 * $level * pow(1.1, $level - 1) / 3600,
		];
	},
	'building.crystalMine.consume'          => function ($level){

		return 10 * $level * pow(1.1, $level - 1);
	},

	// deuterium
	'building.deuteriumSynthesizer.cost'    => function ($level){

		return [
			'metal'   => 225 * $level * pow(1.5, $level - 1),
			'crystal' => 75 * $level * pow(1.5, $level - 1),
		];
	},
	'building.deuteriumSynthesizer.product' => function ($level){

		return [
			'deuterium' => 30 * $level * pow(1.1, $level - 1) / 3600,
		];
	},
	'building.deuteriumSynthesizer.consume' => function ($level){

		return 20 * $level * pow(1.1, $level - 1);
	},

	// solar plant
	'building.solar.cost'                   => function ($level){

		return [
			'metal'   => 75 * pow(1.5, $level - 1),
			'crystal' => 30 * pow(1.5, $level - 1),
		];
	},
	'building.solar.energy'                 => function ($level){

		return 20 * $level * pow(1.1, $level - 1);
	},

	// nuclear plant
	'building.nuclear.cost'                 => function ($level){

		return [
			'metal'     => 900 * pow(1.8, $level - 1),
			'crystal'   => 360 * pow(1.8, $level - 1),
			'deuterium' => 180 * pow(1.8, $level - 1),
		];
	},
	'building.nuclear.product'              => function ($level){

		return [
			'deuterium' => -10 * $level * pow(1.1, $level - 1) / 3600,
		];
	},
	'building.nuclear.energy'               => function ($level){

		return 50 * $level * pow(1.1, $level - 1);
	},

	// robot
	'building.robot.cost'                   => function ($level){

		return [
			'metal'     => 400 * pow(2, $level - 1),
			'crystal'   => 120 * pow(2, $level - 1),
			'deuterium' => 200 * pow(2, $level - 1),
		];
	},

	// nano
	'building.nano.cost'                    => function ($level){

		return [
			'metal'     => 1000000 * pow(2, $level - 1),
			'crystal'   => 500000 * pow(2, $level - 1),
			'deuterium' => 200000 * pow(2, $level - 1),
		];
	},
];