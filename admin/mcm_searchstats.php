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
* t10.de
* 
******************************************************************
              e46d0505ce4c61e03062d256a9ea109d 
******************************************************************
*
* Date:       07.03.2014
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

require('includes/application_top.php');
require_once(DIR_WS_CLASSES . 'split_page_results.php');
require_once(DIR_WS_CLASSES . 't10.searchstats.php');

// product assignment via ajax
if (isset($_POST['assignQuery'])) {

	// uncached json header
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Tue, 10 Jul 1984 13:37:00 GMT');
	header('Content-type: application/json');

	$return = array('success' => false, 'msg' => '');	

	if (empty($_POST['addProduct']) || !is_array($_POST['addProduct'])) {
		$return['msg'] = ERROR_MSG_NO_PRODUCTS;
		die(json_encode($return));
	}

	if (empty($_POST['query'])) {
		$return['msg'] = ERROR_MSG_NO_QUERY;
		die(json_encode($return));
	}


	foreach ($_POST['addProduct'] as $pID) {
		$k 				= new t10_productKeywords($pID, $_SESSION['languages_id']);
		$return[$pID] 	= $k->saveKeywords($pID, strip_tags(trim($_POST['query'])));	
	}

	$return['success'] 	= true;
	$return['msg'] 		= sprintf(SUCCESS_MSG_PRODUCTASSIGNMENT, count($_POST['addProduct']));

	die(json_encode($return));
}

// truncate the list of recorded searches
if (isset($_POST['truncate']) && (int) $_POST['truncate'] == 1) {
	$q = xtc_db_query(sprintf('TRUNCATE TABLE %s', TABLE_T10_SEARCHSTATS));
	xtc_redirect(FILENAME_MODULE_T10_SEARCHSTATS);
}

// product search via ajax
if (isset($_GET['productSearch'])) {

	$search = new t10_searchstats($_GET['query'], $_SESSION['languages_id']);

	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Tue, 10 Jul 1984 13:37:00 GMT');
	header('Content-type: text/html; charset=utf-8');
	die($search->search($_GET['q']));

}

// whitelist possible order fields
$orderFields 		= array('query' 	=> QUERY, 
                       		'searches' 	=> SEARCHES,
                       		'crdate' 	=> CRDATE, 
                       		'tstamp' 	=> TSTAMP, 
                       		'products' 	=> PRODUCTS
                       		);

$orderDirections 	= array('desc', 'asc');

// assign session values, use whitelists from above
if (isset($_GET['orderField']) && in_array($_GET['orderField'], array_keys($orderFields)))
	$_SESSION['t10']['filter_stats']['orderField'] = $_GET['orderField'];

if (isset($_GET['orderDirection']) && in_array($_GET['orderDirection'], $orderDirections))
	$_SESSION['t10']['filter_stats']['orderDirection'] = $_GET['orderDirection'];

// set defaults for both
if (empty($_SESSION['t10']['filter_stats']['orderField']))
	$_SESSION['t10']['filter_stats']['orderField'] = 'searches';

if (empty($_SESSION['t10']['filter_stats']['orderDirection']))
	$_SESSION['t10']['filter_stats']['orderDirection'] = current($orderDirections);


// select all search phrases from table
$q 		= sprintf('SELECT * FROM %s ORDER BY %s %s', TABLE_T10_SEARCHSTATS, $_SESSION['t10']['filter_stats']['orderField'], $_SESSION['t10']['filter_stats']['orderDirection']);
$qSplit = new splitPageResults($_GET['page'], '20', $q, $qNumRows);
$q 		= xtc_db_query($q);

  require (DIR_WS_INCLUDES.'head.php');
?>
<link rel="stylesheet" type="text/css" href="includes/css/t10.searchstats.css">
</head>
<body>
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->
<div data-project="landingpagemanager" data-version="0.1" data-key="<?php echo TAG_KEY; ?>">
<!-- body //-->
<table border="0" width="100%" cellspacing="2" cellpadding="2">
  <tr>
    <td class="columnLeft2" width="<?php echo BOX_WIDTH; ?>" valign="top">
<!-- left_navigation //-->
<?php require(DIR_WS_INCLUDES . 'column_left.php'); ?>
<!-- left_navigation_eof //-->
    </td>
<td class="boxCenter" width="100%" valign="top">

<table border="0" width="100%" cellspacing="0" cellpadding="2">
<tr><td>
<?php if (xtc_db_num_rows($q) > 0):?>
<form action="<?php echo FILENAME_MODULE_T10_SEARCHSTATS; ?>" method="post">
<a href="#" class="truncate button fr" data-msg="<?php echo TRUNCATE; ?>"><?php echo TRUNCATE_LABEL; ?></a>
<input type="hidden" name="truncate" value="1">
</form>
<?php endif; ?>

<div class="pageHeading"><?php echo HEADING_TITLE; ?></div>
</td></tr>

<tr>
<td valign="top">
<div class="productSearch" style="display:none;">
<form action="<?php echo sprintf('%s?%s', FILENAME_MODULE_T10_SEARCHSTATS, http_build_query(array('productSearch' => true))); ?>" method="get" class="productSearch">
<input type="search" name="q" placeholder="<?php echo SEARCH_FILED_PLACEHOLDER; ?>">
<input type="hidden" name="query" value="">
<input type="submit" class="go" value="<?php echo SEARCH_BUTTON_LABEL; ?>">
</form>
<div class="search-result"></div>
</div>
<!--div class="LPMInfo" style="display: none;">
<a href="#" class="close top">&times;</a>

<h1><a href="http://j.mp/1g5d8MN" target="_blank">t10: Landingpagemanager</a></h1>
<p>Die Option Landingpages aus dieser &Uuml;bersicht heraus zu erstellen, steht nur in Verbindung mit dem t10:Landingpagemanager zur Verf&uuml;gung.</p>

<p>Mit dem t10: Landingpagemanager:
<ul>
    <li>Stellen Sie beliebig passende Produkte, Kategorien und Texte zu einer Landingpage zusammen.</li>
    <li>Finden Sie neue Keywords und optimieren Ihren Shop darauf.</li>
    <li>Steigern Sie die Anzahl der Seiten im Google Index.</li>
    <li>Verbessern Sie die Conversionrate durch exakt zum Suchbegriff passende Landingpages.</li>
    <li>Verbessern Sie Ihre Positionen bei Google durch exakt zum verwendeten Keyword passende Landingpages.</li>
    <li>Werten Sie die interne Suche Ihres Shops aus und verbessern die Ergebnisse mit wenigen Klicks.</li>
    <li>Erstellen Sie relevante Zielseiten f&uuml;r AdWords, Newsletter oder Aktionen.</li>
    <li>Setzen Sie sich deutlich von Ihren Mitbewerbern ab, sowohl aus technischer, als auch aus Kundensicht!</li>
</ul>

<p><strong>Mehr Informationen und und ein Beispielvideo finden Sie im <a href="http://j.mp/1g5d8MN" target="_blank">total10 Modulshop</a>.</strong></p>
<p><a href="#" class="close">Meldung schlie&szlig;en</a></p>
</div-->

<table class="stats"><thead><tr>
<?php
// shorten
$currtenFilter 	= $_SESSION['t10']['filter_stats'];

foreach ($orderFields as $orderField => $label) {
// indicate the current direction 
$classes = array($currtenFilter['orderDirection']);
// keep the currently selected direction
$direction 	= $currtenFilter['orderDirection'];

	// if one clicks a second time on the same link
	// switch the direction
	if ($currtenFilter['orderField'] == $orderField) {
		$direction = ($currtenFilter['orderDirection'] == 'asc') ? 'desc' : 'asc';
		
		// highlight the current order field
		$classes[] = 'current';
	}


	// the links title to prevent confusion
	$title 				= sprintf(SORT_BY, (($direction == 'asc') ? SORT_BY_ASC : SORT_BY_DESC), $label);
	$links[$orderField] = sprintf('%s%s?%s', DIR_WS_ADMIN, FILENAME_MODULE_T10_SEARCHSTATS, http_build_query(array('orderField' => $orderField, 'orderDirection' => $direction)));
	$links[$orderField] = sprintf('<th class="%s"><a href="%s" title="%s">%s</a></th>', implode(' ', $classes), $links[$orderField], $title, $label);
}

// the options row
$links[] = '<th></th>';
echo implode(null, $links);
?>

</tr>
</thead>
<tbody>

<?php
$rows = array();
while ($r = xtc_db_fetch_array($q)) {

$url 		= sprintf('%sadvanced_search_result.php?%s', DIR_WS_CATALOG, http_build_query(array('keywords' => $r['query'], 'dnt' => true)));
$link 		= sprintf('<a href="%s" class="viewNewTab nw" title="%s">%s</a>', $url, sprintf(QUERY_TITLE_TEXT, $r['query']), $r['query']);
$options 	= array(
defined("FILENAME_MODULE_T10_TAGS") ? 
sprintf('<a href="%s" class="button">%s</a>', 
sprintf('%s?%s', FILENAME_MODULE_T10_TAGS, http_build_query(array('tag_id' => $r['query'], 'action' => 'new_tag'))), CREATE_LP) : 
sprintf('<!--a href="http://j.mp/1g5d8MN" class="button toggleLPMInfo">%s</a-->', CREATE_LP),
sprintf('<a href="%s" data-query="%s" class="button toggleSearch">%s</a>', '#', $r['query'], PRODUCT_ASSIGNEMNT)
				);

$row= array($link, 
			$r['searches'],
			date('d.m.Y H:i:s', $r['crdate']),
			date('d.m.Y H:i:s', $r['tstamp']),
			$r['products'],
			implode(null, $options));

// make it a nice row
$rows[] 	= sprintf('<td>%s</td>', implode('</td><td>', $row));
}

// output all build rows
echo sprintf('<tr>%s</tr>', implode('</tr><tr>', $rows));
?>

</tbody>
</table>
</td>
</tr>
<tr><td colspan="3">
<table border="0" width="100%" cellspacing="0" cellpadding="2">
<tr>
<td class="smallText" valign="top"><?php echo $qSplit->display_count($qNumRows, '50', (int) $_GET['page'], TEXT_DISPLAY_NUMBER_OF_PRODUCTS); ?></td>
<td class="smallText" align="right"><?php echo $qSplit->display_links($qNumRows, '50', MAX_DISPLAY_PAGE_LINKS, (int) $_GET['page']); ?></td>
</tr>
</table>
</td></tr></table>
</td></tr></table>
</div>

<script src="includes/javascript/t10.searchstats.js" /></script>

<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br />
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>