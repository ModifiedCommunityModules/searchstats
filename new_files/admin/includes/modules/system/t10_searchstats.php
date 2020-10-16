<?php
/* -----------------------------------------------------------------------------------------
   $Id$

   modified eCommerce Shopsoftware
   http://www.modified-shop.org

   Copyright (c) 2009 - 2013 [www.modified-shop.org]
   -----------------------------------------------------------------------------------------
   Released under the GNU General Public License
   ---------------------------------------------------------------------------------------*/

defined( '_VALID_XTC' ) or die( 'Direct Access to this location is not allowed.' );

class t10_searchstats
{
    var $code, $title, $description, $enabled;

    function __construct() 
    {
        $this->code = 't10_searchstats';
        $this->title = MODULE_T10_SEARCHSTATS_TEXT_TITLE;
        $this->description = MODULE_T10_SEARCHSTATS_TEXT_DESCRIPTION;
        $this->sort_order = ((defined('MODULE_T10_SEARCHSTATS_SORT_ORDER')) ? MODULE_T10_SEARCHSTATS_SORT_ORDER : '');
		$this->enabled = ((defined('MODULE_T10_SEARCHSTATS_STATUS') && MODULE_T10_SEARCHSTATS_STATUS == 'true') ? true : false);
    }

    function process($file) 
    {
        //do nothing
    }

    function display() 
    {
        return array('text' => '<br>' . xtc_button(BUTTON_SAVE) . '&nbsp;' .
                               xtc_button_link(BUTTON_CANCEL, xtc_href_link(FILENAME_MODULE_EXPORT, 'set=' . $_GET['set'] . '&module='.$this->code))
                     );
    }

    function check() 
    {
        if(!isset($this->_check)) {
          $check_query = xtc_db_query("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'MODULE_T10_SEARCHSTATS_STATUS'");
          $this->_check = xtc_db_num_rows($check_query);
        }
        return $this->_check;
    }

    function install() 
    {
        xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value,  configuration_group_id, sort_order, set_function, date_added) VALUES ('MODULE_T10_SEARCHSTATS_STATUS', 'true',  '6', '1', 'xtc_cfg_select_option(array(\'true\', \'false\'), ', now())");
		xtc_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_key, configuration_value,  configuration_group_id, sort_order, set_function, date_added) VALUES ('MODULE_T10_SEARCHSTATS_ADMIN_COUNT', 'true',  '6', '1', 'xtc_cfg_select_option(array(\'true\', \'false\'), ', now())");
		
        xtc_db_query("CREATE TABLE IF NOT EXISTS ".TABLE_T10_SEARCHSTATS." (
					  id int(11) NOT NULL AUTO_INCREMENT,
					  crdate int(11) NOT NULL COMMENT 'Record created',
					  tstamp int(11) NOT NULL COMMENT 'Record touched',
					  `query` varchar(128) NOT NULL,
					  searches int(11) NOT NULL,
					  products int(11) NOT NULL,
					  PRIMARY KEY (id),
					  UNIQUE KEY `query` (`query`)
					) ENGINE=MyISAM  DEFAULT CHARSET=utf8");
		xtc_db_query("ALTER TABLE ".TABLE_ADMIN_ACCESS." ADD `t10_searchstats` INT( 1 ) NOT NULL DEFAULT '0'");
		xtc_db_query("UPDATE ".TABLE_ADMIN_ACCESS." SET `t10_searchstats` = 1 WHERE customers_id = 1 LIMIT 1;");
    }

    function remove()
    {
        xtc_db_query("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key LIKE 'MODULE_T10_SEARCHSTATS_%'");
        xtc_db_query("DROP TABLE " . TABLE_T10_SEARCHSTATS);
		xtc_db_query("ALTER TABLE " . TABLE_ADMIN_ACCESS." DROP COLUMN `t10_searchstats`");
    }

    function keys() 
    {
		return array('MODULE_T10_SEARCHSTATS_STATUS', 
					 'MODULE_T10_SEARCHSTATS_ADMIN_COUNT'
					 );
    }    
}
?>