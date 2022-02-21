<?php 

add_action('taxopress_autoterms_after_autoterm_logs', 'taxopress_autoterms_log_display');
function taxopress_autoterms_log_display($current){

    $item_per_page = (isset($current) && isset($current['logs_per_page']) && (int)$current['logs_per_page'] > 0) ? (int)$current['logs_per_page'] : 20;
    $item_page = (isset($_GET['log_page']) && (int)$_GET['log_page'] > 0) ? (int)$_GET['log_page'] : 1;
    
    $meta_query[] = array('relation' => 'AND');
    $meta_query[] = array(
        'key' => '_taxopress_log_component',
        'value' => 'st_autoterms',
    );
    $logs_arg = array(
        'post_type' => 'taxopress_logs',
        'post_status' => 'publish',
        'paged' => $item_page,
        'posts_per_page' => $item_per_page,
        'meta_query' => $meta_query,
        'fields' => 'ids'
    );

    $logs = new WP_Query($logs_arg);
    $post_ids = $logs->posts;

    ?>
    <tr valign="top">
        <td>

        <?php if(count($post_ids) > 0){ 
            
    $page_links = paginate_links( array(
        'base'    => add_query_arg( 'log_page', '%#%' ),
        'format'  => '',
        'total'   => ceil( $logs->found_posts / $item_per_page ),
        'current' => $item_page
    ) );
    ?>
            
		<div class="tablenav" style="display: none;">
            <div class='tablenav-pages'><?php echo $logs->found_posts; ?> <?php esc_html_e( 'Items', 'simple-tags' ); ?></div>
            <div style="float: left">
                <label for="st_autoterms_per_page"><?php esc_html_e( 'Number of items to display', 'simple-tags' ); ?></label>
                <input style="width: auto;" type="number" step="1" min="1" max="999" name="taxopress_autoterm[logs_per_page]" maxlength="3" value="<?php echo $item_per_page; ?>">
            </div>
            <div style="clear:both;"></div>
        </div>

        <div class="taxopress-log-table">
        <table class="visbile-table widefat post fixed">
            <caption><?php esc_html_e( 'Auto term logs', 'simple-tags' ); ?> (<?php echo $logs->found_posts; ?> <?php esc_html_e( 'Items', 'simple-tags' ); ?>)</caption>
            <thead>
                <tr>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'Post', 'simple-tags' ); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'Post type', 'simple-tags' ); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'Taxonomy', 'simple-tags' ); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'Source', 'simple-tags' ); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'Date', 'simple-tags' ); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'Terms added', 'simple-tags' ); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'Status', 'simple-tags' ); ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'Status message', 'simple-tags' ); ?></th>
				</tr>
			</thead>
			
            <tbody>
                <?php
				 $class = 'alternate';
				foreach($post_ids as $post_id){
                    $class = ( $class == 'alternate' ) ? '' : 'alternate';
                    $auto_term_log_post_id = get_post_meta($post_id, '_taxopress_log_post_id', true);
                    $taxopress_log_taxonomy = get_post_meta($post_id, '_taxopress_log_taxonomy', true);
                    $taxopress_log_taxonomy_data = get_taxonomy($taxopress_log_taxonomy);
                    $taxopress_log_taxonomy_name = ($taxopress_log_taxonomy_data && !is_wp_error($taxopress_log_taxonomy_data)) ? $taxopress_log_taxonomy_data->labels->singular_name : '';
                    $auto_term_log_posttype = get_post_type_object(get_post_type($auto_term_log_post_id));
                    $auto_term_log_posttype_name = ($auto_term_log_posttype && !is_wp_error($auto_term_log_posttype)) ? $auto_term_log_posttype->labels->singular_name : '';
                    $auto_term_post_date =get_the_date('l F j, Y h:i A', $post_id);
                    $taxopress_log_status = get_post_meta($post_id, '_taxopress_log_status', true);
                    $taxopress_log_terms = get_post_meta($post_id, '_taxopress_log_terms', true);
                    $taxopress_log_status_message = get_post_meta($post_id, '_taxopress_log_status_message', true);
                    $taxopress_log_action = get_post_meta($post_id, '_taxopress_log_action', true);

                    $log_action_texts = [
                        'save_posts' => esc_html__( 'Manual post update', 'simple-tags' ),
                        'existing_content' => esc_html__( 'Existing content tab action', 'simple-tags' ),
                        'daily_cron_schedule' => esc_html__( 'Scheduled daily cron', 'simple-tags' ),
                        'hourly_cron_schedule' => esc_html__( 'Scheduled hourly cron', 'simple-tags' )
                    ];

                    $log_action_color = [
                        'save_posts' => 'blueviolet',
                        'existing_content' => 'brown',
                        'daily_cron_schedule' => 'chocolate',
                        'hourly_cron_schedule' => 'crimson'
                    ];

                    $log_color = array_key_exists($taxopress_log_action, $log_action_color) ? $log_action_color[$taxopress_log_action] : 'green';


                    $status_message_text = [
                        'invalid_option' => esc_html__( 'Auto terms settings does not exist.', 'simple-tags' ),
                        'term_only_option' => esc_html__( 'Auto terms settings configured to skip post with terms.', 'simple-tags' ),
                        'empty_post_content' => esc_html__( 'Post content is empty.', 'simple-tags' ),
                        'terms_added' => esc_html__( 'Terms added successfully', 'simple-tags' ),
                        'empty_terms' => esc_html__( 'No matching terms for auto terms settings options and the post post', 'simple-tags' )
                    ];
					?>
                    <tr valign="top" class="<?php echo esc_attr($class); ?>">
                        <td data-label="<?php esc_attr_e( 'Post', 'simple-tags' ); ?>" scope="row">
                            <a href="<?php echo esc_url(admin_url('post.php?post='.$auto_term_log_post_id.'&action=edit')); ?>">
                                <?php echo esc_html(get_the_title($auto_term_log_post_id)); ?>
                            </a>
                        </td>
                        <td data-label="<?php esc_attr_e( 'Post type', 'simple-tags' ); ?>">
                            <?php echo esc_html($auto_term_log_posttype_name); ?>
                        </td>
                        <td data-label="<?php esc_attr_e( 'Taxonomy', 'simple-tags' ); ?>">
                            <?php echo esc_html($taxopress_log_taxonomy_name); ?>
                        </td>
                        <td data-label="<?php esc_attr_e( 'Source', 'simple-tags' ); ?>">
                            <font color="<?php echo esc_attr($log_color); ?>">
                                <?php 
                                if(array_key_exists($taxopress_log_action, $log_action_texts)){
                                    echo esc_html($log_action_texts[$taxopress_log_action]);
                                }else{
                                    echo esc_html($taxopress_log_action);
                                }
                                ?>
                            </font>
                        </td>
                        <td data-label="<?php esc_attr_e( 'Date', 'simple-tags' ); ?>">
                            <?php echo esc_html($auto_term_post_date); ?>
                        </td>
                        <td data-label="<?php esc_attr_e( 'Terms added', 'simple-tags' ); ?>">
                            <?php 
                            if($taxopress_log_terms && !empty(trim($taxopress_log_terms))){
                                echo '<font color="green"> '.esc_html(ucwords($taxopress_log_terms)).' </font>';
                            }else{
                                echo '<font color="red"> '. esc_html__('None', 'simple-tags') .' </font>';
                            }
                            ?>
                        </td>
                        <td data-label="<?php esc_attr_e( 'Status', 'simple-tags' ); ?>">
                            <?php 
                            if($taxopress_log_status === 'failed'){
                                echo '<font color="red"> '.esc_html(ucwords($taxopress_log_status)).' </font>';
                            }else{
                                echo '<font color="green"> '.esc_html(ucwords($taxopress_log_status)).' </font>';
                            }
                            ?>
                        </td>
                        <td data-label="<?php esc_attr_e( 'Status message', 'simple-tags' ); ?>">
                            <?php 
                            if(array_key_exists($taxopress_log_status_message, $status_message_text)){
                                echo esc_html($status_message_text[$taxopress_log_status_message]);
                            }else{
                                echo esc_html($taxopress_log_status_message);
                            }
                            ?>
                        </td>
					</tr>
                <?php } ?>
			</tbody>
		</table>

        <?php
            if ( $page_links ) {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo "<div class='tablenav'><div class='tablenav-pages'>". $page_links ."</div></div>";
            }
        ?>
    </div>
		<?php } else { ?>
            <p style="color: red;"><?php esc_html_e('Auto term logs is empty.', 'simple-tags'); ?>
        <?php } ?>
        </td>
    </tr>
    <?php
}
?>