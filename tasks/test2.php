<?php
require_once __DIR__ . '/../bootstrap.php';
$ml = new Ml($app);
$util =  new Util();

$randDocs = [];
$offCategories = [];
$titles = [];
$index = [];
$gluedText = '';
$docsArray = $util->readFile('/Users/Main/Apps/ob-app-back/resources/storage/processed/doclist.txt');
$sampleSize = 5000;
$randDocs = $ml->getSample($docsArray, $sampleSize);

$i =0;
$cats = array('antwerpen', 'deurne','berchem','wilrijk','borgerhout','merksem','hoboken','ekeren','berendrecht','cultuur', 'beleid', 'sport','onderwijs','jeugd','vergunning','aanpassing','verkeersreglement','bestek','bezwaren','belasting','uitbetaling','loketwerking','vzw','onderwijs','amendement','gunning','politie','bedrijfsvastgoed','krediet','vraag','brief','districtscollege','districtsraad','gemeenteraad','wegeniswerken','verkeerssignalisatie','takelingen','correctiebestelling','bestelbons','interpellatie','toelage','ontwerp');

$mainCats = array(
    1 => array('antwerpen'), 
    2 => array('deurne'), 
    3 => array('berchem'), 
    4 => array('wilrijk'), 
    5 => array('borgerhout'), 
    6 => array('merksem'), 
    7 => array('hoboken'), 
    8 => array('ekeren'), 
    9 => array('berendrecht'), 
    10 => array('cultuur'), 
    11 => array('beleid'), 
    12 => array('sport'), 
    13 => array('onderwijs'), 
    14 => array('jeugd'), 
    15 => array('vergunning'), 
    2 => array('aanpassing'), 
    2 => array('verkeer'), 
    2 => array('bestek'), 
    2 => array('bezwaren'), 
    2 => array('belasting'), // samen met bezwaren?
    2 => array('loketwerking'), 
    2 => array('vzw'),  
    2 => array('amendement','interpellatie'), 
    2 => array('gunning'), 
    2 => array('politie'), 
    2 => array('bedrijfsvastgoed'), 
    2 => array('vraag'), 
    2 => array('brief'), 
    2 => array('districtscollege'), 
    2 => array('districtsraad'), 
    2 => array('gemeenteraad'), 
    2 => array('wegeniswerken'), 
    2 => array('takelingen'), 
    2 => array('toelage', 'bestelbons','correctiebestelling','krediet','uitbetaling'), 
    2 => array('ontwerp')
);


foreach($randDocs as $doc) {
    $title = trim ( strip_tags( $doc['title'] ) );
    $pieces = explode(' - ', $title);
    $offCategory = trim ($pieces[0]);
    $terms = $ml->getTerms($title, false);
    $index = $ml->addToIndex($terms, $index, false);
    array_push($offCategories, $offCategory);
    if ($ml->inRefArray($terms, $cats)) $i++;
}

echo ( $i / $sampleSize ) * 100 . ' % matched with categories<br>';

$freqTerms = array_count_values($index) ;
arsort($freqTerms);
$ml->freqTerms = $freqTerms;
$ml->mostFreqTerms(5);
$uniqueOffCats = array_unique($offCategories);
asort($uniqueOffCats);

foreach($uniqueOffCats as $key=>$val) {
    // echo $val . '<br>';
    echo $ml->textWithFreqTerms($val, $ml->freqTerms) . '<br>';
}


