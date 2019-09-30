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
    public $tables;

    // public $categories = array('Stedelijk bedrijfsvastgoed', 'Omgevingsvergunning', 'Politiezone Antwerpen')
    public $tplParts = array(0 => 'Gekoppelde besluiten', 1 => 'Aanleiding en context', 2 => 'Omschrijving stedenbouwkundige handelingen', 3 => 'Juridische grond', 4 => 'Regelgeving: bevoegdheid', 5 => 'Openbaar onderzoek', 6 => 'Argumentatie', 7 => 'Financiële gevolgen', 8 => 'Algemene financiële opmerkingen', 9 => 'Strategisch kader', 10 => 'Adviezen', 11 => 'Besluit', 12 =>'Bijlagen');

    public function __contruct(){
        
    }

    public function __destruct() {
    }
}