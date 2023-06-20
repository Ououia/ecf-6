<?php
require 'vendor/autoload.php';

use Carbon\Carbon;



if (isset($_GET['month']) || isset($_GET['quarter']) || isset($_GET['year'])) {

    $now = Carbon::now()->format('Y-m-d\TH:i:s');


    $host = 'localhost';
    $db   = 'ecfc6';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $opt = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
        $pdo = new PDO($dsn, $user, $pass, $opt);
    } catch (PDOException $e) {
        throw new PDOException($e->getMessage(), (int)$e->getCode());
    }

    $societe = "SELECT * FROM societe";
    $societe = $pdo->query($societe);
    $societe = $societe->fetch();

    $societeNumeroCafat = explode('/', $societe['numerocafat']);
    $societeNumeroRidet = explode('.', $societe['ridet']);


    $month = $_GET['month'] ?? "";
    $quarter = $_GET['quarter'] ?? "";
    $year = $_GET['year'];
    $type =  $_GET["contact"];

    if (isset($year)) {
        // $dateEmbauche = $year;
        if ($quarter != "") {
            switch ($quarter) {
                case 1:
                    $firstMonth = "01";
                    $lastMonth = "03";
                    break;
                case 2:
                    $firstMonth = "04";
                    $lastMonth = "06";
                    break;
                case 3:
                    $firstMonth = "07";
                    $lastMonth = "09";
                    break;
                case 4:
                    $firstMonth = "10";
                    $lastMonth = "12";
                    break;
                default:
                    break;
            }
            $employees = "SELECT * FROM `salaries` WHERE YEAR(dembauche) = 2022 AND MONTH(dembauche) BETWEEN $firstMonth AND $lastMonth";

            $employees = $pdo->query($employees);
            $employees = $employees->fetchAll();
        }

        if ($month != "") {
            $yearOnly = $year;
            $year = $year . $month;
            $stmt = $pdo->prepare("SELECT 
            salaries.id AS salarie_id, salaries.nom, salaries.prenom,salaries.numcafat,salaries.dnaissance,salaries.dembauche,
            SUM(bulletin.brut) AS total_brut,
            SUM(bulletin.nombre_heures) AS total_hours
            FROM 
                bulletin
            INNER JOIN 
                salaries ON bulletin.salarie_id = salaries.id
            WHERE 
                bulletin.periode LIKE :year
            GROUP BY 
            salaries.id, salaries.nom, salaries.prenom");

            // Bind the year variable to the SQL statement
            $stmt->execute(['year' => $year . '%']);

            // Fetch all rows and print them
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        // Prepare the SQL statement
        $stmt = $pdo->prepare("SELECT 
        salaries.id AS salarie_id, salaries.nom, salaries.prenom,salaries.numcafat,salaries.dnaissance,salaries.dembauche,
        SUM(bulletin.brut) AS total_brut,
        SUM(bulletin.nombre_heures) AS total_hours
        FROM 
            bulletin
        INNER JOIN 
            salaries ON bulletin.salarie_id = salaries.id
        WHERE 
            bulletin.periode LIKE :year
        GROUP BY 
        salaries.id, salaries.nom, salaries.prenom");

        // Bind the year variable to the SQL statement
        $stmt->execute(['year' => $year . '%']);

        // Fetch all rows and print them
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // print_r($employees);


    //Create XML
    $doc = new DOMDocument('1.0', 'ISO-8859-1');
    $doc->formatOutput = true;

    // doc
    $docElement = $doc->createElement('doc');
    $doc->appendChild($docElement);

    // entete
    $entete = $doc->createElement('entete');
    $docElement->appendChild($entete);

    $entete->appendChild($doc->createElement('type', 'DN'));
    $entete->appendChild($doc->createElement('version', 'VERSION_2_0'));
    $entete->appendChild($doc->createElement('emetteur', $societe["enseigne"]));
    $entete->appendChild($doc->createElement('dateGeneration', $now));

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

    $periode->appendChild($doc->createElement('type', $type));
    $periode->appendChild($doc->createElement('annee', $yearOnly));
    if ($month != "") {
        $periode->appendChild($doc->createElement('numero', ltrim($month, "0")));
    } elseif ($quarter != "") {
        $periode->appendChild($doc->createElement('numero', $quarter));
    } else {
        $periode->appendChild($doc->createElement('numero', "1"));
    }

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

    $employeur->appendChild($doc->createElement('numero', $societeNumeroCafat[0]));
    $employeur->appendChild($doc->createElement('suffixe', ltrim($societeNumeroCafat[1], "0")));
    $employeur->appendChild($doc->createElement('nom', $societe["enseigne"]));
    $employeur->appendChild($doc->createElement('rid', $societeNumeroRidet[0]));
    $employeur->appendChild($doc->createElement('codeCotisation', "001"));
    $employeur->appendChild($doc->createElement('tauxATPrincipal', $societe["tauxat"]));

    // assures

    $assures = $doc->createElement('assures');
    $corps->appendChild($assures);

    foreach ($employees as $employee) {
        $assure = $doc->createElement('assure');
        $assures->appendChild($assure);

        $assure->appendChild($doc->createElement('numero',  $employee['numcafat']));
        $assure->appendChild($doc->createElement('nom', $employee['nom']));
        $assure->appendChild($doc->createElement('prenoms', $employee['prenom']));
        $assure->appendChild($doc->createElement('dateNaissance', $employee['dnaissance']));
        $assure->appendChild($doc->createElement('codeAT', 'PRINCIPAL'));
        $assure->appendChild($doc->createElement('etablissementRID', $societeNumeroRidet['1']));
        if (isset($year)) {
            $yearEmbauche = date("Y", strtotime($employee['dembauche']));
            if ($yearEmbauche == $year) {
                $assure->appendChild($doc->createElement('dateEmbauche', $employee['dembauche']));
            }
            if ($quarter != "") {
                $yearEmbauche = date("Y-m", strtotime($employee['dembauche']));
                if ($yearEmbauche == $year . "-" . "0" . $quarter) {
                    $assure->appendChild($doc->createElement('dateEmbauche', $employee['dembauche']));
                }
            }
            if ($month != "") {
                $yearEmbauche = date("Ym", strtotime($employee['dembauche']));
                if ($yearEmbauche == $year . $quarter) {
                    $assure->appendChild($doc->createElement('dateEmbauche', $employee['dembauche']));
                }
            }
        }
        // $assure->appendChild($doc->createElement('codeCommune', $employee['']'09'));0
        $assure->appendChild($doc->createElement('nombreHeures', $employee['total_hours']));
        $assure->appendChild($doc->createElement('remuneration', $employee['total_brut']));
    }


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

    //Headers for download
    header("Content-Type: text/xml");
    // header('Content-Disposition: attachment; filename="' . $filename . '"');

    echo $doc->saveXML();
    exit;
}
