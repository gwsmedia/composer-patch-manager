<?php

namespace ComposerPatchManager\Proxy;

class GitProxy {
	const HEURISTIC_NONE = 0;
	const HEURISTIC_RECOUNT = 1;
	const HEURISTIC_C1 = 2;
	const HEURISTIC_REJECT = 3;
	const HEURISTIC_COMPLETE = 4;
	const HEURISTICS = [
		self::HEURISTIC_NONE => '',
		self::HEURISTIC_RECOUNT => '--recount',
		self::HEURISTIC_C1 => '-C1',
		self::HEURISTIC_REJECT => '--reject'
	];

	public static function diff($dir1, $dir2, $outputFile) {
		exec("git diff --no-index \"$dir1\" \"$dir2\" > \"$outputFile\"");
	}


	// TODO: Split plugin
	public static function applyPatch($patch, $heuristicLvl = self::HEURISTIC_NONE, $heuristicsDone = false) {
		if($heuristicLvl == self::HEURISTIC_NONE) echo PHP_EOL."GitProxy: \e[36mTesting patch $patch\e[0m".PHP_EOL; 

		if($heuristicsDone) {
			$checkParam = '-vvv ';
			echo "GitProxy: \e[36mHeuristics complete\e[0m".PHP_EOL;
			echo "GitProxy: \e[36mApplying patch\e[0m".PHP_EOL;
		} else {
			$checkParam = '--check -vvv ';
		}

		$heuristics = self::getHeuristicsUpTo($heuristicLvl);

		$gitCommand = 'git apply '.$checkParam.$heuristics.'"'.$patch.'"';
		echo "GitProxy: \e[36mRunning command \e[35m$gitCommand\e[0m".PHP_EOL;
		exec("$gitCommand > patch.log 2>&1");

		$log = trim(file_get_contents('patch.log'));

		if($heuristicsDone) {
			self::showCommandOutput($log);
			// TODO: move patch log to cpmDir
			unlink('patch.log');
			if(preg_match('/^error: patch failed/m', $log)) {
				echo "GitProxy: \e[41m\e[30mNot all hunks applied successfully.\e[49m\e[36m Check .rej files at the end.\e[0m".PHP_EOL;
			} else {
				echo "GitProxy: \e[42m\e[37mPatch applied successfully!\e[0m".PHP_EOL;
			}
			return;
		}

		switch($heuristicLvl) {
			case self::HEURISTIC_NONE:
			case self::HEURISTIC_RECOUNT:
			case self::HEURISTIC_C1:
				if(preg_match('/^error: .+ patch does not apply$/m', $log)) {
					echo "GitProxy: \e[31mPatch does not apply, \e[33mincreasing heuristic level\e[0m".PHP_EOL;
					self::applyPatch($patch, $heuristicLvl + 1);
				} else {
					echo "GitProxy: \e[32mPatch should apply successfully\e[0m".PHP_EOL;
					self::applyPatch($patch, $heuristicLvl, true);
				}
				break;

			case self::HEURISTIC_REJECT:
				self::showCommandOutput($log);
				echo "GitProxy: \e[43m\e[30mAt this heuristic level, patches will apply as many hunks as possible. Failed hunks will be stored in .rej files to be manually applied.\e[0m".PHP_EOL;
				echo "GitProxy: \e[43m\e[30mWith this in mind, look at the dry-run above.\e[0m \e[36mApply patch?\e[0m";
				$answer = strtolower(readline(" y/n: "));

				if(empty($answer) || $answer[0] != 'y') {
					echo "GitProxy: \e[36mSkipping patch.\e[0m".PHP_EOL;
				} else {
					self::applyPatch($patch, $heuristicLvl, true);
				}
				break;
		}
	}

	private static function showCommandOutput($output) {
		echo PHP_EOL."GitProxy: \e[36mOutput for git command\e[0m".PHP_EOL;
		echo "----------------------------".PHP_EOL;
		echo $output;
		echo PHP_EOL."----------------------------".PHP_EOL.PHP_EOL;
	}

	private static function getHeuristic($level) {
		return self::HEURISTICS[$level];
	}

	private static function getHeuristicsUpTo($level) {
		if($level == SELF::HEURISTIC_NONE) return '';
		return implode(' ', array_slice(self::HEURISTICS, SELF::HEURISTIC_RECOUNT, $level)).' ';
	}
}
