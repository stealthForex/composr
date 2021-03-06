<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2016

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    addon_publish
 */

i_solemnly_declare(I_UNDERSTAND_SQL_INJECTION | I_UNDERSTAND_XSS | I_UNDERSTAND_PATH_INJECTION);

$title = get_screen_title('Publish addons', false);
$title->evaluate_echo();

define('DOWNLOAD_OWNER', 2); // Hard-coded ID of user that gets ownership of the downloads

require_code('addon_publish');
require_code('addons2');
require_code('version');
require_code('version2');
require_code('downloads2');
require_code('files');
require_code('tar');

$target_cat = get_param_string('cat', null);
if ($target_cat === null) {
    if ($GLOBALS['DEV_MODE']) {
        $target_cat = 'Version ' . float_to_raw_string(cms_version_number(), 2, true);
    } else {
        exit('Please pass the target category name in the URL (?cat=name).');
    }
}
$version_branch = get_param_string('version_branch', null);
if ($version_branch === null) {
    if ($GLOBALS['DEV_MODE']) {
        $version_branch = get_version_branch();
    } else {
        exit('Please pass the branch version in the URL (?version_branch=num.x).');
    }
}

$c_main_id = find_addon_category_download_category($target_cat);

// Addons...

if (get_param_integer('import_addons', 1) == 1) {
    $addon_count = 0;

    $categories = find_addon_category_list();
    foreach ($categories as $category) {
        $cat_id = find_addon_category_download_category($category, $c_main_id);
        $addon_arr = get_addons_list_under_category($category);
        foreach ($addon_arr as $addon) {
            $addon_count++;
            publish_addon($addon, $version_branch, $cat_id);
        }
    }

    if ($addon_count == 0) {
        echo '<p>No addons to import</p>';
    } else {
        echo '<p>All addons have been imported as downloads</p>';
    }
}

// Now themes...

if (get_param_integer('import_themes', 1) == 1) {
    $cat_id = find_addon_category_download_category('Themes', $c_main_id);
    $cat_id = find_addon_category_download_category('Professional Themes', $cat_id);

    $dh = opendir(get_custom_file_base() . '/exports/addons');
    $theme_count = 0;
    while (($file = readdir($dh)) !== false) {
        if (preg_match('#^theme-.*\.tar$#', $file) != 0) {
            $theme_count++;

            publish_theme($file, $version_branch, $cat_id);
        }
    }
    closedir($dh);

    if ($theme_count == 0) {
        echo '<p>No themes to import</p>';
    } else {
        echo '<p>All themes have been imported as downloads</p>';
    }
}

function publish_addon($addon, $version_branch, $cat_id)
{
    $file = $addon . '-' . $version_branch . '.tar';
    $from = get_custom_file_base() . '/exports/addons/' . $addon . '-' . $version_branch . '.tar';
    $to = get_custom_file_base() . '/uploads/downloads/' . $file;
    @unlink($to);
    if (!file_exists($from)) {
        warn_exit('Missing: ' . $from);
    }
    if (!file_exists($to)) {
        copy($from, $to);
    }

    $addon_url = 'uploads/downloads/' . urlencode($file);

    $fsize = filesize(get_file_base() . '/' . urldecode($addon_url));

    $test = $GLOBALS['SITE_DB']->query_select_value_if_there('download_downloads', 'url', array('url' => $addon_url));
    if (is_null($test)) {
        $tar = tar_open($from, 'rb');
        $info_file = tar_get_file($tar, 'addon.inf', true);
        $info = better_parse_ini_file(null, $info_file['data']);
        tar_close($tar);

        $name = titleify($info['name']);
        $description = $info['description'];
        $author = $info['author'];
        $dependencies = $info['dependencies'];
        $incompatibilities = $info['incompatibilities'];
        $category = $info['category'];
        $licence = $info['licence'];
        $copyright_attribution = $info['copyright_attribution'];

        if ($dependencies != '') {
            $description .= "\n\n[title=\"2\"]System Requirements / Dependencies[/title]\n\n" . $dependencies;
        }
        if ($incompatibilities != '') {
            $description .= "\n\n[title=\"2\"]Incompatibilities[/title]\n\n" . $incompatibilities;
        }
        if ($licence != '') {
            $description .= "\n\n[title=\"2\"]Licence[/title]\n\n" . $licence;
        }
        if ($copyright_attribution != '') {
            $description .= "\n\n[title=\"2\"]Additional credits/attributions[/title]\n\n" . $copyright_attribution;
        }

        $download_owner = $GLOBALS['FORUM_DRIVER']->get_member_from_username($author);
        if (is_null($download_owner)) {
            $download_owner = DOWNLOAD_OWNER;
        }
        $download_id = add_download($cat_id, $name, $addon_url, $description, $author, '', null, 1, 1, 2, 1, '', $addon . '.tar', $fsize, 0, 0, null, null, 0, 0, $download_owner);

        $screenshot_url = 'data_custom/images/addon_screenshots/' . $addon . '.png';
        if (file_exists(get_custom_file_base() . '/' . $screenshot_url)) {
            add_image('', 'download_' . strval($download_id), '', $screenshot_url, '', 1, 0, 0, 0, '', null, null, null, 0);
        }
    }
}

function publish_theme($file, $version_branch, $cat_id)
{
    $from = get_custom_file_base() . '/exports/addons/' . $file;
    $new_file = basename($file, '.tar') . '-' . $version_branch . '.tar';
    $to = get_custom_file_base() . '/uploads/downloads/' . $new_file;
    @unlink($to);
    copy($from, $to);
    $addon_url = 'uploads/downloads/' . urlencode($new_file);

    $fsize = filesize(get_file_base() . '/' . urldecode($addon_url));

    $test = $GLOBALS['SITE_DB']->query_select_value_if_there('download_downloads', 'url', array('url' => $addon_url));
    if (is_null($test)) {
        $tar = tar_open($from, 'rb');
        $info_file = tar_get_file($tar, 'addon.inf', true);
        $info = better_parse_ini_file(null, $info_file['data']);
        tar_close($tar);

        $name = $info['name'];
        $description = $info['description'];
        $author = $info['author'];

        $download_owner = $GLOBALS['FORUM_DRIVER']->get_member_from_username($author);
        if (is_null($download_owner)) {
            $download_owner = DOWNLOAD_OWNER;
        }
        $download_id = add_download($cat_id, $name, $addon_url, $description, $author, '', null, 1, 1, 2, 1, '', $new_file, $fsize, 0, 0, null, null, 0, 0, $download_owner);

        $screenshot_url = 'data_custom/images/addon_screenshots/' . urlencode(preg_replace('#^theme-#', 'theme__', preg_replace('#\d+$#', '', basename($file, '.tar'))) . '.png');
        if (file_exists(get_custom_file_base() . '/' . $screenshot_url)) {
            add_image('', 'download_' . strval($download_id), '', $screenshot_url, '', 1, 0, 0, 0, '', null, null, null, 0);
        }
    }
}
