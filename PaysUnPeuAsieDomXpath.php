<?php

  //On charge le fichier mondial.xml
  $xml = simplexml_load_file("PackMondial/mondial.xml");
  
  // Création d'une instance de la classe DOMImplementation pour créer une instance de DomDocumentType (dtd)
  $imp = new DOMImplementation;
  
  // Création d'une instance DOMDocumentType (dtd)
  $dtd = $imp->createDocumentType('liste-pays', '', 'liste-pays.dtd');
  
  // Création d'une instance DOMDocument qui devra respecter la dtd liste-pays.dtd
  $domFinal = $imp->createDocument("", "", $dtd);
  
  //Encodage en UTF-8
  $domFinal->encoding = 'UTF-8';
  
  //Pour avoir un XML formaté
  $domFinal->formatOutput = true;

  //Définition des variables pour un pays
  $nomPays = '';
  $capitale = '';
  $proportionAsie = 0;
  $proportionAutres = 0;
  
  //On ecrit l'entete
  $domListePays = $domFinal->createElement("liste-pays");
  
  //On récupère tous les pays
  $listePays = $xml->xpath("//country");
  
  //On traite chaque pays
  foreach($listePays as $pays) {
  
    //On regarde pour chaque pays ses continents
    $encompassedList = $pays->xpath('//encompassed');
    
    foreach($encompassedList as $encompassed) {
      
      if (isset($encompassed['continent']) && isset($encompassed['percentage'])) {

        //On stock le continent et le pourcentage
        $continent = $encompassed['continent'];
        $percentage = $encompassed['percentage'];
        
        //On regarde si le continent est l'asie et si le pourcentage est entre 0 et 100 (pas complètement en Asie)
        if ($continent == 'asia' && $percentage > 0 && $percentage < 100) {

          //On retient les pourcentages pour la génération du XML
          $proportionAsie = $percentage;
          $proportionAutres = 100 - $percentage;
          
          //On retient le nom du pays
          $nomPays = $pays->xpath('//name');
          echo $nomPays[0];
          
          //On regarde les villes pour déterminer la capitale
          $cityList = $pays->getElementsByTagName('city');
          
          //On regarde quelle ville est la capitale du pays
          foreach($cityList as $city) {
            if ($city->hasAttribute('is_country_cap') && $city->getAttribute('is_country_cap') === 'yes') {
            
              $capitale = $city->getElementsByTagName('name')->item(0)->nodeValue;
              
              //On ajoute le pays au domDocument
              $domPays = $domFinal->createElement('pays');
              $domPays->setAttribute('nom', $nomPays);
              $domPays->setAttribute('capitale', $capitale);
              $domPays->setAttribute('proportion-asie', $proportionAsie);
              $domPays->setAttribute('proportion-autres', $proportionAutres);
              $domListePays->appendChild($domPays);
                 
            }
          }           
        }
      }
    }
  }
  
  //On met la racine dans le domDocument
  $domFinal->appendChild($domListePays);
  
  //On valide avec la dtd
  $domFinal->validate();
  
  //On exporte le xml
  $domFinal->save('sortie/liste-pays.xml');
  
  //On l'affiche
  echo $domFinal->saveXML();
  

?>