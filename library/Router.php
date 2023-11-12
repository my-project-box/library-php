<?php
namespace library;

use LogicException;

class Router 
{
	private $routes;
	protected $registry;
	
	public function __construct($registry = '')
	{
	   	if ( !is_dir($_SERVER['DOCUMENT_ROOT'] . '/settings') ) 
		{
			   mkdir($_SERVER['DOCUMENT_ROOT'] . '/settings');
		}

		if ( !file_exists($_SERVER['DOCUMENT_ROOT'] . '/settings/routes.php') ) 
		{
			$file = fopen($_SERVER['DOCUMENT_ROOT'] . '/settings/routes.php',"w");
			fclose ($file);

			//die('Файл с маршрутами в директории "Setting" не существует!');
		}

		$this->routes = require_once( $_SERVER['DOCUMENT_ROOT'] . '/settings/routes.php' );

		if ( empty( $this->routes ) )
			die ('В файле маршрутов пуст!');

		$this->registry = $registry;
		
	} // End: function __construct
	
	
	/**
	* Возвращаем строку
	*/
	private function getUri()
	{
		if(!empty($_SERVER['REQUEST_URI']))
		{
			$uri = parse_url($_SERVER['REQUEST_URI']);
			return trim($uri['path'], '/');
		}
	} // End: function getUri
	
	
	public function run()
	{
		// Получаем строку запроса
		$uri = $this->getUri();
		
		foreach ($this->routes as $uriPattern => $path)
		{
			// Сравниваем $uriPattern и $uri
			if (preg_match("~^$uriPattern~", $uri))
			{
				// Получаем внутрений путь из внешнего согласно правилу
				$externalPath = preg_replace("~$uriPattern~", $path, $uri);
				$segments = explode ('/', $externalPath);
				
				// Определяем Controller и Method
				$controller_method_getparam = explode( ':', array_pop($segments) );

				// Получаем путь до Контроллера
				$path_to_controller = 'app\controller';
				
				if ($segments)
					$path_to_controller .= '\\'. implode( '\\', $segments);
				
				// Получаем имя Controller
				$controllerName = ucfirst(array_shift($controller_method_getparam));

				// Получаем метод контроллера
				$metodName = array_shift($controller_method_getparam);

				// Получаем get параметры
				$parameters = $controller_method_getparam;

				$path_to_controller .= '\\'. $controllerName;
				
				break;
			}
		}
		
		// Подключаем класс контроллера
		if (class_exists($path_to_controller)) {
			// Создаем объект и вызываем метод action...
			$controllerObject = new $path_to_controller($this->registry);
			
			// Проверяет, существует ли метод в данном классе
			if(method_exists($controllerObject, $metodName))
				$result = call_user_func_array (array($controllerObject, $metodName), $parameters);
			else die('Такой метод не существует! Дружище не забудь здесь установить страницу 404')/*header("Location: /")*/;
			
			if ($result != NULL) return false;

		} else die('Такой контроллер '.$controllerName.' не существует!');

	} // End: function run
	
} // End: Class Router