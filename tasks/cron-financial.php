<?php
// Use the composer autoloader to load dependencies. On GC App Engine paths are different
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../functions.php';

use Google\Cloud\Firestore\FirestoreClient;

// Create the Cloud Firestore client
$db = new FirestoreClient();

/* $decicionsRef = $db->collection('decisions');
$documents = $decicionsRef->documents();
foreach ($documents as $document) {
        if ($document->exists()) {
                $data = $document->data();
        } else {
                printf('Document %s does not exist!' . PHP_EOL, $snapshot->id());  
        }
        $fullText = $data['fullText'];
        $$fullText = strtolower($$fullText); // lower case to standardize
        get_financials($fullText);
} */




$text = "   districtscollege Antwerpen
Zitting van 9 september 2019
Besluit
B-punt
Samenstelling
beraadslaging/proces verbaal Kopie
GOEDGEKEURD
District Antwerpen
 de heer Paul Cordy, voorzitter districtscollege; de heer Tom Van den Borne, districtsschepen
de heer Samuel Markowitz, districtsschepen; mevrouw Charlien Van Leuffel; mevrouw Femke Meeusen de heer Herald Claeys, districtssecretaris
Iedereen aanwezig, behalve:
mevrouw Charlien Van Leuffel
Motivering
Gekoppelde besluiten
 2017_CBS_04463 - Binnengemeentelijke decentralisatie - Delegatie bevoegdheden - college van burgemeester en schepenen - districtscolleges - Goedkeuring
 2015_DRAN_00036 - Districtsfonds: toelages die de wijk sterker maken - Toelagereglement. Aanpassing - Goedkeuring
 2013_DRAN_00213 - Districtsfonds: toelages die de wijk sterker maken - Toelagereglement - Goedkeuring
 2019_DRAN_00104 - Beleids- en Beheerscyclus - Aanpassing van het meerjarenplan 2014-2019 en
budgetwijziging 2019 - Goedkeuring
Aanleiding en context
Op 16 december 2013 (jaarnummer 213) keurde de districtsraad het toelagereglement 'districtsfonds: toelages die de wijk sterker maken' goed.
Op 16 maart 2015 (jaarnummer 36) keurde de districtsraad de aanpassing goed van het toelagereglement 'districtsfonds: toelages die de wijk sterker maken'.
Op 12 mei 2017 (jaarnummer 4463) keurde het college van burgemeester en schepenen de decentralisatie van bevoegdheden naar het district goed.
Op 20 mei 2019 (jaarnummer 104) keurde de districtsraad de aanpassing van het meerjarenplan 2014-2019 en budgetwijziging 2019 goed.
In 2019 voorziet het district Antwerpen budgetten voor de betoelaging van projecten, activiteiten of wijkfeesten, conform het reglement dat door de districtsraad werd goedgekeurd.
Het district Antwerpen heeft vijf nieuwe aanvragen ontvangen voor een toelage van het districtsfonds:
    10 2019_DCAN_00534 Districtsfonds. Toelages die de wijk sterker maken. 2019 - Toewijzing en uitbetaling - Goedkeuring
    Project, organisatie en gevraagd bedrag
 Datum, locatie, eerdere ondersteuning
Openingsfeest buurthuis UNIK Buurthuis Brederode fv
 13 september 2019
Paleisstraat 108, 2018 Antwerpen
      1 /6
Grote Markt 1 - 2000 Antwerpen info@antwerpen.be

 Financieel tekort: 2.170,00 EUR
 Niet eerder ondersteund.
An Antwerp Blockparty Foot Juice Art vzw
Financieel tekort: 3.000,00 EUR
 22 september 2019
Ankerrui 5, 2000 Antwerpen
2018: 3.000,00 EUR 2017: 2.000,00 EUR
Talent Show : deel 2 Your Bit Counts vzw
Financieel tekort: 2.700,00 EUR
 28 september 2019
Schijnpoortweg 55, 2060 Antwerpen
2019: 1.500,00 EUR 2018: 2.000,00 EUR 2017: 700,00 EUR 2017: 1.250,00 EUR 2016: 1.290,00 EUR
Permeke Draait Door - Boslabs Bending the Frame vzw
Financieel tekort: 3.500,00 EUR
 10 oktober tot en met 13 oktober 2019 De Coninckplein, 2060 Antwerpen
2018: 2.000,00 EUR 2016: 835,00 EUR 2016: 4.000,00 EUR
Vaders van 't Kiel BRASART vzw
Financieel tekort: 3.500,00 EUR
 4 november 2019 tot en met 31 mei 2020 Schijfstraat 105, 2020 Antwerpen
Niet eerder ondersteund.
       Juridische grond
 de wet van 14 november 1983 betreffende de controle op de toekenning en aanwending van sommige toelagen;
 het algemeen reglement op de toelagen goedgekeurd door de gemeenteraad in de zitting van 18 december 2006 (jaarnummer 2730);
 de aanpassing van het reglement op de toelagen voor het districtsfonds goedgekeurd door de districtsraad in de zitting van 16 maart 2015 (jaarnummer 36).
Regelgeving: bevoegdheid
Met de collegebeslissing van 12 mei 2017 (jaarnummer 04463) werden de bevoegdheden van de districtscolleges gecoördineerd. Artikel 2 bepaalt dat het districtscollege bevoegd is voor cultuur. Artikel 7 bepaalt dat het districtscollege bevoegd is voor evenementen en feestelijkheden.
Artikel 12 en 13 van het toelagereglement 'districtsfonds: toelages die de wijk sterker maken', goedgekeurd op 16 maart 2015 (jaarnummer 36) door de districtsraad, bepaalt dat het districtscollege bevoegd is voor het toekennen of het weigeren van de toelage.
Argumentatie
De districtssecretaris stelt voor om volgende projecten te ondersteunen:
 Buurtwerking Brederode fv organiseert een openingsfeest voor het nieuwe buurthuis UNIK in buurt Brederode/Markgrave. Dit buurthuis is gegroeid uit een project van de Burgerbegroting. Verschillende
     2 /6
Grote Markt 1 - 2000 Antwerpen info@antwerpen.be

actoren en buurtbewoners merkten een nood aan een laagdrempelige ontmoetingsruimte in de buurt waar voornamelijk gezinsactiviteiten georganiseerd worden. Met het openingsfeest van het buurthuis wil de vereniging een voorproefje geven van activiteiten die in de toekomst georganiseerd worden op deze locatie. 's Namiddags worden enkele workshops voorzien waar naar een gezamenlijk artistieke resultaat wordt toegewerkt en is er een mogelijkheid om een boodschap/wens aan te brengen op de Droommuur van het buurthuis. Afsluitend wordt een laagdrempelig feest georganiseerd.
De cultuurantenne adviseert een ondersteuning van 1.670,00 EUR toe te kennen om tussen te komen in artistieke kosten en materiaalkosten verbonden aan het project.
 Foot Juice Art vzw organiseert 'An Antwerp Block Party' in het autoluwe/autoloze weekend. Het feest is een outdoor muziekevenement en focust op de vinyl-scene. Het programma bestaat uit verschillende dj's waarvan een aantal jonge Antwerpenaars zijn. Door de combinatie van het evenement gratis aan te bieden, te organiseren in de buurt van een artistiek jongerencentrum en voldoende reclame te maken hoopt de organisator een breed en gevarieerd publiek aan te spreken.
De cultuurantenne adviseert een ondersteuning toe te kennen van 1.500,00 EUR om deels tussen te komen in technische en artistieke kosten. Foot Juice Art vzw wordt er op gewezen dat het formulier en bijhorende administratieve stukken aandachtiger ingevuld en opgesteld moeten worden. Er worden zaken in de tekstuele bijlage vermeld die niet ter zake doen en duidelijk van een eerder evenementzijn. Dit geeft geen goed beeld. De aangeleverde begroting bevat een waaier aan verschillende bedragen voor dezelfde artiesten. Ook worden geen uitgaven voor aankoop drank of voeding (buiten catering) maar rekent de organisator wel op inkomsten (winst?) vanuit drankverkoop. Het is onduidelijk waar deze inkomsten gegenereerd worden. Het kan evengoed zijn dat dit een groter tekort veroorzaakt dan wat nu al wordt opgegeven. De organisator wordt aangeraden om voor dit en volgende evenementen ook op zoek te gaan naar voldoende sponsors.
 'Vaders van 't Kiel' is een project van BRASART vzw dat inzet op het vaderschap binnen gezinnen van het Kiel. Er wordt samengezeten met zowel een aantal groepen/klassen kinderen als met een aantal vaderfiguren zelf om zicht te krijgen op de aan-/afwezigheid van vaders in het gezin. In een eerste fase wordt gepeild naar de ervaringen van (de) kinderen zelf, om later de vaderfiguren zelf te betrekken en bevragen. De aanvrager werkt voor het project samen met een aantal passende partners (OC NOVA, Academie Hoboken, verschillende scholen) waardoor voldoende spreiding/bereik gegarandeerd wordt. Binnen het project worden schrijfoefeningen en workshops georganiseerd om een goed beeld te krijgen op het thema. Het hele project wordt afgelsoten met een fototentoonstelling in NOVA en in het straatbeeld. Afsluitend zal er een fototentoonstelling uitgewerkt worden. De antenne adviseert een andere manier te vinden om dit project een blijvend karakter te geven, er zijn betere en goedkopere alternatieven dan een website te voorzien.
De cultuurantenne adviseert een ondersteuning van 2.000,00 EUR toe te kennen. Het project zet aan tot nadenken en (zelf)reflectie. Er zijn veel betrokkenen waar nauw met samengewerkt wordt. Het project zal een grote visuele impact hebben in buurt Kiel en bevat een sterk imago-verhogend potentieel.
De districtssecretaris stelt voor om volgende projecten niet te ondersteunen:
 'Talent Show: deel 2' van Your Bit Counts vzw werkt rond de talenten van jongeren uit de buurt. Met een laagdrempelige talentshow wil de organisatie jongeren de kans geven zich te uiten in verschillende kunstvormen en zo hun artistieke kunnen te tonen. Na een toonmoment worden jongeren geselecteerd om verder begeleid te worden in 'hun' kunstvorm.
De cultuurantenne adviseert geen ondersteuning toe te kennen. Het project oogt te besloten, er wordt voornamelijk ingezet op gekend publiek. Dit project betreft het ontdekken van eigenschappen bij jongeren en het begeleiden van deze jongeren, maar er wordt onvoldoende ingegaan op de uitvoering en opvolging. Binnen het project wordt gewerkt met een wedstrijdformule waardoor de winnaars begeleid worden in volgende stappen, maar het zijn net de jongeren die het niet halen wegens ondermaatse prestatie of andere zaken, die meer begeleiding nodig hebben. Er is een lijst met juryprofielen die de
  3 /6
Grote Markt 1 - 2000 Antwerpen info@antwerpen.be

jongeren zullen beoordelen en begeleiden maar er worden geen namen genoemd. Wat met jongeren die zich bekwamen in kustvormen waar geen juryprofielen voor gekozen zijn? De cultuurantenne is onvoldoende overtuigd dat dit project ook effectief haar doel zal behalen, of dat de organisator voldoende begeleiding voor de deelnemers kan garanderen.
De organisator wordt gevraagd meer passende partners te zoeken en om ontmoetingscentrum Het Oude Badhuis te contacteren en betrekken voor eventuele logistieke, promotionele en inhoudelijke begeleiding aangezien het project afspeelt in de directe omgeving van het ontmoetingscentrum en met het project voornamelijk ingezet wordt op jongeren van de buurt.
 Bending The Frame vzw organiseert in het kader van 'Permeke Draait Door' enkele workshops rond zeefdrukken, waarvoor met Boslabs wordt samengewerkt. Er wordt al een volwassenenluik voorzien maar er is onvoldoende budget om ook een gedeelte voor kinderen te voorzien.
De cultuurantenne adviseert geen ondersteuning toe te kennen. Het project wordt volledig binnen het kader van 'Permeke Draait Door' georganiseerd, waarvoor de betrokken organisatie ook resideert in Permeke. Dit project moet binnen het kader van Permeke Draait Door opgenomen worden. Er wordt een verschuiving binnen vzw lokaal cultuurbeleid district Antwerpen voorgesteld om het project te kunnen uitvoeren.
Financiële gevolgen
Ja
Strategisch kader
Dit besluit past in de realisatie van volgende doelstellingen/projecten:
 5 - Bruisende stad
 1SAN03 - Bewoners ervaren het district als een aangename leefomgeving, met een (vrijetijds)aanbod op
maat van verschillende doelgroepen
 1SAN0301 - Iedereen vindt in Antwerpen een veelzijdig cultuur- en kunstaanbod en de ruimte om
zichzelf te ontplooien
 1SAN030101 - Alle inwoners van het district hebben toegang tot en kunnen participeren aan een
veelzijdig, lokaal verankerd en zowel innovatief als traditioneel cultuur- en kunstaanbod
 5 - Bruisende stad
 1SAN03 - Bewoners ervaren het district als een aangename leefomgeving, met een (vrijetijds)aanbod op
maat van verschillende doelgroepen
 1SAN0301 - Iedereen vindt in Antwerpen een veelzijdig cultuur- en kunstaanbod en de ruimte om
zichzelf te ontplooien
 1SAN030103 - Feestelijkheden en evenementen versterken de identiteit van het district en zijn
toegankelijk voor alle bewoners
Besluit
Artikel 1
Het districtscollege keurt de evaluatie door de cultuurantenne van de aanvraag en, hieraan gekoppeld, de betoelaging en uitbetaling van de volgende projecten goed:
      Project en vereniging
  Code
 Bedrag
   4 /6
Grote Markt 1 - 2000 Antwerpen info@antwerpen.be

 Openingsfeest buurthuis UNIK Buurtwerking Brederode fv
  cultuur
1.670,00 EUR
An Antwerp Blockparty Foot Juice Art vzw
  wijkfeest
1.500,00 EUR
Vaders van't Kiel BRASART vzw
  cultuur
2.000,00 EUR
     Artikel 2
Het districtscollege gaat akkoord met de weigering van de betoelaging van de projecten 'Permeke Draait Door/Boslabs' en 'Talent Show: deel 2'.
Artikel 3
De financieel directeur verleent zijn visum en regelt de financiële aspecten als volgt:
   Omschrijving
  Bedrag
Boekingsadres
 Bestelbon
Buurtwerking Brederode fv Paleisstraat 108 bus 2
2018 Antwerpen
Ondernemingsnummer: NXX0035595832 Rekeningnummer: BE70 0013 5330 7725
  1.670,0 0 EUR
budgetplaats: 5002000000 budgetpositie: 649500 functiegebied: 1SAN030101A00000 subsidie: SUB_NR fonds: INTERN begrotingsprogramma: 1AN050739 budgetperiode: 1900
 450510067 6
Foot Juice Art vzw Bolivarplaats 3 2000 Antwerpen
Ondernemingsnummer: 0606.974.827 Rekeningnummer: BE10 0689 0513 3804
  1.500,00 EUR
budgetplaats: 5004500000 budgetpositie: 649800 functiegebied: 1SAN030103A00000 subsidie: SUB_NR fonds: INTERN begrotingsprogramma: 1AN050719 budgetperiode: 1900
 450510065 9
BRASART vzw Pyckestraat 49, bus 11 2018 Antwerpen
Ondernemingsnummer: 0892.167.891 Rekeningnummer: BE46 7350 1856 0936
  2.000,00 EUR
budgetplaats: 5002000000 budgetpositie: 649800 functiegebied: 1SAN030101A00000 subsidie: SUB_NR fonds: INTERN begrotingsprogramma: 1AN050739 budgetperiode: 1900
 4505100660
        5 /6
Grote Markt 1 - 2000 Antwerpen info@antwerpen.be

 Bijlagen
1. Aanvraag_BendingTheFrame.pdf 2. Aanvraag_BRASART.pdf
3. Aanvraag_FootJuiceArt.pdf
4. Aanvraag_YourBitCounts.pdf
5. Aanvraag_BuurtwerkingBrederode.pdf
  6 /6
Grote Markt 1 - 2000 Antwerpen info@antwerpen.be"
;

$amounts = array();

get_financials($text,$amounts);

function get_financials($text,$amounts){
    $amountsClean = array();
    $pieces = explode (' EUR', $text);
    $nrElements = count($pieces);
    //var_dump($pieces);

    $i =0;
    foreach($pieces as $piece){

        $words = explode(' ', $piece);
        $amount = array_pop($words);

        if (strpos($amount, ',') ) { // case "10890,67 EUR" round to 10890
            $parts = explode(',', $amount);
            $wholeNumber = $parts[0];
            $integer = intval(str_replace('.','',$wholeNumber));
        }
        
        if ($i < $nrElements-1 && $integer <> 0) array_push($amounts ,$integer);
        
        $i++;
    }

    // var_dump( $amounts );
    //remove duplicates
    $amountsClean = array_unique($amounts);
    
    var_dump( $amountsClean ); 
    return $amountsClean;
}