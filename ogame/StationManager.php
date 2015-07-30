<?php

/**
 * Class StationManager
 */
class StationManager extends Gene {

	public $plan;

	public $mutateRange;


	public $info;

	public $scoreDetail;

	public static $typeMap = [
		0 => 'metalMine',
		1 => 'solar',
		2 => 'robot',
		3 => 'nano',
	];

	public static $techTree = [
		'nano' => [
			'robot' => 10,
		],
	];

	public static $gameConfig = [];

	private static $_cache = [];
	private static $_maxScore = 0;

	protected static function getCache($plan){
		$key = md5($plan);
		return isset(self::$_cache[$key]) ? self::$_cache[$key] : false;
	}

	protected static function setCache($plan, $val){
		$key = md5($plan);
		self::$_cache[$key] = $val;

		self::$_cache['_keys'][] = $key;
		if (count(self::$_cache) > 100) {
			foreach (array_splice(self::$_cache['_keys'], 0, 30) as $key) {
				unset(self::$_cache[$key]);
			}
		}
	}

	public function __construct($planLength=200, $mutateRange=9){
		$this->plan = $this->generatePlan($planLength);
		$this->mutateRange = $mutateRange;
	}

	protected function generatePlan(){
		$typeLength = count(self::$typeMap);
		for ($i = 0, $s = ''; $i < 300; ++$i) {
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
		$pos = mt_rand(0, count($otherPlan));
		$gene->plan = substr_replace($otherPlan, substr($myPlan, $pos), $pos);
		$this->plan = substr_replace($myPlan, substr($otherPlan, $pos), $pos);
	}

	public function mutate(){
		$typeLength = count(self::$typeMap);

		for ($i = 0; $i < $this->mutateRange; $i++) {
			$pos = mt_rand(0, strlen($this->plan) - 1);
			$this->plan[$pos] = mt_rand(0, $typeLength - 1);
		}
	}

	public function simulate(){

		$plan = $this->plan;
		$metal = self::$gameConfig['startMetal'];
		$level = [];
		foreach (self::$typeMap as $type) {
			$level[$type] = 0;
		}

		$metalProduct = $this->calcMetalProduct($level);

		$timeLeft = self::$gameConfig['time'];
		$timeWaiting = 0;
		$buildingTime = 0;
		$buildingType = null;
		while ($timeLeft--) {

			$metal += $metalProduct;

			if ($buildingTime) {
				// do fast-forwarding
				if ($buildingTime < $timeLeft) {
					$metal += $metalProduct * $buildingTime;
					$timeLeft -= $buildingTime;
					$buildingTime = 0;
				} else {
					$buildingTime -= $timeLeft;
					break;
				}
			}

			if ($buildingType) {
				++$level[$buildingType];
				$metalProduct = $this->calcMetalProduct($level);
				$buildingType = null;
			}

			if (strlen($plan) != 0) {
				//echo $_CONFIG['game.time'] - $timeLeft, ' :: Try building '. $plan[0], PHP_EOL;
				$info = $this->tryBuild($plan[0], $metal, $level);

				if ($info['techLocked']) {
					break;
				}

				if ($info['metalShortage']) {
					// do fast-forwarding
					$timeNeeded = floor($info['metalShortage'] / $metalProduct);
					if ($timeNeeded < $timeLeft) {
						$metal += $metalProduct * $timeNeeded;
						$timeLeft -= $timeNeeded;
						$timeWaiting += $timeNeeded;
					} else {
						break;
					}
				}

				$metal -= $info['cost'];
				$buildingType = $info['type'];
				$buildingTime = $info['buildingTime'];
				$plan = substr($plan, 1);
			}
		}

		$metal += $metalProduct * $timeLeft;
		$this->info = compact('metal', 'level', 'buildingTime', 'buildingType', 'timeWaiting', 'metalProduct');
	}


	private function tryBuild($type, $metal, $level){
		$type = self::$typeMap[$type];
		$techLocked = false;
		$cost = ceil(call_user_func(self::$gameConfig["building.$type.cost.metal"], $level[$type]+1));
		if ($level[$type] == 0 && isset(self::$techTree[$type])) {
			foreach (self::$techTree[$type] as $t => $lvl) {
				if ($level[$t] < $lvl) {
					$techLocked = true;
					break;
				}
			}
		}

		return [
			'type' => $type,
			'techLocked' => $techLocked,
			'cost' => $cost,
			'metalShortage' => $cost > $metal ? $cost - $metal : 0,
			'buildingTime' => ceil(pow(0.5, $level['nano']) * $cost / self::$gameConfig['buildRate'] / (1 + $level['robot'])),
		];

	}

	private function calcMetalProduct($level){
		$baseProduct = self::$gameConfig['baseMetalProduct'];

		if ($level['metalMine'] * $level['solar'] == 0) return $baseProduct;

		$product = call_user_func(self::$gameConfig['building.metalMine.product.metal'], $level['metalMine']);
		$energyConsume = call_user_func(self::$gameConfig['building.metalMine.consume'], $level['metalMine']);
		$energyProduct = call_user_func(self::$gameConfig['building.solar.product.energy'], $level['solar']);
		if ($energyConsume > $energyProduct) {
			return $baseProduct + $product * $energyProduct / $energyConsume;
		}
		return $baseProduct + $product;
	}

	public function getScore(){

		$info = $this->info;

		// 每消耗1000金币得1分
		$cost = 0;
		foreach (self::$typeMap as $type) {
			$level = $info['level'][$type];
			while ($level) {
				$cost += call_user_func(self::$gameConfig["building.$type.cost.metal"], $level--);
			}
		}
		$consumeScore = $cost / 1000;

		$remainMetal = $info['metal'];
		// 尚有建造中的建筑,应该退还其消耗,并按剩余得分来计算
		if ($info['buildingTime']) {
			$type = $info['buildingType'];
			$remainMetal += call_user_func(self::$gameConfig["building.$type.cost.metal"], $info['level'][$type] + 1);
		}
		$metalScore = pow($remainMetal, 1/3);

		// 金币产量得分
		$productScore = sqrt($info['metalProduct']);

		$this->scoreDetail = [
			'total' => floor($consumeScore + $productScore + $metalScore),
			'metal' => $metalScore,
			'consume' => $consumeScore,
			'product' => $productScore,
		];

		return $this->scoreDetail['total'];
	}

	public function selfEvaluate(){

		$score = self::getCache($this->plan);
		if ($score !== false) return $score;

		$this->info = [];
		$this->scoreDetail = [];

		$this->simulate();
		$score = $this->getScore();
		if ($score == 0) {
			print_r($this);
			throw new UnexpectedValueException('Score is 0.');
		}

		if ($score > self::$_maxScore) {
			self::$_maxScore = $score;
			//recordPlan($g, $score, $plan, $info);
			self::setCache($this->plan, $score);
		}
		return $score;
	}

	public function __toString(){
		return substr_replace($this->plan, '...', 10, -3);
	}
}