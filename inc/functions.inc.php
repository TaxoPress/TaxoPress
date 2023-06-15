<?php

/**
 * trim and remove empty element
 *
 * @param string $element
 *
 * @return string
 */
function _delete_empty_element($element)
{
    $element = stripslashes($element);
    $element = trim($element);
    if (!empty($element)) {
        return $element;
    }

    return false;
}

/**
 * Test if page have tags or not...
 *
 * @return boolean
 * @author WebFactory Ltd
 */
function is_page_have_tags()
{
    $taxonomies = get_object_taxonomies('page');

    return in_array('post_tag', $taxonomies, true);
}

/**
 * Register widget on WP
 */
function st_register_widget()
{
    register_widget('SimpleTags_Widget');
    if (1 === (int) SimpleTags_Plugin::get_option_value('active_terms_display')) {
        register_widget('SimpleTags_Shortcode_Widget');
    }
    if (1 === (int) SimpleTags_Plugin::get_option_value('active_post_tags')) {
        register_widget('SimpleTags_PostTags_Widget');
    }
    if (1 === (int) SimpleTags_Plugin::get_option_value('active_related_posts_new')) {
        register_widget('SimpleTags_RelatedPosts_Widget');
    }
}

/**
 * Change menu item order
 */
add_action('custom_menu_order', 'taxopress_re_order_menu');
function taxopress_re_order_menu()
{
    global $submenu;

    foreach ($submenu as $menuName => $menuItems) {
        if ('st_options' === $menuName) {
            $taxopress_settings = $taxopress_dashboard = $taxopress_taxonomies = $taxopress_upgrade = false;

            $taxopress_submenus     = $submenu['st_options'];
            $dashboard_options      = taxopress_dashboard_options();
            $dashboard_option_pages = array_keys($dashboard_options);
            foreach ($taxopress_submenus as $key => $taxopress_submenu) {
                $slug_ = $taxopress_submenu[2];
                if (in_array($slug_, $dashboard_option_pages)) {
                    if (1 === (int) SimpleTags_Plugin::get_option_value($dashboard_options[$slug_]['option_key'])) {
                        $showHide = '';
                    } else {
                        $showHide = ' taxopress-hide-menu-item';
                    }
                    $taxopress_submenus[$key][4] = $slug_ . '-menu-item' . $showHide;
                }

                if ($taxopress_submenu[2] === 'st_options') { //settings
                    $taxopress_settings = $taxopress_submenus[$key];
                    unset($taxopress_submenus[$key]);
                }
                if ($taxopress_submenu[2] === 'st_taxonomies') { //taxonomies
                    $taxopress_taxonomies = $taxopress_submenus[$key];
                    unset($taxopress_submenus[$key]);
                }
                if ($taxopress_submenu[2] === 'st_dashboard') { //dashboard
                    $taxopress_dashboard = $taxopress_submenus[$key];
                    unset($taxopress_submenus[$key]);
                }
                if ($taxopress_submenu[2] === 'st_options-menu-upgrade-link') { //upgrade to pro link
                    $taxopress_upgrade = $taxopress_submenus[$key];
                    unset($taxopress_submenus[$key]);
                }
            }
            // add taxonomies first so it can fall as second item after dashboard
            if ($taxopress_taxonomies) {
                $taxopress_submenus = array_merge([$taxopress_taxonomies], $taxopress_submenus);
            }
            // add dashboard as first item
            if ($taxopress_dashboard) {
                $taxopress_submenus = array_merge([$taxopress_dashboard], $taxopress_submenus);
            }
            // add settings as last item
            if ($taxopress_settings) {
                $taxopress_submenus = array_merge($taxopress_submenus, [$taxopress_settings]);
            }
            // upgrade to pro should be last item if exists
            if ($taxopress_upgrade) {
                $taxopress_submenus = array_merge($taxopress_submenus, [$taxopress_upgrade]);
            }

            //resort array
            ksort($taxopress_submenus);
            $submenu['st_options'] = $taxopress_submenus;

            break;
        }
    }
}

// Init TaxoPress
function init_simple_tags()
{
    new SimpleTags_Client();
    new SimpleTags_Client_TagCloud();

    // Admin and XML-RPC
    if (is_admin()) {
        require STAGS_DIR . '/inc/class.admin.php';
        new SimpleTags_Admin();
    }

    add_action('widgets_init', 'st_register_widget');
}

function taxopress_admin_pages()
{

    $taxopress_pages = [
        'st_dashboard',
        'st_mass_terms',
        'st_auto',
        'st_options',
        'st_manage',
        'st_taxonomies',
        'st_terms_display',
        'st_post_tags',
        'st_related_posts',
        'st_autolinks',
        'st_autoterms',
        'st_suggestterms',
        'st_terms'
    ];

    return apply_filters('taxopress_admin_pages', $taxopress_pages);
}

function is_taxopress_admin_page()
{

    $taxopress_pages = taxopress_admin_pages();

    $is_taxopress_page = false;
    if (isset($_GET['page']) && in_array($_GET['page'], $taxopress_pages)) {
        $is_taxopress_page = true;
    }

    return apply_filters('is_taxopress_admin_page', $is_taxopress_page);
}

function taxopress_starts_with($haystack, $needle)
{
    $length = strlen($needle);
    return substr($haystack, 0, $length) === $needle;
}

function taxopress_is_html($string)
{
    return preg_match("/<[^<]+>/", $string, $m) != 0;
}



function taxopress_is_screen_main_page()
{

    if (isset($_GET['action']) && $_GET['action'] === 'edit') {
        return false;
    }

    if (isset($_GET['add']) && $_GET['add'] === 'new_item') {
        return false;
    }

    if (isset($_GET['add']) && $_GET['add'] === 'taxonomy') {
        return false;
    }

    return true;
}



function taxopress_format_class($class)
{
    return esc_attr(ltrim($class, '.'));
}

function taxopress_add_class_to_format($xformat, $class)
{
    $classes = $class;
    $html    = $xformat;

    $patterns = array();
    $replacements = array();

    // matches where anchor has existing classes contained in single quotes
    $patterns[0] = '/<a([^>]*)class=\'([^\']*)\'([^>]*)>/';
    $replacements[0] = '<a\1class="' . $classes . ' \2"\3>';

    // matches where anchor has existing classes contained in double quotes
    $patterns[1] = '/<a([^>]*)class="([^"]*)"([^>]*)>/';
    $replacements[1] = '<a\1class="' . $classes . ' \2"\3>';

    // matches where anchor tag has no existing classes
    $patterns[2] = '/<a(?![^>]*class)([^>]*)>/';
    $replacements[2] = '<a\1 class="' . $classes . '">';

    $html = preg_replace($patterns, $replacements, $html);

    return $html;
}

function taxopress_change_to_strings($join)
{
    if (is_array($join)) {
        $join = join(", ", $join);
    }
    return $join;
}

function taxopress_change_to_array($array)
{
    if (!is_array($array)) {
        $array = preg_split("/\s*,\s*/", $array);
    }
    return $array;
}

function taxopress_html_character_and_entity($enity_code_as_key = false)
{
    #Source https://dev.w3.org/html5/html-author/charref
    $character_set = [
        //'&amp;' => '&#38;',
        '&lt;' => '&#60;',
        '&gt;' => '&#62;',
        '&nbsp;' => '&#160;',
        '&excl;' => '&#33;',
        '&quot;' => '&#34;',
        '&num;'  => '&#35;',
        '&dollar;' => '&#36;',
        '&percnt;' => '&#37;',
        '&apos;' => '&#39;',
        '&lpar;' => '&#40;',
        '&rpar;' => '&#41;',
        '&ast; ' => '&#42;',
        '&plus;' => '&#43;',
        '&comma;' => '&#44;',
        '&period;' => '&#46;',
        '&sol;' => '&#47;',
        '&colon;' => '&#58',
        '&semi;' => '&#59;',
        '&equals;' => '&#61;',
        '&quest;' => '&#63;',
        '&commat;' => '&#64;',
        '&lsqb;' => '&#91;',
        '&bsol;' => '&#92;',
        '&rsqb;' => '&#93;',
        '&Hat;' => '&#94;',
        '&lowbar;' => '&#95;',
        '&grave;' => '&#96;',
        '&lcub;' => '&#123;',
        '&verbar;' => '&#124;',
        '&rcub;' => '&#125;',
        '&iexcl;' => '&#161;',
        '&cent;' => '&#162;',
        '&pound;' => '&#163;',
        '&copy;' => '&#169;',
        '&ordf;' => '&#170;',
        '&laquo;' => '&#171;',
        '&reg;' => '&#174;',
        '&deg;' => '&#176;',
        '&plusmn;' => '&#177;',
        '&sup2;' => '&#178;',
        '&sup3;' => '&#179;',
        '&acute;' => '&#180;',
        '&sup1;' => '&#185;',
        '&ordm;' => '&#186;',
        '&raquo;' => '&#187;',
        '&frac14;' => '&#188;',
        '&frac12;' => '&#189;',
        '&frac34;' => '&#190;',
        '&rarr;'   => '&#8594;',
        '&larr;'   => '&#8592;',
        '&uarr;'   => '&#8593;',
        '&darr;'   => '&#8595;',
        '&trade;'   => '&#8482;',
        '&rsquo;'   => '&#8217;',
        '&rsquor;'   => '&#8217;',
        '&lsquo;'   => '&#8216;',
        '&lsquor;'   => '&#8218;',
        '&amp;'   => '||taxopressamp||',
    ];

    if ($enity_code_as_key) {
        $character_set = array_flip($character_set);
    }

    return $character_set;
}

/**
 * Sanitize taxopress text field
 *
 * @param string $content
 * @return string
 */
function taxopress_sanitize_text_field($content)
{

    if (is_array($content)) {
        return stripslashes_deep(array_map('taxopress_sanitize_text_field', $content));
    }

    $content = stripslashes_deep($content);

    $content = wp_kses_post($content);

    return $content;
}



/**
 * Dashboard items
 *
 * @param mixed $current
 *
 * @return array
 */
function taxopress_dashboard_options()
{

    $features = [];

    $features['st_taxonomies'] = [
        'label'        => esc_html__('Taxonomies', 'simple-tags'),
        'description'  => esc_html__('This feature allows you to create new taxonomies and edit all the settings for each taxonomy.', 'simple-tags'),
        'option_key'   => 'active_taxonomies',
    ];

    $features['st_terms'] = [
        'label'        => esc_html__('Terms', 'simple-tags'),
        'description'  => esc_html__('This feature allows you to search and edit all the terms on your site.', 'simple-tags'),
        'option_key'   => 'active_st_terms',
    ];

    $features['st_terms_display'] = [
        'label'        => esc_html__('Terms Display', 'simple-tags'),
        'description'  => esc_html__('This feature allows you to create a customizable display of all the terms in one taxonomy.', 'simple-tags'),
        'option_key'   => 'active_terms_display',
    ];

    $features['st_post_tags'] = [
        'label'        => esc_html__('Terms for Current Post', 'simple-tags'),
        'description'  => esc_html__('This feature allows you create a customizable display of all the terms assigned to the current post.', 'simple-tags'),
        'option_key'   => 'active_post_tags',
    ];

    $features['st_related_posts'] = [
        'label'        => esc_html__('Related Posts', 'simple-tags'),
        'description'  => esc_html__('This feature creates a display of similar posts. If a post has the terms “WordPress” and “Website”, Related Posts will display other posts with those same terms.', 'simple-tags'),
        'option_key'   => 'active_related_posts_new',
    ];

    $features['st_autolinks'] = [
        'label'        => esc_html__('Auto Links', 'simple-tags'),
        'description'  => esc_html__('This feature automatically adds links to your chosen terms. If you have a term called “WordPress”, Auto Links finds the word “WordPress” in your content and add links to the archive page for that term.', 'simple-tags'),
        'option_key'   => 'active_auto_links',
    ];

    $features['st_autoterms'] = [
        'label'        => esc_html__('Auto Terms', 'simple-tags'),
        'description'  => esc_html__('Auto Terms can scan your content and automatically assign new and existing terms.', 'simple-tags'),
        'option_key'   => 'active_auto_terms',
    ];

    $features['st_suggestterms'] = [
        'label'        => esc_html__('Suggest Terms', 'simple-tags'),
        'description'  => esc_html__('This feature helps when you\'re writing content. "Suggest Terms" shows a box with all existing terms, and can also analyze your content to find new ideas for terms.', 'simple-tags'),
        'option_key'   => 'active_suggest_terms',
    ];

    $features['st_mass_terms'] = [
        'label'        => esc_html__('Mass Edit Terms', 'simple-tags'),
        'description'  => esc_html__('This feature allows you to quickly edit the terms attached to multiple posts at the same time.', 'simple-tags'),
        'option_key'   => 'active_mass_edit',
    ];

    $features['st_manage'] = [
        'label'        => esc_html__('Manage Terms', 'simple-tags'),
        'description'  => esc_html__('This feature allows you to add, rename, merge, and delete terms for any taxonomy.', 'simple-tags'),
        'option_key'   => 'active_manage',
    ];

    $features = apply_filters('taxopress_dashboard_features', $features);

    return $features;
}

/**
 * Check if current version of taxopress is
 * pro version
 *
 * @return void
 */
function taxopress_is_pro_version()
{
    return defined('TAXOPRESS_PRO_VERSION');
}
