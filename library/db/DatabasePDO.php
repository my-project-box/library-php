<?php
namespace library;

use PDO;
use PDOException;
use Exception;

class Database
{
	private $link;
    private $server;
    private $db_name;
    private $login;
    private $pass;
	
	
	// Создаем конструктор
	public function __construct($params = [])
	{
		$this->server  = $params[0];
        $this->db_name = $params[1];
        $this->login   = $params[2];
        $this->pass    = $params[3];

        $this->connect();
	}
	
	
	
	
	/**
	* Подключаемся к базе данных
	* @return $this
	*
	*/
	private function connect()
	{
		try {
            
			$this->link = new PDO('mysql:host='. $this->server.'; dbname='. $this->db_name .'; charset=utf8mb4;', $this->login, $this->pass, [\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4'"]);
			return $this;
		} 
		catch(PDOException $e) {
			die($e->getMessage());
		}
		
	} // End: function connect
	
	
	
	
	/**
	* Передаем в базу данные
	* @param $sql
	* @return array
	*/
	public function execute($sql = '', $params = [])
	{
		$sth = $this->link->prepare($sql);
		$result = $sth->execute($params);
		
		return [
			'result' => $result,
			// id последней вставленой записи
			'lastInsertId' => $this->link->lastInsertId(),
			//'lastInsertId' => $this->link->query("SELECT LAST_INSERT_ID()")->fetchColumn(),
			 // Возвращает количество строк, затронутых последним SQL-запросом
			'countString' => $sth->rowCount(),
			'error' => $sth->errorInfo()
		];
		
	} // End: function execute
	
	
	
	
	/**
	* Получаем из базы данные
	* @param $sql
	* @return array
	*/
	public function query( string $sql = '', array $params = [] )
	{
		$sth = $this->link->prepare($sql);
		$sth->execute($params);
		
		$result = $sth->fetchAll(PDO::FETCH_ASSOC);
		
		return $result;
		
	} // End: function query
	
	
	
	
	/**
	* Проверим есть ли таблица в базе данных
	*
	*/
	public function tableExists($table)
	{
		try
		{
			// формальный запрос
			$result = $this->query("SELECT 1 FROM $table LIMIT 1", $params = NULL);
		} catch (Exception $e)
		{
			return FALSE;
		}

		return $result !== FALSE;

	} // End: function tableExists
	
	
} // End: class Database