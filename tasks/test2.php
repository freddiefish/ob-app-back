<?php
$tplParts = array('Financiële gevolgen', 'Regelgeving: bevoegdheid', 'Algemene financiële opmerkingen');
$textChop = 'Financiële gevolgen';

if (in_array($textChop, $tplParts))  {
    echo "In array!";
} 

if(is_int( strpos($textChop, $tplParts[0]) )) {
    echo 'match';
}
