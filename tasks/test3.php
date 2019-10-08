<?php
require_once __DIR__ . '/../bootstrap.php';

$ml = new Ml($app);

$cats = array('antwerpen', 'deurne','berchem','wilrijk','borgerhout','merksem','hoboken','ekeren','berendrecht','cultuur', 'beleid', 'sport','onderwijs','jeugd','vergunning','aanpassing','verkeersreglement','bestek','bezwaren','belasting','uitbetaling','loketwerking','vzw','onderwijs','amendement','gunning','politie','bedrijfsvastgoed','krediet','vraag','brief','districtscollege','districtsraad','wegeniswerken','verkeerssignalisatie','takelingen','correctiebestelling','bestelbons','interpellatie','toelage');


$terms = array('districtscollege', 'notulen', 'mei');

if ( $ml->inRefArray($terms, $cats) ) {
    echo 'match!';
};