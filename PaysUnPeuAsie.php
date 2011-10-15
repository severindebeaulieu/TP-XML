<?php 

header('Content-type: text/xml');
include_once('sources/Sax4PHP.php');

class PaysUnPeuAsie extends DefaultHandler {

  private $paysEnAsieMaisPasCompletement_PasACentPourcents = false;
  private $dansCountryAsie = false;
  private $dansCountry = false;
  private $dansNameCountry = false;
  private $unIndien = false; // dans la ville...
  private $dansNameCity = false;
  private $nomPays = '';
  private $capitale = '';
  private $proportionAsie = 0;
  private $proportionAutres = 0;

  //Démarrage du document XML
  function startDocument() {
    echo '<?xml version="1.0" encoding="utf-8"?>';
    echo '<!DOCTYPE liste-pays SYSTEM "liste-pays.dtd">';
    echo '<liste-pays>';
  } 
  
  //Fin du document XML
  function endDocument() {
    echo '</liste-pays>';
  }
  
  //A chaque balise ouvrante rencontrée
  function startElement($nom, $att) {
    //On regarde le nom de la balise
    switch ($nom) {
    
      //Si on ouvre une balise country
      case 'country':
        $this->dansCountryAsie = true;
        $this->dansCountry = true;
        break;
        
      //Si on ouvre une balise name
      case 'name':
        if ($this->dansCountry === true && $this->dansNameCountry === false) {
          $this->dansNameCountry = true;
        } else if ($this->unIndien === true && $this->dansNameCity === false) {
          $this->dansNameCity = true;
        }
        
        break;
        
      //Si on ouvre une balise encompassed
      case 'encompassed':
        //Si le pays appartient au continent Asie mais pas à 100%
        if ($att['continent'] === 'asia' && $att['percentage'] > 0 && $att['percentage'] < 100) {
          $this->proportionAsie = $att['percentage'];
          $this->proportionAutres = 100 - $this->proportionAsie;
          $this->dansCountryAsie = false;
          $this->paysEnAsieMaisPasCompletement_PasACentPourcents = true;
        } else if ($this->dansCountryAsie === true ) {
          $this->proportionAsie = 0;
          $this->proportionAutres = 100;
        }
        break;
      
      //Si on ouvre une balise city
      case 'city':
         
        //On regarde si c'est la capitale du pays
        if(isset($att['is_country_cap']) && $att['is_country_cap'] === 'yes') {
          $this->unIndien = true;
        }
      
        
      default:
        break;
    }
  }
  
  //A chaque balise fermante rencontrée
  function endElement($nom) {
    if ($nom === 'country' && $this->paysEnAsieMaisPasCompletement_PasACentPourcents === true) {
      $this->printPays();
      $this->paysEnAsieMaisPasCompletement_PasACentPourcents = false;
    }
  }
  
  function characters($data) {
    if ($this->dansNameCountry === true) {
      $this->nomPays = $data;
      $this->dansCountry = false;
      $this->dansNameCountry = false;
    } else if ($this->dansNameCity === true) {
      $this->capitale = $data;
      $this->unIndien = false;
      $this->dansNameCity = false;
    }
  }
  
  function printPays() {
    $print = '<pays ';
    $print .= 'nom="'.$this->nomPays.'" ';
    $print .= 'capitale="'.$this->capitale.'" ';
    $print .= 'proportion-asie="'.$this->proportionAsie.'" ';
    $print .= 'proportion-autres="'.($this->proportionAutres).'" ';
    $print .= '/>';
    echo $print;
  }


}

$xml = file_get_contents('PackMondial/mondial.xml');
$sax = new SaxParser(new PaysUnPeuAsie());
try {
  $sax->parse($xml);
} catch (SAXException $e) {  
  echo "\n", $e;
}catch (Exception $e) {
  echo "Default exception >>", $e;
}

?>