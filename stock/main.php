<?php
require dirname(__DIR__) . '/lib/Cache.php';
require dirname(__DIR__) . '/lib/GeneticAlgorithm.php';
require __DIR__ . '/Operator.php';
require __DIR__ . '/StockSimulator.php';

$file = new SplFileObject(__DIR__ . '/tf300', 'r+');
$file->setFlags(SplFileObject::DROP_NEW_LINE | SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY);
$tf300 = [];
foreach ($file as $line) {
	array_unshift($tf300, floatval($line));
}
Operator::$tf300 = $tf300;
unset($file, $tf300);


for ($i = 0; $i < 100; $i++) {
	printf('Run round %d ... ', $i + 1);
	ob_start();
	$score = main();
	ob_get_clean();
	echo $score, PHP_EOL;
}

function main(){

	/* == Config === */
	$geneCount = 10;
	$exchangeProbability = 70;
	$mutateProbability = 15;
	$generations = 10000;
	/* == Config end == */

	$seeds = array_map(function($line) {
		$tokens = explode(' ', trim($line));
		if (count($tokens) == 2) {
			return explode('-', $tokens[1]);
		} else {
			return null;
		}
	}, @file(__DIR__ . DIRECTORY_SEPARATOR . 'seeds.txt') ?: []);
	$seeds = array_filter($seeds);

	$pos = count($seeds) ? array_rand($seeds, min($geneCount, count($seeds))) : [];
	if (!is_array($pos)) $pos = [$pos];

	for ($genes = []; count($genes) < $geneCount;) {
		$strategy = count($pos) ? $seeds[array_shift($pos)] : null;
		if (is_array($strategy) && count($strategy) !== 8) {
			$strategy = null;
		}
		$genes[] = new Operator($strategy);
	}
	$algorithmConfig = compact('genes', 'generations', 'mutateProbability', 'exchangeProbability');
	list($manager, $score) = GeneticAlgorithm::run($algorithmConfig);

	readfile(recordPlan($manager));
	return $score;
}

/**
 * @param Operator $operator
 *
 * @return string
 */
function recordPlan(Operator $operator){

	static $max = 100000;

	$dir = __DIR__ . DIRECTORY_SEPARATOR . 'strategy';
	if (!is_dir($dir)) {
		mkdir($dir);
	}
	$filename = $dir . DIRECTORY_SEPARATOR . $operator->getScore() . '.txt';
	$file = fopen($filename, 'a+');

	$strategy = $operator->strategy;
	$explain = sprintf('
 * 单日上涨达 %.2f%% 后卖出 %d 元
 * 单日下跌达 %.2f%% 后买入 %d 元
 * 累积上涨达 %.2f%% 后卖出 %d 元
 * 累积下跌达 %.2f%% 后买入 %d 元',
		$strategy[0] / 100,
		$strategy[1] * 1000,
		$strategy[2] / 100,
		$strategy[3] * 1000,
		$strategy[4] / 100,
		$strategy[5] * 1000,
		$strategy[6] / 100,
		$strategy[7] * 1000
	);

	$account = $operator->info['account'];
	unset($operator->info['account']);

	ob_start();
	echo PHP_EOL;
	printf('  指数      涨跌(%%)     现金    基金价值'. PHP_EOL);
	echo '------------------------------------------ ', PHP_EOL;
	foreach ($account as $day) {
		printf(' %.2f    %+.2f     %5d    %5d'. PHP_EOL, ...$day);
	}
	echo PHP_EOL;
	$account = ob_get_clean();



	$score = $operator->getScore();
	fwrite($file, var_export([
			'strategy'   => $operator->strategy,
			'explain' => $explain,
			'detail' => $operator->info,
			'account' => $account,
			'score'  => $score,
			'profit' => $score - 100000,
			'profitPercent' => sprintf('%+.2f%%', $score / 1000 - 100),
		], true) . PHP_EOL);
	fwrite($file, ' ================================= ' . PHP_EOL . PHP_EOL);
	fclose($file);

	if ($score > $max) {
		$file = fopen(__DIR__ . DIRECTORY_SEPARATOR . 'seeds.txt', 'a+');
		fwrite($file, $score. ' '. trim($operator->__toString()) . PHP_EOL);
		fclose($file);
		$max += 0.618 * ($score - $max);
		$max = floor($max);
	}


	return $filename;
}
