<?php
require_once __DIR__ . '/../bootstrap.php';
/**
 * from https://developers.google.com/machine-learning/guides/text-classification
 * Tokenizes the texts (ook hier juridische stukken eruit? streven naar 500 woorden MAX_SEQUENCE_LENGTH = 500)into words
 * Creates a vocabulary using the top (most relevant) 20,000 tokens
 * Converts the tokens into sequence vectors
 * Pads the sequences to a fixed sequence length
 * 
 * 
 *  # Fix sequence length to max value. Sequences shorter than the length are
    # padded in the beginning and sequences longer are truncated
    # at the beginning.
 */

use Google\Cloud\Firestore\FirestoreClient;

$firestore = new FirestoreClient();
$ml = new Ml($app);
$db = new Database();

// part train part validate part test (3)
$documents = $db -> getSample($firestore, 'decisions', 10000);
$ml->getMatchedTerms($documents);
