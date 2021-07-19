<?php

class SimpleTags_Admin_AutoTags
{

    const MENU_SLUG = 'st_options';
    // Build admin URL
    static $admin_base_url = '';

    /**
     * SimpleTags_Admin_AutoTags constructor.
     */
    public function __construct()
    {
        self::$admin_base_url = admin_url('admin.php') . '?page=';

        // Admin menu
        add_action('admin_menu', array(__CLASS__, 'admin_menu'));

        // Register taxo, parent method...
        SimpleTags_Admin::register_taxonomy();
    }

    /**
     * Add WP admin menu for Tags
     *
     * @return void
     * @author WebFactory Ltd
     */
    public static function admin_menu()
    {
        add_submenu_page(
            self::MENU_SLUG,
            __('TaxoPress: Auto Terms', 'simpletags'),
            __('Auto Terms', 'simpletags'),
            'simple_tags',
            'st_auto',
            array(
                __CLASS__,
                'pageAutoTerms',
            )
        );
    }

    /**
     * WP Page - Auto Tags
     *
     * @return void
     * @author WebFactory Ltd
     */
    public static function pageAutoTerms()
    {
        global $wpdb;

        // Get options
        $options = get_option(STAGS_OPTIONS_NAME_AUTO);
        if ($options == false) // First save ?
        {
            $options = array();
        }

        if (!isset($options[SimpleTags_Admin::$post_type])) { // First save for this CPT ?
            $options[SimpleTags_Admin::$post_type] = array();
        }

        if (!isset($options[SimpleTags_Admin::$post_type][SimpleTags_Admin::$taxonomy])) { // First save for this taxo ?
            $options[SimpleTags_Admin::$post_type][SimpleTags_Admin::$taxonomy] = array();
        }

        $taxo_options = $options[SimpleTags_Admin::$post_type][SimpleTags_Admin::$taxonomy]; // Edit local option taxo

        $action = false;
        if (isset($_POST['update_auto_list'])) {
            check_admin_referer('update_auto_list-simpletags');

            // Tags list
            $terms = isset($_POST['auto_list']) ? (array) $_POST['auto_list'] : [];

            // Remove empty and duplicate elements
            $terms = array_filter($terms, '_delete_empty_element');
            $terms = array_unique($terms);

            $taxo_options['auto_list'] = maybe_serialize($terms);

            // Active auto terms ?
            $taxo_options['use_auto_terms'] = (isset($_POST['use_auto_terms']) && $_POST['use_auto_terms'] == '1') ? '1' : '0';

            // All terms ?
            $taxo_options['at_all'] = (isset($_POST['at_all']) && $_POST['at_all'] == '1') ? '1' : '0';

            // Selected terms ?
            $taxo_options['at_all_no'] = (isset($_POST['at_all_no']) && $_POST['at_all_no'] == '1') ? '1' : '0';

            // Empty only ?
            $taxo_options['at_empty'] = (isset($_POST['at_empty']) && $_POST['at_empty'] == '1') ? '1' : '0';

            // Full word ?
            $taxo_options['only_full_word'] = (isset($_POST['only_full_word']) && $_POST['only_full_word'] == '1') ? '1' : '0';

            // Support hashtag format ?
            $taxo_options['allow_hashtag_format'] = (isset($_POST['allow_hashtag_format']) && $_POST['allow_hashtag_format'] == '1') ? '1' : '0';

            $options[SimpleTags_Admin::$post_type][SimpleTags_Admin::$taxonomy] = $taxo_options;
            update_option(STAGS_OPTIONS_NAME_AUTO, $options);

            add_settings_error(__CLASS__, __CLASS__, __('Auto terms options updated !', 'simpletags'), 'updated');
        } elseif (isset($_GET['action']) && $_GET['action'] == 'auto_tag') {
            $action = true;
            $n      = (isset($_GET['n'])) ? intval($_GET['n']) : 0;
        }

        $terms_list = [];
        if (isset($taxo_options['auto_list']) && !empty($taxo_options['auto_list'])) {
            $terms = maybe_unserialize($taxo_options['auto_list']);
            if (is_array($terms)) {
                $terms_list = $terms;
            }
        } else {
        }

        settings_errors(__CLASS__);
?>
        <div class="wrap st_wrap">
            <h2><?php _e('Overview', 'simpletags'); ?>
                <?php SimpleTags_Admin::boxSelectorTaxonomy('st_auto'); ?>
                <div class="clear"></div>
        </div>

        <div class="wrap st_wrap">
            <h2><?php printf(__('Auto Terms for %s and %s', 'simpletags'), '<strong>' . SimpleTags_Admin::$post_type_name . '</strong>', '<strong>' . SimpleTags_Admin::$taxo_name . '</strong>'); ?></h2>

            <?php if ($action === false) : ?>

                <h3><?php _e('Auto terms list', 'simpletags'); ?></h3>

                <form action="<?php echo self::$admin_base_url . 'st_auto&taxo=' . SimpleTags_Admin::$taxonomy . '&cpt=' . SimpleTags_Admin::$post_type; ?>" method="post">

                    <p><?php printf(__('This feature allows Wordpress to look into %s content and title for specified terms when saving %s. If your %s content or title contains the word "WordPress" and you have "wordpress" in auto terms list, TaxoPress will add automatically "wordpress" as term for this %s.', 'simpletags'), SimpleTags_Admin::$post_type_name, SimpleTags_Admin::$post_type_name, SimpleTags_Admin::$post_type_name, SimpleTags_Admin::$post_type_name); ?></p>

                    <table class="form-table">

                        <tr valign="top">
                            <th scope="row"><?php _e('Activation', 'simpletags'); ?></th>
                            <td>
                                <input type="checkbox" id="use_auto_terms" name="use_auto_terms" value="1" <?php echo (isset($taxo_options['use_auto_terms']) && $taxo_options['use_auto_terms'] == 1) ? 'checked="checked"' : ''; ?> />
                                <label for="use_auto_terms"><?php printf(__('Activate Auto Tags for the %s taxonomy', 'simpletags'), SimpleTags_Admin::$taxo_name); ?>
                                </label>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e('Terms to use', 'simpletags'); ?></th>
                            <td>
                                <input type="checkbox" id="at_all" name="at_all" value="1" <?php echo (isset($taxo_options['at_all']) && $taxo_options['at_all'] == 1) ? 'checked="checked"' : ''; ?> />
                                <label for="at_all"><?php printf(__('Use all the terms in the %s taxonomy. (Warning, this option can increases the CPU consumption a lot if you have many terms)', 'simpletags'), SimpleTags_Admin::$taxo_name); ?></label>

                                <br /><br />
                                <input type="checkbox" id="at_all_no" name="at_all_no" value="1" <?php echo (isset($taxo_options['at_all_no']) && $taxo_options['at_all_no'] == 1) ? 'checked="checked"' : ''; ?> />
                                <label for="at_all_no"><?php printf(__('Use this option if you don\'t want to use all the terms in your %s taxonomy. You can select specific terms to use.', 'simpletags'), SimpleTags_Admin::$taxo_name); ?></label>

                    <div class="auto-terms-error-red"> <?php echo __('Please choose an option for "Terms to use"', 'simpletags'); ?> </div>

                                <br /><br />
                                 <div class="auto-terms-keyword-list-wrapper">
                                <h3 class="auto-terms-keyword-title"><?php _e('Keywords list', 'simpletags'); ?> </h3> <input class="st-add-suggestion-input" type="button" value="Add +" /> <label for="auto_list">
                                </label>
                    
                    <div class="auto-terms-keyword-list">
                        <?php
                        if (count($terms_list) > 0) {
                            $current = 0;
                            foreach ($terms_list as $term) {
                                $current++;
                                echo '<input type="text" name="auto_list[]" value="' . esc_attr($term) . '" /> <input class="st-delete-suggestion-input" type="button" value="Delete"/>';
                            }
                        } else {
                            echo '<input type="text" name="auto_list[]" /> <input class="st-delete-suggestion-input" type="button" value="Delete"/>';
                        }

                        ?>

                    </div>
                        

                    
                    </div>

                            </td>
                        </tr>

                    </table>


                    <h3><?php _e('Options', 'simpletags'); ?></h3>

                    <table class="form-table">

                        <tr valign="top">
                            <th scope="row"><?php _e('Target', 'simpletags'); ?></th>
                            <td>
                                <input type="checkbox" id="at_empty" name="at_empty" value="1" <?php echo (isset($taxo_options['at_empty']) && $taxo_options['at_empty'] == 1) ? 'checked="checked"' : ''; ?> />
                                <label for="at_empty"><?php printf(__('Autotag only %s without terms.', 'simpletags'), SimpleTags_Admin::$post_type_name); ?></label>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e('Whole Word ?', 'simpletags'); ?></th>
                            <td>
                                <input type="checkbox" id="only_full_word" name="only_full_word" value="1" <?php echo (isset($taxo_options['only_full_word']) && $taxo_options['only_full_word'] == 1) ? 'checked="checked"' : ''; ?> />
                                <label for="only_full_word"><?php printf(__('Autotag %s only when terms found in the content are the same word.', 'simpletags'), SimpleTags_Admin::$post_type_name); ?></label>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php _e('Support Hashtag format ?', 'simpletags'); ?></th>
                            <td>
                                <input type="checkbox" id="allow_hashtag_format" name="allow_hashtag_format" value="1" <?php echo (isset($taxo_options['allow_hashtag_format']) && $taxo_options['allow_hashtag_format'] == 1) ? 'checked="checked"' : ''; ?> />
                                <label for="allow_hashtag_format"><?php _e('When the whole word option is enabled, hashtag will not be autotag because of # prefix. This option allow to fixed this issue!', 'simpletags'); ?></label>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <?php wp_nonce_field('update_auto_list-simpletags'); ?>
                        <input class="button-primary update_auto_list" type="submit" name="update_auto_list" value="<?php _e('Update options &raquo;', 'simpletags'); ?>" />
                    </p>
                </form>

                <h3><?php _e('Auto terms old content', 'simpletags'); ?></h3>
                <p>
                    <?php _e('TaxoPress can also tag all existing contents of your blog. This feature use auto terms list above-mentioned.', 'simpletags'); ?>
                </p>
                <p class="submit">
                    <a class="button-primary" href="<?php echo self::$admin_base_url . 'st_auto&amp;taxo=' . SimpleTags_Admin::$taxonomy . '&amp;cpt=' . SimpleTags_Admin::$post_type . '&amp;action=auto_tag'; ?>"><?php _e('Auto terms all content &raquo;', 'simpletags'); ?></a>
                </p>

                <?php else :
                // Counter
                {
                    if ($n == 0) {
                        update_option('tmp_auto_terms_st', 0);
                    }

                    // Get objects
                    $objects = (array) $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_content FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish' ORDER BY ID DESC LIMIT %d, 20", SimpleTags_Admin::$post_type, $n));

                    if (!empty($objects)) {
                        echo '<ul>';
                        foreach ($objects as $object) {
                            SimpleTags_Client_Autoterms::auto_terms_post($object, SimpleTags_Admin::$taxonomy, $taxo_options, true);

                            echo '<li>#' . $object->ID . ' ' . $object->post_title . '</li>';
                            unset($object);
                        }
                        echo '</ul>';
                ?>
                        <p><?php _e("If your browser doesn't start loading the next page automatically click this link:", 'simpletags'); ?>
                            <a href="<?php echo self::$admin_base_url . 'st_auto&amp;taxo=' . SimpleTags_Admin::$taxonomy . '&amp;cpt=' . SimpleTags_Admin::$post_type . '&amp;action=auto_tag&amp;n=' . ($n + 20); ?>"><?php _e('Next content', 'simpletags'); ?></a>
                        </p>
                        <script type="text/javascript">
                            // <![CDATA[
                            function nextPage() {
                                location.href = "<?php echo self::$admin_base_url . 'st_auto&taxo=' . SimpleTags_Admin::$taxonomy . '&cpt=' . SimpleTags_Admin::$post_type . '&action=auto_tag&n=' . ($n + 20); ?>";
                            }
                            window.setTimeout('nextPage()', 300);
                            // ]]>
                        </script>
            <?php
                    } else {
                        $counter = get_option('tmp_auto_terms_st');
                        delete_option('tmp_auto_terms_st');
                        echo '<p><strong>' . sprintf(__('All done! %s terms added.', 'simpletags'), $counter) . '</strong></p>';
                    }
                }

            endif;
            ?>
            <?php SimpleTags_Admin::printAdminFooter(); ?>
        </div>
<?php
        do_action('simpletags-auto_terms', SimpleTags_Admin::$taxonomy);
    }
}
