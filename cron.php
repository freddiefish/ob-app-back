<?php

mail("frefeys@gmail.com","hallo","msg");
exit;



// Use the composer autoloader to load dependencies.
require __DIR__ . '/vendor/autoload.php';

// scraping the DOM, so init 
use Sunra\PhpSimple\HtmlDomParser;

$updateDate     = date("d-m-Y",$timestamp);
echo $updateDate;
exit;

$year = date("Y");
$month = date("n");
$monthTZero = date("m");

//$data = get_data( 'https://ebesluit.antwerpen.be/publication/19.0802.4796.5619/detail?');
//$data = get_data('https://ebesluit.antwerpen.be/agenda/18.1122.4613.7270/view?' );
//$data = get_data('https://ebesluit.antwerpen.be/calendar/filter?year=' . $year . '&month=' . $month );

// API: https://ebesluit.antwerpen.be/search/ajax?searchText=&yearNumber=&organId=1115782&title=&meetingDate=&order%5B0%5D%5Bdir%5D=asc&start=0&length=10

// get json list of current month's meetings , we can get from the API!
$data = get_data('https://ebesluit.antwerpen.be/calendar/filter?year=' . $year . '&month=' . $monthTZero);
$data = json_decode($data,true);

//store our scraped data
$docList = array();

    $i = 3;

    while($i < 6) {
        // do stuf for all days of the month
        $iter = "$year$month$i";

        foreach($data as  $obj){
            foreach($obj[$iter] as $val) { 
                // echo "objectId ".$val['objectId']."<br/>";
                // get the agenda items
                $urlToScrape = 'https://ebesluit.antwerpen.be/agenda/' . $val['objectId'] . '/view?';
                $eventDate = $val['startDateString'];
                $groupId = $val['groupId'];
                $groupName = $val['className'];
                $docList = create_docList($urlToScrape,$eventDate,$groupId, $groupName,$docList);
            }
        }

        $i++;
    }
 
function create_docList ($urlToScrape,$eventDate,$groupId, $groupName,$docList){ 
    // Create DOM from URL or file
    $dom = HtmlDomParser::file_get_html( $urlToScrape );

    // get only the agenda html 
    $agenda_html = $dom->getElementById("agenda");

    //find published docs first
    foreach($agenda_html->find('a') as $e) {
        $docTitle = $e->innertext;
        $docUrl = $e->href;
        $display = explode('/', $docUrl);
        $docId = $display[2];

        $row= array();
        $row['eventDate'] = $eventDate;
        $row['groupId'] = $groupId;
        $row['groupName'] = $groupName;
        $row['title'] = $docTitle;
        $row['id'] = $docId;
        $row['publiced'] = true;

        array_push($docList, $row);
    }  

    // find not published docs 
    foreach($agenda_html->find('span.title-no-rights') as $e) {
        $docTitle = $e->innertext;
        $row['eventDate'] = $eventDate; // format as timestamp
        $row['groupId'] = $groupId;
        $row['groupName'] = $groupName;
        $row['title'] = $docTitle;
        $row['id'] = null;
        $row['publiced'] = false;
        array_push($docList,  $row);
    }

    return $docList;
}

var_dump($docList);
    
// iterate trough doclist and extract document, add values to db
/* foreach($docList as $value) {
    // first check if document already exists

    // if not scrape and add
    $urlToScrape = 'https://ebesluit.antwerpen.be/publication/'. $value['id'] .'/detail?';
    $fullText = extract_document($url);
} */




function get_data($url) {
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$data = curl_exec($ch);
    curl_close($ch);
    
	return $data;
}


function extract_document($url){
    // Parse pdf file and build necessary objects. date, fulltext, background, decision, every location
    $parser = new \Smalot\PdfParser\Parser();
    $pdf    = $parser->parseFile('https://ebesluit.antwerpen.be/publication/19.0802.4796.5619/download');
 
    $text = $pdf->getText();
    echo $text;
}