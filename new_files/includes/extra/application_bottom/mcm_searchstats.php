<?php
if (defined('MODULE_T10_SEARCHSTATS_STATUS') && (MODULE_T10_SEARCHSTATS_STATUS == 'true')) {

    if (basename($PHP_SELF) == FILENAME_ADVANCED_SEARCH_RESULT && isset($keywords)) {
    // @t10: Search Stats
    // @t10: make sure not to count "searches" originating from backend
    ///////////////////////////////
        if (MODULE_T10_SEARCHSTATS_ADMIN_COUNT == 'false') {
            if ($_SESSION['customers_status']['customers_status_id'] != DEFAULT_CUSTOMERS_STATUS_ID_ADMIN) {
                require_once(DIR_WS_CLASSES . 't10.searchstats.php');
            
                $t10_stats = new t10_searchstats($keywords, $listing_split->number_of_rows);
                $t10_stats->save();
            }
        } elseif (MODULE_T10_SEARCHSTATS_ADMIN_COUNT == 'true') {
            if (!isset($_GET['dnt'])) {
                require_once(DIR_WS_CLASSES . 't10.searchstats.php');
            
                $t10_stats = new t10_searchstats($keywords, $listing_split->number_of_rows);
                $t10_stats->save();
            }
        }
        //////////////////////
        // @t10: Search Stats END
    }
}