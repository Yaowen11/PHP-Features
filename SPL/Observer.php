<?php

class Newspaper implements \SplSubject{
    private $name;
    private $observers;
    private $content;

    public function __construct($name) {
        $this->name = $name;
        $this->observers = new SplObjectStorage();
    }

    //add observer
    public function attach(\SplObserver $observer) {
        $this->observers->attach($observer);
    }

    //remove observer
    public function detach(\SplObserver $observer) {

        $this->observers->detach($observer);
    }

    //set breakouts news
    public function breakOutNews($content) {
        $this->content = $content;
        $this->notify();
    }

    public function getContent() {
        return $this->content." (by {$this->name})";
    }

    //notify observers(or some of them)
    public function notify() {
        foreach ($this->observers as $value) {
            $value->update($this);
        }
    }
}

class Reader implements SplObserver {

    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function update(\SplSubject $subject) {
        echo $this->name.' is reading breakout news <b>'.$subject->getContent().'</b><br>' . PHP_EOL;
    }
}

$newspaper = new Newspaper('NewYork Times');
$allen = new Reader("Allen");
$jim = new Reader('Jim');
$linda = new Reader('Linda');

$newspaper->attach($allen);
$newspaper->attach($jim);
$newspaper->attach($linda);

$newspaper->breakOutNews('USA break down!');
