<?php 
//Taxopress auto terms => Auto terms all content ajax callback
add_action('wp_ajax_taxopress_autoterms_content_by_ajax', 'taxopress_autoterms_content_by_ajax');
function taxopress_autoterms_content_by_ajax()
{
    global $wpdb, $added_post_term;

        $added_post_term = [];

        // run a quick security check
        check_ajax_referer('st-admin-js', 'security');

        //instantiate response default value
        $response['status'] = 'error';
        $response['message'] = '';
        $response['content'] = '';
        $response['total'] = 0;
        $response['next_start'] = 0;
        $response['percentage'] = '';

        $autoterms      = taxopress_get_autoterm_data();
        $auto_term_id = !empty($_POST['taxopress_autoterm_content']['autoterm_id']) ? (int)$_POST['taxopress_autoterm_content']['autoterm_id'] : 0;

        $existing_terms_batches = !empty($_POST['taxopress_autoterm_content']['existing_terms_batches']) ? (int)$_POST['taxopress_autoterm_content']['existing_terms_batches'] : 0;
        $existing_terms_sleep = !empty($_POST['taxopress_autoterm_content']['existing_terms_sleep']) ? (int)$_POST['taxopress_autoterm_content']['existing_terms_sleep'] : 0;
        $limit_days = !empty($_POST['taxopress_autoterm_content']['limit_days']) ? (int)$_POST['taxopress_autoterm_content']['limit_days'] : 0;
        $autoterm_existing_content_exclude = !empty($_POST['taxopress_autoterm_content']['autoterm_existing_content_exclude']) ? (int)$_POST['taxopress_autoterm_content']['autoterm_existing_content_exclude'] : 0;

        $start_from = isset($_POST['start_from']) && (int)$_POST['start_from'] > 0 ? (int)$_POST['start_from'] : 0;
        $offset_start_from = max(0, $start_from);

        if(!current_user_can('simple_tags')){
            $response['message'] = '<div class="taxopress-response-css red"><p>'. esc_html__('Permission denied.', 'simple-tags') .'</p><button type="button" class="notice-dismiss"></button></div>';
            wp_send_json($response);
        }

        if ($start_from === 0) {
            delete_option('tmp_auto_terms_st');
        }

        $auto_term_settings = [
            'autoterm_id' => $auto_term_id,
            'existing_terms_batches' => $existing_terms_batches,
            'existing_terms_sleep' => $existing_terms_sleep,
            'limit_days' => $limit_days,
            'autoterm_existing_content_exclude' => $autoterm_existing_content_exclude,
        ];
        update_option('taxopress_autoterms_content', $auto_term_settings);

        if (empty($auto_term_id)) {
            $response['message'] = '<div class="taxopress-response-css red"><p>'. esc_html__('Auto Term is required, kindly add an Auto Term from Auto Term menu.', 'simple-tags') .'</p><button type="button" class="notice-dismiss"></button></div>';
            wp_send_json($response);
        } elseif (empty($existing_terms_batches)) {
            $response['message'] = '<div class="taxopress-response-css red"><p>'. esc_html__('Limit per batches is required.', 'simple-tags') .'</p><button type="button" class="notice-dismiss"></button></div>';
            wp_send_json($response);
        } elseif (empty($existing_terms_sleep)) {
            $response['message'] = '<div class="taxopress-response-css red"><p>'. esc_html__('Batches wait time is required.', 'simple-tags') .'</p><button type="button" class="notice-dismiss"></button></div>';
            wp_send_json($response);
        }

        if ($auto_term_id && array_key_exists($auto_term_id, $autoterms)) {
                $autoterm_data       = $autoterms[$auto_term_id];
                $autoterm_data['existing_terms_batches'] = $existing_terms_batches;
                $autoterm_data['existing_terms_sleep'] = $existing_terms_sleep;
                $autoterm_data['limit_days'] = $limit_days;
                $autoterm_data['autoterm_exclude'] = $autoterm_existing_content_exclude;
        }else{
            $response['message'] = '<div class="taxopress-response-css red"><p>'. esc_html__('Auto term settings not found', 'simple-tags') .'</p><button type="button" class="notice-dismiss"></button></div>';
            wp_send_json($response);
        }

        if (empty($autoterm_data['post_types'])) {
            $response['message'] = '<div class="taxopress-response-css red"><p>'. esc_html__('The selected Auto Term setting is not enabled for any post type. Please select at least one post type in the Auto Term settings.', 'simple-tags') .'</p><button type="button" class="notice-dismiss"></button></div>';
            wp_send_json($response);
        }

        $limit = (isset($autoterm_data['existing_terms_batches']) && (int)$autoterm_data['existing_terms_batches'] > 0) ? (int)$autoterm_data['existing_terms_batches'] : 20;

        $sleep = (isset($autoterm_data['existing_terms_sleep']) && (int)$autoterm_data['existing_terms_sleep'] > 0) ? (int)$autoterm_data['existing_terms_sleep'] : 0;
        
        if($sleep > 0 && $start_from > 0){
            sleep($sleep);
        }

        $limit_days     = (int) $autoterm_data['limit_days'];
		$limit_days_sql = '';
		if ( $limit_days > 0 ) {
			$limit_days_sql = 'AND post_date > "' . date( 'Y-m-d H:i:s', time() - $limit_days * 86400 ) . '"';
		}

        $post_types = $autoterm_data['post_types'];
        $post_status = isset($autoterm_data['post_status']) && is_array($autoterm_data['post_status']) ? $autoterm_data['post_status'] : ['publish'];

        $total = isset($_POST['total']) ? (int)$_POST['total'] : 0;

        if($autoterm_existing_content_exclude > 0){
            $objects = (array) $wpdb->get_results("SELECT ID, post_title, post_content FROM {$wpdb->posts} LEFT JOIN {$wpdb->postmeta} ON ( ID = {$wpdb->postmeta}.post_id AND {$wpdb->postmeta}.meta_key = '_taxopress_autotermed' ) WHERE post_type IN ('" . implode("', '", $post_types) . "') AND {$wpdb->postmeta}.post_id IS NULL AND post_status IN ('" . implode("', '", $post_status) . "') {$limit_days_sql} ORDER BY ID DESC LIMIT {$limit} OFFSET {$offset_start_from}");
        }else{
            $objects = (array) $wpdb->get_results("SELECT ID, post_title, post_content FROM {$wpdb->posts} WHERE post_type IN ('" . implode("', '", $post_types) . "') AND post_status IN ('" . implode("', '", $post_status) . "') {$limit_days_sql} ORDER BY ID DESC LIMIT {$limit} OFFSET {$offset_start_from}");
        }
        
        $response_content = '';
        if (!empty($objects)) {
            foreach ($objects as $object) {
                update_post_meta($object->ID, '_taxopress_autotermed', 1);
                SimpleTags_Client_Autoterms::auto_terms_post( $object, $autoterm_data['taxonomy'], $autoterm_data, true, 'existing_content', 'st_autoterms' );
                if (!empty($added_post_term[$object->ID])) {
                    $tag_lists = array_map(function($item) {
                        return '<span class="taxopress-term"><span class="term-name">' . $item . '</span></span>';
                    }, $added_post_term[$object->ID]);
                    $added_terms_html = join('', $tag_lists);
                } else {
                    $added_terms_html = esc_html__('No terms added.', 'simple-tags');

                }
                $response_content .= '<li class="result-item">
                <fieldset>
                    <legend> 
                        <span class="result-title">
                            <a target="_blank" href="'. esc_url(get_edit_post_link($object->ID)) . '">' . $object->post_title . '</a>
                        </span> 
                    </legend>
                    <div class="result-content">'. $added_terms_html .'</div>
                </fieldset></li>';
                unset($object);
            }

            $response['status'] = 'progress';
            $response['content'] = $response_content;
            $response['done'] = ($start_from + count($objects));
            $percentage = 100;
            $response['notice'] = '<div class="taxopress-response-css yellow"><p>'. sprintf(esc_html__('Please leave this screen running to continue the scan. To stop the scan, close this screen or click this button: %1s Stop %2s', 'simple-tags'), '<a href="#" class="terminate-autoterm-scan">', '</a>') .'</p></div>';
            $progress_message = '<div class="taxopress-response-css yellow"><p>'. sprintf(esc_html__('Progress Report: %s posts checked.', 'simple-tags'), '<strong>' . ($start_from + count($objects)) . '</strong>') .'</p></div>';
              
        } else {

            $counter = (int)get_option('tmp_auto_terms_st');
            delete_option('tmp_auto_terms_st');
            $percentage = 100;
            $response['status'] = 'sucess';
            $response['done'] = $total;
            $progress_message = '<div class="taxopress-response-css green"><p>'. sprintf(esc_html__('Completed: %s terms added from %s posts checked.', 'simple-tags'), $counter, ($start_from + count($objects))) .'</p><button type="button" class="notice-dismiss"></button></div>';
            $response['message'] = $progress_message;
        }
        $response['percentage'] = $progress_message;
        
        wp_send_json($response);

}