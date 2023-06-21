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
    $periods = [];

    if (isset($year)) {
        $yearOnly = $year;
        $periods = [$year . "01", $year . "02", $year . "03", $year . "04", $year . "05", $year . "06", $year . "07", $year . "08", $year . "09", $year . "10", $year . "11", $year . "12"];
        if ($quarter != "") {
            switch ($quarter) {
                case 1:
                    $periods = [$year . "01", $year . "02", $year . "03"];

                    break;
                case 2:
                    $periods = [$year . "04", $year . "05", $year . "06"];
                    break;
                case 3:
                    $periods = [$year . "07", $year . "08", $year . "09"];

                    break;
                case 4:
                    $periods = [$year . "10", $year . "11", $year . "12"];
                    break;
                default:
                    break;
            }
        }

        if ($month != "") {
            $periods = [$year . $month];
            $yearOnly = $year;
            $year = $year . $month;
        }
        // Prepare the SQL statement
        $in = "'" . implode("','", $periods) . "'"; // assumes data is safe!
        $stmt = $pdo->prepare("SELECT 
        salaries.id AS salarie_id, salaries.nom, salaries.prenom,salaries.numcafat,salaries.dnaissance,salaries.dembauche,salaries.drupture,
        SUM(bulletin.brut) AS total_brut,
        SUM(bulletin.nombre_heures) AS total_hours
        FROM 
            bulletin
        INNER JOIN 
            salaries ON bulletin.salarie_id = salaries.id
        WHERE 
            bulletin.periode IN ($in) 
        GROUP BY 
        salaries.id, salaries.nom, salaries.prenom");

        // Bind the year variable to the SQL statement
        $stmt->execute();

        // Fetch all rows and print them
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $list_employee_id = [];
    foreach ($employees as $employee) {
        array_push($list_employee_id, $employee['salarie_id']);
    }

    //Create XML
    $doc = new DOMDocument('1.0', 'ISO-8859-1');
    // $doc->formatOutput = true;

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
        // print_r($employee);

        $assure = $doc->createElement('assure');
        $assures->appendChild($assure);

        $assure->appendChild($doc->createElement('numero',  $employee['numcafat']));
        $assure->appendChild($doc->createElement('nom', $employee['nom']));
        $assure->appendChild($doc->createElement('prenoms', $employee['prenom']));
        $assure->appendChild($doc->createElement('dateNaissance', $employee['dnaissance']));
        $assure->appendChild($doc->createElement('codeAT', 'PRINCIPAL'));
        $assure->appendChild($doc->createElement('etablissementRID', $societeNumeroRidet['1']));
        foreach ($periods as $period) {
            $yearEmbauche = date("Ym", strtotime($employee['dembauche']));
            if ($yearEmbauche == $period) {
                $assure->appendChild($doc->createElement('dateEmbauche', $employee['dembauche']));
            }
        }

        $assure->appendChild($doc->createElement('nombreHeures', $employee['total_hours']));
        $assure->appendChild($doc->createElement('remuneration', $employee['total_brut']));

        // assiettes
        $assiettes = $doc->createElement('assiettes');
        $assure->appendChild($assiettes);
        $in = "'" . implode("','", $periods) . "'"; // assumes data is safe!

        // $ligne_bulletins_query =  $pdo->prepare("SELECT * 
        // FROM `bulletin` 
        // JOIN ligne_bulletin on ligne_bulletin.bulletin_id = bulletin.id 
        // WHERE periode IN ($in) 
        // AND bulletin.salarie_id = :salarie_id 
        // AND ligne_bulletin.rubrique_id IN (58,57,67,56,59,60,61,62,68,64,65,66)
        // ORDER BY ligne_bulletin.rubrique_id");

        // $ligne_bulletins_query->execute(["salarie_id" => $employee["salarie_id"]]);
        // $ligne_bulletins = $ligne_bulletins_query->fetchAll();

        $testligne_query = $pdo->prepare("SELECT rubrique_id, sum(base) as base , sum(montant_salarial) as montant_salarial, sum(montant_patronal)  as montant_patronal
        FROM `bulletin`
        JOIN ligne_bulletin on ligne_bulletin.bulletin_id = bulletin.id
        WHERE periode IN ($in)
        AND bulletin.salarie_id = :salarie_id  
        AND ligne_bulletin.rubrique_id IN (58,57,67,56,59,60,61,62,68,64,65,66)
        GROUP BY ligne_bulletin.rubrique_id");

        $testligne_query->execute(["salarie_id" => $employee["salarie_id"]]);
        $testligne = $testligne_query->fetchAll();



        foreach ($testligne as $ligne) {
            if ($ligne["rubrique_id"] == 57 && $ligne["base"] != 0) {
                $assiette = $doc->createElement('assiette');
                $assiettes->appendChild($assiette);
                $assiette->appendChild($doc->createElement('type', 'RUAMM'));
                $assiette->appendChild($doc->createElement('valeur', $ligne["base"]));
            }

            if ($ligne["rubrique_id"] == 58 && $ligne["base"] != 0) {
                $assiette = $doc->createElement('assiette');
                $assiettes->appendChild($assiette);
                $assiette->appendChild($doc->createElement('type', 'RUAMM'));
                $assiette->appendChild($doc->createElement('tranche', 'TRANCHE_2'));
                $assiette->appendChild($doc->createElement('valeur', $ligne["base"]));
            }

            if ($ligne["rubrique_id"] == 67 && $ligne["base"] != 0) {
                $assiette = $doc->createElement('assiette');
                $assiettes->appendChild($assiette);
                $assiette->appendChild($doc->createElement('type', 'FIAF'));
                $assiette->appendChild($doc->createElement('valeur', $ligne["base"]));
            }
            if ($ligne["rubrique_id"] == 56 && $ligne["base"] != 0) {
                $assiette = $doc->createElement('assiette');
                $assiettes->appendChild($assiette);
                $assiette->appendChild($doc->createElement('type', 'PRESTATIONS_FAMILIALES'));
                $assiette->appendChild($doc->createElement('valeur', $ligne["base"]));
            }
            if ($ligne["rubrique_id"] == 56 && $ligne["base"] != 0) {
                $assiette = $doc->createElement('assiette');
                $assiettes->appendChild($assiette);
                $assiette->appendChild($doc->createElement('type', 'CHOMAGE'));
                $assiette->appendChild($doc->createElement('valeur', $ligne["base"]));
            }
            if ($ligne["rubrique_id"] == 62 && $ligne["base"] != 0) {
                $assiette = $doc->createElement('assiette');
                $assiettes->appendChild($assiette);
                $assiette->appendChild($doc->createElement('type', 'ATMP'));
                $assiette->appendChild($doc->createElement('valeur', $ligne["base"]));
            }
            if ($ligne["rubrique_id"] == 68 && $ligne["base"] != 0) {
                $assiette = $doc->createElement('assiette');
                $assiettes->appendChild($assiette);
                $assiette->appendChild($doc->createElement('type', 'FDS'));
                $assiette->appendChild($doc->createElement('valeur', $ligne["base"]));
            }
            if ($ligne["rubrique_id"] == 64 && $ligne["base"] != 0) {
                $assiette = $doc->createElement('assiette');
                $assiettes->appendChild($assiette);
                $assiette->appendChild($doc->createElement('type', 'FORMATION_PROFESSIONNELLE'));
                $assiette->appendChild($doc->createElement('valeur', $ligne["base"]));
            }
            if ($ligne["rubrique_id"] == 65 && $ligne["base"] != 0) {
                $assiette = $doc->createElement('assiette');
                $assiettes->appendChild($assiette);
                $assiette->appendChild($doc->createElement('type', 'FSH'));
                $assiette->appendChild($doc->createElement('valeur', $ligne["base"]));
            }
            if ($ligne["rubrique_id"] == 66 && $ligne["base"] != 0) {
                $assiette = $doc->createElement('assiette');
                $assiettes->appendChild($assiette);
                $assiette->appendChild($doc->createElement('type', 'CCS'));
                $assiette->appendChild($doc->createElement('valeur', $ligne["base"]));
            }
        }
        foreach ($periods as $period) {
            $dateRupture = date("Ym", strtotime($employee['drupture']));
            if ($dateRupture == $period) {
                $assure->appendChild($doc->createElement('dateRupture', $employee['drupture']));
            }
        }
    }


    // decompte
    $decompte = $doc->createElement('decompte');
    $corps->appendChild($decompte);

    $cotisations = $doc->createElement('cotisations');
    $decompte->appendChild($cotisations);

    $test = "'" . implode("','", $list_employee_id) . "'";

    $decompte_query = $pdo->prepare("SELECT rubrique_id, sum(base) as base , sum(montant_salarial) as montant_salarial, sum(montant_patronal)  as montant_patronal ,   sum(montant_salarial) + sum(montant_patronal) as sum
    FROM `bulletin`
    JOIN ligne_bulletin on ligne_bulletin.bulletin_id = bulletin.id
    WHERE periode IN ($in)
    AND bulletin.salarie_id IN ($test)  
    AND ligne_bulletin.rubrique_id IN (58,57,67,56,59,60,61,62,68,64,65,66)
    GROUP BY ligne_bulletin.rubrique_id");

    $decompte_query->execute();
    $decompte_array = $decompte_query->fetchAll();
    $totalCotisation = [];
    foreach ($decompte_array as $array) {
        // print_r($array);
        if ($array["rubrique_id"] == 57 && $array["base"] != 0) {
            $cotisation = $doc->createElement('cotisation');
            $cotisations->appendChild($cotisation);
            $cotisation->appendChild($doc->createElement('type', 'RUAMM'));
            $cotisation->appendChild($doc->createElement('assiette', $array["base"]));
            $cotisation->appendChild($doc->createElement('valeur', ($array["sum"]) * -1));
        }

        if ($array["rubrique_id"] == 58 && $array["base"] != 0) {
            $cotisation = $doc->createElement('cotisation');
            $cotisations->appendChild($cotisation);
            $cotisation->appendChild($doc->createElement('type', 'RUAMM'));
            $cotisation->appendChild($doc->createElement('tranche', 'TRANCHE_2'));
            $cotisation->appendChild($doc->createElement('assiette', $array["base"]));
            $cotisation->appendChild($doc->createElement('valeur', ($array["sum"]) * -1));
        }

        if ($array["rubrique_id"] == 67 && $array["base"] != 0) {
            $cotisation = $doc->createElement('cotisation');
            $cotisations->appendChild($cotisation);
            $cotisation->appendChild($doc->createElement('type', 'FIAF'));
            $cotisation->appendChild($doc->createElement('assiette', $array["base"]));
            $cotisation->appendChild($doc->createElement('valeur', ($array["sum"]) * -1));
        }
        if ($array["rubrique_id"] == 56 && $array["base"] != 0) {
            $cotisation = $doc->createElement('cotisation');
            $cotisations->appendChild($cotisation);
            $cotisation->appendChild($doc->createElement('type', 'PRESTATIONS_FAMILIALES'));
            $cotisation->appendChild($doc->createElement('assiette', $array["base"]));
            $cotisation->appendChild($doc->createElement('valeur', ($array["sum"]) * -1));
        }
        if ($array["rubrique_id"] == 56 && $array["base"] != 0) {
            $cotisation = $doc->createElement('cotisation');
            $cotisations->appendChild($cotisation);
            $cotisation->appendChild($doc->createElement('type', 'CHOMAGE'));
            $cotisation->appendChild($doc->createElement('assiette', $array["base"]));
            $cotisation->appendChild($doc->createElement('valeur', ($array["sum"]) * -1));
        }
        if ($array["rubrique_id"] == 62 && $array["base"] != 0) {
            $cotisation = $doc->createElement('cotisation');
            $cotisations->appendChild($cotisation);
            $cotisation->appendChild($doc->createElement('type', 'ATMP'));
            $cotisation->appendChild($doc->createElement('assiette', $array["base"]));
            $cotisation->appendChild($doc->createElement('valeur', ($array["sum"]) * -1));
        }
        if ($array["rubrique_id"] == 68 && $array["base"] != 0) {
            $cotisation = $doc->createElement('cotisation');
            $cotisations->appendChild($cotisation);
            $cotisation->appendChild($doc->createElement('type', 'FDS'));
            $cotisation->appendChild($doc->createElement('assiette', $array["base"]));
            $cotisation->appendChild($doc->createElement('valeur', ($array["sum"]) * -1));
        }
        if ($array["rubrique_id"] == 64 && $array["base"] != 0) {
            $cotisation = $doc->createElement('cotisation');
            $cotisations->appendChild($cotisation);
            $cotisation->appendChild($doc->createElement('type', 'FORMATION_PROFESSIONNELLE'));
            $cotisation->appendChild($doc->createElement('assiette', $array["base"]));
            $cotisation->appendChild($doc->createElement('valeur', ($array["sum"]) * -1));
        }
        if ($array["rubrique_id"] == 65 && $array["base"] != 0) {
            $cotisation = $doc->createElement('cotisation');
            $cotisations->appendChild($cotisation);
            $cotisation->appendChild($doc->createElement('type', 'FSH'));
            $cotisation->appendChild($doc->createElement('assiette', $array["base"]));
            $cotisation->appendChild($doc->createElement('valeur', ($array["sum"]) * -1));
        }
        if ($array["rubrique_id"] == 66 && $array["base"] != 0) {
            $cotisation = $doc->createElement('cotisation');
            $cotisations->appendChild($cotisation);
            $cotisation->appendChild($doc->createElement('type', 'CCS'));
            $cotisation->appendChild($doc->createElement('assiette', $array["base"]));
            $cotisation->appendChild($doc->createElement('valeur', ($array["sum"]) * -1));
        }

        array_push($totalCotisation, ($array["sum"]) * -1);
    }


    $decompte->appendChild($doc->createElement('totalCotisations', array_sum($totalCotisation)));


    // // deductions
    $deductions = $doc->createElement('deductions', ' ');
    $decompte->appendChild($deductions);

    if (isset($year)) {
        $filename = "DN-" . $year . "A01" . "-" . $societeNumeroCafat[0] . $societeNumeroCafat[1]  . ".xml";
        if ($quarter != "") {
            switch ($quarter) {
                case 1:
                    $filename = "DN-" . $year . "T01" . "-" . $societeNumeroCafat[0] . $societeNumeroCafat[1]  . ".xml";

                    break;
                case 2:
                    $filename = "DN-" . $year . "T02" . "-" . $societeNumeroCafat[0] . $societeNumeroCafat[1]  . ".xml";
                    break;
                case 3:
                    $filename = "DN-" . $year . "T03" . "-" . $societeNumeroCafat[0] . $societeNumeroCafat[1]  . ".xml";

                    break;
                case 4:
                    $filename = "DN-" . $year . "T04" . "-" . $societeNumeroCafat[0] . $societeNumeroCafat[1]  . ".xml";
                    break;
                default:
                    break;
            }
        }

        if ($month != "") {
            $filename = "DN-" . $yearOnly . "M" . $month . "-" . $societeNumeroCafat[0] . $societeNumeroCafat[1]  . ".xml";
        }
    }


    //Headers for download
    header("Content-Type: text/xml");
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    echo $doc->saveXML();
    exit;
}
