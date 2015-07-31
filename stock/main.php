<?php
require dirname(__DIR__) . '/lib/GeneticAlgorithm.php';
require __DIR__. '/Operator.php';

/* == Config === */
$geneCount = 10;
$exchangeProbability = 70;
$mutantProbability = 15;
$mutateBits = 5;
$generations = 10000;
/* == Config end == */

for ($genes = []; count($genes) < $geneCount;) {
	$genes[] = new Operator();
}
$algorithmConfig = compact('genes', 'generations', 'mutateProbability', 'exchangeProbability');
list($manager, $score) = GeneticAlgorithm::run($algorithmConfig);

recordPlan($manager);

print_r($manager->info);
print_r($manager->scoreDetail);



/**
 * @param Operator $operator
 */
function recordPlan(Operator $operator){

	$dir = __DIR__. DIRECTORY_SEPARATOR. 'strategy';
	if (!is_dir($dir)) {
		mkdir($dir);
	}
	$file = fopen($dir. DIRECTORY_SEPARATOR. $operator->getScore(). '.txt', 'a+');
	fwrite($file, var_export([
			'plan' => $operator->strategy,
			'detail' => $operator->info,
			'score' => $operator->scoreDetail,
		], true). PHP_EOL);
	fwrite($file, ' ================================= '. PHP_EOL. PHP_EOL);
	fclose($file);
}
