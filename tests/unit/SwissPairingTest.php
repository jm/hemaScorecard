<?php

use PHPUnit\Framework\TestCase;

/*******************************************************************************
	Tests for generate_SwissPairings()

	Fighters are passed in as rows of rosterID, wins, rank, and hadBye.
	Pools come back zero-indexed, top score group first, bye pool last.

*******************************************************************************/

class SwissPairingTest extends TestCase {

	private function fighter($rosterID, $wins, $rank, $hadBye = false){
		return ['rosterID' => $rosterID, 'wins' => $wins, 'rank' => $rank, 'hadBye' => $hadBye];
	}

	private function noRefights($fighters){
		$refights = [];
		foreach($fighters as $f1){
			foreach($fighters as $f2){
				$refights[$f1['rosterID']][$f2['rosterID']] = 0;
			}
		}
		return $refights;
	}

	public function testPairsTopHalfAgainstBottomHalfWithinAScoreGroup(){
		$fighters = [];
		for($i = 1; $i <= 8; $i++){
			$fighters[] = $this->fighter($i, 0, $i);
		}

		$pools = generate_SwissPairings($fighters, $this->noRefights($fighters), 4);

		$this->assertSame([[1,5],[2,6],[3,7],[4,8]], $pools);
	}

	public function testPairsWithinScoreGroupsBeforeCrossingThem(){
		$fighters = [];
		for($i = 1; $i <= 8; $i++){
			$fighters[] = $this->fighter($i, ($i <= 4 ? 1 : 0), $i);
		}

		$pools = generate_SwissPairings($fighters, $this->noRefights($fighters), 4);

		$this->assertSame([[1,3],[2,4],[5,7],[6,8]], $pools);
	}

	public function testOddScoreGroupFloatsItsLowestFighterDown(){
		$fighters = [];
		for($i = 1; $i <= 6; $i++){
			$fighters[] = $this->fighter($i, ($i <= 3 ? 1 : 0), $i);
		}

		$pools = generate_SwissPairings($fighters, $this->noRefights($fighters), 3);

		$this->assertSame([[1,2],[3,5],[4,6]], $pools);
	}

	public function testOddFieldGivesByeToLowestFighterWithoutOne(){
		$fighters = [];
		for($i = 1; $i <= 5; $i++){
			$fighters[] = $this->fighter($i, 0, $i, ($i == 5));
		}

		$pools = generate_SwissPairings($fighters, $this->noRefights($fighters), 3);

		$this->assertSame([[1,3],[2,5],[4]], $pools);
	}

	public function testAvoidsARematchWhenAnotherOpponentIsAvailable(){
		$fighters = [];
		for($i = 1; $i <= 4; $i++){
			$fighters[] = $this->fighter($i, 0, $i);
		}
		$refights = $this->noRefights($fighters);
		$refights[1][3] = 1;
		$refights[3][1] = 1;

		$pools = generate_SwissPairings($fighters, $refights, 2);

		$this->assertSame([[1,4],[2,3]], $pools);
	}

	public function testAllowsARematchWhenNothingElseIsPossible(){
		$fighters = [$this->fighter(1, 1, 1), $this->fighter(2, 1, 2)];
		$refights = [1 => [1 => 0, 2 => 1], 2 => [1 => 1, 2 => 0]];

		$pools = generate_SwissPairings($fighters, $refights, 1);

		$this->assertSame([[1,2]], $pools);
	}

	public function testReturnsNullWhenThereAreNotEnoughPools(){
		$fighters = [];
		for($i = 1; $i <= 5; $i++){
			$fighters[] = $this->fighter($i, 0, $i);
		}

		$pools = generate_SwissPairings($fighters, $this->noRefights($fighters), 2);

		$this->assertNull($pools);
	}

}
