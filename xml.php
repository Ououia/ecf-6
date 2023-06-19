<?php

//Create XML
$doc = new DOMDocument('1.0', 'ISO-8859-1');
$doc->formatOutput = true;

$root = $doc->createElement('doc');
$doc->appendChild($root);

//... Add your other XML elements here
// doc
$docElement = $doc->createElement('doc');
$doc->appendChild($docElement);

// entete
$entete = $doc->createElement('entete');
$docElement->appendChild($entete);

$entete->appendChild($doc->createElement('type', 'DN'));
$entete->appendChild($doc->createElement('version', 'VERSION_2_0'));
$entete->appendChild($doc->createElement('emetteur', 'ATP'));
$entete->appendChild($doc->createElement('dateGeneration', '2022-10-13T12:00:00'));

$logiciel = $doc->createElement('logiciel');
$entete->appendChild($logiciel);

$logiciel->appendChild($doc->createElement('editeur', 'MONEDITEUR'));
$logiciel->appendChild($doc->createElement('nom', 'MONPROGICIEL'));
$logiciel->appendChild($doc->createElement('version', '11.0.0 Patch 3'));
$logiciel->appendChild($doc->createElement('dateVersion', '2022-12-25'));

// corps
$corps = $doc->createElement('corps');
$docElement->appendChild($corps);

// periode
$periode = $doc->createElement('periode');
$corps->appendChild($periode);

$periode->appendChild($doc->createElement('type', 'TRIMESTRIEL'));
$periode->appendChild($doc->createElement('annee', '2023'));
$periode->appendChild($doc->createElement('numero', '1'));

// attributs
$attributs = $doc->createElement('attributs');
$corps->appendChild($attributs);

$attributs->appendChild($doc->createElement('complementaire', 'false'));
$attributs->appendChild($doc->createElement('contratAlternance', 'false'));
$attributs->appendChild($doc->createElement('pasAssureRemunere', 'false'));
$attributs->appendChild($doc->createElement('pasDeReembauche', 'false'));

// employeur
$employeur = $doc->createElement('employeur');
$corps->appendChild($employeur);

$employeur->appendChild($doc->createElement('numero', '123456'));
$employeur->appendChild($doc->createElement('suffixe', '1'));
$employeur->appendChild($doc->createElement('nom', 'TENNIS CONSULTING'));
$employeur->appendChild($doc->createElement('rid', '12345'));
$employeur->appendChild($doc->createElement('codeCotisation', '001'));
$employeur->appendChild($doc->createElement('tauxATPrincipal', '0.72'));

// assures
$assures = $doc->createElement('assures');
$corps->appendChild($assures);

$assure = $doc->createElement('assure');
$assures->appendChild($assure);


$assure->appendChild($doc->createElement('numero', '458784'));
$assure->appendChild($doc->createElement('nom', 'LENDL'));
$assure->appendChild($doc->createElement('prenoms', 'IVAN DIMITRI'));
$assure->appendChild($doc->createElement('dateNaissance', '1960-03-07'));
$assure->appendChild($doc->createElement('codeAT', 'PRINCIPAL'));
$assure->appendChild($doc->createElement('etablissementRID', '123'));
$assure->appendChild($doc->createElement('codeCommune', '09'));
$assure->appendChild($doc->createElement('nombreHeures', '156.00'));
$assure->appendChild($doc->createElement('remuneration', '500000'));

// assiettes
$assiettes = $doc->createElement('assiettes');
$assure->appendChild($assiettes);

$assiette1 = $doc->createElement('assiette');
$assiettes->appendChild($assiette1);

$assiette1->appendChild($doc->createElement('type', 'RUAMM'));
$assiette1->appendChild($doc->createElement('valeur', '500000'));

$assiette2 = $doc->createElement('assiette');
$assiettes->appendChild($assiette2);

$assiette2->appendChild($doc->createElement('type', 'FIAF'));
$assiette2->appendChild($doc->createElement('valeur', '500000'));

$assure->appendChild($doc->createElement('dateRupture', '2023-01-31'));
$assure->appendChild($doc->createElement('observations', 'FIN DE CONTRAT'));

// decompte
$decompte = $doc->createElement('decompte');
$corps->appendChild($decompte);

// cotisations
$cotisations = $doc->createElement('cotisations');
$decompte->appendChild($cotisations);

$cotisation = $doc->createElement('cotisation');
$cotisations->appendChild($cotisation);

$cotisation->appendChild($doc->createElement('type', 'RUAMM'));
$cotisation->appendChild($doc->createElement('tranche', 'TRANCHE_1'));
$cotisation->appendChild($doc->createElement('assiette', '3583400'));
$cotisation->appendChild($doc->createElement('valeur', '556144'));

$decompte->appendChild($doc->createElement('totalCotisations', '1803203'));

// deductions
$deductions = $doc->createElement('deductions');
$decompte->appendChild($deductions);

$deduction1 = $doc->createElement('deduction');
$deductions->appendChild($deduction1);

$deduction1->appendChild($doc->createElement('type', 'ACOMPTE'));
$deduction1->appendChild($doc->createElement('valeur', '3203'));

$deduction2 = $doc->createElement('deduction');
$deductions->appendChild($deduction2);

$deduction2->appendChild($doc->createElement('type', 'RBS'));
$deduction2->appendChild($doc->createElement('valeur', '100000'));

$decompte->appendChild($doc->createElement('montantAPayer', '1700000'));

$filename = "output.xml";

// headers for download
header('Content-Type: text/xml');
header('Content-Disposition: attachment; filename="' . $filename . '"');

echo $doc->saveXML();
exit;
