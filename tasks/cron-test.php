<?php
// Use the composer autoloader to load dependencies. On GC App Engine paths are different
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../functions.php';

use Google\Cloud\Firestore\FirestoreClient;

// Create the Cloud Firestore client
$db = new FirestoreClient();

mailScriptResult() ;
