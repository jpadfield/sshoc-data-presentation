<?php

// Used to allow the full model queries to run
ini_set('memory_limit', '1024M'); // or you could use 1G
#ini_set('max_execution_time', '60'); 

include ("functions/functions_local.php");
  
////////////////////////////////////////////////////////////////////////

// USED for Debugging  
$console = array();
//$console["_GET"] = $_GET;
// $console["_POST"] = $_POST;
//if (isset($console["_POST"]["password"])) 
//  {$console["_POST"]["password"] = "HIDDEN VALUE";}
////////////////////////////////////////////////////////////////////////


// Not used yet ////////////////////////////////////////////////////////
if (!isset($_GET["theme"])) {$_GET["theme"] = "light";}
if ($_GET["theme"]) {$theme = $_GET["theme"];}

$theme = getTheme ($theme);
////////////////////////////////////////////////////////////////////////

$pd =  array(
  "title" => "Open Data in Heritage Science",
  "home" => "",
  "buttons" => "",
  "console" => "",
  "body" => ""
    );
    
$imod = getModal ("info");
$emod = getModal ("examples", "", false);
  
  
if (isset($_GET["pid"]))
	{  
  $q = $GLOBALS["searchPrefix"].'
SELECT DISTINCT * WHERE {
  ?thing ?op ?oo .
OPTIONAL {
  ?thing rdfs:label ?label_t . }
OPTIONAL {
  ?thing crm:P2_has_type ?tt .
  ?tt rdfs:label ?label_tt . }

OPTIONAL {
  ?oo rdfs:label ?label_o . }
OPTIONAL {
  ?oo crm:P2_has_type ?oot .
  ?oot rdfs:label ?label_ot . }

  ?is ?ip ?thing . 
OPTIONAL {
  ?is rdfs:label ?label_i . }
OPTIONAL {
  ?is crm:P2_has_type ?ist .
  ?ist rdfs:label ?label_it . }

FILTER ( ?thing = ng:'.$_GET["pid"].' ) .
} LIMIT 100 ';

  $nsp = $ep."/namespace/sshoc-combined";
  $spep = $nsp."/sparql";
    
  $example = array(
    "query" => $q,
    "display" => false,
    "name" => false);
  }
else if (isset($_POST["query"]))
  {
  if (preg_match ("/[#][\s]*use[\s]*[:][\s]*grounds/", $_POST["query"], $m))
    {$nsp = $ep."/namespace/sshoc-grounds";
     $spep = $nsp."/sparql";}
  else if (preg_match ("/[#][\s]*use[\s]*[:][\s]*combined/", $_POST["query"], $m))
    {$nsp = $ep."/namespace/sshoc-combined";
     $spep = $nsp."/sparql";}
   $example = array(
      "query" => $_POST["query"],
      "display" => false,
      "name" => false);}
else if (isset($_GET["query"]))
  {
  $s1 = str_replace (" ", "+", urldecode($_GET["query"]));
  $gq = gzuncompress(base64_decode($s1)); 
  if (preg_match ("/[#][\s]*use[\s]*[:][\s]*grounds/", $gq, $m))
    {$nsp = $ep."/namespace/sshoc-grounds";
     $spep = $nsp."/sparql";}
  $example = array(
      "query" => $gq,
      "display" => false,
      "name" => false);
  }
else if (isset($_GET["example"]))
  {$examples = examplesGet();
   if (isset($examples["raphael"][$_GET["example"]]))
    {$example = $examples["raphael"][$_GET["example"]];}
   else if (isset($examples["grounds"][$_GET["example"]]))
    {$example = $examples["grounds"][$_GET["example"]];
     $nsp = $ep."/namespace/sshoc-grounds";
     $spep = $nsp."/sparql";}
  }
else
  {$example = array();}

if ($example)
  {
  $data = exampleResults ($example);
  $pd["home"] = getBSButton ("home", "./", false);
    
  if ($example["name"] == "painting-details-display")
    {		
    $d = $data["raw"][0];
    
    foreach ($d as $k => $av)
      {$d[$k] = $av["value"];}
      
    $d["thumb"] = resizeIIIFthumb ($d["thumb"], ",512");
    
    ob_start();
		echo <<<END
 <div class="row justify-content-center position-relative my-2 bg-light">  

   <div class="card my-3" style="max-width: 640px;">
  <div class="row g-0">
    <div class="col-md-4" style="display: flex;align-items: center;">
      <img src="$d[thumb]" class="img-fluid mx-auto d-block" alt="...">
    </div>
    <div class="col-md-8">
      <div class="card-body">
        <h5 class="card-title">$d[shortTitle]</h5>
        <!-- <p class="card-text">This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.</p> -->
        <ul class="list-group list-group-flush">
          <li class="list-group-item"><span class="text-primary">Artist:</span> $d[artist]</li>
          <li class="list-group-item"><span class="text-primary">Artist dates:</span> $d[birth_year] - $d[death_year]</li>
          <li class="list-group-item"><span class="text-primary">Full title:</span> $d[title]</li>
          <li class="list-group-item"><span class="text-primary">Date Made:</span> $d[date]</li>
          <li class="list-group-item"><span class="text-primary">Medium and support:</span> $d[medium] on $d[support]</li>
          <li class="list-group-item"><span class="text-primary">Dimensions:</span> $d[height] x $d[width]</li>
          <li class="list-group-item"><span class="text-primary">Inv. Number:</span> $d[invno]</li>
          <li class="list-group-item"><span class="text-primary">Location in Gallery:</span> $d[location]</li>
        </ul>
        <div class="card-body">
          <a href="http://www.nationalgallery.org.uk/paintings/$d[invno]" class="card-link text-opacity-50">NG Website link</a>
        </div>
        <!-- <p class="card-text"><small class="text-muted">Last updated 3 mins ago</small></p> -->
      </div>
  
    </div>
  </div>
</div>     

  </div>
END;
    $extrahtml = ob_get_contents();
		ob_end_clean(); 
		
    $body = $extrahtml.$data["html"];
    }
  else if ($example["name"] == "image-details-display")
    {	
    $d = $data["raw"][0];
    
    foreach ($d as $k => $av)
      {$d[$k] = $av["value"];}
    
    ob_start();
		echo <<<END
  <div class="row justify-content-center position-relative my-2 bg-light position-relative">
    <div class="card-group" style="justify-content: center;">
      <div class="card text-center my-3" style="max-width: 640px;min-height:500px;">
        <div id="openseadragonviewerdiv" class="openseadragon h-100"></div>
        <p class="m-2">$d[caption]</p>
      </div>     
      <div class="card my-3 p-2" style="max-width: 250px;">
        <p><a href="https://openseadragon.github.io/">OpenSeadragon</a> viewer for:</p>
        <ul><li><a href="$d[iiifinfo]">$d[filename]</a></li></ul>
        <a href="https://iiif.io"><img class=" mx-auto d-block" src="./graphics/iiif_logo.png" alt="iiif logo" style="max-width:64px;"></a>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/openseadragon@2.4.2/build/openseadragon/openseadragon.min.js" integrity="sha256-NMxPj6Qf1CWCzNQfKoFU8Jx18ToY4OWgnUO1cJWTWuw=" crossorigin="anonymous"></script>
	<script src="https://cdn.rawgit.com/Pin0/openseadragon-justified-collection/1.0.2/dist/openseadragon-justified-collection.min.js"></script>
	<script>
			
	var myOSDInstance = OpenSeadragon({
		id:            "openseadragonviewerdiv",
		prefixUrl:     "https://openseadragon.github.io/openseadragon/images/",
		
		sequenceMode: true,
		showReferenceStrip: true,
		tileSources:   ["$d[iiifinfo]"]
		});
</script>
END;
    $extrahtml = ob_get_contents();
		ob_end_clean(); 
		
    $body = $extrahtml.$data["html"];
    }
  else if ($example["name"] == "text-details-display")
    {			
    $d = $data["raw"];
    
    $texts= array();
    
    $default_text = array (
      "label" => false,
      "title" => false,
      "note" => false,
      "content" => array(),
      "thumbs" => array(),
      "children" => array()
      );
    
    foreach ($d as $k => $av)
      {
      $p = $default_text;
      $c = $default_text;
      $c2 = $default_text;
      
      // Labels
      if (isset($av["label"])) 
        {$p["label"] = $av["label"]["value"];}        
      if (isset($av["child_label"])) 
        {$c["label"] = $av["child_label"]["value"];}        
      if (isset($av["childl2_label"])) 
        {$c2["label"] = $av["childl2_label"]["value"];}
        
      // Titles
      if (isset($av["title"])) 
        {$p["title"] = $av["title"]["value"];}        
      if (isset($av["child_title"]))      
        {$c["title"] = $av["child_title"]["value"];}        
      if (isset($av["childl2_title"]))      
        {$c2["title"] = $av["childl2_title"]["value"];}
      
      //notes
      if (isset($av["child_note"]))
        {$c["note"] = $av["child_note"]["value"];}
      if (isset($av["childl2_note"]))
        {$c2["note"] = $av["childl2_note"]["value"];}        
        
      // content
      if (isset($av["child_content"]))
        {$c["content"][$av["child_content"]["xml:lang"]] = $av["child_content"]["value"];}
      if (isset($av["childl2_content"]))
        {$c2["content"][$av["childl2_content"]["xml:lang"]] = $av["childl2_content"]["value"];}
         
      // thumbs
      if (isset($av["child_thumb"]))
        {$c["thumbs"][$av["child_caption"]["value"]] = array (
          "uri" => $av["child_thumb"]["value"],
          "caption" => $av["child_caption"]["value"]);}
      if (isset($av["childl2_thumb"]))
        {$c2["thumbs"][$av["childl2_caption"]["value"]] = array (
          "uri" => $av["childl2_thumb"]["value"],
          "caption" => $av["childl2_caption"]["value"]);}        
          
      if (!isset($texts[$p["label"]]))
        {$texts[$p["label"]] = $p;}
        
      if (!isset($texts[$p["label"]]["children"][$c["label"]]) and $c["label"])
        {$texts[$p["label"]]["children"][$c["label"]] = $c;}
      else if ($c["label"])
        {
        $texts[$p["label"]]["children"][$c["label"]]["content"] = 
          array_merge ($texts[$p["label"]]["children"][$c["label"]]["content"], $c["content"]);
        $texts[$p["label"]]["children"][$c["label"]]["thumbs"] = 
          array_merge ($texts[$p["label"]]["children"][$c["label"]]["thumbs"], $c["thumbs"]);
        }
        
      if (!isset($texts[$p["label"]]["children"][$c["label"]]["children"][$c2["label"]]) and $c2["label"])
        {$texts[$p["label"]]["children"][$c["label"]]["children"][$c2["label"]] = $c2;}        
      else if ($c2["label"])
        {
        $texts[$p["label"]]["children"][$c["label"]]["children"][$c2["label"]]["content"] = 
          array_merge ($texts[$p["label"]]["children"][$c["label"]]["children"][$c2["label"]]["content"], $c2["content"]);
        $texts[$p["label"]]["children"][$c["label"]]["children"][$c2["label"]]["thumbs"] = 
          array_merge ($texts[$p["label"]]["children"][$c["label"]]["children"][$c2["label"]]["thumbs"], $c2["thumbs"]);
        }
      }

    ob_start(); 
    
    echo '<div class="row justify-content-center position-relative my-2 p-3 bg-light position-relative">';

    foreach ($texts as $l => $a)
      {
      echo "<h4>$a[title]</h4>";
      echo "<div class=\"accordion accordion-flush\" id=\"accordionFlushExample\">";

      foreach ($a["children"] as $lc => $ac)
        {
        echo <<<END
        <div class="accordion-item">
          <h2 class="accordion-header" id="flush-heading-$lc">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse-$lc" aria-expanded="false" aria-controls="flush-collapse-$lc">
              $ac[title]
            </button>
          </h2>
          <div id="flush-collapse-$lc" class="accordion-collapse collapse" aria-labelledby="flush-heading-$lc" data-bs-parent="#accordionFlushExample">
            <div class="accordion-body">
      
END;
        echo "<div class=\"container\">";
        if (isset($ac["content"]["en"]))
          {
          $enc = $ac["content"]["en"];
          echo <<<END
              <div class="row align-items-start bg-light p-2">
                <div class="col">
                  ${enc}@en
                </div>
END;
          unset ($ac["content"]["en"]);
          
          foreach ($ac["content"] as $n => $cc)
            {
            echo <<<END
                <div class="col">
                  ${cc}@${n}
                </div>
END;
            }          
          echo "</div>";
          }
        
        if ($ac["note"])
          {
          echo "
            <div class=\"row\">
              <div class=\"col blockquote-footer text-end\">
                $ac[note]
              </div>
            </div>            
            ";
          }  
          
        if ($ac["thumbs"])
          {
          echo "
            <div class=\"row\">
              <div class=\"col text-center\">
              ";
          foreach ($ac["thumbs"] as $tn => $ta)
            {echo "<img src='$ta[uri]' style=\"height:128px;width:auto;\" alt='$ta[caption]'>";}
          echo "</div></div>";
          }
        echo "</div>";
         
        if ($ac["children"])
          {
           echo "<div class=\"accordion accordion-flush\" id=\"accordionFlushExample2-$lc\">";
          }
    
        foreach ($ac["children"] as $lc2 => $ac2)
          {
          echo <<<END
        <div class="accordion-item">
          <h2 class="accordion-header" id="flush-heading-$lc2">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse-$lc2" aria-expanded="false" aria-controls="flush-collapse-$lc2">
              $ac2[title]
            </button>
          </h2>
          <div id="flush-collapse-$lc2" class="accordion-collapse collapse" aria-labelledby="flush-heading-$lc2" data-bs-parent="#accordionFlushExample2-$lc">
            <div class="accordion-body">
      
END;
           
           
           echo "<div class=\"container\">";
           if (isset($ac2["content"]["en"]))
            {
            $enc2 = $ac2["content"]["en"];
            echo <<<END
              <div class="row align-items-start p-2">
                <div class="col">
                  ${enc2}@en
                </div>
END;
            unset ($ac2["content"]["en"]);
          
            foreach ($ac2["content"] as $n => $cc)
              {
              echo <<<END
                <div class="col">
                  ${cc}@${n}
                </div>
END;
              } 
            echo "</div>";
            }
          if ($ac2["note"])
            {
            echo "
              <div class=\"row\">
                <div class=\"col  blockquote-footer text-end\">
                  $ac2[note]
                </div>
              </div>            
            ";
          }
          else {"</br>";} 
          
          if ($ac2["thumbs"])
            {
            echo "
              <div class=\"row\">
                <div class=\"col text-center\">
                ";
            foreach ($ac2["thumbs"] as $tn => $ta)
              {echo "<img src='$ta[uri]' style=\"height:128px;width:auto;\" alt='$ta[caption]'>";}
            echo "</div></div>";
            }
          echo "</div>";
          echo "</div></div></div>";
          }
        if ($ac["children"])
          {echo '</div>';}
        echo "</div></div></div>";
        }
      echo "</div>";
      }
   
		echo "</div>";

    $extrahtml = ob_get_contents();
		ob_end_clean(); 
		
    $body = $extrahtml.$data["html"];   
    }
  else if ((isset($_GET["format"]) and $_GET["format"] == "json") or
    (isset($_POST["query-return"]) and $_POST["query-return"] == "json"))
    {header('Content-Type: application/json');
     header("Access-Control-Allow-Origin: *");
     echo $data["json"];
     exit;}
  else if (isset($_GET["format"]) and $_GET["format"] == "graph")
    {header("Content-Type: text/csv");
     header("Access-Control-Allow-Origin: *");
     echo $data["triples"];
     exit;}
  else
    {$body = $data["html"];}
  }
else
  {
  $im1c = "Detail of Raphael's, The Madonna of the Pinks (La Madonna dei Garofani), The National Gallery, London, NG6596, imaged 19/02/2004.";
  $im2c = "Detail of a sample taken from Raphael's, Portrait of Pope Julius II, The National Gallery, London, NG27, imaged 30/06/2005. - Mid green background from semi-circular feature along right hand edge which appears to have a white underlayer, just opposite the knotted gold thread on chair tassle, Sampled 21/03/2001, S15, Visible light, 220x 35mm).";
  ob_start();
	echo <<<END
  <div class="alert alert-primary w-80 mx-auto " role="alert">
    <div class="container">
      <div class="row">
        <div class="col">
          <img src="https://research.ng-london.org.uk/iiif/pics/tmp/raphael_pyr/N-6596/18.1_Visible_Light_Images/N-6596-00-000009-PYR.tif/pct:28,1,37.5,36/,192/0/default.jpg" class="float-start imgshadow rounded" alt="$im1c" title="$im1c" style="margin-right:10px;">        
          <p>This site presents data formatted as part of a task entitled <b>Issues in providing Open Data in Heritage Science</b> (T5.6) within the <a href="https://www.sshopencloud.eu/">SSHOC Horizon-2020 project</a>. This work, led by the <a href="https://www.nationalgallery.org.uk/">National Gallery</a>, has explored increasing the accessibility and interoperability of existing Heritage Science data sets and making them more <a href="https://www.go-fair.org/fair-principles/">FAIR</a>.</p>
          
          <p>The first dataset represents data held within in an old (2007) web-based digital platform, the <a href="https://cima.ng-london.org.uk/documentation/">Raphael Research Resource</a>. This system is based on a traditional relational database, containing a range of Heritage and Heritage Science data. This dataset has been fully mapped to an open standard ontology, creating a fully semantic, linkable, shareable,machine-readable FAIR dataset, with resolvable URIs. where possible. Its complex, bespoke ontology has been combined with a local Beta Persistent Identifier (PID) system and mapped to the standard <a href="https://www.cidoc-crm.org/">CIDOC-CRM ontology</a> and other external Linked Open Data resources. For example, many of the keywords/terms within text, metadata and controlled lists have been indexed/described within a hierarchical structure and mapped to external open thesauri and vocabularies (eg <a href="http://vocab.getty.edu/">Getty Vocabularies</a>, <a href="https://www.wikidata.org/">WikiData</a>).</p>
          <img src="https://research.ng-london.org.uk/iiif/pics/tmp/raphael_pyr/N-0027/14_Microscopy/14.1_Cross_Sections/35mm/27_S15_Mid-green_from_semi_circular_feature_RH_background_edge_x220-PYR.tif/pct:20,25,37.5,56/,192/0/default.jpg" class="float-end imgshadow rounded" alt="$im2c" title="$im2c"  style="margin:10px 0px 10px 10px;">
          <p>The second dataset, describe <a href="https://doi.org/10.5281/zenodo.5838339">here</a>, comes from another web-based digital platform, developed in the <a href="http://www.iperionch.eu/">IPERION-CH Horizon-2020 project</a> to present data in relation to the preparation layers used in 16​th​-century Italian paintings. This dataset has also been mapped to the CIDOC-CRM and linked to external vocabularies. At this time it has not yet been possible to fully open up the original web-based platform, but in relation to the work in this task a new version of it, complete with data and images released under a creative commons licence, has been created based on <a href="https://research.ng-london.org.uk/iperion-smk/">Italian and Dutch paintings from the collection of the National Gallery of Denmark (SMK)</a>.</p>
          
          <p>Direct access to both of these datasets is provided via open <a href="https://en.wikipedia.org/wiki/SPARQL">SPARQL</a> end-points, which allows users to perform queries or searches using the SPARQL query language. A series of detailed example queries, including some with subsequent presentation demonstrations have been provided on this site for the first dataset along with the ability to edit and create new queries. These queries also act to demonstrate how the CIDOC CRM has been used to model the data. At this time access to the second dataset is provided via a simple example query, which can also be edited to create new bespoke queries and data presentations.</p>         
        </div>
      </div>
      <div class="row">
        <div class="d-grid gap-2 col-6 mx-auto">
        
        
          <a role="button" title="Example SPARQL search" style="" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#examplesModal" data-bs-placement="right" data-bs-original-title="Example SPARQL search"><i class="bi-bookmarks fs-3 item-light text-light"> - Explore example Queries</i></a>

        
        </div>
      </div>        
    </div>
  </div>
  <div class="alert alert-light w-75 mx-auto" role="alert">
    <div class="container">
      <div class="row">
        <div class="col-12">
          <div class="box w-100 text-center"><img src="./graphics/sshoc-eu-tag.png" style="height:48px;" alt="SSHOC, (Social Sciences and Humanities Open Cloud), has received funding from the European Union’s Horizon 2020 project call H2020-INFRAEOSC-04-2018, grant agreement #823782" class="img-fluid my-3 mx-auto"></div>
        </div>
      </div>        
    </div>
  </div>   
END;
	$body = ob_get_contents();
  ob_end_clean(); // Don't send output to client";
  
  $pd["home"] = getBSButton ("home", "", false);
  }
  
$pd["body"] = $body.$imod.$emod;
    
$bts = array("examples", "search", "search2", "info" );
 
foreach ($bts as $bk => $bt)
  {$pd["buttons"] .= getBSButton ($bt);}
			      
$pd["console"] = "console.log(".json_encode($console).");";

echo buildPage ($pd);

?>
