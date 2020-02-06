<?php


namespace Algorithms\Sort;


class SelectionSort
{
    public function selectSort(array $origin): array
    {
        $originCount = count($origin);
        if ($originCount < 2) {
            return $origin;
        }
        for ($index = 0; $index < $originCount; ++$index) {
            $minIndex = $index;
            for ($l = $index + 1; $l < $originCount; ++$l) {
                if ($origin[$l] < $origin[$index]) {
                    $minIndex = $l;
                }
            }
            $tmp = $origin[$index];
            $origin[$index] = $origin[$minIndex];
            $origin[$minIndex] = $tmp;
        }
        return $origin;
    }
}