<?php

class GeneticAlgorithm {

	/**
	 * @var Gene[]
	 */
	private $genes = [];

	/**
	 * @var callable
	 */
	private $selectFunc;

	/**
	 * @var callable
	 */
	private $evaluateFunc;

	public $mutateProbability = 7;

	public $exchangeProbability = 60;

	public $generations = 1000;


	public static function run(array $config){

		$ga = new static;
		foreach ($config as $key => $val) {
			if (method_exists($ga, 'set'. $key)) {
				$ga->{'set'. $key}($val);
			} elseif (property_exists($ga, $key)) {
				$ga->$key = $val;
			}
		}

		return $ga->calculate();
	}


	/**
	 * @param Gene[] $genes
	 */
	public function setGenes(array $genes){
		foreach ($genes as $gene) {
			if (!$gene instanceof Gene) {
				throw new InvalidArgumentException('Not an instance of Gene.');
			}
		}
		$this->genes = $genes;
	}

	/**
	 * @param callable $func
	 */
	public function setEvaluateFunc(callable $func){
		$this->evaluateFunc = $func;
	}

	/**
	 * @param callable $func
	 */
	public function setSelectFunc(callable $func){
		$this->selectFunc = $func;
	}

	public function calculate(){
		$highest = null;
		$highScore = 0;
		$idleRound = 0;
		for ($i = 0; $i < $this->generations; $i++) {
			list($gene, $score) = $this->select();
			printf('Gen:%d   Score: %d   Gene: %s'. PHP_EOL, $i, $score, $gene);
			if ($score > $highScore) {
				$highScore = $score;
				$highest = clone $gene;
				$idleRound = 0;
			} elseif ($score === $highScore) {
				$idleRound < 3 && $idleRound++;
			}
			$this->exchange();
			$this->mutate();
			for ($j = 0; $j < $idleRound; $j++) {
				$this->mutate();
			}
		}

		return [$highest, $highScore];
	}

	/**
	 * @return array [Gene, int]
	 */
	protected function select(){

		$high = null;
		$highScore = 0;

		$parents = [];
		foreach ($this->genes as $gene) {
			$score = $gene->selfEvaluate();
			$parents[] = compact('gene', 'score');
			if ($score > $highScore) {
				$highScore = $score;
				$high = $gene;
			}
		}

		if (is_callable($this->selectFunc)) {
			$this->genes = call_user_func($this->selectFunc, $parents);
		} else {
			$this->genes = $this->selectInternal($parents);
		}

		return [$high, $highScore];
	}

	private function selectInternal($parents){

		$sum = 0;
		$accumulates = [];
		$children = [];
		usort($parents, function($a, $b){
			return $b['score'] - $a['score'];
		});

		array_pop($parents); // 直接淘汰表现最差的一个
		$children[] = reset($parents)['gene']; // 表现最好的一个直接被选中

		foreach ($parents as $key => $parent) {
			$sum += $parent['score'];
			$accumulates[$key] = $sum;
		}

		// 持续选择，直到下一代个数与上一代的持平
		$total = 1 + count($parents) - count($children);
		for ($i = 0; $i < $total; $i++) {
			$r = mt_rand(0, $sum - 1);
			foreach ($accumulates as $key => $val) {
				if ($r < $val) {
					$children[] = $parents[$key]['gene'];
					continue 2;
				}
			}
			print_r(get_defined_vars());
			throw new UnexpectedValueException('Unexpected execution point.');
		}

		return $children;
	}

	protected function exchange(){

		shuffle($this->genes);
		for ($i = 0, $t = floor(count($this->genes) / 2); $i < $t; $i++) {
			if (mt_rand(0, 99) < $this->exchangeProbability) {
				$this->genes[$i*2]->exchange($this->genes[$i*2+1]);
			}
		}
	}

	protected function mutate(){

		foreach ($this->genes as $gene) {
			if (mt_rand(0, 99) < $this->mutateProbability) {
				$gene->mutate();
			}
		}
	}
}


abstract class Gene {

	abstract public function selfEvaluate();

	abstract public function exchange(Gene $gene);

	abstract public function mutate();

	abstract public function __toString();

}