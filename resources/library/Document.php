<?php

class Document {
    public $id;
    public $intId;
    public $category;
    public $title;
    public $fullText;
    public $background;
    public $decision;
    public $financials;
    public $addenda;
    public $tplParts = array('Gekoppelde besluiten', 'Aanleiding en context', 'Omschrijving stedenbouwkundige handelingen','Juridische grond', 'Regelgeving: bevoegdheid', 'Argumentatie', 'Financiële gevolgen', 'Algemene financiële opmerkingen', 'Strategisch kader', 'Adviezen', 'Besluit','Bijlagen');

    public function __contruct(){
        
    }

    public function __destruct() {
    }
}