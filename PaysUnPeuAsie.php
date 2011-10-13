<?php 

header('Content-type: text/xml');
include_once('sources/Sax4PHP.php');

class PaysUnPeuAsie extends DefaultHandler {

  private $jeSuisDansPays = false;
  private $nomPays = '';
  private $jeSuisDansNomPays = false;
  private $idCapital = '';

  //Démarrage du document XML
  function startDocument() {
    echo '<?xml version="1.0" encoding="utf-8"?>';
    echo '<!DOCTYPE mondial SYSTEM "liste-pays.dtd">';
    echo '<liste-pays>';
  } 
  
  //Fin du document XML
  function endDocument() {
    echo '</liste-pays>';
  }
  
  //A chaque balise ouvrante rencontrée
  function startElement($nom, $att) {
    //On regarde le nom de la balise
    switch $nom {
    
      //Si on ouvre une balise country
      case 'country':
        //On indique qu'on se trouve dans un pays
        $this->jeSuisDansPays = true;
        
        //On stock l'id de la capitale
        if ($att['capital']) {
          $this->idCapital = $att['capital'];
        } else {
          $this->idCapital = '';
        }
        break;
        
      //Si on ouvre une balise name
      case 'name':
        if ($this->jeSuisDansPays === true) {
          $this->jeSuisDansNomPays = true;
        }
        break;
        
      //Si on ouvre une balise encompassed
      case 'encompassed':
        //Si le pays appartient au continent Asie mais pas à 100%
        if ($att['continent'] === 'asia' && $att['percentage'] > 0 && $att['percentage'] > 100) {
          $this->proportionAsie = $att['percentage'];
          $this->proportionAutres = 100 - $this->proportionAsie;
        }
        break;
      
      //Si on ouvre une balise   
      case '':
        
      default:
        break;
    }
  }
  
  //A chaque balise fermante rencontrée
  function endElement($nom) {
    switch $nom {
      case 'country':
        
        break;
      default:
        break;
    }
  }
  
  function characters($data) {
    if ($this->jeSuisDansNomPays === true) {
      $this->nomPays = $data;
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