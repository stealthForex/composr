<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2016

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core
 */

/**
 * Standard code module initialisation function.
 *
 * @ignore
 */
function init__sitemap_xml()
{
    require_code('xml');

    require_code('sitemap');

    define('URLS_PER_SITEMAP_SET', 250); // Limit is 50,000, but we are allowed up to 50,000 sets, so let's be performant here and have small sets
}

/**
 * Top level function to (re)generate a Sitemap (xml file, Google-style).
 */
function sitemap_xml_build()
{
    $last_time = intval(get_value('last_sitemap_time_calc_inner', null, true));
    $time = time();

    // Build from sitemap_cache table
    $set_numbers = $GLOBALS['SITE_DB']->query_select('sitemap_cache', array('DISTINCT set_number'), null, ' WHERE last_updated>=' . strval($last_time));
    if (count($set_numbers) > 0) {
        foreach ($set_numbers as $set_number) {
            rebuild_sitemap_set($set_number['set_number'], $last_time);
        }

        // Delete any nodes marked for deletion now they've been reflected in the XML
        $GLOBALS['SITE_DB']->query_delete('sitemap_cache', array(
            'is_deleted' => 1,
        ));

        // Rebuild index file
        rebuild_sitemap_index();

        // Ping search engines
        ping_sitemap_xml(get_custom_base_url() . '/data_custom/sitemaps/index.xml');
    }

    set_value('last_sitemap_time_calc_inner', strval($time), true);
}

/**
 * Write out a Sitemap XML set.
 *
 * @param  integer $set_number Set number
 * @param  TIME $last_time Last sitemap generation time
 */
function rebuild_sitemap_set($set_number, $last_time)
{
    // Open
    $sitemaps_out_temppath = cms_tempnam(); // We write to temporary path first to minimise the time our target file is invalid (during generation)
    $sitemaps_out_file = fopen($sitemaps_out_temppath, 'wb');
    $sitemaps_out_path = get_custom_file_base() . '/data_custom/sitemaps/set_' . strval($set_number) . '.xml';
    $blob = '<' . '?xml version="1.0" encoding="' . get_charset() . '"?' . '>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    fwrite($sitemaps_out_file, $blob);

    // Nodes accessible by guests, and not deleted (ignore update time as we are rebuilding whole set)
    $where = array('set_number' => $set_number, 'is_deleted' => 0);
    $nodes = $GLOBALS['SITE_DB']->query_select('sitemap_cache', array('*'), $where);
    foreach ($nodes as $node) {
        $page_link = $node['page_link'];
        list($zone, $attributes, $hash) = page_link_decode($page_link);

        if (!has_actual_page_access(get_member(), $attributes['page'], $zone)) {
            continue;
        }

        $add_date = $node['add_date'];
        $edit_date = $node['edit_date'];
        $priority = $node['priority'];

        $url = _build_url($attributes, $zone, null, false, false, true, $hash);

        $optional_details = '';

        $_lastmod_date = is_null($edit_date) ? $add_date : $edit_date;
        if (!is_null($_lastmod_date)) {
            $xml_date = xmlentities(date('Y-m-d\TH:i:s', $_lastmod_date) . substr_replace(date('O', $_lastmod_date), ':', 3, 0));
            $optional_details = '
        <lastmod>' . $xml_date . '</lastmod>';
        }

        $langs = find_all_langs();
        foreach (array_keys($langs) as $lang) {
            if ($lang != get_site_default_lang()) {
                $url = _build_url($attributes + array('keep_lang' => $lang), $zone, null, false, false, true, $hash);

                $optional_details = '
        <xhtml:link rel="alternate" hreflang="' . strtolower($lang) . '" href="' . xmlentities($url) . '" />';
            }
        }

        $url_blob = '
    <url>
        <loc>' . xmlentities($url) . '</loc>' . $optional_details . '
        <changefreq>' . xmlentities($node['refreshfreq']) . '</changefreq>
        <priority>' . float_to_raw_string($priority) . '</priority>
    </url>
';
        fwrite($sitemaps_out_file, $url_blob);
    }

    // Close
    $blob = '</urlset>';
    fwrite($sitemaps_out_file, $blob);
    fclose($sitemaps_out_file);
    @unlink($sitemaps_out_path);
    if (!file_exists(dirname($sitemaps_out_path))) {
        require_code('files2');
        make_missing_directory(dirname($sitemaps_out_path));
    }
    rename($sitemaps_out_temppath, $sitemaps_out_path);
    sync_file($sitemaps_out_path);
    fix_permissions($sitemaps_out_path);

    // Gzip
    if (function_exists('gzencode')) {
        file_put_contents($sitemaps_out_path . '.gz', gzencode(file_get_contents($sitemaps_out_path), -1));
    }
}

/**
 * Write out a Sitemap XML index.
 */
function rebuild_sitemap_index()
{
    // Open
    $sitemaps_out_temppath = cms_tempnam(); // We write to temporary path first to minimise the time our target file is invalid (during generation)
    $sitemaps_out_file = fopen($sitemaps_out_temppath, 'wb');
    $sitemaps_out_path = get_custom_file_base() . '/data_custom/sitemaps/index.xml';
    $blob = '<' . '?xml version="1.0" encoding="' . get_charset() . '"?' . '>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    fwrite($sitemaps_out_file, $blob);

    // Write out each set
    $sitemap_sets = $GLOBALS['SITE_DB']->query_select('sitemap_cache', array('set_number', 'MAX(last_updated) AS last_updated'), null, 'GROUP BY set_number');
    foreach ($sitemap_sets as $sitemap_set) {
        $path = get_custom_file_base() . '/data_custom/sitemaps/set_' . strval($sitemap_set['set_number']) . '.xml';
        $url = get_custom_base_url() . '/data_custom/sitemaps/set_' . strval($sitemap_set['set_number']) . '.xml';
        if (is_file($path . '.gz')) {
            // Point to .gz if we have been gzipping. We cannot assume we have consistently managed that
            $path .= '.gz';
            $url .= '.gz';
        }

        $xml_date = xmlentities(date('Y-m-d\TH:i:s', $sitemap_set['last_updated']) . substr_replace(date('O', $sitemap_set['last_updated']), ':', 3, 0));

        $set_blob = '
    <sitemap>
        <loc>' . xmlentities($url) . '</loc>
        <lastmod>' . $xml_date . '</lastmod>
    </sitemap>
';
        fwrite($sitemaps_out_file, $set_blob);
    }

    // Close
    $blob = '</sitemapindex>';
    fwrite($sitemaps_out_file, $blob);
    fclose($sitemaps_out_file);
    @unlink($sitemaps_out_path);
    rename($sitemaps_out_temppath, $sitemaps_out_path);
    sync_file($sitemaps_out_path);
    fix_permissions($sitemaps_out_path);
}

/**
 * Ping search engines with an updated sitemap.
 *
 * @param  URLPATH $url Sitemap URL
 * @return string HTTP result output
 */
function ping_sitemap_xml($url)
{
    // Ping search engines
    $out = '';
    if (get_option('auto_submit_sitemap') == '1') {
        $ping = true;
        $base_url = get_base_url();
        $not_local = (substr($base_url, 0, 16) != 'http://localhost') && (substr($base_url, 0, 16) != 'http://127.0.0.1') && (substr($base_url, 0, 15) != 'http://192.168.') && (substr($base_url, 0, 10) != 'http://10.');
        if (($ping) && (get_option('site_closed') == '0') && ($not_local)) {
            // Submit to search engines
            $services = array(
                'http://www.google.com/webmasters/tools/ping?sitemap=',
                'http://submissions.ask.com/ping?sitemap=',
                'http://www.bing.com/webmaster/ping.aspx?siteMap=',
                'http://search.yahooapis.com/SiteExplorerService/V1/updateNotification?appid=SitemapWriter&url=',
            );
            foreach ($services as $service) {
                $out .= http_download_file($service . urlencode($url), null, false);
            }
        }
    }
    return $out;
}

/**
 * Our sitemap cache table may need bootstrapping for some reason.
 * Normally we build it iteratively.
 */
function build_sitemap_cache_table()
{
    if (!is_guest()) {
        warn_exit('Will not generate sitemap as non-Guest');
    }

    $GLOBALS['NO_QUERY_LIMIT'] = true;

    if (php_function_allowed('set_time_limit')) {
        set_time_limit(0);
    }

    $GLOBALS['MEMORY_OVER_SPEED'] = true;

    // Load ALL URL ID monikers (for efficiency)
    global $LOADED_MONIKERS_CACHE;
    if ($GLOBALS['SITE_DB']->query_select_value('url_id_monikers', 'COUNT(*)'/*, array('m_deprecated' => 0) Poor performance to include this and it's unnecessary*/) < 10000) {
        $results = $GLOBALS['SITE_DB']->query_select('url_id_monikers', array('m_moniker', 'm_resource_page', 'm_resource_type', 'm_resource_id'), array('m_deprecated' => 0));
        foreach ($results as $result) {
            $LOADED_MONIKERS_CACHE[$result['m_resource_page']][$result['m_resource_type']][$result['m_resource_id']] = $result['m_moniker'];
        }
    }

    // Load ALL guest permissions (for efficiency)
    load_up_all_module_category_permissions(get_member());

    // Runs via a callback mechanism, so we don't need to load an arbitrary complex structure into memory.
    $callback = '_sitemap_cache_node';
    $meta_gather = SITEMAP_GATHER_TIMES;
    retrieve_sitemap_node(
        '',
        $callback,
        /*$valid_node_types=*/null,
        /*$child_cutoff=*/null,
        /*$max_recurse_depth=*/null,
        /*$options=*/SITEMAP_GEN_CHECK_PERMS | SITEMAP_GEN_CONSIDER_VALIDATION,
        /*$zone=*/'_SEARCH',
        $meta_gather
    );
}


/**
 * Callback for reference a Sitemap node in the cache.
 *
 * @param  array $node The Sitemap node
 *
 * @ignore
 */
function _sitemap_cache_node($node)
{
    $page_link = $node['page_link'];
    if ($page_link === null) {
        return;
    }

    $add_date = $node['extra_meta']['add_date'];
    $edit_date = $node['extra_meta']['edit_date'];
    $priority = $node['sitemap_priority'];
    $refreshfreq = $node['sitemap_refreshfreq'];

    $guest_access = true;

    notify_sitemap_node_add($page_link, $add_date, $edit_date, $priority, $refreshfreq, $guest_access);
}

/**
 * Add a row to our sitemap cache.
 *
 * @param SHORT_TEXT $page_link The page-link
 * @param ?TIME $add_date The add time (null: unknown)
 * @param ?TIME $edit_date The edit time (null: same as add time)
 * @param float $priority The sitemap priority, a SITEMAP_IMPORTANCE_* constant
 * @param ID_TEXT $refreshfreq The refresh frequency
 * @set always hourly daily weekly monthly yearly never
 * @param boolean $guest_access Whether guests may access this resource in terms of category permissions not zone/page permissions (if not set to 1 then it will not end up in an XML sitemap, but we'll keep tabs of it for other possible uses)
 */
function notify_sitemap_node_add($page_link, $add_date, $edit_date, $priority, $refreshfreq, $guest_access)
{
    // Maybe we're still installing
	if (!$GLOBALS['SITE_DB']->table_exists('sitemap_cache') || running_script('install')) {
        return;
    }

    $fresh = ($GLOBALS['SITE_DB']->query_select_value('sitemap_cache', 'COUNT(*)') == 0);

    // Find set number we will write into
    $set_number = $GLOBALS['SITE_DB']->query_select_value_if_there('sitemap_cache', 'set_number', null, 'GROUP BY set_number HAVING COUNT(*)<' . strval(URLS_PER_SITEMAP_SET));
    if (is_null($set_number)) {
        // Next set number in sequence
        $set_number = $GLOBALS['SITE_DB']->query_select_value_if_there('sitemap_cache', 'MAX(set_number)');
        if (is_null($set_number)) {
            $set_number = 0;
        } else {
            $set_number++;
        }
    }

    // Save into sitemap
    $GLOBALS['SITE_DB']->query_delete('sitemap_cache', array(
        'page_link' => $page_link,
    ), '', 1);
    $GLOBALS['SITE_DB']->query_insert('sitemap_cache', array(
        'page_link' => $page_link,
        'set_number' => $set_number,
        'add_date' => is_null($add_date) ? null : $add_date,
        'edit_date' => is_null($edit_date) ? $add_date : $edit_date,
        'last_updated' => time(),
        'is_deleted' => 0,
        'priority' => $priority,
        'refreshfreq' => $refreshfreq,
        'guest_access' => $guest_access ? 1 : 0,
    ));

    // First population into the table? Do a full build too
    if ($fresh) {
        if (is_guest()) {
            build_sitemap_cache_table();
        }
    }
}

/**
 * Edit a row in our sitemap cache.
 *
 * @param SHORT_TEXT $page_link The page-link
 * @param boolean $guest_access Whether guests may access this resource in terms of category permissions not zone/page permissions (if not set to 1 then it will not end up in an XML sitemap, but we'll keep tabs of it for other possible uses)
 */
function notify_sitemap_node_edit($page_link, $guest_access)
{
    $rows = $GLOBALS['SITE_DB']->query_select('sitemap_cache', array('*'), array(
        'page_link' => $page_link,
    ), '', 1);
    if (!isset($rows[0])) {
        return; // Allows us to call a bit lazily when we're not sure even if we added in the first place
    }

    $GLOBALS['SITE_DB']->query_delete('sitemap_cache', array(
        'page_link' => $page_link,
    ), '', 1);
    $GLOBALS['SITE_DB']->query_insert('sitemap_cache', array(
        'edit_date' => time(),
        'last_updated' => time(),
        'guest_access' => $guest_access ? 1 : 0,
    ) + $rows[0]);
}

/**
 * Mark a row from our sitemap cache as for deletion.
 * It won't be immediately deleted, as we use this as a signal that the XML sitemap will need updating too.
 * Updates are done in batch, via CRON.
 *
 * @param SHORT_TEXT $page_link The page-link
 */
function notify_sitemap_node_delete($page_link)
{
    $GLOBALS['SITE_DB']->query_update('sitemap_cache', array(
        'last_updated' => time(),
        'is_deleted' => 1,
    ), array('page_link' => $page_link), '', 1);
}
