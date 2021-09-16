<?php
if (!defined('MODULE_MCM_SEARCHSTATS_STATUS') && (MODULE_MCM_SEARCHSTATS_STATUS != 'true')) {
    return;
}

use ModifiedCommunityModules\SearchStats\Classes\SearchStatsPublic;
require_once DIR_FS_DOCUMENT_ROOT . '/vendor-no-composer/autoload.php';

if (basename($PHP_SELF) == FILENAME_ADVANCED_SEARCH_RESULT && isset($keywords)) {
    // @t10: Search Stats
    // @t10: make sure not to count "searches" originating from backend
    ///////////////////////////////
    if (MODULE_MCM_SEARCHSTATS_ADMIN_COUNT == 'false') {
        if ($_SESSION['customers_status']['customers_status_id'] != DEFAULT_CUSTOMERS_STATUS_ID_ADMIN) {
            $t10_stats = new SearchStatsPublic($keywords, $listing_split->number_of_rows);
            $t10_stats->save();
        }
    } elseif (MODULE_MCM_SEARCHSTATS_ADMIN_COUNT == 'true') {
        if (!isset($_GET['dnt'])) {
            $t10_stats = new SearchStatsPublic($keywords, $listing_split->number_of_rows);
            $t10_stats->save();
        }
    }
    //////////////////////
    // @t10: Search Stats END
}