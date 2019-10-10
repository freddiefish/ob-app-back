<?php

use Sunra\PhpSimple\HtmlDomParser;
use Google\Cloud\Core\Timestamp;

class Scraper
{
    public $logger;
    public $dl;
    public $util;
    public $docList =[];

    public function __construct($logger, $dl, $util)
    {
        $this->logger = $logger;
        $this->dl = $dl;
        $this->util = $util;
    }
    /** 
     * for a given time into history, scrapes all documents form calendar webpage: https://ebesluit.antwerpen.be/calendar/show
     * @param int daysToScreen
     * @return array docList
    */

    public function getDocumentList($daysToScreen = 30) 
    {
        $startDate  = new DateTime();
        $timeSpan   = new DateInterval('P' . $daysToScreen . 'D');
        $timeSpan->invert = 1;
        $startDate->add($timeSpan);
        $stopDate   = new DateTime();
        $updateDate = $startDate;
        list($day, $month, $monthTrailZero, $year) = $this->dateStringify($updateDate);

        $json = $this->scrapeCalendar($monthTrailZero, $year);

        while( $updateDate < $stopDate) 
        {
            list($updateDay, $updateMonth, $updateMonthTrailZero, $updateYear) = $this->dateStringify($updateDate);
            $this->logger->info("Update for: " . $updateDay . '-' .  $updateMonth . '-' . $updateYear); 

            if ($year <> $updateYear OR $monthTrailZero <> $updateMonthTrailZero) 
            {
                $year               = $updateYear;
                $month              = $updateMonth;
                $monthTrailZero     = $updateMonthTrailZero;
                // update calender view
                $json = $this->scrapeCalendar($monthTrailZero, $year);
            }

            $iterator = "$year$month$updateDay";

            foreach ($json as $obj) 
            {

                if (array_key_exists($iterator, $obj)) 
                { // not all dates are available
                    foreach($obj[$iterator] as $val) 
                    { 
                        // get the day's event
                        $this->pathToScrape   = $val['url'];
                        $this->eventDate      = $val['startDateString'];
                        $this->groupId        = $val['groupId'];
                        $this->groupName      = $val['className'];
                        $this->scrapeEventDocs();
                    }
                }
            }
            $updateDate->add(new DateInterval('P1D')); //increment one day
        }
        printf('getDocList: found %s docs' . PHP_EOL, count($this->docList) );
        $this->logger->info('getDocList: found docs: ' . count($this->docList)); 
    }


    /**
     * stringifies a given date and returns array of date elements
     *
     */

    public function dateStringify($date)
    {
        $dateTimestamp   = $date->getTimestamp();

        $year            = date("Y", $dateTimestamp);
        $month           = date("n", $dateTimestamp);
        $monthTrailZero  = date("m", $dateTimestamp);
        $day             = date("j", $dateTimestamp);

        return array($day, $month, $monthTrailZero, $year);
    }

    /**
     * gets json response for calendar month 
     * @param int year
     * @param int month (with trailing zero!)
     * @return string   json 
     */

    public function scrapeCalendar($month, $year) 
    {
        $DOM = $this->dl->doCurl(API_BASE_DIR . '/calendar/filter?year=' . $year . '&month=' . $month);
        $json = json_decode($DOM,true);
        $this->logger->info('Returned json for ' . $month . ' '. $year . ' in calendar'); 

        return $json;
    }


    /**
     * scrape an event for all its documents
     */

    public function scrapeEventDocs() 
    {
        $DOM = HtmlDomParser::str_get_html( $this->dl->doCurl( API_BASE_DIR . $this->pathToScrape ) );

        // get the agenda html 
        $agendaHtml = $DOM->getElementById("agenda");

        // published 
        foreach($agendaHtml->find('a') as $e) {
            $docHref = $e->href;
            $pieces = explode('/', $docHref);
            $docId = $pieces[2];

            $this->createDocEntry($e, $docId);
        }

        // not published 
        foreach($agendaHtml->find('span.title-no-rights') as $e) {
            // non published docs have no id, so create random id
            $docId = $this->util->getRandomString(8); 

            $this->createDocEntry($e, $docId, $published = false);
        }

        // prevent memory leaks
        $DOM->clear();
        unset($DOM);
        unset($agendaHtml);
        unset($e);
     }


     /**
     * creates a doc entry in docList
     * @param object e
     * @param string    docId
     */

    public function createDocEntry($e, $docId, $published = true) 
    {
        $docTitle           = $e->innertext;
        $row['docId']       = $docId;
        $row['offTitle']    = $docTitle;
        list($row['intId'], $row['title']) 
                            = $this->getTitleElements($docTitle) ;
        $row['eventDate']   = $this->eventDate;
        $row['groupId']     = $this->groupId;
        $row['groupName']   = $this->groupName;
        $row['published']   = $published;
        $row['sortIndex1']  = new Timestamp(new DateTime($row['eventDate'])) . $docId; // to order the items in the infinite scroll view in the app
        array_push($this->docList, $row);
        printf('createDocEntry: docId %s' . PHP_EOL,  $row['docId'] );
    }


    /**
     * process official title (eg. "2019_DCME_00239 - Districtscollege - Notulen 19 september 2019 - Goedkeuring")
     * @param string text
     * @return array intId, cleanTitle
     */

    public function getTitleElements($txt) {
        //process the official title, split off first part (e.g. 2016_MV_00157 - Mondelinge vraag van raa...)
        $pieces = explode(" - ", $txt);  
        $intId = trim($pieces[0]);
    
        //remove intId from title 
        $cleanTitle = str_replace( $pieces[0] . " - " , "" , $txt ); 

        $endToRemove = end($pieces);        
        $cleanTitle = str_replace( " - " . $endToRemove , '' , $cleanTitle ); //case insensitive replace
        
        return array($intId, $cleanTitle );
    }
}