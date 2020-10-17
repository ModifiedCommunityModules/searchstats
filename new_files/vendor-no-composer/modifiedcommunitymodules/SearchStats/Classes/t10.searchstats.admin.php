<?php
/*-----------------------------------------------------------------
*  __           __             ___     _     __
* /\ \__       /\ \__         /\_ \  /' \  /'__`\
* \ \ ,_\   ___\ \ ,_\    __  \//\ \/\_, \/\ \/\ \
*  \ \ \/  / __`\ \ \/  /'__`\  \ \ \/_/\ \ \ \ \ \
*   \ \ \_/\ \L\ \ \ \_/\ \L\.\_ \_\ \_\ \ \ \ \_\ \
*    \ \__\ \____/\ \__\ \__/.\_\/\____\\ \_\ \____/
*     \/__/\/___/  \/__/\/__/\/_/\/____/ \/_/\/___/
*     
* 
******************************************************************
              e46d0505ce4c61e03062d256a9ea109d 
******************************************************************
*
* Date:       10.02.2014
* Author:     total10 UG / info@t10.de
*
* total10 UG (haftungsbeschränkt)
* Gänsemarkt 43
* 20354 Hamburg
*
* http://www.t10.de
* info@t10.de
* +49 (0)40 4191 3355
*
* Copyright (c) 2014 total10 UG (haftungsbeschränkt)
* 
* Released under the GNU General Public License
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
* See the GNU General Public License for more details.
*
* http://j.mp/1fLHb9V
*
* ------------------------------------------------------------------
*/

class t10_productKeywords {

    public $pID = 0;
    public $lID = 0; // defaults to german
    public $delimiter = false;
    public $possibleDelimiters = [',', ' '];

    // to handle non utf8 connection types
    private $character_set_client = '';

    public function __construct($pID, $lID = 2)
    {
        if (!empty($pID)) {
            $this->pID = (int) $pID;
        }

        // make sure language stuff is done properly
        if (!empty($lID)) {
            $this->lID = (int) $lID;
        }

        // get the connections current charset
        $r = xtc_db_fetch_array(xtc_db_query("SHOW VARIABLES LIKE 'character_set_client'"));
        
        // save it to reset connection charset on destruction
        if ($r['Variable_name'] == 'character_set_client' && !empty($r['Value'])) {
            $this->character_set_client = $r['Value'];
        }

        // make sure this instances connection is utf8
        xtc_db_query('SET NAMES utf8');

        // default delimiter, 
        // getDelimiter returns at least the first of the possible delimiters
        $this->delimiter = $this->getDelimiter('');
    }

    public function __desctruct()
    {
        // restore default character set, if saved on construction
        if (!empty($this->character_set_client)) {
            xtc_db_query(sprintf('SET NAMES %s', $this->character_set_client));
        }
    }

    public function getKeywords($pID = 0)
    {
        // delivers keywords for any pID given or current instance's pID 
        $pID = (!empty($piD) && (int) $pID > 0) ? (int) $pID : $this->pID;

        $q = xtc_db_query(sprintf('SELECT products_id, products_keywords FROM %s WHERE products_id = %u AND language_id=%u ', TABLE_PRODUCTS_DESCRIPTION, $pID, $this->lID));
        $r = xtc_db_fetch_array($q);

        if (empty($r) || empty($r['products_keywords'])) {
            return [];
        }

        // get the keywords delimiter aka the admins favorite delimiter to separate keywords 
        $this->delimiter = $this->getDelimiter($r['products_keywords']);

        // explode the thing! 
        $keywords = $this->parseKeywords($r['products_keywords']);

        return $keywords;
    }


    public function saveKeywords($pID, $s)
    {
        if (empty($pID)) {
            return false;
        }

        // this one sets the delimiter to whatever the admin prefered for this product
        $oldKeywords = $this->getKeywords($pID);
        // this one gets each keyword from search string, using any delimiter known
        $newKeywords = $this->parseKeywords($s);
        // combine old and new keywords, make sure values are unique
        // since the front end search is case insensitive, make sure to treat
        // keyword, KeyWord, KEYWORD (...) as one
        $keywords = $this->array_iunique(array_merge($oldKeywords, $newKeywords));
        
        // build data to update record
        $data = array('products_keywords' => implode($this->delimiter, $keywords));
        $update = xtc_db_perform(TABLE_PRODUCTS_DESCRIPTION, $data, 'update', sprintf('products_id=%u AND language_id=%u', $pID, $this->lID));

        // make a nice display from this
        $resultingKeywords = $this->highlightKeywords($oldKeywords, $newKeywords);

        return [
            'success' => $update ? true : false, 
            'keywordString' => implode($this->delimiter, $resultingKeywords)
        ];
    }

    public function highlightKeywords($old, $new)
    {
        if (empty($new)) {
            return [];
        }

        $old = $this->parseKeywords($old);
        $new = $this->parseKeywords($new);

        $keywords = $this->array_iunique(array_merge($old, $new));
        $resultingKeywords = [];
        

        foreach ($keywords as $k) {
            // highlight this as new keyword
            if ($this->in_iarray($k, $new)) {
                $resultingKeywords[$k] = sprintf('<span class="keyword new-keyword" title="%s"> %s</span>', sprintf(NEW_KEYWORDS_LABEL, $k), $k); 
            }

            // if it is "also" old, overwrite this
            if ($this->in_iarray($k, $old)) {
                if ($this->in_iarray($k, $new)) {
                    $resultingKeywords[$k] = sprintf('<span class="keyword duplicate-keyword" title="%s"> %s</span>', sprintf(OLD_KEYWORDS_LABEL, $k), $k);
                } else {
                    $resultingKeywords[$k] = sprintf('<span class="keyword old-keyword"> %s</span>', $k);
                }
            }
        }

        return $resultingKeywords;
    }


    public function parseKeywords($s)
    {
        // in case an array has been given implode that
        if (is_array($s)) {
            $s = implode($this->delimiter, $s);
        }

        if (empty($s)) {
            return [];
        }

        $d = implode(' ', $this->possibleDelimiters);
        $tok = strtok($s, $d);
        $keywords = [];

        while ($tok !== false) {
            $keywords[] = $tok;
            $tok = strtok($d);
        }

        // return an array of clean keywords
        return $keywords;
    } 


    public function getDelimiter($s)
    {
        // the returing delimiter
        $foundDelimiter = false;

        foreach ($this->possibleDelimiters as $p) {
            $foundDelimiter = $p;

            // if the current delimiter $p is found in haystack $s break loop
            if (strstr($s, $p)) {
                break;
            }
        }

        return $foundDelimiter;
    }


    private function in_iarray($s, $a)
    {    
        foreach($a as $v) {
            if (strcasecmp($s, $v) == 0) {
                return true;
            }
        }

        return false;
    }


    private function array_iunique($a)
    {
        $n = [];
        
        foreach ($a as $k => $v) {        
            if (!$this->in_iarray($v, $n)) {
                $n[$k]=$v;
            }
        }
        return $n;
    }
}


class t10_searchstats
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
            $k = new t10_productKeywords($r['products_id'], $this->lID);

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