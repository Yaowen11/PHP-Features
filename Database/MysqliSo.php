<?php

namespace Database;

use mysqli;

class MysqliSo
{
    private mysqli $link;

    public function __construct(string $host, string $user, string $password, string $db)
    {
        $this->link = mysqli_connect($host, $user, $password, $db);
    }

    public function action()
    {
        $mysqlStatement = $this->link->prepare('select id, user_id, created_at from user where id > ? limit ?, ?');
        if ($mysqlStatement) {
            $mysqlStatement->bind_param('iii', $id, $start, $end);
            $id = 10;
            $start = 1;
            $end = 10;
        } else {
            var_dump($this->link->error);
        }
        $mysqlStatement->execute();
        $mysqlStatement->bind_result($id, $user_id, $created_at);
        $result = [];
        while ($mysqlStatement->fetch()) {
            $result[] = ['id' => $id, 'user_id' => $user_id, 'created_at' => $created_at];
        }
        $mysqlStatement->close();
        $this->link->close();
        return $result;
    }
}