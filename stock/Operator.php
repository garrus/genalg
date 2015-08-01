<?php

class Operator extends Gene{

	public static $tf300=[];

	public static $setting=[
		'worth' => 30000,
		'cash' => 70000,
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

	public $info = [];

	public function __construct($strategy=null){
		$this->strategy = $strategy ?: $this->generateStrategy();
	}

	public function generateStrategy(){

		$s = [];
		$s[] = mt_rand(1, 1000);
		$s[] = mt_rand(1, 20);
		$s[] = mt_rand(1, 1000);
		$s[] = mt_rand(1, 20);
		$s[] = mt_rand(1, 10000);
		$s[] = mt_rand(1, 50);
		$s[] = mt_rand(1, 10000);
		$s[] = mt_rand(1, 50);
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
		switch ($pos) {
			case 0:
			case 2:
				$v = mt_rand(1, 1000);
				break;
			case 1:
			case 3:
				$v = mt_rand(1, 20);
				break;
			case 4:
			case 6:
				$v = mt_rand(1, 10000);
				break;
			case 5:
			case 7:
				$v = mt_rand(1, 50);
				break;
			default:
				return;
		}
		$this->strategy[$pos] = $v;
	}

	public function __toString(){
		return implode('-', $this->strategy);
	}

}
