<?php


namespace Algorithms\Lists;


class BinarySearch
{
    public function binarySearchRecursive(array $order, int $searchValue, $start, $end): int
    {
        $middleIndex = $start + (int) (($end - $start) / 2);
        if ($searchValue > $order[$middleIndex]) {
            return $this->binarySearchRecursive($order, $searchValue, $middleIndex + 1, $end);
        } elseif ($searchValue < $order[$middleIndex]) {
            return $this->binarySearchRecursive($order, $searchValue, $start, $middleIndex - 1);
        } else {
            return $middleIndex;
        }
    }

    public function binarySearchLoop(array $order, int $searchValue)
    {
        $startIndex = 0;
        $endIndex = count($order) - 1;
        while ($startIndex <= $endIndex) {
            $middleIndex = $startIndex + (int) (($endIndex - $startIndex) / 2);
            if ($searchValue > $order[$middleIndex]) {
                $startIndex = $middleIndex + 1;
            } elseif ($searchValue < $order[$middleIndex]) {
                $endIndex = $middleIndex - 1;
            } else {
                return $middleIndex;
            }
        }
        return -1;
    }
}