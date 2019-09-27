<?php

class Document {

    // entities
    public $locations;
    public $organisations;
    public $financials;
    public $people;

    // general fields
    public $id;
    public $intId;
    public $category;
    public $title;
    public $textParts;
    public $addenda;
    public $tplParts = array('Gekoppelde besluiten', 'Aanleiding en context', 'Omschrijving stedenbouwkundige handelingen','Juridische grond', 'Regelgeving: bevoegdheid', 'Openbaar onderzoek', 'Argumentatie', 'Financiële gevolgen', 'Algemene financiële opmerkingen', 'Strategisch kader', 'Adviezen', 'Besluit','Bijlagen');

    public function __contruct(){
        
    }

    public function __destruct() {
    }
}