<?php

class PQtest extends SplPriorityQueue
{
    public function compare($priority1, $priority2)
    {
        return $priority1 <=> $priority2;
    }
}

$objPQ = new PQtest();

$objPQ->insert('A', 3);
$objPQ->insert('B', 6);
$objPQ->insert('C', 1);
$objPQ->insert('D', 2);

$objPQ->setExtractFlags(SplPriorityQueue::EXTR_BOTH);

$objPQ->top();

while ($objPQ->valid()) {
    print_r($objPQ->current()); echo PHP_EOL;
    $objPQ->next();
}

$h = new SplMaxHeap();
// [parent, child]
$h->insert([9, 11]);
$h->insert([0, 1]);
$h->insert([1, 2]);
$h->insert([1, 3]);
$h->insert([1, 4]);
$h->insert([1, 5]);
$h->insert([3, 6]);
$h->insert([2, 7]);
$h->insert([3, 8]);
$h->insert([5, 9]);
$h->insert([9, 10]);

for ($h->top(); $h->valid(); $h->next()) {
    list($parentId, $myId) = $h->current();
    echo "$myId ($parentId)" . PHP_EOL;
}