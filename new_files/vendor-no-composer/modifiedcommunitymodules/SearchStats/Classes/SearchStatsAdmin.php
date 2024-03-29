<?php

namespace ModifiedCommunityModules\SearchStats\Classes;

class SearchStatsAdmin
{

    // the query from db
    public $query = [];
    public $lID = 0;

    // to handle non utf8 connection types
    private $character_set_client = '';
    
    public function __construct($query = '', $lID = 2)
    {

        if (!empty($query)) {
            $this->query = explode(' ', preg_replace('/[^a-z0-9äöüÖÜÄß\-\., ]+/i', '', strip_tags(trim($query))));
        }

        // make sure language stuff is done properly
        if (!empty($lID)) {
            $this->lID = (int) $lID;
        }

        // get the connections current charset
        $r = xtc_db_fetch_array(xtc_db_query("SHOW VARIABLES LIKE 'character_set_client'"));
        
        // save it to reset connection charset on destruction
        if (!empty($r['Value'])) {
            $this->character_set_client = $r['Value'];
        }

        // make sure this instances connection is utf8
        xtc_db_query('SET NAMES utf8');
    }


    public function __desctruct()
    {
        // restore default character set, if saved on construction
        if (!empty($this->character_set_client)) {
            xtc_db_query(sprintf('SET NAMES %s', $this->character_set_client));
        }
    }



    public function search($search)
    {
        if (empty($search)) {
            return false;
        }

        // make the search a little more open
        $search = explode(' ', trim($search));
        $w = array('pd.language_id = ' . $this->lID);

        if (!empty($search)) {
            // search for matches within product texts independent from
            // the order of fragments or words
            foreach ($search as $word) {
                $w[] = sprintf('pd.products_name LIKE "%%%s%%"', $word);
            }
        }

        // perform search
        $sql = xtc_db_query(sprintf('SELECT pd.products_id, pd.products_name FROM %s AS pd 
                                      WHERE %s', TABLE_PRODUCTS_DESCRIPTION, implode(' AND ', $w)));

        // return sad message, if nothing was found
        if (xtc_db_num_rows($sql) < 1) {
            return sprintf('<label class="no-result">%s</label>', sprintf(SEARCH_NO_RESULTS, implode(' ', $search)));
        }

        // render html for search result nicely
        // add a checkbox to select all checkboxes
        $return = array(sprintf('<label class="checkbox toggle"><input type="checkbox" class="toggle"> %s </label>', TOGGLE_CHECKBOX_LABEL));


        while ($r = xtc_db_fetch_array($sql)) {
            
            // get the keyword object to display initial keywords nicely
            // and to just have ONE place for keyword handling
            $k = new SearchStatsKeywords($r['products_id'], $this->lID);

            $resultingKeywords = $k->highlightKeywords($k->getKeywords(), $this->query);
            $resultingKeywords = (is_array($resultingKeywords) > 0) ? sprintf('<span class="keywords">%s</span>', implode($k->delimiter, $resultingKeywords)) : '';

            // products link

            $link = sprintf(FILENAME_CATEGORIES . '?cPath=0' . '&pID=' . $r['products_id'] . '&action=new_product'); 
            //$link = sprintf('%sproduct_info.php?%s', DIR_WS_CATALOG, http_build_query(array('products_id' => $r['products_id'])));
            $link = sprintf('<a href="%s" class="viewNewTab" title="%s">%s</a>', $link, sprintf(VIEW_IN_SHOP, $r['products_name']), $this->highlightSearchResult($r['products_name'], $search));


            // all found products
            $return[] = sprintf('<label class="checkbox" data-products-id="%1$u">
                                    <input type="checkbox" class="product" name="addProduct[%1$u]" value="%1$u">
                                    <span class="products-name">%2$s</span>
                                       %3$s
                                    </label>',
                                    $r['products_id'],
                                    $link,
                                    $resultingKeywords);

        }

        // add a "submit button"
        $return[] = sprintf('<input type="submit" id="submitProductAssignment" class="button" value="%s">', ASSIGN_PRODUCTS);
        $return[] = '<input type="hidden" name="assignQuery" value="1">';
        $return[] = sprintf('<input type="hidden" name="query" value="%s">', implode(' ', $this->query));



        // the url to submit the products to
        $formAction = sprintf('%s%s', DIR_WS_ADMIN, FILENAME_MODULE_T10_SEARCHSTATS);
        
        return sprintf('<form id="productAssignment" action="%s" method="post"><div class="message"></div>%s</form>', $formAction, implode(null, $return));
    }


    public function highlightSearchResult($s, $q = array())
    {
        return $s;
    }
}



?>