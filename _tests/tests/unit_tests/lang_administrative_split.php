<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2016

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    testing_platform
 */

/**
 * Composr test case class (unit testing).
 */
class lang_administrative_split_test_set extends cms_test_case
{
    public function setUp()
    {
        require_code('string_scan');

        parent::setUp();
    }

    public function testLangMistakes()
    {
        $lang = fallback_lang();
        list($just_lang_strings_admin, $just_lang_strings_non_admin, $lang_strings_shared, $lang_strings_unknown, $all_strings_in_lang) = string_scan($lang);

        foreach (array_merge($lang_strings_shared, $lang_strings_unknown) as $str) {
            $this->assertTrue(false, $str . ': not defined as either administrative or not');
        }
    }
}
