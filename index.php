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
    case '/delete-log':
        require 'tasks/delete-log.php';
        break;
    case '/mail-log':
        require 'tasks/mail-log.php';
        break;
    case '/test':
        require 'tasks/test.php';
        break;
    default:
        http_response_code(404);
        exit('Not Found');
}