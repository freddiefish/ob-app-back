<?php
spl_autoload_register(function($className) {
	$file = LIBRARY_PATH . '/' . $className . '.php';
	if (file_exists($file)) {
		include $file;
	} else { 
        echo 'Class Autoloader: we have a problem Houston!';
    }
});