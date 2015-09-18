<?php

// Turn on and off preprocess functions
require_once dirname(__FILE__) . '/preprocess/html.preprocess.inc';
// require_once dirname(__FILE__) . '/preprocess/utils.inc';
// require_once dirname(__FILE__) . '/preprocess/breadcrumb.inc';
require_once dirname(__FILE__) . '/preprocess/form.inc';
// require_once dirname(__FILE__) . '/preprocess/menu.inc';
// require_once dirname(__FILE__) . '/preprocess/message.inc';
// require_once dirname(__FILE__) . '/preprocess/item_list.inc';
// require_once dirname(__FILE__) . '/preprocess/pager.inc';
// require_once dirname(__FILE__) . '/preprocess/links.inc';
// require_once dirname(__FILE__) . '/preprocess/status_report.inc';

/**
 * Implements hook_css_alter().
 */

function bear_coat_css_alter(&$css) {
  $exclude = array(
    'modules/aggregator/aggregator.css' => FALSE,
    'modules/comment/comment.css' => FALSE,
    'modules/system/system.css' => FALSE,
    'modules/system/system.menus.css' => FALSE,
    'modules/system/system.messages.css' => FALSE,
    'modules/system/system.theme.css' => FALSE,
    'modules/user/user.css' => FALSE,
    'modules/search/search.css' => FALSE,
    'modules/filter/filter.css' => FALSE,
    'modules/field/theme/field.css' => FALSE,
    'modules/forum/forum.css' => FALSE,
    'misc/vertical-tabs.css' => FALSE,
  );
  $css = array_diff_key($css, $exclude);
}

/**
 * Implements hook_theme().
 */
function bear_coat_theme($existing, $type, $theme, $path) {
  return array(
    'search_block_input_wrapper' => array(
      'render element' => 'element',
      'function' => 'bear_coat_search_block_input_wrapper',
    ),
    'pager_item_list' => array(
      'variables' => array(
        'items' => array(),
      ),
    ),
    'labeled_button' => array(
      'render element' => 'element',
    ),
  );
}