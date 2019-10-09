<?php
spl_autoload_register(function($className) {
	$file = LIBRARY_PATH . '/' . $className . '.php';
	try
	{
		if (!file_exists($file))
		{
			throw new Exception('Class file ' . $className .'.php not found.');
		}
		include $file;
	}
	catch (Exception $e)
	{
		echo 'Autoloader problem: ' . $e->getMessage() ;
	}
});