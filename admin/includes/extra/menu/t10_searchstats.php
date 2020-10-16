<?php
# @t10: Search Stats

defined( '_VALID_XTC' ) or die( 'Direct Access to this location is not allowed.' );

  //Sprachabhaengiger Menueeintrag, kann fuer weiter Sprachen ergaenzt werden
  switch ($_SESSION['language_code']) {
    case 'de':
      define('BOX_T10_SEARCHSTATS','Suchbegriffstatistik');
      break;
    case 'en':
      define('BOX_T10_SEARCHSTATS','Search statistics');
      break;  
    default:
      define('BOX_T10_SEARCHSTATS','Search statistics');
      break;
  }

  //BOX_HEADING_TOOLS = Name der box in der der neue Menueeintrag erscheinen soll
  $add_contents[BOX_HEADING_STATISTICS][] = array( 
    'admin_access_name' => 't10_searchstats',   //Eintrag fuer Adminrechte
    'filename' => 't10_searchstats.php',        //Dateiname der neuen Admindatei
    'boxname' => BOX_T10_SEARCHSTATS,     //Anzeigename im Menue
    'parameter' => '',                  //zusaetzliche Parameter z.B. 'set=export'
    'ssl' => ''                         //SSL oder NONSSL, kein Eintrag = NONSSL
    );
  
?>