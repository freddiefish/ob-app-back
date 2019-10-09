<?php
use Google\Cloud\Logging\LoggingClient;
// Create a PSR-3-Compatible logger
$logger = LoggingClient::psrBatchLogger('ob-back-app');