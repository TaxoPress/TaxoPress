<?php 
//Taxopress auto terms => Auto terms all content ajax callback
add_action('wp_ajax_taxopress_autoterms_content_by_ajax', 'taxopress_autoterms_content_by_ajax');
function taxopress_autoterms_content_by_ajax()
{
    global $wpdb;

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
        $auto_term_id = isset($_POST['auto_term_id']) ? (int)$_POST['auto_term_id'] : 0;
        $start_from = ( isset($_POST['start_from']) && (int)$_POST['start_from'] > 0) ? ((int)$_POST['start_from']) : 0;
        $offset_start_from = ( isset($_POST['start_from']) && (int)$_POST['start_from'] > 0) ? ((int)$_POST['start_from']-1) : 0;


        if(!current_user_can('simple_tags')){
            $response['message'] = esc_html__('Permission denied.', 'simple-tags');
            wp_send_json($response);
        }

        if($auto_term_id === 0 ){
            $response['message'] = esc_html__('Kindly save your auto terms settings before running this function', 'simple-tags');
            wp_send_json($response);
        }

        if ($auto_term_id && array_key_exists($auto_term_id, $autoterms)) {
                $autoterm_data       = $autoterms[$auto_term_id];
        }else{
            $response['message'] = esc_html__('Auto term settings not found', 'simple-tags');
            wp_send_json($response);
        }

        $limit = (isset($autoterm_data['existing_terms_batches']) && (int)$autoterm_data['existing_terms_batches'] > 0) ? (int)$autoterm_data['existing_terms_batches'] : 20;

        $sleep = (isset($autoterm_data['existing_terms_sleep']) && (int)$autoterm_data['existing_terms_sleep'] > 0) ? (int)$autoterm_data['existing_terms_sleep'] : 0;

        $autoterm_existing_content_exclude = isset($autoterm_data['autoterm_existing_content_exclude']) ? (int)$autoterm_data['autoterm_existing_content_exclude'] : 0;
        
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
                $response_content .= '<li>#' . $object->ID . ' ' . $object->post_title . '</li>';
                unset($object);
            }
            $response['status'] = 'progress';
            $response['content'] = $response_content;
            $response['done'] = ($start_from + count($objects));
            $percentage = 100;
            $progress_message = sprintf(esc_html__('Progress report: %s posts checked.', 'simple-tags'), ($start_from + count($objects)));
              
        } else {

            $counter = (int)get_option('tmp_auto_terms_st');
            delete_option('tmp_auto_terms_st');
            $response['status'] = 'sucess';
            $response['done'] = $total;
            $response['message'] = sprintf(esc_html__('All done! %s terms added.', 'simple-tags'), $counter);
            $percentage = 100;
            $progress_message = sprintf(esc_html__('Completed: %s posts checked.', 'simple-tags'), ($start_from + count($objects)));
        }
            $response['percentage'] = '<div class="taxopress-loader-border"><div class="taxopress-loader-green" style="width:100%;">'.$progress_message.'</div></div>';
        
            wp_send_json($response);

}