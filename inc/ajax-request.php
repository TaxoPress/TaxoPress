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
        $start_from = isset($_POST['start_from']) ? (int)$_POST['start_from'] : 0;


        if(!current_user_can('simple_tags')){
            $response['message'] = __('Permission denied.', 'simple-tags');
            wp_send_json($response);
        }

        if($auto_term_id === 0 ){
            $response['message'] = __('Kindly save your auto terms settings before running this function', 'simple-tags');
            wp_send_json($response);
        }

        if ($auto_term_id && array_key_exists($auto_term_id, $autoterms)) {
                $autoterm_data       = $autoterms[$auto_term_id];
        }else{
            $response['message'] = __('Auto term settings not found', 'simple-tags');
            wp_send_json($response);
        }

        $post_types = $autoterm_data['post_types'];
        $post_status = isset($autoterm_data['post_status']) && is_array($autoterm_data['post_status']) ? $autoterm_data['post_status'] : ['publish'];

        $total = isset($_POST['total']) ? (int)$_POST['total'] : $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('%s') AND post_status IN ('%s')", implode( "', '", $post_types ), implode( "', '", $post_status ) ));
        $response['total'] = $total;
        $objects = (array) $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_content FROM {$wpdb->posts} WHERE post_type IN ('%s') AND post_status IN ('%s') ORDER BY ID DESC LIMIT %d, 20", implode( "', '", $post_types ), implode( "', '", $post_status), $start_from ));

        $response_content = '';
        if (!empty($objects)) {
            foreach ($objects as $object) {
                SimpleTags_Client_Autoterms::auto_terms_post( $object, $autoterm_data['taxonomy'], $autoterm_data, true );
                $response_content .= '<li>#' . $object->ID . ' ' . $object->post_title . '</li>';
                unset($object);
            }
            $response['status'] = 'progress';
            $response['content'] = $response_content;
            $response['done'] = ($start_from + count($objects));
            $percentage = round((($start_from + count($objects))/$total)*100);
              
        } else {

            $counter = (int)get_option('tmp_auto_terms_st');
            delete_option('tmp_auto_terms_st');
            $response['status'] = 'sucess';
            $response['done'] = $total;
            $response['message'] = sprintf(__('All done! %s terms added.', 'simple-tags'), $counter);
            $percentage = 100;
        }
            $response['percentage'] = '<div class="taxopress-loader-border"><div class="taxopress-loader-green" style="width:'.$percentage.'%;">'.$percentage.'%</div></div>';
        
            wp_send_json($response);

}

?>