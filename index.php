<?php
switch (@parse_url($_SERVER['REQUEST_URI'])['path']) {
    case '/':
        require 'homepage.php';
        break;
    case '/cron-test':
        require 'tasks/cron-test.php';
        break;
    case '/cron-daily':
        require 'tasks/cron-daily.php';
        break;
    case '/cron-weekly':
        require 'tasks/cron-weekly.php';
        break;
    case '/log':
        require 'tasks/log.php';
        break;
    default:
        http_response_code(404);
        exit('Not Found');
}