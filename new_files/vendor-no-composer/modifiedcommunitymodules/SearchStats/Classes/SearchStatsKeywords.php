<?php

namespace ModifiedCommunityModules\SearchStats\Classes;

class SearchStatsKeywords {

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

?>