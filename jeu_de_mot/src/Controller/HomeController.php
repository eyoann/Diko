<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use MongoDB\Client;

class HomeController extends AbstractController
{
    /**
     * @Route("/home", name="home")
     */
    public function index()
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    /**
     * @Route("/search", name="search")
     */
    public function search(Request $request)
    {

    	$client = new Client("mongodb://localhost:27017");
		$dico = $client->mydb->dico;

    	//REQUEST
    	$search = $request->query->get('q');
    	$search = str_replace(" ", "+", $search);
    	//SEARCH SERVER JDM
    	$contenu = file_get_contents("http://www.jeuxdemots.org/rezo-dump.php?gotermsubmit=Chercher&gotermrel=$search&rel=");


    	//RECUPERER LE DUMP CODE
    	//preg_match("/<CODE>(.*)<\/CODE>/s", $contenu, $matches, PREG_OFFSET_CAPTURE);
    	//$contenu = $matches[0];


    	//ID
		$regex = "/eid=(?P<id>[0-9]*)/";
		preg_match($regex, $contenu, $id, PREG_OFFSET_CAPTURE);

		$id = $id['id'][0];

    	//Recuperer la rechercher
    	preg_match("/e;[0-9]*;'(?P<nom>.*)';[0-9]*;[0-9]*(;'(?P<cnom>.*)')*/", utf8_encode($contenu), $name, PREG_OFFSET_CAPTURE);
    	$search = array_key_exists('cnom', $name) ? $name['cnom'][0] : $name['nom'][0];


    	//RECUPERER LES DEFINITIONS
    	preg_match("/<def>(.*)<\/def>/s", $contenu, $defs, PREG_OFFSET_CAPTURE);

    	if($defs) {
	    	$s = utf8_encode(strip_tags($defs[0][0]));

			$regex = "/^([0-9]+\. )?(.*)$/m";
			$definition = "";

			$defs = [];

			$i = 0;

			preg_match_all($regex, $s, $matches, PREG_SET_ORDER);

			foreach ($matches as $match) {

				if ($match[1] && $definition) {
					$defs[$i] = $definition;
					$i++;
					$definition = "";
				}

				$definition .= $match[2];
			}

			if ($definition) {
				$defs[$i] = $definition;
			}
		}

    	//BDD
    	preg_match_all("/e;(?P<id>[0-9]*);'(?P<nom>.*)';[0-9]*;[0-9]*(;'(?P<cnom>.*)')*/", utf8_encode($contenu), $addBDD, PREG_SET_ORDER);
    	foreach ($addBDD as $key => $value) {
    		$arrayBDD [$value['id']] = [
    		'terme' =>array_key_exists('cnom', $value) ? $value['cnom'] : $value['nom'],
    		'search' => str_replace(" ", "+", $value['nom'])
    		];
    	}
    	unset($arrayBDD['239128']); //Remove _COM
    	unset($arrayBDD['2983124']); //Remove _SW


		$forlist = [
			'0' => 'Associations d\'idées',
			'4' => 'r_pos',
			'5' => 'Synonymes',
			'6' => 'Generiques',
			'8' => 'Spécifiques',
			'9' => "Parties de $search",
			'10' => "$search fait partie de",
			'11' =>'Locutions',
			'13' =>"Que peut faire $search ? (agent)",
			'14' =>"$search comme objet",
			'15' =>"Lieu ou peut se trouver $search",
			'16' =>'Instrument',
			'17' =>'Caractéristiques',
			'18' =>'iL',
			'19' =>'Lemme',
			'20' =>"Plus intense que $search",
			'21' =>"Moins intense que $search",
			'22' =>'Termes étymologiquement apparentés',
			'24' =>"Que peut faire $search",
			'25' =>"Que peut-on faire avec $search",
			'26' =>"Que peut-on faire de/a $search (patient)",
			'28' =>"Lieux où peut se trouver/dérouler $search",
			'32' =>'Sentiments',
			'37' =>"Rôles teliques $search",
			'38' =>"Rôles agentifs $search",
			'46' =>"$search comme objet (interne)",
			'48' =>"$search comme instrument",
			'50' =>'Matiere/Substance',
			'106' =>'Couleurs',
			'121' =>'Possède',
			'155' =>"Peut utiliser un objet ou produit par $search",
			'156' =>"Par qui/quoi $search peut-être utilisé",
			'666' =>'Totaki',
			'777' =>'Wikipedia',
			'999' =>'Inhib'
		];

		$forArray = array();
		$list = array();

		foreach ($forlist as $key => $value) {

			$regex = "/r;[0-9]*;$id;([0-9]*);$key;[0-9]*/";

			$out = $this->createArrayID($regex, $contenu);

			$list = array_merge($list, $out);

			$forArray ["$value"]["out"] = $out;

			$regex = "/r;[0-9]*;([0-9]*);$id;$key;[0-9]*/";

			$in = $this->createArrayID($regex, $contenu);

			$list = array_merge($list, $in);

			$forArray ["$value"]["in"] = $in;
		}

		foreach ($forArray as $key => $array) {
			foreach ($array as $inout => $value) {
				$forArray[$key][$inout] = $this->relation($value, $arrayBDD);
			}
		}




		//POS
		$pos = $forArray['r_pos']['out'];
		unset($forArray['r_pos']);
		unset($forlist['4']);

		//lemme
		$lemme = $forArray['Lemme']['out'];
		unset($forArray['Lemme']);
		unset($forlist['19']);

		//IL
		$iL = $forArray['iL']['out'];
		unset($forArray['iL']);
		unset($forlist['18']);



		for ($i=0; $i < 31; $i+=4) {
			$listCheckBox [] = array_slice($forlist, $i, 4);
		}

    	return $this->render('home/search.html.twig',
    		['q' => $search,
    		 'defs' => $defs,
    		 'assos' => $forArray,
    		 'pos' => $pos,
    		 'lemme' => $lemme,
    		 'iL' => $iL,
    		 'listCheckBox' => $listCheckBox
    		 ]);
    }

    public function createArrayID($regex, $contenu) {

    	preg_match_all($regex, $contenu, $relation, PREG_SET_ORDER);
    	$arrayid = [];
		foreach ($relation as $key => $value) {
			$arrayid [] = (int) $value[1];
		}
		return $arrayid;
    }


    public function relation($arrayid, $mots) {

		if (!isset($arrayid)) {
			return null;
		}

		$relationOut = array();

		foreach ($arrayid as $key => $value) {
			if(array_key_exists($value,$mots)) {
				$relationOut[$value] = [
				'terme' => $mots[$value]['terme'],
				'search' => $mots[$value]['search']
				];
			}
		}

		return $relationOut;
	}
}

