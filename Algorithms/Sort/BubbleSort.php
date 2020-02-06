<?php

namespace Algorithms\Sort;

class BubbleSort
{
    public function bubbleSort(array $origin): array
    {
        $originCount = count($origin);
        if ($originCount < 2) {
            return $origin;
        }
        for ($index = 0; $index < $originCount; ++$index) {
            for ($l = $index + 1; $l < $originCount; ++$l) {
                if ($origin[$index] > $origin[$l]) {
                    $tem = $origin[$index];
                    $origin[$index] = $origin[$l];
                    $origin[$l] = $tem;
                } elseif ($origin[$index] == $origin[$l]) {
                    continue;
                }
            }
        }
        return $origin;
    }
}