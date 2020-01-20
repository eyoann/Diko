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

    	if ($this->get_http_response_code("http://www.jeuxdemots.org/rezo-dump.php?gotermsubmit=Chercher&gotermrel=$search&rel=") != "200") {
    		$this->addFlash(
                    'warning',
                    "Le serveur de JDM ne répond pas"
                );
    		return $this->redirectToRoute('home');
		} else {
    		$contenu = file_get_contents("http://www.jeuxdemots.org/rezo-dump.php?gotermsubmit=Chercher&gotermrel=$search&rel=");
		}

    	$regex = "/Le terme \'.*\' n\'existe pas/";
    	preg_match($regex, $contenu, $exist, PREG_OFFSET_CAPTURE);

    	$count = count($exist);
    	if($count > 0) {

    		$this->addFlash(
                    'warning',
                    "Ce mot n'existe pas"
                );
    		return $this->redirectToRoute('home');
    	}

    	//Recuperer la rechercher
    	preg_match("/e;[0-9]*;'(?P<nom>.*)';[0-9]*;[0-9]*(;'(?P<cnom>.*)')*/", utf8_encode($contenu), $name, PREG_OFFSET_CAPTURE);
    	$search = array_key_exists('cnom', $name) ? $name['cnom'][0] : $name['nom'][0];


    	//ID
		$regex = "/eid=(?P<id>[0-9]*)/";
		preg_match($regex, $contenu, $id, PREG_OFFSET_CAPTURE);

		$id = $id['id'][0];

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
			'1' => 'Raffinement sémantique',
			'2' => 'Raffinement morphologique',
			'3' => 'Domaine',
			'4' => 'r_pos',
			'5' => 'Synonymes',
			'6' => 'Generiques',
			'7' => 'Contraire',
			'8' => 'Spécifiques',
			'9' => "Parties de $search",
			'10' => "$search fait partie de",
			'11' =>'Locutions',
			'13' =>"Que peut faire $search ? (agent)",
			'14' =>"$search comme objet (patient)",
			'15' =>"Lieu ou peut se trouver $search (chose>lieu)",
			'16' =>'action>Instrument',
			'17' =>'Caractéristiques',
			'18' =>'iL',
			'19' =>'Lemme',
			'20' =>"Plus intense que $search",
			'21' =>"Moins intense que $search",
			'22' =>'Termes étymologiquement apparentés',
			'23' =>'Caractéristiques-1',
			'24' =>"Que peut faire $search",
			'25' =>"Que peut-on faire avec $search",
			'26' =>"Que peut-on faire de/a $search (patient)",
			'27' =>"Domaine-1",
			'28' =>"Lieux où peut se trouver/dérouler $search",
			'30' =>"Lieu>Action",
			'31' =>"Action>Lieu",
			'32' =>'Sentiments',
			'34' => 'Manière',
			'35' => 'glose/sens/signification',
			'37' =>"Rôles teliques $search",
			'38' =>"Rôles agentifs $search",
			'39' => 'verbe/action',
			'40' => 'action/verbe',
			'41' => 'conséquence',
			'42' => 'cause',
			'43' => 'adj>verb',
			'44' => 'verb>adj',
			'49' => 'action>temps',
			'50' =>'objet>matière',
			'51' => 'matière>objet',
			'52' => 'successeur',
			'53' => 'produit',
			'54' => "$search est le produit de",
			'55' => "s'oppose à",
			'56' => "a comme opposition",
			'57' => "implication",
			'58' => "quantificateur",
			'59' => "équivalent masc",
			'60' => "équivalent fem",
			'61' => "équivalent",
			'62' => "maniere-1",
			'63' => "implication agentive",
			'64' => "$search a pour instance",
			'65' => "verbe>real",
			'67' => "similaire",
			'68' => "ensemble>item",
			'69' => "item>ensemble",
			'70' => "processus>agent",
			'71' => "variante",
			'72' => "r_syn_strict",
			'73' => "$search est plus petit que",
			'74' => "$search est plus gros que",
			'75' => "Accompagne",
			'76' => "processus>patient",
			'77' => "verbe participe passé",
			'78' => "co-hyponyme",
			'79' => "verbe participe présent",
			'80' => "processus>instrument",
			'99' => "dérivation morphologique",
			'100' =>"$search a comme auteur",
			'101' =>"$search a comme personnages",
			'102' =>"$search se nourrit de",
			'103' =>"$search a comme acteurs",
			'104' =>"mode de déplacement",
			'105' => "$search a comme interprètes",
			'106' =>'Couleurs',
			'107' => "$search a comme cible",
			'108' => "$search a comme symptomes",
			'109' => "prédécesseur dans le temps",
			'110' => "diagnostique",
			'111' => "prédécesseur dans l'espace",
			'112' => "successeur",
			'113' => "relation sociale/famille",
			'114' => "Tributaire de",
			'115' => "sentiment-1",
			'116' => "A quoi est-ce relié ?",
			'117' => "r_foncteur",
			'119' => "But",
			'120' => "But-1",
			'121' =>'pers>possession',
			'122' =>'possession>pers',
			'123' =>'Auxiliaire utilisé pour ce verbe',
			'124' =>'prédécesseur logique',
			'125' =>'successeur logique',
			'126' =>"Relation d'incompatibilité (générique)",
			'127' =>"Relation d'incompatibilité",
			'129' =>'nécessite / requiert',
			'130' =>"$search est une instance de",
			'131' => "$search est concerné par",
			'132' => "$search est un symptome de",
			'133' => "$search a pour unités",
			'134' => "favorise",
			'135' => "circumstances",
			'136' => "$search est l'auteur de",
			'149' => "complément d'agent",
			'150' => "action>bénéficiaire",
			'151' => "$search descend de",
			'152' => "domaines de substitution",
			'153' => "propriété",
			'154' => "voix active",
			'155' =>"Peut utiliser un objet ou produit par $search",
			'156' =>"Par qui/quoi $search peut-être utilisé",
			'157' => "adj>nomprop",
			'158' => "nomprop>adj",
			'159' => "adj>adv",
			'160' => "adv>adj",
			'161' => "homophone",
			'162' => "confusion potentielle",
			'163' => "concernant",
			'164' => "adj>nom",
			'165' => "nom>adj",
			'166' => "Opinion",
			'333' => 'Translation',
			'666' =>'Totaki',
			'777' =>'Wikipedia',
			'999' =>'Inhib'
		];

		$forArray = array();

		foreach ($forlist as $key => $value) {

			$regex = "/r;[0-9]*;$id;([0-9]*);$key;[0-9]*/";

			$out = $this->createArrayID($regex, $contenu);

			$regex = "/r;[0-9]*;([0-9]*);$id;$key;[0-9]*/";

			$in = $this->createArrayID($regex, $contenu);

			if(count($out) != 0 || count($in) != 0) {
				$forArray ["$value"]["out"] = $out;
				$forArray ["$value"]["in"] = $in;
			} else {
				unset($forlist[$key]);
			}
		}

		foreach ($forArray as $key => $array) {
			foreach ($array as $inout => $value) {
				$forArray[$key][$inout] = $this->relation($value, $arrayBDD);
			}
		}

		$pos = null;
		if(array_key_exists('r_pos', $forArray)) {
			//POS
			$pos = $forArray['r_pos']['out'];
			unset($forArray['r_pos']);
			unset($forlist['4']);
		}

		$lemme = null;
		if(array_key_exists('Lemme', $forArray)) {
			//lemme
			$lemme = $forArray['Lemme']['out'];
			unset($forArray['Lemme']);
			unset($forlist['19']);
		}

		$iL = null;
		if(array_key_exists('iL', $forArray)) {
			//IL
			$iL = $forArray['iL']['out'];
			unset($forArray['iL']);
			unset($forlist['18']);
		}

		for ($i=0; $i < count($forlist); $i+=4) {
			$listCheckBox [] = array_slice($forlist, $i, 4);
		}

    	return $this->render('home/search.html.twig',
    		['q' => $search,
    		 'defs' => $defs,
    		 'assos' => $forArray,
    		 'pos' => $pos,
    		 'lemme' => $lemme,
    		 'iL' => $iL,
    		 'listCheckBox' => $listCheckBox,
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

	public function get_http_response_code($url) {
    	$headers = get_headers($url);
    	return substr($headers[0], 9, 3);
	}

}

