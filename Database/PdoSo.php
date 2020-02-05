<?php

class PdoSo
{
    private pdo $link;

    public function __construct(string $host, string $user, string $password, string $db)
    {
        $this->link = new PDO("mysql:dbname=$db;host=$host;charset=UTF-8", $user, $password);
    }

    public function update(string $sql, array $data) : bool
    {
        $pdoStatement = $this->link->prepare($sql);
        return $pdoStatement->execute($data);
    }

    public function select(string $sql, array $data) : array
    {
        $pdoStatement = $this->link->prepare($sql);
        $result = [];
        if ($pdoStatement->execute($data)) {
            $result = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        }
        return $result;
    }
}