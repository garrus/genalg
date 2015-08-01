<?php

class Operator extends Gene{

	public static $tf300=[];

	public static $setting=[
		'startWorth' => 30000,
		'startCash' => 70000,
	];

	private static $_maxScore = 0;

	/**
	 * 一系列数字
	 * 单日上涨多少点后卖出（单位：万分之一）
	 * 单日上涨多少点后卖出金额（单位：千元）
	 * 单日下跌多少点后买入
	 * 单日下跌多少点后买入金额
	 * 累积上涨多少点后卖出
	 * 累积上涨多少点后卖出金额
	 * 累积下跌多少点后买入
	 * 累积下跌多少点后买入金额
	 *
	 * @var array
	 */
	public $strategy = '';

	public $info = '';

	public function __construct($strategy=null){
		$this->strategy = $strategy ?: $this->generateStrategy();
	}

	public function generateStrategy(){

		$s = [];
		$s[] = mt_rand(1, 10000);
		$s[] = mt_rand(1, 10);
		$s[] = mt_rand(1, 10000);
		$s[] = mt_rand(1, 10);
		$s[] = mt_rand(1, 10000);
		$s[] = mt_rand(1, 10);
		$s[] = mt_rand(1, 10000);
		$s[] = mt_rand(1, 10);
		return $s;
	}

	public function selfEvaluate(){
		//$score = Cache::get($this->__toString());
		//if ($score !== false) return $score;

		$this->info = StockSimulator::run(self::$tf300, self::$setting, $this->strategy);
		$score = $this->getScore();
		if ($score == 0) {
			print_r($this);
			throw new UnexpectedValueException('Score is 0.');
		}

		//if ($score > self::$_maxScore) {
			//self::$_maxScore = $score;
			//recordPlan($g, $score, $plan, $info);
			//Cache::set($this->__toString(), $score);
		//}
		return $score;
	}

	public function getScore(){

		$info = $this->info;
		return intval($info['cash'] + $info['worth']);
	}

	/**
	 * @param Gene|Operator $gene
	 */
	public function exchange(Gene $gene){

		$my = $this->strategy;
		$his = $gene->strategy;
		$pos = mt_rand(1, count($my) - 1);

		$mySnippet = array_splice($my, $pos, count($my), array_splice($his, $pos));
		$this->strategy = $my;
		$gene->strategy = array_merge($his, $mySnippet);
	}

	public function mutate(){

		$pos = array_rand($this->strategy);
		if ($pos % 2 == 0) {
			$this->strategy[$pos] = mt_rand(1, 10000);
		} else {
			$this->strategy[$pos] = mt_rand(1, 10);
		}
	}

	public function __toString(){
		return implode('-', $this->strategy);
	}

}


class StockSimulator {

	const BUY_FEE_RATE = 0.006;
	const SELL_FEE_RATE = 0.005;


	public static function run($tf300, $setting, $strategy){

		$cash = $setting['startCash'];
		$worth = $setting['startWorth'];

		list(
			$BUY_ON_RISE,
			$BUY_AMOUNT_ON_RISE,
			$SELL_ON_FALL,
			$SELL_AMOUNT_ON_FALL,
			$BUY_ON_RISE_TOTAL,
			$BUY_AMOUNT_ON_RISE_TOTAL,
			$SELL_ON_FALL_TOTAL,
			$SELL_AMOUNT_ON_FALL_TOTAL
			) = $strategy;

		$yesterdayIndex = array_shift($tf300);
		$lastOptIndex = $yesterdayIndex;

		$totalFee = 0;
		$optCount = 0;

		foreach ($tf300 as $todayIndex) {
			if ($todayIndex == $yesterdayIndex) continue;

			if ($cash < 0 || $worth < 0) {
				throw new UnexpectedValueException('Unexpected. Cash='. $cash. ', Worth='. $worth);
			}

			$worth *= ($todayIndex / $yesterdayIndex);

			if ($todayIndex < $lastOptIndex) {
				$totalFall = round(10000 * ($lastOptIndex - $todayIndex) / $lastOptIndex);
				if ($totalFall >= $SELL_ON_FALL_TOTAL) {
					// 抛出
					$fee = self::doSelling($cash, $worth, $SELL_AMOUNT_ON_FALL_TOTAL * 1000);
					$lastOptIndex = $todayIndex;
					if ($fee) {
						++$optCount;
						$totalFee += $fee;
					}
					continue;
				}
			} else {
				$totalRise = round(10000 * ($todayIndex - $lastOptIndex) / $lastOptIndex);
				if ($totalRise >= $BUY_ON_RISE_TOTAL) {
					// 抛出
					$fee = self::doBuying($cash, $worth, $BUY_AMOUNT_ON_RISE_TOTAL * 1000);
					$lastOptIndex = $todayIndex;
					if ($fee) {
						++$optCount;
						$totalFee += $fee;
					}
					continue;
				}
			}

			if ($todayIndex < $yesterdayIndex) {
				// 跌
				$fall = round(10000 * ($yesterdayIndex - $todayIndex) / $yesterdayIndex);
				if ($fall >= $SELL_ON_FALL) {
					// 抛出
					$fee = self::doSelling($cash, $worth, $SELL_AMOUNT_ON_FALL * 1000);
					$lastOptIndex = $todayIndex;
					if ($fee) {
						++$optCount;
						$totalFee += $fee;
					}
				}
			} else {
				// 涨
				$rise = round(10000 * ($todayIndex - $yesterdayIndex) / $yesterdayIndex);
				if ($rise >= $BUY_ON_RISE) {
					// 抛出
					$fee = self::doBuying($cash, $worth, $BUY_AMOUNT_ON_RISE * 1000);
					$lastOptIndex = $todayIndex;
					if ($fee) {
						++$optCount;
						$totalFee += $fee;
					}
				}
			}
		}

		return compact('cash', 'worth', 'totalFee', 'optCount');
	}


	protected static function doSelling(&$cash, &$worth, $amount){

		$actual = min($worth, $amount);
		if ($actual < 200) {
			return 0;
		}

		$cash += floor($actual * (1 - self::SELL_FEE_RATE));
		$worth -= $actual;
		return ceil($actual * self::SELL_FEE_RATE);
	}

	protected static function doBuying(&$cash, &$worth, $amount){

		$actual = min($cash, $amount);
		if ($actual < 1000) {
			return 0;
		}

		$cash -= $actual;
		$worth += floor($actual * (1 - self::BUY_FEE_RATE));
		return ceil($actual * self::BUY_FEE_RATE);
	}



}