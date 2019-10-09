<?php
switch (@parse_url($_SERVER['REQUEST_URI'])['path']) {
    case '/':
        require 'index.html';
        break;
    case '/doc-relevance':
        require 'tasks/doc-relevance.php';
        break;
    case '/scrape':
        require 'tasks/scrape.php';
        break;
    case '/test':
        require 'tasks/test.php';
        break;
    default:
        http_response_code(404);
        exit('Not Found');
}
/* phpinfo();
$msg = 'test xDebug ' . $_SERVER['DOCUMENT_ROOT'];
echo $msg;
echo '<br>' . 'ENDED'; */