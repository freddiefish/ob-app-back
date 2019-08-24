<?php
switch (@parse_url($_SERVER['REQUEST_URI'])['path']) {
    case '/':
        require 'index.html';
        break;
    case '/cron.php':
        require 'cron.php';
        break;
    default:
        http_response_code(404);
        exit('Not Found');
}
?>