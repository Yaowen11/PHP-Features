<?php


namespace Algorithms\Sort;


class InsertionSort
{
    function insertSort(array $origin): array
    {
        $originCount = count($origin);
        if ($originCount < 2) {
            return $origin;
        }
        for ($index = 1; $index < $originCount; ++$index) {
            $tmp = $origin[$index];
            for ($l = $index - 1; $l >= 0; --$l) {
                if ($origin[$l] > $tmp) {
                    $origin[$l + 1] = $origin[$l];
                } else {
                    break;
                }
            }
            $origin[$l + 1] = $tmp;
        }
        return $origin;
    }
}