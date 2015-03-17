<?php

/**
 * Implements template_preprocess_html().
 * 1. Adds path variables.
 * 2. Include bear skin theme options in Drupal's JS object
 * 3. Include a CSS class on the body tag if the site uses sticky footer
 */
function bear_skin_preprocess_html(&$variables, $hook) {
  // Add variables and paths needed for HTML5 and responsive support.
  $variables['base_path'] = base_path();
  $variables['path_to_bear_skin'] = drupal_get_path('theme', 'bear_skin');

  // put some settings into javascript
  drupal_add_js(array(
    'bear_skin' => array(
      'stickyFooter' => (bool) theme_get_setting('sticky_footer'),
      'userMenu' => (bool) theme_get_setting('user_menu')
    )
  ), 'setting');

  // if the sticky footer option is selected, set a class
  if (theme_get_setting('sticky_footer')) {
    $variables['classes_array'][] = 'with-sticky-footer';
  }
}

/**
 * Implements template_preprocess_page().
 * 1. Check if sidebars have content; Add boolean to $variables
 * 2. Setup the user menu (by default displayed in header)
 */
function bear_skin_preprocess_page(&$variables) {
  $page = $variables['page'];

  // check if there is content in the sidebars
  $variables['has_sidebar_first'] = FALSE;
  $variables['has_sidebar_second'] = FALSE;
  if (!empty($page['sidebar_first'])) {
    $variables['has_sidebar_first'] = TRUE;
  }
  if (!empty($page['sidebar_second'])) {
    $variables['has_sidebar_second'] = TRUE;
  }

  // setup the user menu to display in the header
  $variables['user_menu'] = theme('links__user_menu', array(
    'links' => menu_navigation_links('user-menu'),
    'attributes' => array(
      'class ' => array('nav-user__list'),
      'aria-labelledby' => 'userMenuLabel'
    )
  ));
}

/**
 * Implements template_preprocess_region()
 * 1. Add SMACCS / BEM style CSS class to regions
 */
function bear_skin_preprocess_region(&$variables) {
  $region = $variables['region'];
  $variables['classes_array'][] = 'region--' . str_replace('_', '-', $region);
}

/**
 * Implements template_preprocess_block()
 * 1. Add a class to the block to indicate its type and region placement
 */
function bear_skin_preprocess_block(&$variables) {
  $module = str_replace('_', '-', $variables['block']->module) .'-'. $variables['block']->delta;
  $region = str_replace('_', '-', $variables['block']->region);
  $variables['classes_array'][] = 'block__' . $module . '--' . $region;
}
/**
 * Implements hook_css_alter().
 * 1. Remove some of Drupal's default CSS
 * 2. Force insertion of CSS as <link> tags instead of @import
 *    if CSS aggregation is turned off
 */
function bear_skin_css_alter(&$css) {
  // remove drupal's default message css
  unset($css['modules/system/system.messages.css']);
  unset($css['modules/system/system.menus.css']);
  unset($css['profiles/bear/themes/zen/system.menus.css']);

  // if css aggregation is off, include css as link tags
  // this allows livereload / guard to inject css
  if (!variable_get('preprocess_css')) {
    foreach ($css as $key => $value) {
      // Skip core files.
      $is_core = (strpos($value['data'], 'misc/') === 0 || strpos($value['data'], 'modules/') === 0);
      if (!$is_core) {
        // This option forces embeding with a link element.
        $css[$key]['preprocess'] = FALSE;
      }
    }
  }
}

/**
 * Implements template_preprocess_menu_link()
 * 1. Make a more specific CSS class for menu list items <li>
 * 2. Make a CSS class on menu list items <li> referencing their level depth
 * 3. Make a more specific CSS class for menu links <a>
 * 4. Save the menu name and depth as attributes
 */
function bear_skin_preprocess_menu_link(&$variables, $hook) {
  $menu_name = $variables['element']['#original_link']['menu_name'];
  $depth_word = _bear_skin_number_to_text($variables['element']['#original_link']['depth']);

  $variables['element']['#attributes']['class'][] = $menu_name . '__item';
  $variables['element']['#attributes']['class'][] = $menu_name . '__item--level-' . $depth_word;
  $variables['element']['#localized_options']['attributes']['class'][] = $menu_name . '__link';

  // save the menu name and depth as data attributes
  // this is a hack so that the <ul class="menu"> element can ultimately have
  // CSS classes that reflect the specific menu name and its depth in the tree
  $variables['element']['#attributes']['data-menu-name'] = $menu_name;
  $variables['element']['#attributes']['data-menu-depth'] = $depth_word;

}

/**
 * Implements template_preprocess_menu_tree()
 * 1. Pick the data attributes for menu name and depth,
 *    save them as elements in the $variables array
 *    then the template_menu_tree hook can add them as CSS classes
 */
function bear_skin_preprocess_menu_tree(&$variables) {
  $tree = new DOMDocument();
  @$tree->loadHTML($variables['tree']);
  $links = $tree->getElementsByTagname('li');
  $menu_name = '';
  $menu_depth = '';

  foreach ($links as $link) {
    $menu_name = $link->getAttribute('data-menu-name');
    $menu_depth = $link->getAttribute('data-menu-depth');
    break;
  }

  $variables['menu_name'] = $menu_name;
  $variables['menu_depth'] = $menu_depth;
}

/**
 * Implements template_menu_tree()
 * 1. Make CSS classes out of the data attributes stored in
 *    the template_preprocess_menu_tree hook
 */
function bear_skin_menu_tree(&$variables) {
  return '<ul class="menu ' . $variables['menu_name'] . '--level-' . $variables['menu_depth'] . '">' . $variables['tree'] . '</ul>';
}

/**
 * Implements theme_links()
 * specifically for the user_menu only!
 * 1. Add a SMACCS / BEM style CSS class to <a> items
 */
function bear_skin_links__user_menu(&$variables) {
  foreach ($variables['links'] as $key => &$link) {
    $link['html'] = TRUE;
    $link['attributes'] = array('class' => 'nav-user__link');
  }
  return theme_links($variables);
}

/**
 * Implements theme_status_messages()
 * 1. Add some additional CSS classes
 * 2. Make the alerts accessible using WAI standards
 */
function bear_skin_status_messages($variables) {
  $display = $variables['display'];
  $output = '';

  $status_heading = array(
    'status' => t('Status message'),
    'error' => t('Error message'),
    'warning' => t('Warning message'),
    'success' => t('Status message')
  );
  foreach (drupal_get_messages($display) as $type => $messages) {
    $type = ($type === 'status') ? 'success' : $type;
    $output .= "<div class=\"messages--$type messages $type\">\n";
    if (!empty($status_heading[$type])) {
      $output .= '<h2 class="u-hidden">' . $status_heading[$type] . "</h2>\n";
    }
    $output .= " <ul class=\"messages__list\">\n";
    foreach ($messages as $message) {
      $output .= "  <li class=\"messages__item\" role=\"alert\" aria-live=\"assertive\">" . $message . "</li>\n";
    }
    $output .= " </ul>\n";
    $output .= "</div>\n";
  }
  return $output;
}

/**
 * Implements theme_breadcrumb()
 * 1. Make the breadcrumbs more accessible using WAI standards
 * 2. Add SMACCS / BEM style CSS classes to HTML elements in breadcrumbs
 */
function bear_skin_breadcrumb(&$variables) {
  $breadcrumb = $variables['breadcrumb'];

  $crumbs = '';
  if (!empty($breadcrumb)) {
    $crumbs = '<nav role="navigation" aria-label="breadcrumbs">' . "\n";
    $crumbs .= '<h2 class="u-hidden" id="breadcrumbLabel">' . t('You are here:') . '</h2>';
    $crumbs .= '<ul class="breadcrumbs" aria-labelledby="breadcrumbLabel">' . "\n";
    foreach ($breadcrumb as $value) {
      $value = str_replace('<a', '<a class="breadcrumbs__link"', $value);
      // the breadcrumb divider has aria-hidden, which should make it ignored by screen readers
      $crumbs .= '<li class="breadcrumbs__item">' . $value . ' <span class="breadcrumbs__divider" aria-hidden="true">&raquo;</span></li>' . "\n";
    }
    $crumbs .= '</ul>' . "\n";
    $crumbs .= '</nav>' . "\n";
  }
  return $crumbs;
}

/**
 * Implements theme_menu_local_tasks()
 * 1. Make the tabs more accessible using WAI standards
 * 2. Add SMACCS / BEM style CSS classes to HTML elements in tab containers
 */
function bear_skin_menu_local_tasks(&$variables) {
  $output = '';

  // Add theme hook suggestions for tab type.
  foreach (array('primary', 'secondary') as $type) {
    if (!empty($variables[$type])) {
      foreach (array_keys($variables[$type]) as $key) {
        if (isset($variables[$type][$key]['#theme']) && ($variables[$type][$key]['#theme'] == 'menu_local_task' || is_array($variables[$type][$key]['#theme']) && in_array('menu_local_task', $variables[$type][$key]['#theme']))) {
          $variables[$type][$key]['#theme'] = array(
            'menu_local_task__' . $type,
            'menu_local_task'
          );
        }
      }
    }
  }

  if (!empty($variables['primary'])) {
    $variables['primary']['#prefix'] = '<h2 class="u-hidden" id="primaryTabsLabel">' . t('Primary tabs') . '</h2>';
    $variables['primary']['#prefix'] .= '<ul class="tabs-primary tabs primary" role="tablist" aria-labelledby="primaryTabsLabel">';
    $variables['primary']['#suffix'] = '</ul>';
    $output .= drupal_render($variables['primary']);
  }
  if (!empty($variables['secondary'])) {
    $variables['secondary']['#prefix'] = '<h2 class="u-hidden" id="secondaryTabsLabel">' . t('Secondary tabs') . '</h2>';
    $variables['secondary']['#prefix'] .= '<ul class="tabs-secondary tabs secondary" role="tablist" aria-labelledby="secondarTabsLabel">';
    $variables['secondary']['#suffix'] = '</ul>';
    $output .= drupal_render($variables['secondary']);
  }

  return $output;
}

/**
 * Implements theme_menu_local_task()
 * 1. Make the tab items more accessible using WAI standards
 * 2. Add SMACCS / BEM style CSS classes to HTML elements in individual tabs
 */
function bear_skin_menu_local_task($variables) {
  $type = $class = FALSE;

  $link = $variables['element']['#link'];
  $link_text = $link['title'];

  // Check for tab type set in zen_menu_local_tasks().
  if (is_array($variables['element']['#theme'])) {
    $type = in_array('menu_local_task__secondary', $variables['element']['#theme']) ? 'tabs-secondary' : 'tabs-primary';
  }

  // Add SMACSS-style class names.
  if ($type) {
    $link['localized_options']['attributes']['class'][] = $type . '__tab-link';
    $class = $type . '__tab';
  }

  $link['localized_options']['attributes']['role'] = 'tab';

  if (!empty($variables['element']['#active'])) {
    // Add text to indicate active tab for non-visual users.
    $active = ' <span class="u-hidden">' . t('(active tab)') . '</span>';

    // If the link does not contain HTML already, check_plain() it now.
    // After we set 'html'=TRUE the link will not be sanitized by l().
    if (empty($link['localized_options']['html'])) {
      $link['title'] = check_plain($link['title']);
    }
    $link['localized_options']['html'] = TRUE;
    $link_text = t('!local-task-title!active', array(
      '!local-task-title' => $link['title'],
      '!active' => $active
    ));

    if (!$type) {
      $class = 'active';
    }
    else {
      $link['localized_options']['attributes']['class'][] = 'is-active';
      $class .= ' is-active';
    }
  }

  return '<li' . ($class ? ' class="' . $class . '"' : '') . '>' . l($link_text, $link['href'], $link['localized_options']) . "</li>\n";
}

/**
 * Implements theme_preprocess_node()
 * 1. Add SMACCS / BEM style CSS classes for nodes and node titles
 * 2. Let node teasers have a tpl file called node--teaser.tpl.php
 */
function bear_skin_preprocess_node(&$variables) {
  $view_mode = $variables['view_mode'];
  $type = $variables['type'];

  // add theme suggestion for node teasers
  // add teaser CSS classes
  if ($view_mode === 'teaser') {
    $variables['classes_array'][] = 'node-' . $type . '-teaser';
    $variables['title_attributes_array']['class'][] = 'node-teaser__title';
    $variables['title_attributes_array']['class'][] = 'node-' . $type . '-teaser__title';
    array_unshift($variables['theme_hook_suggestions'], 'node__teaser');
  }

}

/**
 * Implements theme_field()
 * In order to get to the least amount of CSS nesting possible:
 * 1. Very specific classes for fields
 *    examples:
 *      the 'body' field of a 'page' node in full view has a class of node-page-body
 *      the 'date' field of a 'event' node in teaser view is node-event-date-teaser
 * 2. SMACCS / BEM style CSS classes for multiple field groups, single fields, and field labels
 *    examples:
 *      the label of a 'date' field for an 'event' node is node-event-date__label
 *      if a field has multiple items, they are inside a div with class node-event-date__group
 *      each item of a field has a class like node-event-date__content
 * 3. Add aria labels for WAI accessibility
 */
function bear_skin_field(&$variables) {
  // create a CSS class
  // the type of object this field is inside
  if ($variables['element']['#entity_type'] === 'node') {
    $object_class = 'node-' . $variables['element']['#object']->type;
  } else {
    $object_class = $variables['element']['#entity_type'];
  }
  $object_class = $object_class . '-' . $variables['field_name_css'];
  $object_class = ($variables['element']['#view_mode'] === 'full') ? $object_class : $object_class . '-' . $variables['element']['#view_mode'];

  $variables['classes'] = $variables['classes'] . ' ' . $object_class;
  $output = '';

  // Render the label, if it's not hidden.
  if (!$variables['label_hidden']) {
    $output .= '<div class="field-label ' . $object_class . '__label"' . $variables['title_attributes'] . ' id="label-for-' . $object_class . '">' . $variables['label'] . ':&nbsp;</div>';
  }

  // Render the items
  // Add a group wrapper if there is more than one item
  if (count($variables['items']) > 1) {
    $output .= '<div class="field-items ' . $object_class . '__group"' . $variables['content_attributes'] . '>';
    foreach ($variables['items'] as $delta => $item) {
      $classes = 'field-item ' . $object_class . '__content';
      $output .= '<div class="' . $classes . '"' . $variables['item_attributes'][$delta] . '>' . drupal_render($item) . '</div>';
    }
    $output .= '</div>';
  } else {
    $item = reset($variables['items']);
    $classes = 'field-item ' . $object_class . '__content';
    $output .= '<div class="' . $classes . '"' . $variables['item_attributes'][0] . '>' . drupal_render($item) . '</div>';
  }

  // Render the top-level DIV.
  if (!$variables['label_hidden']) {
    $output = '<div class="' . $variables['classes'] . '"' . $variables['attributes'] . ' aria-labelledby="label-for-' . $object_class . '">' . $output . '</div>';
  } else {
    $output = '<div class="' . $variables['classes'] . '"' . $variables['attributes'] . '>' . $output . '</div>';
  }

  return $output;
}

/***********************
 * Let's load some CSS on specific targets - uncomment to use
 ************************/

// function bear_skin_preprocess_page(&$vars) {
//   // Add JS & CSS by node type
//   if (isset($vars['node']) && $vars['node']->type == 'page') {
//     //drupal_add_js(path_to_theme(). '/js/supercool_scripts.js');
//     //drupal_add_css(path_to_theme(). '/css/supercool_sheet.css');
//   }
//   // Add JS & CSS by node ID
//   if (isset($vars['node']) && $vars['node']->nid == '1') {
//     //drupal_add_js(path_to_theme(). '/js/supercool_scripts.js');
//     //drupal_add_css(path_to_theme(). '/css/supercool_sheet.css');
//   }
// }

/**
 * Convert a number to its word
 * @param $number
 * @return string
 */
function _bear_skin_number_to_text($number) {
  $number = (int) $number;
  switch ($number) {
    case 0:
      return 'top';
    case 1:
      return 'one';
    case 2:
      return 'two';
    case 3:
      return 'three';
    case 4:
      return 'four';
    case 5:
      return 'five';
    case 6:
      return 'six';
    default:
      return '';
  }
}