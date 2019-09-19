<?php
require_once __DIR__ . '/../bootstrap.php';

use Google\Cloud\Firestore\FirestoreClient;

$firestore = new FirestoreClient();
$ml = new Ml($app);
$db = new Database();

$documents = $db -> getSample($firestore, 'decisions', 10000);
$ml->getMatchedTerms($documents);