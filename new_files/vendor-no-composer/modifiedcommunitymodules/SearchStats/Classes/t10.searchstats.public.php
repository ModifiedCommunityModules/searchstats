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

class t10_searchstats
{
    public $query = '';
    public $numProducts = 0;
    public $now;


    public function __construct($query, $numProducts = 0)
    {
        // assign and clean this query
        if (!empty($query) && strlen($query) < 70) {
            $this->query = strip_tags(trim(xtc_db_input($query)));
        }

        // since the number of found products depends on the 
        // query build by advanced_search_result.php and that
        // one is arwfully complex, therefore the number of products 
        // found is supplied to the class as well. Also to match
        // exactly the number of products found by the customer
        if (!empty($numProducts)) {
            $this->numProducts = (int) $numProducts;
        }

        // consistent timestamp for all actions
        $this->now = time();

    }


    // save the queryn to database
    public function save()
    {

        if (empty($this->query)) {
            return false;
        }
    
        function crawlerDetect($USER_AGENT) // TODO: outsource function
        {
            $crawlers_agents = 'AdsBot-Google|AdsBot-Google-Mobile|AdsBot|Google|GoogleBot|Googlebot|TwengaBot|Bot|Spider|spider|Crawler|crawler';
            $crawlers = explode('|', $crawlers_agents);
        
            foreach ($crawlers as $crawler) {
                if (strpos($USER_AGENT, $crawler) !== false) {
                    return true;
                }
            }
            return false;
        }

        if (crawlerDetect($_SERVER['HTTP_USER_AGENT']) === true) {
            return false;
        }

        // check if the current query is already in table
        $r = $this->getQuery($this->query);

        // there is already a record for the current query
        if ($r !== false) {

            // alter some things
            $r['tstamp'] = $this->now;
            $r['products'] = $this->numProducts;

            // counter
            $r['searches'] ++;

            // update 
            xtc_db_perform(TABLE_T10_SEARCHSTATS, $r, 'update', 'id=' . $r['id']);
        } else {

            // data for new record
            $data = [
                'crdate' => $this->now,
                'tstamp' => $this->now,
                //'query' => $this->query,
                'query' => stripslashes($this->query),
                'searches' => 1,
                'products' => $this->numProducts
            ];

            // insert
            xtc_db_perform(TABLE_T10_SEARCHSTATS, $data);
        }

        // no matter what!
        return true;
    }


    private function getQuery($query = null)
    {
        if (empty($query)) {
            return false;
        }

        $q = xtc_db_query(sprintf('SELECT * FROM %s WHERE query="%s"', TABLE_T10_SEARCHSTATS, $query));
        $n = xtc_db_num_rows($q);
        $r = xtc_db_fetch_array($q);

        if ($n > 0) {
            return $r;
        }

        return false;
    }

}