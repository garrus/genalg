<?php
require dirname(__DIR__) . '/lib/GeneticAlgorithm.php';
require __DIR__. '/StationManager.php';

/* == Config === */
$geneCount = 6;
$planLength = 300;
$exchangeProbability = 70;
$mutantProbability = 15;
$mutateBits = 5;
$generations = 10000;
/* == Config end == */

StationManager::$gameConfig = require __DIR__. '/game.php';

for ($genes = []; count($genes) < $geneCount;) {
	$genes[] = new StationManager($planLength, $mutateBits);
}
$algorithmConfig = compact('genes', 'generations', 'mutateProbability', 'exchangeProbability');
list($manager, $score) = GeneticAlgorithm::run($algorithmConfig);

recordPlan($manager);

print_r($manager->info);
print_r($manager->scoreDetail);

/**
 * @param StationManager $manager
 */
function recordPlan(StationManager $manager){

	$detail['energy'] = [
		'consume' => call_user_func(StationManager::$gameConfig['building.metalMine.consume'], $manager->info['level']['metalMine']),
		'product' => call_user_func(StationManager::$gameConfig['building.solar.product.energy'], $manager->info['level']['solar']),
	];

	$dir = __DIR__. DIRECTORY_SEPARATOR. 'plans';
	if (!is_dir($dir)) {
		mkdir($dir);
	}
	$file = fopen($dir. DIRECTORY_SEPARATOR. $manager->scoreDetail['total']. '.txt', 'a+');
	fwrite($file, var_export([
			'plan' => $manager->plan,
			'detail' => $manager->info,
			'score' => $manager->scoreDetail,
		], true). PHP_EOL);
	fwrite($file, ' ================================= '. PHP_EOL. PHP_EOL);
	fclose($file);
}
