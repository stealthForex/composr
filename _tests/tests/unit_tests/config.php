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
class config_test_set extends cms_test_case
{
    public function testListConfigConsistency()
    {
        $hooks = find_all_hooks('systems', 'config');
        foreach (array_keys($hooks) as $hook) {
            require_code('hooks/systems/config/' . $hook);
            $ob = object_factory('Hook_config_' . $hook);
            $details = $ob->get_details();
            if ($details['type'] == 'list') {
                $list = explode('|', $details['list_options']);
                $this->assertTrue(in_array($ob->get_default(), $list));
            }
        }
    }

    public function testReasonablePerCategory()
    {
        $categories = array();

        $hooks = find_all_hooks('systems', 'config');
        foreach (array_keys($hooks) as $hook) {
            require_code('hooks/systems/config/' . filter_naughty($hook));
            $ob = object_factory('Hook_config_' . $hook);
            $details = $ob->get_details();
            if (!isset($categories[$details['category']])) {
                $categories[$details['category']] = 0;
            }
            $categories[$details['category']]++;
        }

        foreach ($categories as $category => $count) {
            $this->assertTrue($count > 3, $category . ' only has ' . integer_format($count));
            $this->assertTrue($count < 160, $category . ' has as much as ' . integer_format($count)); // max_input_vars would not like a high number
        }
    }

    public function testConsistentGroupOrdering()
    {
        $categories = array();

        $hooks = find_all_hooks('systems', 'config');
        foreach ($hooks as $hook => $hook_type) {
            require_code('hooks/systems/config/' . filter_naughty($hook));
            $ob = object_factory('Hook_config_' . $hook);
            $details = $ob->get_details();
            if (!isset($categories[$details['category']])) {
                $categories[$details['category']] = array();
            }
            if (!isset($categories[$details['category']][$details['group']])) {
                $categories[$details['category']][$details['group']] = array();
            }
            $categories[$details['category']][$details['group']][] = $details;
        }

        foreach ($categories as $category => $group) {
            foreach ($group as $group_name => $options) {
                $has_orders = null;
                $orders = array();

                foreach ($options as $option) {
                    $_has_orders = isset($option['order_in_category_group']);
                    if ($has_orders !== null) {
                        if ($has_orders != $_has_orders) {
                            $this->assertTrue(false, $category . '/' . $group_name . ' has inconsistent ordering settings (some set, some not)');
                            break;
                        }
                    } else {
                        $has_orders = $_has_orders;
                    }

                    if ($has_orders) {
                        if (isset($orders[$option['order_in_category_group']])) {
                            $this->assertTrue(false, $category . '/' . $group_name . ' has duplicated order for ' . strval($option['order_in_category_group']));
                        }

                        $orders[$option['order_in_category_group']] = true;
                    }
                }
            }
        }
    }
}
