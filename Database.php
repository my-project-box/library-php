<?php

namespace library;

/**
 *  Класс работы с базой данных
 * 
 */
class Database 
{
    private $connect;

    
    /**
     *  Подключаемся к базе
     * 
     */
    public function __construct($db, $dbname, $host, $login, $pass) 
    {
        try {

            $this->connect = new \PDO($db .':dbname='. $dbname .';host='. $host, $login, $pass);

        } catch(PDOException $e) {
            
            echo $e->getMessage();

        }
        
    }



    /**
     * Получаем данные
     * 
     */
    public function query(string $sql = '', array $params = []) 
    {
        $sth = $this->connect->prepare($sql);
        $sth->execute($params);
        $result = $sth->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'result'       => $result,
            'row'          => $sth->rowCount(),
            'lastInsertId' => $this->connect->lastInsertId()
        ];
    }
}

$db2 = new Database('mysql', 'parsing', 'localhost', 'root', 'root');

//$sql = 'INSERT INTO source SET name = :name';
//$params = ['name' => 'test'];

$sql = 'SELECT * FROM source';
$params = [];

$res = $db2->query($sql, $params);

print_r($res);