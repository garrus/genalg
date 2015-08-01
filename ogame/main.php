<?php
require dirname(__DIR__) . '/lib/Cache.php';
require dirname(__DIR__) . '/lib/GeneticAlgorithm.php';
require __DIR__ . '/StationManager.php';

StationManager::$gameConfig = require __DIR__ . '/game.php';

for ($i = 0; $i < 100; $i++) {
	printf('Run round %d ... ', $i + 1);
	ob_start();
	$score = main();
	ob_get_clean();
	echo $score, PHP_EOL;
}

function main(){

	/* == Config === */
	$geneCount = 6;
	$planLength = 100;
	$exchangeProbability = 70;
	$mutateProbability = 10;
	$mutateBits = 20;
	$generations = 10000;
	/* == Config end == */

	$seeds = file(__DIR__ . DIRECTORY_SEPARATOR . 'seeds.txt');
	$seeds = array_filter($seeds, function ($seed){

		return !!trim($seed);
	});
	$pos = array_rand($seeds, $geneCount);

	for ($genes = []; count($genes) < $geneCount;) {
		$genes[] = new StationManager($planLength, $mutateBits, trim($seeds[array_shift($pos)]));
	}
	$algorithmConfig = compact('genes', 'generations', 'mutateProbability', 'exchangeProbability');
	list($manager, $score) = GeneticAlgorithm::run($algorithmConfig);

	if (!empty($manager->info['product'])) {
		readfile(recordPlan($manager));
	}
	return $score;
}

/**
 * @param StationManager $manager
 *
 * @return string
 */
function recordPlan(StationManager $manager){

	static $max = 17000;

	$game = StationManager::$gameConfig;

	$detail = $manager->info;
	foreach ($detail['product'] as &$amount) {
		$amount *= 3600;
	}
	$detail['idlePercent'] = sprintf('%.1f', 100 * $detail['timeWaiting'] / $game['time'], 1);
	$detail['idleHours'] = sprintf('%.1f', $detail['timeWaiting'] / 3600);

	$level = $detail['level'];

	// 计算能量
	$map = [
		'metal'     => 'metalMine',
		'crystal'   => 'crystalMine',
		'deuterium' => 'deuteriumSynthesizer',
	];

	$product = call_user_func($game['building.solar.energy'], $level['solar']) +
		call_user_func($game['building.nuclear.energy'], $level['nuclear']);

	$consume = 0;
	foreach ($map as $resType => $buildingType) {
		$consume += call_user_func($game["building.$buildingType.consume"], $level[$buildingType]);
	}
	$detail['energy'] = compact('consume', 'product');

	$dir = __DIR__ . DIRECTORY_SEPARATOR . 'plans';
	if (!is_dir($dir)) {
		mkdir($dir);
	}

	$totalScore = $manager->scoreDetail['total'];
	$filename = $dir . DIRECTORY_SEPARATOR . $totalScore . '.txt';
	$file = fopen($filename, 'a+');
	fwrite($file, var_export([
			'plan'   => $manager->plan,
			'detail' => $detail,
			'score'  => $manager->scoreDetail,
		], true) . PHP_EOL);
	fwrite($file, ' ================================= ' . PHP_EOL . PHP_EOL);
	fclose($file);

	if ($totalScore > $max) {
		$file = fopen(__DIR__ . DIRECTORY_SEPARATOR . 'seeds.txt', 'a+');
		fwrite($file, trim($manager->plan) . PHP_EOL);
		fclose($file);
		$max += 0.618 * ($totalScore - $max);
	}

	return $filename;
}
