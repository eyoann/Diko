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
    	//SEARCH SERVER JDM
    	$contenu = file_get_contents("http://www.jeuxdemots.org/rezo-dump.php?gotermsubmit=Chercher&gotermrel=$search&rel=");

    	//RECUPERER LE DUMP CODE
    	preg_match("/<CODE>(.*)<\/CODE>/s", $contenu, $matches, PREG_OFFSET_CAPTURE);

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
				//print_r($match);

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

		//ID
		$regex = "/eid=([0-9]*)/";
		preg_match($regex, $contenu, $id, PREG_OFFSET_CAPTURE);
		$id = $id[1][0];


		$forlist = [
			'0' => 'Idée associée',
			'4' => 'r_pos',
			'5' => 'Synonyme',
			'6' => 'r_isa',
			'8' => 'r_hypo',
			'9' => 'r_has_part',
			'10' => 'r_holo',
			'11' =>'r_locution',
			'12' =>'r_flpot',
			'13' =>'r_agent',
			'14' =>'r_patient',
			'15' =>'r_lieu',
			'16' =>'r_instr',
			'17' =>'r_carac',
			'18' =>'r_data',
			'19' =>'r_lemma',
			'20' =>'r_has_magn',
			'21' =>'r_has_antimagn',
			'22' =>'r_family',
			'23' =>'r_carac-1',
			'24' =>'r_agent-1',
			'25' =>'r_instr-1',
			'26' =>'r_patient-1',
			'28' =>'r_lieu-1',
			'32' =>'r_sentiment',
			'35' =>'r_meaning/glose',
			'36' =>'r_infopot',
			'37' =>'r_telic_role',
			'38' =>'r_agentif_role',
			'46' =>'r_chunk_objet',
			'48' =>'r_chunk_instr',
			'50' =>'r_object&gt;mater',
			'66' =>'r_chunk_head',
			'106' =>'r_color',
			'115' =>'r_sentiment',
			'121' =>'r_own',
			'128' =>'r_node2relnode',
			'155' =>'r_make_use_of',
			'156' =>'r_is_used_by',
			'200' =>'r_context',
			'444' =>'r_link',
			'555' =>'r_cooccurrence',
			'666' =>'r_aki',
			'777' =>'r_wiki',
			'999' =>'r_inhib'
		];

		$forArray = array();
		$list = array();

		foreach ($forlist as $key => $value) {

			$regex = "/r;[0-9]*;$id;([0-9]*);$key;[0-9]*/";

			$out = $this->createArrayID($regex, $contenu);

			$list = array_merge($list, $out);

			$forArray ["$value-out"] = $out;

			$regex = "/r;[0-9]*;([0-9]*);$id;$key;[0-9]*/";

			$in = $this->createArrayID($regex, $contenu);

			$list = array_merge($list, $in);

			$forArray ["$value-in"] = $in;
		}


		$result = $dico->find(array('id' => array('$in' => $list)));

		foreach ($result as $entry) {
			$mots[$entry['id']] = $entry['terme'];

		}

		unset($result);

		foreach ($forArray as $key => $value) {
			$forArray[$key] = $this->relation($value, $mots);
		}

    	return $this->render('home/search.html.twig',
    		['q' => $search,
    		 'defs' => $defs,
    		 'assos' => $forArray
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
				$relationOut[$value] = $mots[$value];
			}
		}

		return $relationOut;
	}
}

