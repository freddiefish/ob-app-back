<?php
// Use the composer autoloader to load dependencies. On GC App Engine paths are different
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../functions.php';

// $file = __DIR__. '/../Besluit-8.pdf';



        $dir = sys_get_temp_dir();
        var_dump($dir);
        $URL = 'https://ebesluit.antwerpen.be/publication/19.0911.5685.1892/download';
        $fileName= 'besluit';
        $path = $dir . '/' . $fileName.'.pdf';
        $file__download= curl_init();

        curl_setopt($file__download, CURLOPT_URL, $URL);
        curl_setopt($file__download, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($file__download, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($file__download, CURLOPT_AUTOREFERER, true);
        $result= curl_exec($file__download);
        file_put_contents($path, $result);

        $parser = new \Smalot\PdfParser\Parser();
        $pdf    = $parser->parseFile($path);

        $text = $pdf->getText();

        var_dump($text);
?>
