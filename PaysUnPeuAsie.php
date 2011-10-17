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
  private $imp;
  private $dtd;
  private $domFinal;
  
  // Entête
  private $domListePays;

  //Démarrage du document XML
  function startDocument() {
  
    // Création d'une instance de la classe DOMImplementation pour créer une instance de DomDocumentType (dtd)
    $this->imp = new DOMImplementation();
    
    // Création d'une instance DOMDocumentType (dtd)
    $this->dtd = $this->imp->createDocumentType('liste-pays', '', 'liste-pays.dtd');
    
    // Création d'une instance DOMDocument qui devra respecter la dtd liste-pays.dtd
    $this->domFinal = $this->imp->createDocument("", "", $this->dtd);
    
    //Encodage en UTF-8
    $this->domFinal->encoding = 'UTF-8';
    
    //Pour avoir un XML formaté
    $this->domFinal->formatOutput = true;
    
    //On ecrit la racine
    $this->domListePays = $this->domFinal->createElement("liste-pays");
  } 
  
  //Fin du document XML
  function endDocument() {
    //On met la racine dans le domDocument
    $this->domFinal->appendChild($this->domListePays);
    
    //On valide avec la dtd
    $this->domFinal->validate();
    
    //On exporte le xml
    $this->domFinal->save('sortie/liste-pays-sax.xml');
    
    //On l'affiche
    echo $this->domFinal->saveXML();
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
      $this->createPays();
      $this->paysEnAsieMaisPasCompletement_PasACentPourcents = false;
    }
  }
  
  //Des qu'on rencontre une chaine de caractère
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
  
  //Fonction qui génère un noeud pour un pays (en xml)
  function createPays() {    
    $domPays = $this->domFinal->createElement('pays');
    $domPays->setAttribute('nom', $this->nomPays);
    $domPays->setAttribute('capitale', $this->capitale);
    $domPays->setAttribute('proportion-asie', $this->proportionAsie);
    $domPays->setAttribute('proportion-autres', $this->proportionAutres);
    $this->domListePays->appendChild($domPays);
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