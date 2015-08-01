<?php

/**
 * Class StationManager
 */
class StationManager extends Gene {

	public $plan;

	public $mutateRange;


	public $info;

	public $scoreDetail;


	public static $gameConfig = [];


	private static $_maxScore = 0;


	public function __construct($planLength=200, $mutateRange=9, $plan=null){
		$this->plan = $plan ?: $this->generatePlan($planLength);
		$this->mutateRange = $mutateRange;
	}

	protected function generatePlan($planLength){
		$typeLength = count(StationManagerSimulator::$typeMap);
		for ($i = 0, $s = ''; $i < $planLength; ++$i) {
			$s .= mt_rand(0, $typeLength - 1);
		}
		return $s;
	}

	/**
	 * @param Gene|StationManager $gene
	 */
	public function exchange(Gene $gene){
		$myPlan = $this->plan;
		$otherPlan = $gene->plan;
		$pos = mt_rand(0, strlen($otherPlan) - 1);
		$gene->plan = substr_replace($otherPlan, substr($myPlan, $pos), $pos);
		$this->plan = substr_replace($myPlan, substr($otherPlan, $pos), $pos);
	}

	public function mutate(){
		$typeLength = count(StationManagerSimulator::$typeMap);
		$mutateBits = mt_rand($this->mutateRange / 2, $this->mutateRange);
		for ($i = 0; $i < $mutateBits; $i++) {
			$pos = mt_rand(0, strlen($this->plan) - 1);
			$this->plan[$pos] = mt_rand(0, $typeLength - 1);
		}
	}

	public function getScore(){

		$game = self::$gameConfig;
		$info = $this->info;

		// 每消耗1000金币得1分
		$cost = 0;
		foreach (StationManagerSimulator::$typeMap as $type) {
			$level = $info['level'][$type];
			while ($level) {
				$cost += array_sum(call_user_func($game["building.$type.cost"], $level--));
			}
		}
		$consumeScore = $cost / 1000;

		$remainResource = array_sum($info['resource']);
		// 尚有建造中的建筑,应该退还其消耗,并按剩余得分来计算
		if ($info['buildingTime']) {
			$type = $info['buildingType'];
			$remainResource += array_sum(call_user_func($game["building.$type.cost"], $info['level'][$type] + 1));
		}
		$resourceScore = pow($remainResource, 1/3);

		// 产量得分
		$productScore = array_sum($info['product']) * 3600;

		$this->scoreDetail = [
			'total' => floor($consumeScore + $productScore + $resourceScore),
			'resource' => $resourceScore,
			'consume' => $consumeScore,
			'product' => $productScore,
		];

		return $this->scoreDetail['total'];
	}

	public function selfEvaluate(){

		$score = Cache::get($this->plan);
		if ($score !== false) return $score;

		$this->info = [];
		$this->scoreDetail = [];

		$this->info = StationManagerSimulator::run($this->plan);
		$score = $this->getScore();
		if ($score == 0) {
			print_r($this);
			throw new UnexpectedValueException('Score is 0.');
		}

		if ($score > self::$_maxScore) {
			self::$_maxScore = $score;
			//recordPlan($g, $score, $plan, $info);
			Cache::set($this->plan, $score);
		}
		return $score;
	}

	public function __toString(){
		return $this->plan; // substr_replace($this->plan, '...', 10, -3);
	}
}


class StationManagerSimulator {

	public static $typeMap = [
		0 => 'metalMine',
		1 => 'crystalMine',
		2 => 'deuteriumSynthesizer',
		3 => 'solar',
		4 => 'nuclear',
		5 => 'robot',
		6 => 'nano',
	];

	public static $techTree = [
		'nano' => [
			'robot' => 10,
		],
		'nuclear' => [
			'deuteriumSynthesizer' => 5,
		],
	];

	private static $_instance;

	public $gameConfig;

	private function __construct($gameConfig){
		$this->gameConfig = $gameConfig;
	}

	public static function run($plan){
		if (!self::$_instance) {
			self::$_instance = new static(StationManager::$gameConfig);
		}
		return self::$_instance->runPlan($plan);
	}

	public function runPlan($plan){

		$level = [];
		foreach (self::$typeMap as $type) {
			$level[$type] = 0;
		}
		$resource = $this->gameConfig['startResource'];
		$product = $this->gameConfig['baseProduct'];
		$timeLeft = $this->gameConfig['time'];
		$timeWaiting = 0;
		$buildingTime = 0;
		$buildingType = null;

		while ($timeLeft--) {

			$this->doProduce($resource, $product, 1);

			if ($buildingTime) {
				// do fast-forwarding
				if ($buildingTime < $timeLeft) {
					$this->doProduce($resource, $product, $buildingTime);
					$timeLeft -= $buildingTime;
					$buildingTime = 0;
				} else {
					$buildingTime -= $timeLeft;
					break;
				}
			}

			if ($buildingType) {
				++$level[$buildingType];
				$product = $this->calculateProduct($level);
				$buildingType = null;
			}

			if (strlen($plan) != 0) {
				//echo $_CONFIG['game.time'] - $timeLeft, ' :: Try building '. $plan[0], PHP_EOL;
				$info = $this->tryBuild($plan[0], $resource, $level);
				if ($info['techLocked']) {
					break;
				}
				if (!empty($info['shortage'])) {
					$timeNeeded = $this->calcTimeNeeded($info['shortage'], $product);
					if ($timeNeeded < $timeLeft) {
						$this->doProduce($resource, $product, $timeNeeded);
						$timeLeft -= $timeNeeded;
						$timeWaiting += $timeNeeded;
					} else {
						break;
					}
				}

				foreach ($info['cost'] as $resType => $amount) {
					$resource[$resType] -= $amount;
				}

				$buildingType = $info['type'];
				$buildingTime = $info['buildingTime'];
				$plan = substr($plan, 1);
			}
		}

		$this->doProduce($resource, $product, $timeLeft);

		return compact('resource', 'product', 'level', 'buildingTime', 'buildingType', 'timeWaiting');
	}

	/**
	 * @param array $resource
	 * @param array $product
	 * @param int $duration
	 */
	private function doProduce(&$resource, $product, $duration){

		foreach ($product as $type => $val) {
			$resource[$type] += $val * $duration;
		}
	}

	/**
	 * @param array $level
	 * @return array
	 */
	private function calculateProduct($level){

		static $map = [
			'metal' => 'metalMine',
			'crystal' => 'crystalMine',
			'deuterium' => 'deuteriumSynthesizer',
		];
		if ($level['solar'] == 0) {
			return $this->gameConfig['baseProduct'];
		}

		$energyConsume = 0;
		$product = ['metal' => 0, 'crystal' => 0, 'deuterium' => 0];
		foreach ($map as $resType => $buildingType) {
			$energyConsume += call_user_func($this->gameConfig["building.$buildingType.consume"], $level[$buildingType]);
			foreach (call_user_func($this->gameConfig["building.$buildingType.product"], $level[$buildingType]) as $t => $amount) {
				$product[$t] += $amount;
			}
		}
		$product['deuterium'] += call_user_func($this->gameConfig["building.nuclear.product"], $level['nuclear'])['deuterium'];

		$energyProduct = call_user_func($this->gameConfig['building.solar.energy'], $level['solar']) +
			call_user_func($this->gameConfig['building.nuclear.energy'], $level['nuclear']);

		$produceRate = $energyConsume > $energyProduct ? $energyProduct / $energyConsume : 1;
		array_walk($product, function(&$val, $resType) use ($produceRate){
			$val *= $produceRate;
			$val += $this->gameConfig['baseProduct'][$resType];
		});
		return $product;
	}


	/**
	 * @param array $resourceNeed
	 * @param array $product
	 *
	 * @return float|int
	 */
	private function calcTimeNeeded($resourceNeed, $product){

		$durations = [];
		foreach ($resourceNeed as $type => $val) {
			if ($product[$type] <= 0) return PHP_INT_MAX;
			$durations[] = $val / $product[$type];
		}
		return ceil(max($durations));
	}


	private function tryBuild($type, $resource, $level){
		$type = self::$typeMap[$type];
		$techLocked = false;

		if ($level[$type] == 0 && isset(self::$techTree[$type])) {
			foreach (self::$techTree[$type] as $t => $lvl) {
				if ($level[$t] < $lvl) {
					$techLocked = true;
					break;
				}
			}
		}

		$shortage = [];
		$cost = call_user_func($this->gameConfig["building.$type.cost"], $level[$type]+1);
		foreach ($cost as $resType => $amount) {
			if ($resource[$resType] < $amount) {
				$shortage[$resType] = $amount - $resource[$resType];
			}
		}
		$buildingTime = ceil(pow(0.5, $level['nano'])) * array_sum($cost) / $this->gameConfig['buildRate'] / (1 + $level['robot']);

		return compact('type', 'techLocked', 'cost', 'shortage', 'buildingTime');
	}



}