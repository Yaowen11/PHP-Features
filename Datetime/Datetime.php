<?php

# 第一天和最后一天

var_dump(date("Y-m-d", strtotime("last day of -1 month", strtotime("2017-03-31"))));
// 输出2017-02-28
var_dump(date("Y-m-d", strtotime("first day of +1 month", strtotime("2017-08-31"))));
// 输出2017-09-01
var_dump(date("Y-m-d", strtotime("first day of next month", strtotime("2017-01-31"))));
// 输出2017-02-01
var_dump(date("Y-m-d", strtotime("last day of last month", strtotime("2017-03-31"))));

# 迭代时间

$start = new DateTime('2019-12-09');
$today = new DateTime('2019-12-13');
$periodInterval = \DateInterval::createFromDateString('1 day');
$periodIterator = new \DatePeriod($start, $periodInterval, $today, \DatePeriod::EXCLUDE_START_DATE);
$everyDay = [];
foreach ($periodIterator as $date) {
    $everyDay[] = $date->format('Y-m-d');
}

var_dump($everyDay);