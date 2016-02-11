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
 * @package    cns_forum
 */

/**
 * Hook class.
 */
class Hook_content_meta_aware_post
{
    /**
     * Get content type details. Provides information to allow task reporting, randomisation, and add-screen linking, to function.
     *
     * @param  ?ID_TEXT $zone The zone to link through to (null: autodetect).
     * @return ?array Map of award content-type info (null: disabled).
     */
    public function info($zone = null)
    {
        if (get_forum_type() != 'cns' || !isset($GLOBALS['FORUM_DB'])) {
            return null;
        }

        if (is_null($zone)) {
            $zone = get_module_zone('forumview');
            if (is_null($zone)) {
                return null;
            }
        }

        return array(
            'support_custom_fields' => true,

            'content_type_label' => 'cns:FORUM_POST',

            'connection' => $GLOBALS['FORUM_DB'],
            'table' => 'f_posts',
            'id_field' => 'id',
            'id_field_numeric' => true,
            'parent_category_field' => 'p_topic_id',
            'parent_category_meta_aware_type' => 'topic',
            'is_category' => false,
            'is_entry' => true,
            'category_field' => 'p_cache_forum_id', // For category permissions
            'category_type' => 'forums', // For category permissions
            'parent_spec__table_name' => 'f_forums',
            'parent_spec__parent_name' => 'f_parent_forum',
            'parent_spec__field_name' => 'id',
            'category_is_string' => false,

            'title_field' => 'p_title',
            'title_field_dereference' => false,
            'description_field' => 'p_post',
            'thumb_field' => null,
            'thumb_field_is_theme_image' => false,

            'view_page_link_pattern' => '_SEARCH:topicview:findpost:_WILD',
            'edit_page_link_pattern' => '_SEARCH:topics:edit_post:_WILD',
            'view_category_page_link_pattern' => '_SEARCH:topicview:browse:_WILD',
            'add_url' => '',
            'archive_url' => $zone . ':forumview',

            'support_url_monikers' => false,

            'views_field' => null,
            'order_field' => null,
            'submitter_field' => 'p_poster',
            'author_field' => null,
            'add_time_field' => 'p_time',
            'edit_time_field' => 'p_last_edit_time',
            'date_field' => 'p_time',
            'validated_field' => 'p_validated',

            'seo_type_code' => null,

            'feedback_type_code' => 'post',

            'permissions_type_code' => 'forums',

            'search_hook' => 'cns_posts',
            'rss_hook' => null,
            'attachment_hook' => 'cns_post',
            'unvalidated_hook' => 'cns_posts',
            'notification_hook' => null,
            'sitemap_hook' => null,

            'addon_name' => 'cns_forum',

            'cms_page' => 'topics',
            'module' => 'forumview',

            'commandr_filesystem_hook' => 'forums',
            'commandr_filesystem__is_folder' => false,

            'support_revisions' => true,

            'support_privacy' => false,

            'support_content_reviews' => false,

            'actionlog_regexp' => '\w+_POST',
        );
    }

    /**
     * Run function for content hooks. Renders a content box for an award/randomisation.
     *
     * @param  array $row The database row for the content
     * @param  ID_TEXT $zone The zone to display in
     * @param  boolean $give_context Whether to include context (i.e. say WHAT this is, not just show the actual content)
     * @param  boolean $include_breadcrumbs Whether to include breadcrumbs (if there are any)
     * @param  ?ID_TEXT $root Virtual root to use (null: none)
     * @param  boolean $attach_to_url_filter Whether to copy through any filter parameters in the URL, under the basis that they are associated with what this box is browsing
     * @param  ID_TEXT $guid Overridden GUID to send to templates (blank: none)
     * @return Tempcode Results
     */
    public function run($row, $zone, $give_context = true, $include_breadcrumbs = true, $root = null, $attach_to_url_filter = false, $guid = '')
    {
        require_code('cns_posts2');

        return render_post_box($row, false, $give_context, $include_breadcrumbs, is_null($root) ? null : intval($root), $guid);
    }
}
