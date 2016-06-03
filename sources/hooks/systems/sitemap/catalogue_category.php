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
 * @package    catalogues
 */

/**
 * Hook class.
 */
class Hook_sitemap_catalogue_category extends Hook_sitemap_content
{
    protected $content_type = 'catalogue_category';
    protected $screen_type = 'category';

    // If we have a different content type of entries, under this content type
    protected $entry_content_type = array('catalogue_entry');
    protected $entry_sitetree_hook = array('catalogue_entry');

    /**
     * Get the permission page that nodes matching $page_link in this hook are tied to.
     * The permission page is where privileges may be overridden against.
     *
     * @param  string $page_link The page-link
     * @return ?ID_TEXT The permission page (null: none)
     */
    public function get_privilege_page($page_link)
    {
        return 'cms_catalogues';
    }

    /**
     * Find details of a position in the Sitemap.
     *
     * @param  ID_TEXT $page_link The page-link we are finding.
     * @param  ?string $callback Callback function to send discovered page-links to (null: return).
     * @param  ?array $valid_node_types List of node types we will return/recurse-through (null: no limit)
     * @param  ?integer $child_cutoff Maximum number of children before we cut off all children (null: no limit).
     * @param  ?integer $max_recurse_depth How deep to go from the Sitemap root (null: no limit).
     * @param  integer $recurse_level Our recursion depth (used to limit recursion, or to calculate importance of page-link, used for instance by XML Sitemap [deeper is typically less important]).
     * @param  integer $options A bitmask of SITEMAP_GEN_* options.
     * @param  ID_TEXT $zone The zone we will consider ourselves to be operating in (needed due to transparent redirects feature)
     * @param  integer $meta_gather A bitmask of SITEMAP_GATHER_* constants, of extra data to include.
     * @param  ?array $row Database row (null: lookup).
     * @param  boolean $return_anyway Whether to return the structure even if there was a callback. Do not pass this setting through via recursion due to memory concerns, it is used only to gather information to detect and prevent parent/child duplication of default entry points.
     * @return ?array Node structure (null: working via callback / error).
     */
    public function get_node($page_link, $callback = null, $valid_node_types = null, $child_cutoff = null, $max_recurse_depth = null, $recurse_level = 0, $options = 0, $zone = '_SEARCH', $meta_gather = 0, $row = null, $return_anyway = false)
    {
        $_ = $this->_create_partial_node_structure($page_link, $callback, $valid_node_types, $child_cutoff, $max_recurse_depth, $recurse_level, $options, $zone, $meta_gather, $row);
        if ($_ === null) {
            return null;
        }
        list($content_id, $row, $partial_struct) = $_;

        // level 0 = root
        // level 1 = zone
        if ($recurse_level == 2) {
            $sitemap_priority = SITEMAP_IMPORTANCE_MEDIUM;
        } else {
            $sitemap_priority = SITEMAP_IMPORTANCE_LOW;
        }

        $struct = array(
            'sitemap_priority' => $sitemap_priority,
            'sitemap_refreshfreq' => 'weekly',

            'privilege_page' => $this->get_privilege_page($page_link),

            'edit_url' => build_url(array('page' => 'cms_catalogues', 'type' => '_edit_category', 'id' => $content_id), get_module_zone('cms_catalogues')),
        ) + $partial_struct;

        if ($GLOBALS['SITE_DB']->query_select_value_if_there('catalogues', 'c_is_tree', array('c_name' => $content_id)) == 1) {
            $struct['extra_meta']['is_a_category_tree_root'] = true;
        }

        if (!$this->_check_node_permissions($struct)) {
            return null;
        }

        // Sometimes page groupings link direct to catalogue categories, so search for an icon
        $row_x = $this->_load_row_from_page_groupings(null, $zone, 'catalogues', 'category', $content_id);
        if ($row_x != array()) {
            if (($options & SITEMAP_GEN_LABEL_CONTENT_TYPES) == 0) {
                $struct['title'] = null;
            }
            $struct['extra_meta']['image'] = null;
            $struct['extra_meta']['image_2x'] = null;
            $this->_ameliorate_with_row($options, $struct, $row_x, $meta_gather);
        }

        if ($callback !== null) {
            call_user_func($callback, $struct);
        }

        // Categories done after node callback, to ensure sensible ordering
        $children = $this->_get_children_nodes($content_id, $page_link, $callback, $valid_node_types, $child_cutoff, $max_recurse_depth, $recurse_level, $options, $zone, $meta_gather, $row, '', null, 'cc_order,' . $GLOBALS['SITE_DB']->translate_field_ref('cc_title'));
        $struct['children'] = $children;

        return ($callback === null || $return_anyway) ? $struct : null;
    }
}
