<?php

namespace platz1de\EasyEdit\pattern\functional;

use platz1de\EasyEdit\pattern\Pattern;
use platz1de\EasyEdit\selection\Selection;
use platz1de\EasyEdit\utils\HeightMapCache;
use platz1de\EasyEdit\utils\SafeSubChunkIteratorManager;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;

class SmoothPattern extends Pattern
{
	/**
	 * @param int                         $x
	 * @param int                         $y
	 * @param int                         $z
	 * @param SafeSubChunkIteratorManager $iterator
	 * @param Selection                   $selection
	 * @return Block|null
	 */
	public function getFor(int $x, int $y, int $z, SafeSubChunkIteratorManager $iterator, Selection $selection): ?Block
	{
		HeightMapCache::load($iterator, $selection);

		$max = 0;
		$tMax = 0;
		$min = 0;
		$tMin = 0;
		for ($kernelX = -1; $kernelX <= 1; $kernelX++) {
			for ($kernelZ = -1; $kernelZ <= 1; $kernelZ++) {
				$m = HeightMapCache::getHighest($x + $kernelX, $z + $kernelZ);
				if ($m !== null) {
					$max += $m;
					$tMax++;
				}

				$m = HeightMapCache::getLowest($x + $kernelX, $z + $kernelZ);
				if ($m !== null) {
					$min += $m;
					$tMin++;
				}
			}
		}
		if ($tMax !== 0) {
			$max /= $tMax;
		} elseif ($tMin !== 0) {
			$max = $selection->getCubicEnd()->getY();
		}
		if ($tMin !== 0) {
			$min /= $tMin;
		}
		$max = round($max);
		$min = round($min);
		$oMax = HeightMapCache::getHighest($x, $z) ?? (int) $selection->getCubicEnd()->getY();
		$oMin = HeightMapCache::getLowest($x, $z) ?? (int) $selection->getCubicStart()->getY();
		$oMid = ($oMin + $oMax) / 2;
		$mid = ($min + $max) / 2;

		if ($tMin >= 5 && $min !== $max) {
			if ($y >= $mid && $y <= $max) {
				$k = ($y - $mid) / ($max - $mid);
				$gy = $oMid + round($k * ($oMax - $oMid));
				$iterator->moveTo($x, (int) $gy, $z);
				return BlockFactory::get($iterator->getCurrent()->getBlockId($x & 0x0f, $gy & 0x0f, $z & 0x0f), $iterator->getCurrent()->getBlockData($x & 0x0f, $gy & 0x0f, $z & 0x0f));
			}

			if ($y <= $mid && $y >= $min) {
				$k = ($y - $mid) / ($min - $mid);
				$gy = $oMid + round($k * ($oMin - $oMid));
				$iterator->moveTo($x, (int) $gy, $z);
				return BlockFactory::get($iterator->getCurrent()->getBlockId($x & 0x0f, $gy & 0x0f, $z & 0x0f), $iterator->getCurrent()->getBlockData($x & 0x0f, $gy & 0x0f, $z & 0x0f));
			}
		}

		return BlockFactory::get(0);
	}
}