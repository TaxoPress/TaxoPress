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

function taxopress_menu_separator($identifier, $parent) {
    // create a separator menu
    $separator_menu = [];
    $separator_menu[] = '';
    $separator_menu[] = $parent;
    $separator_menu[] = $parent . $identifier;
    $separator_menu[] = '';
    $separator_menu[] = 'taxopress-menu-separator ' . $parent . $identifier;

    return $separator_menu;
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
            $taxopress_settings = $taxopress_dashboard = $taxopress_taxonomies = $taxopress_linked_terms = $taxopress_upgrade = false;

            $taxopress_submenus     = $submenu['st_options'];
            $dashboard_options      = taxopress_dashboard_options();
            $dashboard_option_pages = array_keys($dashboard_options);
            foreach ($taxopress_submenus as $key => $taxopress_submenu) {
                $slug_ = $taxopress_submenu[2];
                $parent_ = $taxopress_submenu[1];
                if (in_array($slug_, $dashboard_option_pages)) {
                    if (1 === (int) SimpleTags_Plugin::get_option_value($dashboard_options[$slug_]['option_key'])) {
                        $showHide = '';
                    } else {
                        $showHide = ' taxopress-hide-menu-item';
                    }
                    $taxopress_submenus[$key][4] = $slug_ . '-menu-item' . $showHide;
                }

                if ($slug_ === 'st_options') { //settings
                    $taxopress_settings = $taxopress_submenus[$key];
                    unset($taxopress_submenus[$key]);
                }
                if ($slug_ === 'st_taxonomies') { //taxonomies
                    $taxopress_taxonomies = $taxopress_submenus[$key];
                    unset($taxopress_submenus[$key]);
                }
                if ($slug_ === 'st_dashboard') { //dashboard
                    $taxopress_dashboard = $taxopress_submenus[$key];
                    unset($taxopress_submenus[$key]);
                }
                if ($slug_ === 'st_linked_terms') { //linked terms
                    $taxopress_linked_terms = $taxopress_submenus[$key];
                    unset($taxopress_submenus[$key]);
                }
                
                if ($slug_ === 'st_options-menu-upgrade-link') { //upgrade to pro link
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
            // add linked terms
            if ($taxopress_linked_terms) {
               $taxopress_submenus = taxopress_add_at_menu_index(['st_terms', 'st_taxonomies', 'st_dashboard'], $taxopress_linked_terms, $taxopress_submenus);
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

            // add separator 1 to menus
            $separator1_positions = ['st_posts', 'st_linked_terms', 'st_terms', 'st_taxonomies', 'st_dashboard'];
            foreach ($separator1_positions as $pos) {
                $index = array_search($pos, array_column($taxopress_submenus, 2));
                if ($index !== false) {
                    $separator = taxopress_menu_separator('st_separator_end', 'simple_tags');
                    array_splice($taxopress_submenus, $index + 1, 0, [$separator]);
                    break;
                }
            }

            // Add separator 2 to menus
            $separator2_positions = ['st_autolinks', 'st_related_posts', 'st_post_tags', 'st_terms_display'];
            foreach ($separator2_positions as $pos) {
                 $index = array_search($pos, array_column($taxopress_submenus, 2));
                if ($index !== false) {
                    $separator = taxopress_menu_separator('st_separator_end', 'simple_tags');
                    array_splice($taxopress_submenus, $index + 1, 0, [$separator]);
                    break;
                }
            }

            // Add separator 3 to menus
            $separator2_positions = ['st_taxopress_ai', 'st_autoterms_content', 'st_autoterms'];
            foreach ($separator2_positions as $pos) {
                 $index = array_search($pos, array_column($taxopress_submenus, 2));
                if ($index !== false) {
                    $separator = taxopress_menu_separator('st_separator_end', 'simple_tags');
                    array_splice($taxopress_submenus, $index + 1, 0, [$separator]);
                    break;
                }
            }

            if (!taxopress_is_pro_version()) {
                // Add separator 4 to menus
                $index = array_search('st_options', array_column($taxopress_submenus, 2));
                if ($index !== false) {
                    $separator = taxopress_menu_separator('st_separator_end', 'simple_tags');
                    array_splice($taxopress_submenus, $index + 1, 0, [$separator]);
                }
            }

            //resort array
            ksort($taxopress_submenus);
            $submenu['st_options'] = $taxopress_submenus;

            break;
        }
    }
}

function taxopress_add_at_menu_index($key_options, $new_menu, $existing_menus) {

    foreach($key_options as $key_option) {
        $index = array_search($key_option, array_column($existing_menus, 2));
        if ($index !== false) {
            $index++;//add it after
            array_splice($existing_menus, $index, 0, [$new_menu]);
            break;
        }
    }
    // add as last if index is false;
    if ($index === false) {
        $existing_menus = array_merge($existing_menus, [$new_menu]);
    }

    return $existing_menus;
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
        'st_autoterms_content',
        'st_terms',
        'st_posts',
        'st_taxopress_ai'
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
        '&amp;'   => 'taxopressamp',
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

    $features['st_posts'] = [
        'label'        => esc_html__('Posts', 'simple-tags'),
        'description'  => esc_html__('This feature allows you to search for terms and see all the posts attached to that term.', 'simple-tags'),
        'option_key'   => 'active_st_posts',
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

/**
 * Case insensitive in_array function
 *
 * @param string $needle
 * @param array $haystack
 * @return bool
 */
function taxopress_in_array_i($needle, $haystack) {
    return in_array(strtolower($needle), array_map('strtolower', $haystack));
}

/**
 * Check if synonyms is enabled
 * 
 * @return bool
 */
function taxopress_is_synonyms_enabled() {
    return ((int) SimpleTags_Plugin::get_option_value('active_features_synonyms') === 1);
}

/**
 * Check if linked term is enabled
 * 
 * @return bool
 */
function taxopress_is_linked_terms_enabled() {
    return ((int) SimpleTags_Plugin::get_option_value('active_features_linked_terms') === 1);
}

/**
 * Get term synonyms
 *
 * @param string|integer $term
 * @param string $taxonomy
 * @return array  $term_synonyms
 */
function taxopress_get_term_synonyms($term, $taxonomy = '') {
    $term_synonyms = [];

    if (!taxopress_is_synonyms_enabled()) {
        // simply return empty array if feature is disabled
        return $term_synonyms;
    }

    if ((int)$term > 0) {
        $term_synonyms = (array) get_term_meta($term, '_taxopress_term_synonyms', true);
    } else {
        $terms_object = get_term_by('name', esc_attr($term), $taxonomy);
        if (is_object($terms_object) && isset($terms_object->term_id)) {
            $term_synonyms = (array) get_term_meta($terms_object->term_id, '_taxopress_term_synonyms', true);
        }
    }
    $term_synonyms = array_filter($term_synonyms);

    return $term_synonyms;
}

/**
 * Get linked terms
 *
 * @param string|integer $term
 * @param string $taxonomy
 * @param string $taxonomy
 * @return array $term_object
 */
function taxopress_get_linked_terms($term_id, $taxonomy = '', $term_object = false) {
    global $wpdb;

    $linked_terms = [];

    if (!taxopress_is_linked_terms_enabled()) {
        // simply return empty array if feature is disabled
        return $linked_terms;
    }

    $table_name = $wpdb->prefix . 'taxopress_linked_terms';

    $query = $wpdb->prepare(
        "SELECT * FROM $table_name WHERE term_id = %d OR linked_term_id = %d",
        $term_id,
        $term_id
    );

    $linked_terms = $wpdb->get_results($query);

    return $linked_terms;
}

/**
 * Find out which is a linked term in our data
 */
function taxopress_get_linked_term_data($linked_term_option, $term_id) {

    if ((int)$linked_term_option->linked_term_id === (int)$term_id) {
        $taxopress_linked_term_id       = $linked_term_option->term_id;
        $taxopress_linked_term_name     = $linked_term_option->term_name;
        $taxopress_linked_term_taxonomy = $linked_term_option->term_taxonomy;
    } else {
        $taxopress_linked_term_id       = $linked_term_option->linked_term_id;
        $taxopress_linked_term_name     = $linked_term_option->linked_term_name;
        $taxopress_linked_term_taxonomy = $linked_term_option->linked_term_taxonomy;
    }

    $linked_term = (object) [
        'term_id'       => $taxopress_linked_term_id,
        'term_name'     => $taxopress_linked_term_name,
        'term_taxonomy' => $taxopress_linked_term_taxonomy
    ];

    return $linked_term;
}

/**
 * Add linked terms and it synonyms to list
 *
 * @param array $lists
 * @param string|integer $term
 * @param string $lists
 * @param bool $linked
 * @param bool $named_term
 * 
 * @return array $term_object
 */
function taxopress_add_linked_term_options($lists, $term, $taxonomy, $linked = false, $named_term = false) {

    if (!taxopress_is_linked_terms_enabled()) {
        // simply return $lists if feature is disabled
        return $lists;
    }

    /**
     * Linked term is no longer in option and it's
     * now category wide. So, this function is useless until we 
     * think of how to make it work with our UI
     * 
     * So, let return original $lists for now
     */
    return $lists;

    if ((int)$term > 0) {
        $term_id = $term;
    } else {
        $terms_object = get_term_by('name', esc_attr($term), $taxonomy);
        if (is_object($terms_object) && isset($terms_object->term_id)) {
            $term_id = $terms_object->term_id;
        } else {
            $term_id = 0;
        }
    }

    if ($term_id > 0) {
        // get linked terms
        $linked_terms = taxopress_get_linked_terms($term_id, $taxonomy, true);
        if (!empty($linked_terms)) {
            if (!empty($linked_terms)) {
                foreach ($linked_terms as $linked_term) {
                    $linked_term_name = stripslashes($linked_term->name);
                    $linked_term_id   = $linked_term->term_id;
                    if ($linked) {
                        $term_value = get_term_link($linked_term, $linked_term->taxonomy);
                    } elseif ($named_term) {
                        $term_value = $linked_term_name;
                    } else {
                        $term_value = $linked_term_id;
                    }

                    // add linked term
                    $lists[$linked_term_name] = $term_value;

                    // add linked term synonyms
                    $term_synonyms = taxopress_get_term_synonyms($linked_term_id);
                    if (!empty($term_synonyms)) {
                        foreach ($term_synonyms as $term_synonym) {
                            $lists[$term_synonym] = $term_value;
                        }
                    }
                }
            }
        }
    }

    return $lists;
}

/**
 * Fetch our TAXOPRESS SuggestTerms option.
 * SuggestTerms screen has been removed but we need this function 
 * to migrate the needed settings.
 *
 * @return mixed
 */
function taxopress_get_suggestterm_data()
{
    return array_filter((array)apply_filters('taxopress_get_suggestterm_data', get_option('taxopress_suggestterms', []),
        get_current_blog_id()));
}

function taxopress_get_all_wp_roles() {
    global $wp_roles;

    if (!isset($wp_roles)) {
        $wp_roles = new \WP_Roles();
    }
    
    return $wp_roles->roles;
}

/**
 * Check if current user can manage taxopress metabox
 */
function can_manage_taxopress_metabox($user_id = false) {
    $can_manage = false;

    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    $user = get_userdata($user_id);
    if (is_object($user) && isset($user->roles)) {
        foreach ($user->roles as $role_name) {
            if (!empty(SimpleTags_Plugin::get_option_value('enable_' . $role_name . '_metabox'))) {
                $can_manage = true;
                break;
            }
        }

    }

    return $can_manage;
}

/**
 * Check if current user can manage metabox taxonomy
 */
function can_manage_taxopress_metabox_taxonomy($taxonomy, $user_id = false) {
    $can_manage = false;

    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    $user = get_userdata($user_id);
    if (is_object($user) && isset($user->roles)) {
        foreach ($user->roles as $role_name) {
            $role_options = (array) SimpleTags_Plugin::get_option_value('enable_metabox_' . $role_name . '');
            if (in_array($taxonomy, $role_options)) {
                $can_manage = true;
                break;
            }
        }

    }

    return $can_manage;
}

/**
 * Get all the taxonomy removed for current user
 */
function taxopress_user_role_removed_taxonomy($user_id = false) {

    $removed_taxonomies_tax = [];
    $removed_taxonomies_css = [];
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    $user = get_userdata($user_id);
    if (is_object($user) && isset($user->roles)) {
        foreach ($user->roles as $role_name) {
            $role_options = (array) SimpleTags_Plugin::get_option_value('remove_taxonomy_metabox_' . $role_name . '');
            $role_options = array_filter($role_options);
            if (!empty($role_options)) {
                foreach ($role_options as $removed_tax) {
                    $removed_taxonomies_tax[] = $removed_tax;
                    if ($removed_tax == 'category') {
                        $removed_taxonomies_css[] = '#category-add-toggle, #categories, #categorydiv, #categorydivsb, th.column-categories, td.categories, #screen-options-wrap label[for=categorydiv-hide]';
                    } elseif ($removed_tax == 'post_tag') {
                        $removed_taxonomies_css[] = '#tags, #tagsdiv,#tagsdivsb,#tagsdiv-post_tag, th.column-tags, td.tags, #screen-options-wrap label[for=tagsdiv-post_tag-hide]';
                    } else {
                        $removed_taxonomies_css[] = "#{$removed_tax}, #{$removed_tax}div,#{$removed_tax}divsb,#tagsdiv-{$removed_tax}, th.column-{$removed_tax}, td.{$removed_tax}, #screen-options-wrap label[for=tagsdiv-{$removed_tax}-hide], #screen-options-wrap label[for={$removed_tax}div-hide]";
                    }
                }
            }
        }

    }

    $removed_taxonomies = [
        'taxonomies' => $removed_taxonomies_tax,
        'custom_css' => $removed_taxonomies_css
    ];

    return $removed_taxonomies;
}