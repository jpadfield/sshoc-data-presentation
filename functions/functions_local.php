<?php

##Default port settings, these may need to be reset for specific projects
$ep = "https://rdf.ng-london.org.uk/bg/";
$spep = "https://rdf.ng-london.org.uk/bg/bigdata/sparql";
$nsp = $ep."/namespace/sshoc-raphael";
$spep = $nsp."/sparql";
$graphTitle = array (
  "default" => false,
  "raphael" => "Raphael Research Resource",
  "grounds" => "IPERION-CH Grounds Database",
  "combined" => "Combined Dataset: Raphael + IPERION-CH Grounds Data");
 	
$onts = array ( 
    "rdfs"     => "http://www.w3.org/2000/01/rdf-schema#",  
    "owl"     => "http://www.w3.org/2002/07/owl#", 
    "rdf"     => "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
    "crm"      => "http://www.cidoc-crm.org/cidoc-crm/", 
    "aat"      => "http://vocab.getty.edu/page/aat/",
    "wd"      => "http://www.wikidata.org/entity/",
    "ng"      => "https://data.ng-london.org.uk/",
    "dig"      => "http://www.cidoc-crm.org/crmdig/",
    "sci"      => "http://www.cidoc-crm.org/crmsci/"
    );  
    
$searchPrefix = "";    
foreach ($onts as $key => $value) 
  {$searchPrefix .= "PREFIX ".$key.":<".$value.">\n";} 

// Not currently used
function getEndpoint ($name)
  {
  global $ep;
  
  $lep = $ep."namespace/$name/sparql";
  
  $res = selectQuery ("SELECT DISTINCT (count(?entity) AS ?Entities) WHERE{ ?entity ?p ?o}");
  prg(0, $res);
  $res2 = selectQuery ("SELECT (COUNT(*) as ?Triples) WHERE { { ?s ?p ?o } }");
  prg(1, $res2);
  
  $endpoints = array (
    "endpoint" => $ep."namespace/$name/sparql",
    "triples" => false,
    "entites" => false,
  );
  
  }
  
function selectQuery ($query="", $decode=true, $ep=false)
	{
	global $spep;
  
  if (!$ep) {$ep = $spep;}
  
	$API_ENDPOINT = $ep."?format=json&query=%s";
	$url = sprintf($API_ENDPOINT, urlencode($query));
	$ch = curl_init();

	$options = array(
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_ENCODING       => "",
    CURLOPT_CONNECTTIMEOUT => 120,
    CURLOPT_TIMEOUT        => 120,
    CURLOPT_MAXREDIRS      => 10,
		);
	curl_setopt_array( $ch, $options );
	$response = curl_exec($ch); 
	if($decode) {$response = json_decode($response, true);}

	curl_close($ch);

	if(isset($response["results"]["bindings"]))
		{return($response["results"]["bindings"]);}
	else {return($response);}
	}


function prg($exit=false, $alt=false, $noecho=false)
	{
	if ($alt === false) {$out = $GLOBALS;}
	else {$out = $alt;}
	
	ob_start();
  
  if (php_sapi_name() === 'cli')
    {echo "\n";}
  else
    {echo "<pre class=\"wrap\">";}
    
	if (is_object($out))
		{var_dump($out);}
	else
		{print_r ($out);}

  if (php_sapi_name() === 'cli') 
    {echo "\n";}
  else
    {echo "</pre>";}
    
	$out = ob_get_contents();
	ob_end_clean(); // Don't send output to client
  
	if (!$noecho) {echo $out;}
		
	if ($exit) {exit;}
	else {return ($out);}
	}  
	

// Used to convert a SPARQL query and the results into an extended set 
// of triples which can be modelled in the Dynamic Modeller:
// https://research.ng-london.org.uk/modelling
// 
function SPARQLToTriples ($q)
  {
  global $console;
  
  
  // remove any comments from the query
  $q = preg_replace("/[#][^>^\r^\n]+[\r\n]/", " ", $q);
        
  $q = preg_replace("/\r|\n/", " ", $q);
  $q = preg_replace("/\s\s+/", " ", $q);

  $template = array();
  $triples = array();
  
  if (preg_match ( "/(.+)(SELECT[ DISTNC]*)(.+)(WHERE[^{]*{)(.+)(}[^}]*)$/", $q, $sq))
    {                
    preg_match_all('/aat[:]([0-9]+)[\s]*([.;])/', $sq[5], $aat, PREG_OFFSET_CAPTURE);
    
    foreach ($aat[1] as $ak => $av)
      {$sq[5] .= "\n  OPTIONAL {aat:$av[0] rdfs:label ?$av[0]_label .}";}
      
    $nq = "$sq[1] $sq[2] * $sq[4] $sq[5] $sq[6]";

    $r = selectQuery ($nq);

    $ltmp = preg_replace("/[\s]*[.]/", "..", trim($sq[5]));
    $ltmp = preg_replace("/OPTIONAL/", "", $ltmp);
    $ltmp = preg_replace("/[{}]/", "", $ltmp);
    $lines = explode("..", $ltmp);
    
    $qvars = array();
        
    foreach ($lines as $k => $l)
      { 
      if (preg_match_all("/[?][a-zA-Z0-9_]+/", $l, $m))
        {
        foreach ($m[0] as $mk => $mv)
          {$mv = preg_replace("/\?/", "", $mv);
           $qvars[$mv] = 1;}        
        }
        
      $l = preg_replace("/\?/", "", $l);
      $l2 = explode (";", trim($l));

      $s = "";
      foreach ($l2 as $k2 => $l3)
        {
        $l3 = trim($l3);
        $l3 = preg_replace("/[\s]+/", " ", $l3);
        $al3 = explode(" ", $l3);
        
        $ual3 = array();
        $c = "";
        $cno = 0;
        foreach ($al3 as $k3 => $v)
          {
          if (preg_match ( "/^[\"].+$/", $v, $m))
            {$c = $v;}
          else if (preg_match ( "/^.+[\"].+$/", $v, $m))
            {$c .= " ".$v;
             $ual3[$cno] = $c;
             $c = "";
             $cno++;}
          else if ($c)
            {$c .= " ".$v;}
          else
            {$ual3[$cno] = $v;
             $cno++;}
          }
         
        if (count($ual3) == 3)
          {$s = $ual3[0];}
        else
          {array_unshift($ual3, $s);}
          
        if ($ual3[0])
          {$template[] = $ual3;}
        }        
      }
     
    // Remove the <> around URLs in the query
    foreach ($template as $tn => $t)
      {foreach ($t as $tn2 => $v)
        {$template[$tn][$tn2] = trim (trim ($v), "<>");}}

    foreach ($r as $rn => $a)
      {
      foreach ($template as $tn => $t)
        {
        $ct = $t;       
        $check = array(false, false, false);
        foreach ($t as $tn2 => $v)
          {            
          if(isset($a[$v]))
            {
            $check[$tn2] = true;
            if ($a[$v]["type"] == "uri")
              {$ct[$tn2] = $a[$v]["value"];}
            else if ($a[$v]["type"] == "bnode")
              {$ct[$tn2] = $a[$v]["value"];}
            else if ($a[$v]["type"] == "literal")
              {if (isset($a[$v]["xml:lang"])) {$suf = '@'.$a[$v]["xml:lang"];}
               else {$suf = "";}
               $ev = strip_tags($a[$v]["value"]);
               $ct[$tn2] = '"'.$ev.'"'.$suf;}
            else
              {
               // to catch errors
               prg(0, $a);
               prg(0, $v);
               prg(0, $a[$v]["type"]);
               prg(1, $t);}
            }            
          }
        
        // ignore triplets for optional variables in the template 
        // which did not return a value      
        if (!$check[0] and isset($qvars[$ct[0]]))
          {$ct = false;}
        else if (!$check[2] and isset($qvars[$ct[2]]))
          {$ct = false;}
        
        if ($ct)
          {
          $ct = checkPrefixes ($ct);
          $ct[2] = preg_replace("/\r|\n/", "<br/>", $ct[2]);
          //$ct[2] = json_encode ($ct[2]);
          $tl = implode("\t", $ct);
                  
          if (!in_array($tl, $triples))
            {$triples[] = $tl;}
          }
        }
      }
    }

  $out = implode("\n", $triples);    
  return ($out);
  }
  
function checkPrefixes ($triple)
  {
  global $onts;
  
  $new = array_keys ($onts);
  array_walk($new, function(&$value, $key) { $value = $value.':'; } );
  $triple[0] = str_replace($onts, $new, $triple[0]);
  $triple[1] = str_replace($onts, $new, $triple[1]);
  $triple[2] = str_replace($onts, $new, $triple[2]);
  
  return ($triple);
  }
  
////////////////////////////////////////////////////////////////////////
// function group: Specific page building functions
////////////////////////////////////////////////////////////////////////


function exampleResults ($data)
  {    
  if (!isset($data["type"])) 
		{$data["type"] = false;}
		
  $data["query"] = trim($data["query"]);  
  $data["encodedQuery"] = urlencode(base64_encode(gzcompress($data["query"])));

  $data["page_link"] = './?query='.$data["encodedQuery"];
  $data["json_link"] = './?query='.$data["encodedQuery"].'&format=json';
  
  $r = selectQuery ($data["query"], true); 

  $data["raw"] = $r;
  $data["json"] = json_encode ($r, JSON_UNESCAPED_SLASHES);
  $data["triples"] = SPARQLToTriples ($data["query"]);
  $data["encodedTriples"] = urlencode(base64_encode(gzcompress($data["triples"])));
    
  $data["model_link"] = 'https://research.ng-london.org.uk/modelling/?data='.$data["encodedTriples"];

  $t1 = "SPARQL";
  
  ob_start();
  echo <<<END
  <div class="container-fluid p-0">
  <div class="row m-0 px-2 w-100">
    <div class="col h5 p-0">
      Query Results
    </div>
    <div class="col h5 p-0 text-end">
      <a class="ms-2" title="View graphical model of the results" href="$data[model_link]" target="_blank"><i class="bi bi-eye"></i></a>
      <a class="ms-2" title="Shareable direct link to this page" href="$data[page_link]" target="_blank"><i class="bi bi-share"></i></a>
      <a class="ms-2" title="View results formatted in JSON" href="$data[json_link]" target="_blank"><i class="bi bi-filetype-json"></i></a>
    </div>
  </div>
</div>
END;
  $t2 = ob_get_contents();
  ob_end_clean(); // Don't send output to client
  
  $qf = queryForm ($data["query"], "./");    
  $dq = htmlspecialchars($data["query"]);
  
    
  if (!$data["display"]) 
    {$data["display"] = "Custom query example";}  
  
  ob_start();
  echo <<<END
  <div class="row justify-content-center flex-grow-1 position-relative">  
    
    <div class="col-md-6 col-12" style="padding:0px 2px 0px 0px;">               
      <div class="h-100 d-flex flex-column">
	
	<div id="qheader" class="flex-column justify-content-center px-2 bg-light">    
	  <div class="p-0" style=""><p class="h5 mt-2" style="">$t1: <span class="text-primary">$data[display]</span></p></div>
	</div>
	<div class="flex-column justify-content-center flex-grow-1" style="min-height:300px;">                  
	  <div id="rheight" style="height:100px;">
	    $qf
	  </div>                   
	</div>
	<div class="flex-column justify-content-center px-2">
	  <div class="text-white p-0" style="height:50px">	    
      <button type="button" class="btn btn-primary float-end" style="margin-left:10px;" title="Execute SPARQL Query" onClick="submitQueryForm()">
        <i class="bi bi-arrow-right-circle"></i> Execute
      </button>
      <button type="button" class="btn btn-secondary float-end" style="margin-left:10px;" title="Reset SPARQL Query" onClick="resetQueryForm()">
        <i class="bi bi-arrow-repeat"></i> Reset
      </button> 
	  </div>
    
	</div>	             
	
      </div>        
    </div>
        
    <div class="col-md-6 col-12" style="padding:0px 0px 0px 2px;">
      <div class="h-100 d-flex flex-column">
	
	<div id="rheader"  class="flex-column justify-content-center px-2 bg-light">    
	  <div class="p-0 mt-2" style="">$t2</div>
	</div>
        <div class="flex-column justify-content-center flex-grow-1 overflow-hidden" style="min-height:300px;">
	  <pre id="qresults" class="json-renderer overflow-auto" style="padding:0px;margin:0px;border: 1px solid black;
    margin-top: 5px;min-height:290px;">$data[json]</pre>
	</div>
        <div class="flex-column justify-content-center px-2">
	  <div class="text-white p-0" style="height:50px"></div>
	</div>
	        
      </div>        
       
    </div>            
  
  </div>       
   
END;
  $data["html"] = ob_get_contents();
  ob_end_clean(); // Don't send output to client
    

  return($data);    
  }
  

  
 function queryForm ($query, $action="../sparql/")
  {
  ob_start();
  echo <<<END
  
	  <form id="spform" action="$action" method="post">  
	    <textarea id="qform" name="query" style="width:100%;margin:5px 5px 0px 0px;height:80px;min-height:290px;" cols="10">
$query 
	    </textarea>
	    <input type="hidden" id="soft-limit" name="soft-limit" />
      <input type="hidden" id="query-return" name="query-return" value="gui" />
	  </form> 
  
END;
    $html = ob_get_contents();
    ob_end_clean(); // Don't send output to client
    
  return ($html);
  }

function examplesGet ()
  {  
  $examples = array(  
    "raphael" => array(
      "custom" => array ( // UPDATED
        "type" => "custom",
        "name" => "custom", 
        "display" => "Custom query example", 
        "query" => $GLOBALS["searchPrefix"].'  
# Custom query example: Edit this basic SPARQL query to  create custom queries. 

SELECT DISTINCT * WHERE {
  ?sub ?pre ?obj .
} LIMIT 10 '),
      "ng-paintings" => array ( // UPDATED
        "type" => "simple",
        "name" => "ng-paintings", 
        "display" => "Find National Gallery paintings", 
        "query" => $GLOBALS["searchPrefix"].'        
# Find National Gallery paintings

SELECT DISTINCT ?pid ?title ?titletype WHERE {
  ?pid crm:P46i_forms_part_of ?y ;
    crm:P102_has_title ?z .
  ?z rdfs:label ?title ;
    crm:P2_has_type aat:300417208 .
  aat:300417208 rdfs:label ?titletype .
  ?y rdfs:label "The National Gallery Collection"@en .
}'
        ),
      "painting-thumbnail" => array (
        "type" => "simple",
        "name" => "painting-thumbnail", 
        "display" => "Finding painting thumbnail", 
        "query" => $GLOBALS["searchPrefix"].'          
# Finding painting thumbnail

SELECT ?painting ?invno ?thumb ?imagetype ?paintingtype WHERE {
  ?painting crm:P48_has_preferred_identifier ?o ;
    crm:P2_has_type aat:300033618 .
  aat:300033618 rdfs:label ?paintingtype .
  ?o rdfs:label ?invno .
  ?vim crm:P138_represents ?painting .
  ?tim crm:P65_shows_visual_item ?vim .
  ?tim crm:P2_has_type wd:Q873806 .
  wd:Q873806 rdfs:label ?tl .
  ?tim crm:P48_has_preferred_identifier ?tn .
  ?tn rdf:value ?thumb .
  
  ?im crm:P65_shows_visual_item ?vim .
  ?im crm:P2_has_type aat:300404411 . 
  aat:300404411 rdfs:label ?imagetype .
FILTER ( ?painting = ng:0FJS-0001-0000-0000 ) .
  
} '
        ),
      "related-category" => array (
        "type" => "simple",
        "name" => "related-category", 
        "display" => "Find related things by category", 
        "query" => $GLOBALS["searchPrefix"].'          
# Find related things by category 
# To find digital texts grouped by category the ?grouptype needs 
# to be changed to aat:300424602 - digital document         
          
SELECT ?painting ?invno ?groupname ?thing ?category ?grouptype
WHERE {
  ?painting crm:P48_has_preferred_identifier ?o .
  ?o rdfs:label ?invno .
  ?g crm:P62_depicts ?painting ;
    crm:P2_has_type	aat:300025976 ;
    crm:P2_has_type	aat:300053463 ;
    rdfs:label ?groupname ;
    crm:P106_is_composed_of ?thing .
  aat:300053463 rdfs:label ?category .
  ?thing crm:P2_has_type ?gt .
  ?gt rdfs:label ?grouptype .
  FILTER ( ?painting = ng:0FJS-0001-0000-0000 ) .
  FILTER ( ?gt = aat:300215302 ).
  
} LIMIT 5  '
        ),
      "painting-x-rays" => array (
        "type" => "simple",
        "name" => "painting-x-rays", 
        "display" => "Find x-ray images", 
        "query" => $GLOBALS["searchPrefix"].'
# Find x-ray images

SELECT ?v ?im ?thumb ?l WHERE {
  ?painting crm:P48_has_preferred_identifier ?o .
  ?o rdfs:label ?v .
  ?vim crm:P138_represents   ?painting .
  ?tim crm:P65_shows_visual_item ?vim .
  ?tim crm:P2_has_type wd:Q873806 .
  wd:Q873806 rdfs:label ?tl .
  ?tim crm:P48_has_preferred_identifier ?tn .
  ?tn rdf:value ?thumb .
  
  ?im crm:P65_shows_visual_item ?vim .
  ?im crm:P2_has_type dig:D9_Data_Object .  
  ?im crm:P108i_was_produced_by ?e .
  ?e crm:P32_used_general_technique aat:300419323 .  
  aat:300419323 rdfs:label ?l .
  
} LIMIT 10'
        ),
      "artist-details" => array (
        "type" => "simple",
        "name" => "artist-details", 
        "display" => "Find artist details", 
        "query" => $GLOBALS["searchPrefix"].'
# Find artist details

SELECT ?artist ?name ?birth_year ?death_year WHERE {
  ?artist rdfs:label ?name ;
      crm:P100i_died_in ?de ;
      crm:P98i_was_born ?be .
?be crm:P4_has_time_span ?bts .
    ?bts rdfs:label ?birth_year .
?de crm:P4_has_time_span ?dts .
    ?dts rdfs:label ?death_year .
  FILTER ( ?name = "Raphael"@en ) .
} '
        ),
      "painting-details" => array (
        "type" => "complex",
        "name" => "painting-details", 
        "display" => "Find painting details", 
        "query" => $GLOBALS["searchPrefix"].'
# Find full painting details

SELECT 
  ?painting ?invno ?thumb ?location 
  ?collection ?shortTitle ?title ?width
  ?height ?support ?medium ?artist ?date ?curator
WHERE {
  ?painting crm:P48_has_preferred_identifier ?o ;
    crm:P53_has_former_or_current_location ?l ;
    crm:P46i_forms_part_of ?c ; 
    crm:P102_has_title ?st ;
    crm:P43_has_dimension ?wd ;
    crm:P43_has_dimension ?hd ;
    crm:P46_is_composed_of ?ma ;
    crm:P46_is_composed_of ?mb ;
    crm:P102_has_title ?t  ;
    crm:P108i_was_produced_by ?pe;    
    crm:P109_has_current_or_former_curator ?cu .
    
  ?painting crm:P2_has_type ?ptt .
  OPTIONAL {?ptt rdfs:label ?pttl . }.
  
  ?cu rdfs:label ?curator .

  ?pe crm:P2_has_type  crm:E12_Production  ;
     crm:P14_carried_out_by ?pa ;
     crm:P4_has_time_span ?ts .
  ?pa rdfs:label ?artist .
  ?ts rdfs:label ?date .
   
  ?ma crm:P2_has_type aat:300014844 ;
    crm:P45_consists_of ?sm .  
  ?sm crm:P2_has_type ?smt .
  ?smt rdfs:label ?support .
  
  ?mb crm:P2_has_type aat:300163343 ;
    crm:P45_consists_of ?mm .  
  ?mm crm:P2_has_type ?mmt .
  ?mmt rdfs:label ?medium .
  
  ?wd crm:P90_has_value ?width .
  ?wd crm:P2_has_type aat:300055647  .
  
  ?hd crm:P90_has_value ?height .
  ?hd crm:P2_has_type aat:300055644  .
  
  ?st crm:P2_has_type aat:300417208 ;
    rdfs:label ?shortTitle .
  
  ?t crm:P2_has_type aat:300417209 ;
    rdfs:label ?title .
  
  ?c rdfs:label ?collection .
  ?c crm:P2_has_type ?colt .
  OPTIONAL {?colt rdfs:label ?coltl . }.
  
  ?l  rdfs:label ?location .
  ?l crm:P2_has_type ?lt .
  OPTIONAL {?lt rdfs:label ?ltl . }.
  
  ?o rdfs:label ?invno .
  
  ?vim crm:P138_represents ng:0FJS-0001-0000-0000 .
  ?tim crm:P65_shows_visual_item ?vim .
  ?tim crm:P2_has_type wd:Q873806 .
  wd:Q873806 rdfs:label ?tl .
  ?tim crm:P48_has_preferred_identifier ?tn .
  ?tn rdf:value ?thumb .
  
  ?im crm:P65_shows_visual_item ?vim .
  ?im crm:P2_has_type aat:300404411 . 
  aat:300404411 rdfs:label ?imagetype .
FILTER ( ?painting = ng:0FJS-0001-0000-0000 ) .
  
}  '
        ),
      "image-details" => array (
        "type" => "complex",
        "name" => "image-details", 
        "display" => "Find image details", 
        "query" => $GLOBALS["searchPrefix"].'
# Find full image details
SELECT 
?image ?filename ?height ?width 
?levels ?pyramid ?iiifinfo ?caption
WHERE {
  ?image crm:P2_has_type aat:300215302 ;
      crm:P149_is_identified_by ?bn  ;
      dig:L56_has_pixel_width  ?width ;
      dig:L57_has_pixel_height  ?height .
  OPTIONAL { ?image  crm:P70_is_documented_in ?cbn . 
    OPTIONAL {  ?cbn rdfs:label ?caption }
    }

  ?bn rdfs:label ?filename  .  

  ?e dig:L21_used_as_derivation_source ?image .
  ?e dig:L22_created_derivative ?pim .
  ?pim crm:P2_has_type wd:Q3411251 .
  ?pim crm:P43_has_dimension ?bnd .
  ?bnd crm:P90_has_value ?levels .
  ?pim crm:P149_is_identified_by ?pn .
  ?pn rdfs:label ?pyramid .
  ?pim crm:P70i_is_documented_in ?iiifinfo .

  FILTER ( ?filename = "N-6596-00-000020.tif"@en  ) .
} '
          ),
       "text-details" => array (
          "type" => "complex",
          "name" => "text-details", 
          "display" => "Find details for a given digital text", 
          "query" => $GLOBALS["searchPrefix"].'
# Find details for a given digital text

SELECT DISTINCT ?text ?title ?label ?content ?note ?order ?thumb ?caption  WHERE {
  ?text rdfs:label ?label ;
    crm:P43_has_dimension ?dn;
    crm:P102_has_title ?tn .
  ?tn rdfs:label ?title .
  ?dn crm:P90_has_value ?order .   
OPTIONAL { 
    ?text  rdf:value ?content  . 
    FILTER(LANG(?content) = "en") .} .
OPTIONAL {  ?text crm:P3_has_note ?note . }
OPTIONAL {
  ?text crm:P67_refers_to ?imn . 
  ?imn crm:P2_has_type aat:300215302 .
  ?imn crm:P70_is_documented_in ?cd .
  ?cd crm:P2_has_type crm:E31_Document .
  ?cd rdfs:label ?caption.  
  ?imn crm:P65_shows_visual_item ?vim .
  ?tim crm:P65_shows_visual_item ?vim .
  ?tim crm:P2_has_type wd:Q873806 .
  ?tim crm:P48_has_preferred_identifier ?tbn .
  ?tbn rdf:value ?thumb . }

  FILTER ( ?label = "Hofmann_Provenance_2008_NG1171_Part_II_0008"@en ) . 
} '
          ),
          
          
       "text-details-2" => array (
          "type" => "complex",
          "name" => "text-details-2", 
          "display" => "Find further details for a given digital text", 
          "query" => $GLOBALS["searchPrefix"].'
# Find further details for a given digital text - including "child" texts

SELECT DISTINCT 
  ?text ?title ?label ?content ?note ?order ?thumb ?caption 
    ?child  ?child_title ?child_label ?child_order  
    ?child_content ?child_note ?child_thumb ?child_caption 
WHERE {
  ?text rdfs:label ?label ;
    crm:P43_has_dimension ?dn;
    crm:P102_has_title ?tn .
  ?tn rdfs:label ?title .
  ?dn crm:P90_has_value ?order .   
OPTIONAL { 
    ?text  rdf:value ?content  . 
    FILTER(LANG(?content) = "en") .} .
OPTIONAL {  ?text crm:P3_has_note ?note . }
OPTIONAL {
  ?text crm:P67_refers_to ?imn . 
  ?imn crm:P2_has_type aat:300215302 .
  ?imn crm:P70_is_documented_in ?cd .
  ?cd crm:P2_has_type crm:E31_Document .
  ?cd rdfs:label ?caption.  
  ?imn crm:P65_shows_visual_item ?vim .
  ?tim crm:P65_shows_visual_item ?vim .
  ?tim crm:P2_has_type wd:Q873806 .
  ?tim crm:P48_has_preferred_identifier ?tbn .
  ?tbn rdf:value ?thumb . }
OPTIONAL { 
  ?text crm:P106_is_composed_of ?child .
  ?child rdfs:label ?child_label ;
    crm:P43_has_dimension ?chdn;
    crm:P102_has_title ?chtn .
  ?chtn rdfs:label ?child_title .
  ?chdn crm:P90_has_value ?child_order .   

OPTIONAL { 
    ?child  rdf:value ?child_content  . 
    FILTER(LANG(?child_content) = "en") .} .
  OPTIONAL {  ?child crm:P3_has_note ?child_note . }
  OPTIONAL {
    ?child crm:P67_refers_to ?cimn . 
    ?cimn crm:P2_has_type aat:300215302 .
    ?cimn crm:P70_is_documented_in ?ccd .
    ?ccd crm:P2_has_type crm:E31_Document .
    ?ccd rdfs:label ?child_caption.  
    ?cimn crm:P65_shows_visual_item ?cvim .
    ?ctim crm:P65_shows_visual_item ?cvim .
    ?ctim crm:P2_has_type wd:Q873806 .
    ?ctim crm:P48_has_preferred_identifier ?ctbn .
    ?ctbn rdf:value ?child_thumb . 
    }
}

FILTER ( ?label = "Hofmann_Provenance_2008_NG1171_NG6480_Part_I_0001"@en  ) . 
 
} LIMIT 20  '
          ),
          
      "painting-details-display" => array (
          "type" => "display",
          "name" => "painting-details-display", 
          "display" => "Find and display main details for a given painting", 
          "query" => $GLOBALS["searchPrefix"].'
# Find and display main details for a given painting. 

SELECT
  ?painting ?invno ?thumb ?location
  ?collection ?shortTitle ?title ?width
  ?height ?support ?medium ?artist ?date ?curator
  ?birth_year ?death_year
WHERE {
  ?painting crm:P48_has_preferred_identifier ?o ;
    crm:P53_has_former_or_current_location ?l ;
    crm:P46i_forms_part_of ?c ;
    crm:P102_has_title ?st ;
    crm:P43_has_dimension ?wd ;
    crm:P43_has_dimension ?hd ;
    crm:P46_is_composed_of ?ma ;
    crm:P46_is_composed_of ?mb ;
    crm:P102_has_title ?t  ;
    crm:P108i_was_produced_by ?pe;    
    crm:P109_has_current_or_former_curator ?cu .

  ?cu rdfs:label ?curator .

  ?pe crm:P2_has_type  crm:E12_Production  ;
     crm:P14_carried_out_by ?pa ;
     crm:P4_has_time_span ?ts .
  ?pa rdfs:label ?artist ;
     crm:P100i_died_in ?de ;
     crm:P98i_was_born ?be .
?be crm:P4_has_time_span ?bts .
    ?bts rdfs:label ?birth_year .
?de crm:P4_has_time_span ?dts .
    ?dts rdfs:label ?death_year .

  ?ts rdfs:label ?date .
   
  ?ma crm:P2_has_type aat:300014844 ;
    crm:P45_consists_of ?sm .  
  ?sm crm:P2_has_type ?smt .
  ?smt rdfs:label ?support .
 
  ?mb crm:P2_has_type aat:300163343 ;
    crm:P45_consists_of ?mm .  
  ?mm crm:P2_has_type ?mmt .
  ?mmt rdfs:label ?medium .
 
  ?wd crm:P90_has_value ?width .
  ?wd crm:P2_has_type aat:300055647  .
 
  ?hd crm:P90_has_value ?height .
  ?hd crm:P2_has_type aat:300055644  .
 
  ?st crm:P2_has_type aat:300417208 ;
    rdfs:label ?shortTitle .
 
  ?t crm:P2_has_type aat:300417209 ;
    rdfs:label ?title .
 
  ?c rdfs:label ?collection .
  ?l  rdfs:label ?location .
  ?o rdfs:label ?invno .
 
  ?vim crm:P138_represents ng:0FJS-0001-0000-0000 .
  ?tim crm:P65_shows_visual_item ?vim .
  ?tim crm:P2_has_type wd:Q873806 .
  wd:Q873806 rdfs:label ?tl .
  ?tim crm:P48_has_preferred_identifier ?tn .
  ?tn rdf:value ?thumb .
 
  ?im crm:P65_shows_visual_item ?vim .
  ?im crm:P2_has_type aat:300404411 .
  aat:300404411 rdfs:label ?imagetype .
FILTER ( ?painting = ng:0FJS-0001-0000-0000 ) .
 
}  '
          ),  
      "image-details-display" => array (
          "type" => "display",
          "name" => "image-details-display", 
          "display" => "Find and display a IIIF image", 
          "query" => $GLOBALS["searchPrefix"].'
# Find and display a IIIF image. 

SELECT 
?image ?filename ?height ?width 
?levels ?pyramid ?iiifinfo ?caption
WHERE {
  ?image crm:P2_has_type aat:300215302 ;
      crm:P149_is_identified_by ?bn  ;
      dig:L56_has_pixel_width  ?width ;
      dig:L57_has_pixel_height  ?height .
  OPTIONAL { ?image  crm:P70_is_documented_in ?cbn . 
    OPTIONAL {  ?cbn rdfs:label ?caption }
    }

  ?bn rdfs:label ?filename  .  

  ?e dig:L21_used_as_derivation_source ?image .
  ?e dig:L22_created_derivative ?pim .
  ?pim crm:P2_has_type wd:Q3411251 .
  ?pim crm:P43_has_dimension ?bnd .
  ?bnd crm:P90_has_value ?levels .
  ?pim crm:P149_is_identified_by ?pn .
  ?pn rdfs:label ?pyramid .
  ?pim crm:P70i_is_documented_in ?iiifinfo .

  FILTER ( ?filename = "N-6596-00-000020.tif"@en  ) .
}   '
          ),  
      "text-details-display" => array (
          "type" => "display",
          "name" => "text-details-display", 
          "display" => "Find and display the details for a given digital text", 
          "query" => $GLOBALS["searchPrefix"].'
# Find and display the details for a given digital text. 

SELECT DISTINCT 
    ?text ?title ?label 
    ?child ?child_title ?child_label ?child_order ?child_content ?child_note ?child_thumb ?child_caption
    ?childl2 ?childl2_title ?childl2_label ?childl2_order ?childl2_content ?childl2_note ?childl2_thumb ?childl2_caption
WHERE {
  ?text rdfs:label ?label ;    
    crm:P102_has_title ?tn  ;
    crm:P67_refers_to ?painting .
  ?tn rdfs:label ?title . 
OPTIONAL { 
  ?text crm:P106_is_composed_of ?child .
  ?child rdfs:label ?child_label ;
    crm:P43_has_dimension ?chdn;
    crm:P102_has_title ?chtn .
  ?chtn rdfs:label ?child_title .
  ?chdn crm:P90_has_value ?child_order .   
  OPTIONAL { ?child  rdf:value ?child_content . }   
  OPTIONAL { ?child crm:P3_has_note ?child_note . }
  OPTIONAL {
    ?child crm:P67_refers_to ?cimn . 
    ?cimn crm:P2_has_type aat:300215302 .
    ?cimn crm:P70_is_documented_in ?ccd .
    ?ccd crm:P2_has_type crm:E31_Document .
    ?ccd rdfs:label ?child_caption.  
    ?cimn crm:P65_shows_visual_item ?cvim .
    ?ctim crm:P65_shows_visual_item ?cvim .
    ?ctim crm:P2_has_type wd:Q873806 .
    ?ctim crm:P48_has_preferred_identifier ?ctbn .
    ?ctbn rdf:value ?child_thumb . 
    }
  OPTIONAL { 
    ?child crm:P106_is_composed_of ?childl2 .
    ?childl2 rdfs:label ?childl2_label ;
      crm:P43_has_dimension ?chl2dn;
      crm:P102_has_title ?chl2tn .
    ?chl2tn rdfs:label ?childl2_title .
    ?chl2dn crm:P90_has_value ?childl2_order .   
    OPTIONAL { 
        ?childl2  rdf:value ?childl2_content  . 
        #FILTER(LANG(?child_content) = "en") .
        } .
      OPTIONAL {  ?childl2 crm:P3_has_note ?childl2_note . }
      OPTIONAL {
        ?childl2 crm:P67_refers_to ?cl2imn . 
        ?cl2imn crm:P2_has_type aat:300215302 .
        ?cl2imn crm:P70_is_documented_in ?cl2cd .
        ?cl2cd crm:P2_has_type crm:E31_Document .
        ?cl2cd rdfs:label ?childl2_caption.  
        ?cl2imn crm:P65_shows_visual_item ?cl2vim .
        ?cl2tim crm:P65_shows_visual_item ?cl2vim .
        ?cl2tim crm:P2_has_type wd:Q873806 .
        ?cl2tim crm:P48_has_preferred_identifier ?cl2tbn .
        ?cl2tbn rdf:value ?childl2_thumb . 
        }
      }  
    }
    FILTER ( ?painting = ng:0EWJ-0001-0000-0000 &&  ?label = "Hofmann_Provenance_2008_NG1171_Part_II"@en  ).
  } 
ORDER BY ASC (?child_order) ASC (?childl2_order)
LIMIT 50    '
          ),  
      "object-full-model" => array (
          "type" => "model",
          "name" => "object-full-model", 
          "display" => "Find Full Object Model", 
          "query" => $GLOBALS["searchPrefix"].'
# Full Object Mapping Model
# Returned variables have been simplified to speed up the query
# View the graphical model to see the full object map

SELECT DISTINCT
  ?painting ?invno
WHERE {
  ?painting crm:P48_has_preferred_identifier ?o ;
    crm:P53_has_former_or_current_location ?l ;
    crm:P46i_forms_part_of ?c ; 
    crm:P102_has_title ?st ;
    crm:P43_has_dimension ?wd ;
    crm:P43_has_dimension ?hd ;
    crm:P46_is_composed_of ?ma ;
    crm:P46_is_composed_of ?mb ;
    crm:P102_has_title ?t  ;
    crm:P108i_was_produced_by ?pe;    
    crm:P109_has_current_or_former_curator ?cu .
    
  ?painting crm:P2_has_type ?ptt .
  OPTIONAL {?ptt rdfs:label ?pttl . }.
  
  ?cu rdfs:label ?curator .

  ?pe crm:P2_has_type  crm:E12_Production  ;
     crm:P14_carried_out_by ?pa ;
     crm:P4_has_time_span ?ts .
  ?pa rdfs:label ?artist .
  ?ts rdfs:label ?date .
   
  ?ma crm:P2_has_type ?mat ;
    crm:P45_consists_of ?sm .  
  ?sm crm:P2_has_type ?smt .
  ?smt rdfs:label ?support .
  
  ?mb crm:P2_has_type ?mbt ;
    crm:P45_consists_of ?mm .  
  ?mm crm:P2_has_type ?mmt .
  ?mmt rdfs:label ?medium .
  
  ?wd crm:P90_has_value ?width .
  ?wd crm:P2_has_type aat:300055647  .
  ?wd crm:P91_has_unit ?wunit .
  ?wunit rdfs:label ?wunitl .
  
  ?hd crm:P90_has_value ?height .
  ?hd crm:P2_has_type aat:300055644  .
  ?hd crm:P91_has_unit ?hunit .
  ?hunit rdfs:label ?hunitl .
  
  ?st crm:P2_has_type ?stt ;
    rdfs:label ?shortTitle .
  OPTIONAL {?stt rdfs:label ?sttl . }.

  
  ?t crm:P2_has_type ?ftt ;
    rdfs:label ?title .
  OPTIONAL {?ftt rdfs:label ?fttl . }.
  
  ?c rdfs:label ?collection .
  ?c crm:P2_has_type ?colt .
  OPTIONAL {?colt rdfs:label ?coltl . }.
  
  ?l  rdfs:label ?location .
  ?l crm:P2_has_type ?lt .
  OPTIONAL {?lt rdfs:label ?ltl . }.
  
  ?o rdfs:label ?invno .
  ?o crm:P2_has_type ?ot .
  OPTIONAL {?ot rdfs:label ?otl . }.
  
FILTER ( ?painting = ng:0D0E-0001-0000-0000 ) .
  
}   '
          ),  
      "artist-full-model" => array (
          "type" => "model",
          "name" => "artist-full-model", 
          "display" => "Find Full Artist Event Model", 
          "query" => $GLOBALS["searchPrefix"].'
# Full Artist Event Mapping Model
# Returned variables have been simplified to speed up the query
# View the graphical model to see the full artist event map

SELECT DISTINCT ?artist ?name ?birth_year ?death_year WHERE {
  ?artist rdfs:label ?name ;
      crm:P2_has_type ?at ;
      crm:P100i_died_in ?de ;
      crm:P98i_was_born ?be .

OPTIONAL { ?at rdfs:label ?atl . } .
OPTIONAL { ?artist crm:P3_has_note ?note . } .
OPTIONAL { 
    ?artist crm:P1_is_identified_by ?id .
    ?id rdfs:label ?idl .
    OPTIONAL { ?id crm:P2_has_type ?idt .} .
    } .

?be crm:P4_has_time_span ?bts ;
    crm:P2_has_type ?bet .
    ?bts rdfs:label ?birth_year .

OPTIONAL { 
    ?bts crm:P2_has_type ?btst .
    OPTIONAL { ?btst rdfs:label ?btstl . } .
    } .
OPTIONAL { ?bet rdfs:label ?betl . } .

?de crm:P4_has_time_span ?dts ;
    crm:P2_has_type ?det  .
    ?dts rdfs:label ?death_year .

OPTIONAL { ?det rdfs:label ?detl . } .
OPTIONAL { 
    ?dts crm:P2_has_type ?dtst .
    OPTIONAL { ?dtst rdfs:label ?dtstl . } .
    } .

  FILTER ( ?name = "Raphael"@en ) .
}    '
          ),  
      "image-full-model" => array (
          "type" => "model",
          "name" => "image-full-model", 
          "display" => "Find Full Image Model", 
          "query" => $GLOBALS["searchPrefix"].'
# Full (Almost) Image Mapping Model
# Returned variables have been simplified to speed up the query
# View the graphical model to see the main image map

SELECT DISTINCT 
?image ?filename 
WHERE {
  ?image crm:P149_is_identified_by ?bn  ;
      crm:P62_depicts ?t1 ;
      crm:P65_shows_visual_item ?vim ;
      dig:L56_has_pixel_width  ?width ;
      dig:L57_has_pixel_height  ?height ;
      crm:P43_has_dimension ?imdim ;
      crm:P2_has_type ?imt .
  OPTIONAL { 
      ?image  crm:P70_is_documented_in ?cbn . 
      ?cbn ?sdf ?cbnt .
      OPTIONAL {  ?cbn rdfs:label ?caption .  } .
      }

  OPTIONAL { ?vim ?vimp ?vimv . } .

  OPTIONAL { ?imt rdfs:label ?imtl . } .

  ?bn rdfs:label ?filename  .  
  ?bn crm:P2_has_type ?bnt  . 
  OPTIONAL { ?bnt rdfs:label ?bntl . } . 

  OPTIONAL { 
      ?imdim ?imdimp ?imdimt .
      OPTIONAL {?imdimt rdfs:label ?imdimtl . } .
      }

 OPTIONAL {
   ?image  crm:P108i_was_produced_by ?pe .
   OPTIONAL {
      ?pe ?pep ?pev . 
      OPTIONAL { ?pev rdfs:label ?pevl . } .
      } .
    } .

  FILTER ( ?filename = "N-6596-00-000020.tif"@en  ) .
} 
   '
          ),  
      "image-pyramid-model" => array (
          "type" => "model",
          "name" => "image-pyramid-model", 
          "display" => "Find Pyramid Image Model", 
          "query" => $GLOBALS["searchPrefix"].'
# Pyramid Image Mapping Model
# Returned variables have been simplified to speed up the query
# View the graphical model to see the full pyramid image map

SELECT DISTINCT 
?image ?filename
WHERE {
  ?image crm:P149_is_identified_by ?bn  ;
      crm:P62_depicts ?t1 ;
      crm:P65_shows_visual_item ?vim ;
      dig:L56_has_pixel_width  ?width ;
      dig:L57_has_pixel_height  ?height ;
      crm:P43_has_dimension ?imdim ;
      crm:P2_has_type ?imt .

  OPTIONAL { ?vim ?vimp ?vimv . } .

  OPTIONAL { ?imt rdfs:label ?imtl . } .

  ?bn rdfs:label ?filename  .  
  ?bn crm:P2_has_type ?bnt  . 
  OPTIONAL { ?bnt rdfs:label ?bntl . } . 

  ?e dig:L21_used_as_derivation_source ?image .
  ?e dig:L23_used_software_or_firmware ?sv .
  ?e crm:P2_has_type ?et .
  ?e dig:L22_created_derivative ?pim .
  ?pim crm:P2_has_type wd:Q3411251 .
  ?pim crm:P43_has_dimension ?bnd .
  ?bnd crm:P90_has_value ?levels .
  ?pim crm:P149_is_identified_by ?pn .
  ?pn rdfs:label ?pyramid .
  ?pim crm:P70i_is_documented_in ?iiifinfo .

    OPTIONAL {
       ?sv  ?svp ?svo .
       OPTIONAL {  ?svo rdfs:label ?svol .  } .
    } .

  FILTER ( ?filename = "N-6596-00-000020.tif"@en  ) .
} 
	
   '
          ),  
      "sample-full-model" => array (
          "type" => "model",
          "name" => "sample-full-model", 
          "display" => "Find Full Sample Model", 
          "query" => $GLOBALS["searchPrefix"].'
# Full Sample Mapping Model
# Returned variables have been simplified to speed up the query
# View the graphical model to see the full sample map

SELECT DISTINCT ?sample ?sampleLabel WHERE {
  ?sample sci:O25i_is_contained_in ?painting  .
  ?sample rdfs:label ?sampleLabel .
  ?sample crm:P2_has_type ?sampleType . 
  ?sample sci:O18i_was_altered_by ?embed .

OPTIONAL { ?sampleType rdfs:label ?sampleTypeLabel . } .
 ?painting crm:P102_has_title ?paintingTitle .
 ?painting crm:P59_has_section ?sampleSite .
 ?paintingTitle rdfs:label ?paintingTitleLabel .

OPTIONAL { 
    ?sampleSite crm:P2_has_type ?sst .
    OPTIONAL {  ?sampleSite rdfs:label ?ssl . } .
    OPTIONAL { ?sst rdfs:label ?sstl . } .
    } .

OPTIONAL {     
    ?embed ?emp ?emv .
    OPTIONAL { ?emv rdfs:label ?emvl . } .
    } .

  ?samevent sci:O5_removed ?sample ;
      sci:O3_sampled_from ?painting ;
      crm:P14_carried_out_by ?person ;
      crm:P70_is_documented_in ?reason ;
      crm:P2_has_type ?set ;
      sci:O4_sampled_at ?sampleSite ;
      rdfs:comment ?comment .

 OPTIONAL { ?set rdfs:label ?setl . } .

 OPTIONAL { 
    ?reason ?rnp ?rnv .
    OPTIONAL { ?rnv rdfs:label ?rnvl . } .
    } .

 OPTIONAL { 
    ?person ?pnp ?pnv .
    OPTIONAL { ?pnv rdfs:label ?pnvl . } .
    } .

 ?ime crm:P39_measured ?sample .

 OPTIONAL { 
    ?ime ?imep ?imev .
    OPTIONAL { ?imev rdfs:label ?imevl . } .
    OPTIONAL { 
      ?imev crm:P149_is_identified_by ?imevid .
      ?imevid  rdfs:label ?imevidl . } .
   } .


FILTER ( ?sampleLabel ="N-0027-00_inorganic_sample_012"@en )

} LIMIT 100 
	
   '
          ),  
      "institution-full-model" => array (
          "type" => "model",
          "name" => "institution-full-model", 
          "display" => "Find Full Institution Model", 
          "query" => $GLOBALS["searchPrefix"].'
# Full Institution Mapping Model
# The institution identifier has the wrong type on it
## It also might be good to just use an rdfs:label here.
# Returned variables have been simplified to speed up the query
# View the graphical model to see the full institution map

SELECT DISTINCT
  ?painting ?location
WHERE {
  ?painting crm:P48_has_preferred_identifier ?o ;
    crm:P53_has_former_or_current_location ?l ;
    crm:P46i_forms_part_of ?c ; 
    crm:P102_has_title ?st .
    
  ?st crm:P2_has_type aat:300417208 ;
    rdfs:label ?shortTitle .
    
  ?c rdfs:label ?collection .
  ?c crm:P2_has_type ?colt .
  OPTIONAL { ?colt rdfs:label ?coltl . }.
  
  ?l  rdfs:label ?location .
  ?l crm:P89_falls_within ?ng.
  ?l crm:P2_has_type ?lt .
  OPTIONAL { ?lt rdfs:label ?ltl . }.
  
  ?ng crm:P53_has_former_or_current_location ?city .
  ?ng crm:P48_has_preferred_identifier ?ngid .

  ?ng crm:P2_has_type ?ngType .
  OPTIONAL {  ?ngType rdfs:label ?ngTypel . }.
  
  ?ngid crm:P2_has_type ?ngidType .
  ?ngid rdfs:label ?ngidl .
  OPTIONAL {  ?ngidType rdfs:label ?ngidTypel . }.

   ?city crm:P2_has_type ?cityType .
   ?city rdfs:label ?cityName .

  ?o rdfs:label ?invno .
  ?o crm:P2_has_type ?oType .
  OPTIONAL {  ?oType rdfs:label ?oTypel . }.

  
FILTER ( ?painting = ng:0FJS-0001-0000-0000 ) .
  
} 
	
   '
          ),  
      "document-full-model" => array (
          "type" => "model",
          "name" => "document-full-model", 
          "display" => "Find Full Document Model", 
          "query" => $GLOBALS["searchPrefix"].'
# Full Document Mapping Model
# The actual live file path is not current included in the mapping
# The crm:P165_incorporates leads to a symbolic object which is not exploited yet.

# Returned variables have been simplified to speed up the query
# View the graphical model to see the full document map

SELECT DISTINCT 
?doc ?label
WHERE {
  ?doc ?p ?o .
  ?doc crm:P43_has_dimension ?ddim .
  ?doc  rdfs:label ?label .
  ?doc crm:P67_refers_to ?thing .
OPTIONAL { ?o rdfs:label ?ol . } .
?ddim ?dp ?dt .
OPTIONAL { ?dt rdfs:label ?dtl . } .
OPTIONAL { ?thing crm:P48_has_preferred_identifier ?thingID .
 ?thingID rdfs:label ?invno . } .

filter ( ?label = "Technical_Bulletin_25_2004_4-35"@en ) .
} 
	
   '
          )
        ),
      "grounds" => array(
        "custom-2" => array ( // UPDATED
          "type" => "custom",
          "name" => "custom-2", 
          "display" => "Custom query example - Grounds", 
          "query" => $GLOBALS["searchPrefix"].'  
# Custom query example: Edit this basic SPARQL query to  create custom queries.
# The following comment is required to query the grounds end-point
# use: grounds

SELECT DISTINCT * WHERE {
  ?sub ?pre ?obj .
} LIMIT 10 '),
        )
  );
  
  return ($examples);
  }

function examplesList ($which="default")
  {
  global $graphTitle;
  
  $examples = examplesGet ();
  $use = $examples[$which];
  
  $sim = "";
  $com = "";
  $dis = "";
  $mod = "";
  $cus = "";
   
  if (isset($graphTitle[$which]))
    {$head = "<h5>$graphTitle[$which]</h5>";}
  else
    {$head = "<h5>Other Examples</h5>";}
  
  foreach ($use as $key => $a) 
    {if ($a["type"] == "simple") 
      {$sim .= formatExampleLink ($a["name"], $a["display"]);}
     else if ($a["type"] == "custom") 
      {$cus .= formatExampleLink ($a["name"], $a["display"]);}
     else if ($a["type"] == "complex") 
      {$com .= formatExampleLink ($a["name"], $a["display"]);}
     else if ($a["type"] == "display")  
      {$dis .= formatExampleLink ($a["name"], $a["display"]);}
     else if ($a["type"] == "model")  
      {$mod .= formatExampleLink ($a["name"], $a["display"]);}}
 
  if ($sim)
    {$sim = "<h6>Simple Example Queries</h6><ul>".$sim."</ul>";}
    
  if ($com)
    {$com = "<h6>Complex Example Queries</h6><ul>".$com."</ul>";}
    
  if ($dis)
    {$dis = "<h6>Display Examples</h6><ul>".$dis."</ul>";}  
    
  if ($mod)
    {$mod = "<h6>Mapping Model Examples</h6><ul>".$mod."</ul>";}  
    
  if ($cus)
    {$cus = "<h6>Build Custom Query</h6><ul>".$cus."</ul>";}
      
  return ($head.$sim.$com.$dis.$mod.$cus);
  }
  
function formatExampleLink ($name, $display)
  {    
  ob_start();  
    echo <<<END
    <li>
      <a href="?example=$name" title="$display - HTML">$display</a>
      <a href="?example=$name&format=json" title="$display - JSON" class="float-end" target="_blank">
        <i class="bi bi-filetype-json" style="cursor:pointer;"></i>
      </a>
    </li>
END;
  $html = ob_get_contents();
  ob_end_clean();   
  
  return($html);
  }
  

function checkEndpoint ()
  {$q= "SELECT ?s WHERE { ?s ?p ?o . } LIMIT 1";
   if (selectQuery ($q)) {return (true);}
   else {return(1);}}


function getsslJSONfile ($uri, $decode=true)
	{
	$arrContextOptions=array(
    "ssl"=>array(
        "verify_peer"=>false,
        "verify_peer_name"=>false,),);  

	$response = file_get_contents($uri, false, stream_context_create($arrContextOptions));
	
	if ($decode)
		{return (json_decode($response, true));}
	else
		{return ($response);}
	}
	
function getExternalDetails($searchterm, $uri="", $extra="")
	{$uri = $uri.$searchterm.$extra;
	 $arr = getsslJSONfile($uri);
	 return($arr);}


function resizeIIIFthumb ($uri, $size)
  {
  if (preg_match("/^(.+[\/])([^\/]+)([\/][0-9]+[\/][a-z]+[.][a-z]+)$/", $uri, $m))
    {$uri = $m[1].$size.$m[3];}
  return ($uri);
  }
  
  		  	
function getModal ($which, $default="", $close=true)
  { 
  global $theme;
     
  $modals = array(
    "login" => array (
      "id" => "loginModal",
      "label" => "Please Login to Search the Internal Image Viewer",
      "body" => '<form class="justify-content-center" style="padding:0.5rem 0px 0.5rem 0px;" method="post">
          <div class="mb-3"> 
	    <div class="input-group mb-3">
	      <span class="input-group-text" id="basic-addonS1">@</span>
	      <input type="text" class="form-control" id="email" name="email" placeholder="Email" aria-label="Email" aria-describedby="loginHelp">
	    </div>
            <div id="loginHelp" class="form-text '.$theme["hclass"].'">National Gallery email address.</div>
          </div>	  
          <div class="mb-3"> 
	    <div class="input-group mb-3">
	      <span class="input-group-text" id="basic-addonS2"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-lock" viewBox="0 0 16 16">
  <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2zM5 8h6a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/>
</svg></span>
	      <input type="password" class="form-control" id="password" name="password" placeholder="Password" aria-label="Password" aria-describedby="passwordHelp">
	    </div>
            <div id="passwordHelp" class="form-text '.$theme["hclass"].'">National Gallery password.</div>
          </div>
          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" value="1" id="rememberme" name="rememberme"  aria-describedby="remembermeHelp">
            <label class="form-check-label '.$theme["hclass"].'" for="rememberme">Remember Me</label>
            <div id="remembermeHelp" class="form-text '.$theme["hclass"].'">This option will keep you logged in for longer.</div>
          </div>
	  <input type="hidden" class="form-control" id="signin" name="signin" value="1">
          <button type="submit" class="btn '.$theme["btclass"].' searchsubmit float-end" id="submit-login-modal">Login</button>
        </form>'),
    "info" => array (
      "id" => "infoModal",
      "label" => "Further Information",
      "body" => '
      
  <div class="form-text '.$theme["hclass"].'">
  
    <h5>Issues in providing Open Data in Heritage Science</h5>
    <p>This site presents access to two example Heritage Science data sets that have been mapped to the CIDOC-CRM ontology and a few of the the <a href="https://www.cidoc-crm.org/collaborations">related CIDOC models</a>. They have both been formatted as RDF XML and uploaded to an open instance of the <a href="https://blazegraph.com/">Blazegraph Triple Store</a>, hosted on one of the National Galleries public research servers. <a href="https://www.w3.org/TR/rdf-sparql-query/">SPARQL queries</a> can be submitted using the GUI provided or direct queries can be submitted to the end-points using URLs with the following format:</p>
    
    <p class="btn-light item-secondary" style="text-align:center;padding:5px 0 5px 0;">
      https://END-POINT-URL?format=json&query=FULLESCAPEDSPARQLQUERY
    </p>
    
    <p>A series of example SPARQL queries are presented, for data set one, to document how the datasets are organised and the various relationships that have been mapped to the CIDOC CRM. A simple default query is also presented, for both of the data sets, to allow new bespoke queries to be created. The queries pages are organised into two main sections; the SPARQL query on the left and the results, formatted as JSON, presented on the right. Three additional interaction options are also provided through the small icons in the upper right:</p> 

    <ul class="list-group list-group-flush">
      <li class="list-group-item" style="background-color: #f8f9fa;"><span class="text-primary"><i class="bi bi-eye"></i></span> - Re-format the results and present the defined relationships as a graphic model (see below).</li>
      <li class="list-group-item" style="background-color: #f8f9fa;"><span class="text-primary"><i class="bi bi-share"></i></span> - A shareable link for the current query presentation webpage - including bespoke queries.</li>
      <li class="list-group-item" style="background-color: #f8f9fa;"><span class="text-primary"><i class="bi bi-filetype-json"></i></span> - A direct shareable link to a simple json version of the results of the current query - including bespoke queries.</li>
    </ul><br/>
    
    <table class="table">
      <tbody>
        <tr>
          <td><span class="text-primary">Website Code:</span></td>
          <td><a href="https://github.com/jpadfield/sshoc-data-presentation">GitHub</a></td>
        </tr>
      </tbody>
    </table>
      
    
    <h6>Data set 1: The Raphael Research Resource</h6>
    <p>In 2007 the <a href="https://cima.ng-london.org.uk/documentation">Raphael Research Resource</a> project began to examine how complex conservation, scientific and art historical research could be combined in a flexible digital form. Exploring the presentation of interrelated high resolution images and text, along with how the data could be stored in relation to an event driven ontology in the form of <a href="http://www.w3.org/TR/rdf-concepts/">RDF triples</a>. The original <a href="https://cima.ng-london.org.uk/documentation">main user interface</a> is still live and the data stored within the system is presented here in the form of <a href="http://en.wikipedia.org/wiki/Linked_Data">open linkable data</a> combined with a <a href="http://en.wikipedia.org/wiki/SPARQL">SPARQL</a> end-point.</p>
    <table class="table">
      <tbody>
        <tr>
          <td><span class="text-primary">End-point:</span></td>
          <td colspan="3">https://rdf.ng-london.org.uk/bg/bigdata/sparql/namespace/sshoc-raphael</td>
        </tr>
        <tr>
          <td><span class="text-primary">Classes:</span></td>
          <td>36</td>
          <td><span class="text-primary">Properties:</span></td>
          <td>69</td>
        </tr>
        <tr>
          <td><span class="text-primary">Entities:</span></td>
          <td>40915</td>
          <td><span class="text-primary">Triples:</span></td>
          <td>414401</td>
        </tr>
        <tr>
          <td><span class="text-primary">Ontologies:</span></td>
          <td>CIDOC CRM, CRM Dig, CRM Sci</td>
          <td><span class="text-primary">Full XML:</span></td>
          <td>DOI</td>
        </tr>
        <tr>
          <td><span class="text-primary">Mapping Code:</span></td>
          <td><a href="https://github.com/jpadfield/sshoc_raphael_modelling">GitHub</a></td>
          <td><span class="text-primary">Original GUI:</span></td>
          <td><a href="https://cima.ng-london.org.uk/documentation">Link</a></td>
        </tr>
      </tbody>
    </table>

    <h6>Data set 2: The Grounds Database</h6>
    <p>In 2018 the <a href="https://doi.org/10.5281/zenodo.5838339">IPERION-CH Grounds Database</a> was presented to examine how the data produced through the scientific examination of historic painting preparation or grounds samples, from multiple institutions could be combined in a flexible digital form. Exploring the presentation of interrelated high resolution images, text, complex metadata and procedural documentation. The original <a href="https://research.ng-london.org.uk/iperion/">main user interface</a> is live, though password protected at this time, and the data stored within the system is presented here in the form of <a href="http://en.wikipedia.org/wiki/Linked_Data">open linkable data</a> combined with a <a href="http://en.wikipedia.org/wiki/SPARQL">SPARQL</a> end-point.</p>
    <table class="table">
      <tbody>
        <tr>
          <td><span class="text-primary">End-point:</span></td>
          <td colspan="3">https://rdf.ng-london.org.uk/bg/bigdata/sparql/namespace/sshoc-grounds</td>
        </tr>
        <tr>
          <td><span class="text-primary">Classes:</span></td>
          <td>60</td>
          <td><span class="text-primary">Properties:</span></td>
          <td>78</td>
        </tr>
        <tr>
          <td><span class="text-primary">Entities:</span></td>
          <td>41197</td>
          <td><span class="text-primary">Triples:</span></td>
          <td>474470</td>
        </tr>
        <tr>
          <td><span class="text-primary">Ontologies:</span></td>
          <td>CIDOC CRM, CRM Dig, CRM Sci</td>
          <td><span class="text-primary">Full XML:</span></td>
          <td>DOI</td>
        </tr>
        <tr>
          <td><span class="text-primary">Mapping Code:</span></td>
          <td><a href="https://github.com/jpadfield/sshoc_grounds_modelling">GitHub</a></td>
          <td><span class="text-primary">Original GUI:</span></td>
          <td><a href="https://research.ng-london.org.uk/iperion/">Link</a></td>
        </tr>
      </tbody>
    </table>
    
    
    <h6>Data set 3: The SMK Grounds Database (Not searchable here)</h6>
    <p>The work carried out within T5.6 of SSHOC also provided technical support for the production of a new open version of the Grounds Database, with all of its content made re-usable under a defined creative-commons licences. A generous grant from The Samuel H. <a href="https://www.kressfoundation.org/">Kress Foundation</a> for the project "The digitization of cross-sections from Italian and Dutch paintings" at <a href="https://www.smk.dk/">The National Gallery of Denmark (SMK)</a> enabled the digitization and analyses of cross-sections from a total of 158 Italian 14th to 17th C. and 17th C. Dutch paintings from the SMK collection to be made available in this open access art and technology research database on ground layers. With samples from the collections of <a href="https://www.nationalmuseum.se/en/om-nationalmuseum">Nationalmuseum Stockholm</a> and Museum of National History, <a href="https://dnm.dk/en/">Frederiksborg Castle in Hillerød</a> the database includes an additional 11 paintings. Effort within the SSHOC project re-formatted the provided data and enabled it to be presented within the IPERION-CH Grounds Database GUI. Further work was also carried out to open up this data to non specialists, allowing access to the same images via a simple keyword search option, though a Simple IIIF Discovery site, the development of which was supported by the AHRC funded Practical IIIF project, the SSHOC project.<a href="https://www.iperionhs.eu/">H2020 IPERION HS</a> project and the </p>
    <table class="table">
      <tbody>
        <tr>
          <td><span class="text-primary">IIIF Discovery End-point:</span></td>
          <td colspan="3">https://research.ng-london.org.uk/smk/</td>
        </tr>
        <tr>
          <td><span class="text-primary">Full Website:</span></td>
          <td><a href="https://research.ng-london.org.uk/iperion-smk/">Link</a></td>
          <td><span class="text-primary">Simple IIIF Discovery Website:</span></td>
          <td><a href="https://research.ng-london.org.uk/ss-smk/">Link</a></td>
        </tr>
        <tr>
          <td><span class="text-primary">Simple IIIF Discovery Code:</span></td>
          <td><a href="https://github.com/jpadfield/iiif-discovery">GitHub</a></td>
        </tr>
      </tbody>
    </table>
    <hr/>

    <h6>The Dynamic Modeller</h6>
    <p>Initially in response to restrictions imposed by COVID-19 a <a href="https://research.ng-london.org.uk/modelling/">live, online, dynamic, modelling system</a> was developed to facilitate the collaborative development of semantic models and flow diagrams within SSHOC, but also within other related research projects such as the <a href="https://linked.art/">Linked.Art</a> project and the <a href="https://www.iperionhs.eu/">H2020 IPERION HS</a> research project.​ This is an interactive modelling system which can automatically convert simple tab separated triples or JSON-LD into graphical models using the <a href="https://mermaid-js.github.io/">mermaid library</a>. To improve the accessibility and understanding of these presented data sets the output of each of the SPARQL queries, including bespoke queries, can be automatically formatted and modelled using the dynamic modeller. This can be achieved by clicking on the small "eye" icon in the upper right corer of any of the SPARQL query pages.</p>
    <table class="table">
      <tbody>
        <tr>
          <td><span class="text-primary">Full Website:</span></td>
          <td><a href="https://research.ng-london.org.uk/modelling/">Link</a></td>
          <td><span class="text-primary">Website Code:</span></td>
          <td><a href="https://github.com/jpadfield/dynamic-modelling">GitHub</a></td>
        </tr>
      </tbody>
    </table>    
  </div>
'),
    "examples" => array (
      "id" => "examplesModal",
      "label" => "Example SPARQL Queries",
      "body" => examplesList ("raphael").examplesList ("grounds")));

  if(isset($modals[$which]))
    {$use = $modals[$which];}
  else
    {$use = $modals["info"];} 
    
  if ($close)
    {$close = '<div class="modal-footer">
            <button type="button" class="btn '.$theme["btclass"].'" data-bs-dismiss="modal">Close</button>
          </div>';}  
        
  ob_start();
  echo <<<END
 
    <div class="modal fade" id="$use[id]" tabindex="-1" aria-labelledby="$use[id]Label" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="$theme[bgstyle]">
          <div class="modal-header">
            <h5 class="modal-title $theme[hclass]" id="$use[id]Label">$use[label]</h5>
            <button type="button" class="btn-close $theme[btclose]" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            $use[body]
          </div>
          $close
        </div>
      </div>
    </div>
                
END;
  
  $html = ob_get_contents();
  ob_end_clean(); 
  
  return($html);
  }
 
function getBSButton ($which, $lextra="", $li=true )
  {
  global $theme;
  
  $bdefault = array(
      "title" => "Default",
      "class" => "bi-bootstrap",
      "target" => "",
      "link" => "",
      "text" => false);  
      
  // classes selected from https://icons.getbootstrap.com/
  $buttons = array(
    "home" => array(
      "title" => "Home",
      "class" => "bi-house",
      "link" => ""),
    "login" => array(
      "title" => "Login",
      "class" => "bi-person",
      "target" => "#loginModal"),
    "logout" => array(
      "title" => "Logout",
      "class" => "bi-person-check-fill",
      "link" => "href='./?logout=1'"),
    "dark" => array(
      "title" => "Change to the Light Theme",
      "class" => "bi-sun",
      "link" => "./?theme=light"),
    "light" => array(
      "title" => "Change to the Dark Theme",
      "class" => "bi-sun-fill",
      "link" => "./?theme=dark"),
    "info" => array(
      "title" => "Further Information",
      "class" => "bi-info-circle",
      "target" => "#infoModal"),
    "examples" => array(
      "title" => "Example SPARQL searches",
      "class" => "bi-bookmarks",
      "target" => "#examplesModal"),
    "search" => array(
      "title" => "Custom SPARQL search - Raphael Data",
      "class" => "bi-search",
      "link" => "./?example=custom",
      "text" => "<sup>1</sup>"),
    "search2" => array(
      "title" => "Custom SPARQL search - Grounds Data",
      "class" => "bi-search",
      "link" => "./?example=custom-2",
      "text" => "<sup>2</sup>")
    );

  if(isset($buttons[$which])) 
    {$b = array_merge($bdefault, $buttons[$which]);}
  else
    {$b = array_merge($bdefault, $buttons["info"]);}  
  
  if ($b["link"] or $lextra)
    {$b["link"] = "href='$b[link]$lextra'";}
    
  if ($b["link"] or $lextra or $b["target"])
    {$icolor = $theme["iclass"];}
  else
    {$icolor = $theme["aiclass"] . "\" style=\"cursor:default;";}
     
  if ($b["target"])
    {$atop =  "<a role=\"button\" title=\"$b[title]\" style=\"\" class=\"nav-link py-2 px-2\" ".
      "data-bs-toggle=\"modal\" data-bs-target=\"$b[target]\" data-bs-placement=".
      "\"right\" data-bs-original-title=\"$b[title]\">";}
  else
    {$atop =  "<a role=\"button\"  $b[link] title=\"$b[title]\" id=\"$which\" ".
      "class=\"nav-link py-2 px-2\" data-bs-toggle=\"tooltip\" ".
      "data-bs-placement=\"right\" data-bs-original-title=\"$b[title]\">";}
        
  if ($li)
    {$html = "<li class=\"nav-item\">$atop<i class=\"$b[class] fs-3 $icolor\">$b[text]</i></a></li>";}
  else
    {$html = "$atop<i class=\"$b[class] fs-3 $icolor\">$b[text]</i></a>";}
    
  return($html);  
  }
  
function getTheme ($which)
  {
  $theme = array(
    "dark" => array(
      "name" => "dark",
      "bgstyle" => "background-color:#515151;",
      "iclass" => "item-primary text-primary",
      "aiclass" => "item-light text-light",
      "hclass" => "text-light",
      "btclass" => "btn-outline-dark",
      "btclose" => "btn-close-white",
      "logo" => "https://research.ng-london.org.uk/ng/graphics/ng_logo_wtr_125.png"),
    "light" => array(
      "name" => "light",
      "bgstyle" => "background-color:white;",
      "iclass" => "item-primary text-primary",
      "aiclass" => "item-dark text-dark",
      "hclass" => "text-dark",
      "btclass" => "btn-outline-primary",
      "btclose" => "",
      "logo" => "https://research.ng-london.org.uk/ng/graphics/ng_logo_tr_125.png")
    );
  
  if ($which == "dark")
    {return($theme["dark"]);}
  else
    {return($theme["light"]);}  
  }
 
function buildPage ($pd=array())
  {
  global $theme;
  
  $root = "https://rdf.ng-london.org.uk/sshoc";
  $dpd =  array(
    "title" => "Page Title",
    "body" => "Page Body",
    );
    
  $pd = array_merge($dpd, $pd);
  
  if ($pd["title"])
    {
    ob_start();  
    echo <<<END
    <div class="row">
      <div class=" col-md-6 col-4 flex-grow-1 align-items-center d-flex mb-3">     
        <div class="box w-100 text-primary">
          <h1 class="text-center m-0 display-6">$pd[title]</h1>
        </div>
      </div>
      <div class="col-md-2 col-3 flex-shrink-1 align-items-center d-flex mb-3">
          <div class="box w-100 text-primary">
            <a href="https://sshopencloud.eu/"><img class="float-end me-3 img-fluid" src="$root/graphics/sshoc-logo.png" alt="SSHOC" style="max-height:41px;width:auto;"></a>          
          </div>  
      </div>
      <div class="col-md-2 col-3 flex-shrink-1 align-items-center d-flex mb-3">
          <div class="box w-100 text-primary">
            <a href="https://www.nationalgallery.org.uk/"><img class="float-end me-3 img-fluid" src="$theme[logo]" alt="Site Logo" style="max-height:41px;width:auto;"></a>
          </div>  
      </div>
    </div>
END;
    $pd["title"] = ob_get_contents();
    ob_end_clean(); 
    }
   
  ob_start();  
  echo <<<END
  <div class="container-fluid h-100">
    <div class="row justify-content-center h-100">
      <div class="col-12">
        <div class="h-100 d-flex flex-column">
        
          <div class="row justify-content-center">
            $pd[title]
          </div>          
          
          $pd[body]   
          
          <footer class="footer mt-auto border-top border-2">
            <div class="container-fluid">
              <div class="row">
                <div class="col-3" style="text-align:left;"><a href="https://www.nationalgallery.org.uk/terms-of-use"><img height="16" alt="© The National Gallery 2021" title="© The National Gallery 2021" src="$root/graphics/copyright-NG.png"></a></div>
                <div class="col-1" style="text-align:center;"></div>
                <div class="col-8" style="text-align:right;"><a href="https://rightsstatements.org/page/NoC-NC/1.0/?language=en"><img height="16" alt="In Copyright - Educational Use Permitted" title="In Copyright - Educational Use Permitted" src="https://rightsstatements.org/files/buttons/NoC-NC.dark-white-interior.svg"></a>&nbsp;&nbsp;<a rel="license" href="https://creativecommons.org/licenses/by-nc/4.0/"><img alt="Creative Commons Licence" style="border-width:0" src="https://i.creativecommons.org/l/by-nc/4.0/88x31.png"></a></div>
              </div>
            </div>
          </footer>
                    
        </div>
      </div>
    </div>
  </div>
END;
    $pd["body"] = ob_get_contents();
    ob_end_clean(); 
        
  ob_start();  
  echo <<<END
  
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Presenting direct access to Heritage Science datasets via an open SPARQL end-point." />
		<meta name="keywords" content="SSHOC, The National Gallery, London, National Gallery London, Scientific, Research, Heritage, Culture, JSON, PHP, Javascript, Dissemination, VRE, IIIF, Discovery, OpenSeadragon" />
    <meta name="author" content="Joseph Padfield| joseph.padfield@ng-london.org.uk |National Gallery | London UK | website@ng-london.org.uk |www.nationalgallery.org.uk" />
    <meta name="image" content="" />
    <link rel="shortcut icon" href="$root/graphics/favicon.png" type="image/png" />
    <title>SSHOC: Making Heritage Science Data FAIR</title>
    
    <link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.3/css/all.min.css" integrity="sha256-2H3fkXt6FEmrReK448mDVGKb3WW2ZZw35gI7vqHOE4Y=" crossorigin="anonymous" rel="stylesheet" type="text/css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" integrity="sha256-YvdLHPgkqJ8DVUxjjnGVlMMJtNimJ6dYkowFFvp4kKs=" crossorigin="anonymous">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
	
    <link href="https://cdn.jsdelivr.net/npm/jquery.json-viewer@1.4.0/json-viewer/jquery.json-viewer.css" integrity="sha256-rXfxviikI1RGZM3px6piq9ZL0YZuO5ETcO8+toY+DDY=" crossorigin="anonymous" rel="stylesheet" type="text/css">
	
    <link href="https://cdn.jsdelivr.net/npm/highlight.js@11.2.0/styles/github.css" integrity="sha256-Oppd74ucMR5a5Dq96FxjEzGF7tTw2fZ/6ksAqDCM8GY=" crossorigin="anonymous" rel="stylesheet" type="text/css">		
    
    <link href="$root/css/main.css" rel="stylesheet" type="text/css">
    
     <style>
    </style>
    
  </head>

<body onload="onLoad();">

  <div class="container-fluid h-100">
    <div class="row h-100">
      <div class="col-md-auto bg-light sticky-top">
        <div class="d-flex flex-md-column flex-row flex-nowrap bg-light align-items-center sticky-top">
          $pd[home]
          <!-- <a class="d-block p-1 link-dark text-decoration-none" title="" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-original-title="Icon-only"> <i class="bi-house fs-3"></i> </a> -->
          <div class="w-100">
            <div class="mx-auto">
              <ul class="nav nav-pills nav-flush flex-md-column flex-row flex-nowrap mb-auto mx-auto text-center justify-content-center px-1 align-items-center">                    
                $pd[buttons]
              </ul>
            </div>
          </div>
        </div>
      </div>
      <div class="col-sm p-1 h-100">
        
        
          $pd[body]    
        
      </div>           
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/tether@2.0.0/dist/js/tether.min.js" integrity="sha256-cExSEm1VrovuDNOSgLk0xLue2IXxIvbKV1gXuCqKPLE=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha256-9SEPo+fwJFpMUet/KACSwO+Z/dKMReF9q4zFhU/fT9M=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery.json-viewer@1.4.0/json-viewer/jquery.json-viewer.js" integrity="sha256-klSHtWPkZv4zG4darvDEpAQ9hJFDqNbQrM+xDChm8Fo=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.2.0/build/highlight.min.js"></script>	
<script src="$root/javascript/json_viewer.js"></script>	
<script>


function onLoad() {
  parseJSON();
  doResize ();  
  $pd[console]      
  }
  
// Bind to the resize event of the window object
$(window).on("resize", function () {    
  doResize();    
  // Invoke the resize event immediately
  }).resize();
 
function doResize () {
  
  var lobj = document.getElementById('rheight');
  var robj = document.getElementById('qresults');
  var lform = document.getElementById('qform');
    
  if ( $(lobj).length )  {
    $(lobj).height(80);
    $(robj).height(80);
    $(robj).width(80);
  
    var lhh = $('#qheader').height();
    $('#rheader').height(lhh);
  
    var ph = $(robj.parentElement).height();
    var pw = $(robj.parentElement).width();
  
    $(lobj).height(ph);
    $(lform).height(ph - 20);   
    $(robj).height(ph - 16);    
  
    $(robj).width(pw - 4);  
    }
  }

function submitQueryForm(which="gui")
  {var tmp = $('#soft-limit-tmp').val();
   $('#soft-limit').val(tmp);
   $('#query-return').val(which); 
   if (which !== "gui")
    {
    $('#spform').attr('target', '_blank');
    }
   $('#spform').submit();}
   
function resetQueryForm()
  {document.getElementById('spform').reset();}
  
</script>
</body>
</html>
END;
  
  $html = ob_get_contents();
  ob_end_clean(); 
  
  return($html);  
  }

?>
