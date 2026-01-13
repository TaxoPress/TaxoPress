<?php

class SimpleTags_Client_Schedule
{
    static $instance;

    public function __construct()
    {

        add_action('taxopress_cron_autoterms_weekly', [$this, 'taxopress_cron_autoterms_weekly_execution']);
        add_filter('cron_schedules', [$this, 'taxopress_weekly_cron_schedule']);
        add_action('init', [$this, 'schedule_taxopress_cron_events']);

    }

    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function schedule_taxopress_cron_events()
    {

        $autoterms_schedule = taxopress_get_autoterms_schedule_data();
        $cron_schedule = isset($autoterms_schedule['cron_schedule']) ? $autoterms_schedule['cron_schedule'] : 'disable';

        if ($cron_schedule === 'weekly' && !wp_next_scheduled('taxopress_cron_autoterms_weekly')) {
            wp_schedule_event(time(), 'weekly', 'taxopress_cron_autoterms_weekly');
        }
    }

    public function taxopress_weekly_cron_schedule($schedules)
    {
        if (!isset($schedules['weekly'])) {
            $schedules['weekly'] = array(
                'interval' => 604800,
                'display'  => esc_html__('Once Weekly', 'simple-tags')
            );
        }
        return $schedules;
    }

    public function taxopress_cron_autoterms_weekly_execution()
    {
        $this->taxopress_cron_autoterms_execution('weekly');
    }

    public function taxopress_cron_autoterms_execution($frequency = 'weekly')
    {
        global $wpdb;

        $autoterms_schedule = taxopress_get_autoterms_schedule_data();
        $autoterms = taxopress_get_autoterm_data();
        $autoterm_schedule_ids = isset($autoterms_schedule['autoterm_id']) ? (array)$autoterms_schedule['autoterm_id'] : [];
        $autoterm_data = [];

        foreach ($autoterm_schedule_ids as $autoterm_schedule_id) {
            if (!empty($autoterms[$autoterm_schedule_id])) {
                $autoterm_data[$autoterm_schedule_id] = $autoterms[$autoterm_schedule_id];
            }
        }
        if (empty($autoterm_data)) {
            return;
        }

        $autoterms_to_run = [];
        foreach ($autoterm_data as $id => $a) {
            if (isset($a['cron_schedule'])) {
                if ($a['cron_schedule'] === $frequency) {
                    $autoterms_to_run[$id] = $a;
                }
                continue;
            }
            $global_cron = isset($autoterms_schedule['cron_schedule']) ? $autoterms_schedule['cron_schedule'] : 'disable';
            if ($global_cron === $frequency) {
                $autoterms_to_run[$id] = $a;
            }
        }

        if (empty($autoterms_to_run)) {
            return;
        }

        $autoterm_schedule_exclude = isset($autoterms_schedule['autoterm_schedule_exclude']) ? (int)$autoterms_schedule['autoterm_schedule_exclude'] : 0;
        $limit = (isset($autoterms_schedule['schedule_terms_batches']) && (int)$autoterms_schedule['schedule_terms_batches'] > 0) ? (int)$autoterms_schedule['schedule_terms_batches'] : 20;
        $sleep = (isset($autoterms_schedule['schedule_terms_sleep']) && (int)$autoterms_schedule['schedule_terms_sleep'] > 0) ? (int)$autoterms_schedule['schedule_terms_sleep'] : 0;
        $schedule_terms_limit_days = !empty($autoterms_schedule['schedule_terms_limit_days']) ? (int)$autoterms_schedule['schedule_terms_limit_days'] : 0;

        $post_types = [];
        $post_status = [];
        foreach ($autoterms_to_run as $a) {
            if (!empty($a['post_types']) && is_array($a['post_types'])) {
                $post_types = array_unique(array_merge($post_types, $a['post_types']));
            }
            if (!empty($a['post_status']) && is_array($a['post_status'])) {
                $post_status = array_unique(array_merge($post_status, $a['post_status']));
            }
        }
        if (empty($post_types)) {
            return;
        }
        if (empty($post_status)) {
            $post_status = ['publish'];
        }

        $schedule_terms_limit_days_sql = '';
        if ($schedule_terms_limit_days > 0) {
            $schedule_terms_limit_days_sql = 'AND post_date > "' . date('Y-m-d H:i:s', time() - $schedule_terms_limit_days * 86400) . '"';
        }

        if ($autoterm_schedule_exclude > 0) {
            $objects = (array) $wpdb->get_results("SELECT ID, post_title, post_content FROM {$wpdb->posts} LEFT JOIN {$wpdb->postmeta} ON ( ID = {$wpdb->postmeta}.post_id AND {$wpdb->postmeta}.meta_key = '_taxopress_autotermed' ) WHERE post_type IN ('" . implode("', '", array_map('esc_sql', $post_types)) . "') AND {$wpdb->postmeta}.post_id IS NULL AND post_status IN ('" . implode("', '", array_map('esc_sql', $post_status)) . "') {$schedule_terms_limit_days_sql} ORDER BY ID DESC LIMIT {$limit}"
            );
        } else {
            $objects = (array) $wpdb->get_results("SELECT ID, post_title, post_content FROM {$wpdb->posts} WHERE post_type IN ('" . implode("', '", array_map('esc_sql', $post_types)) . "') AND post_status IN ('" . implode("', '", array_map('esc_sql', $post_status)) . "') {$schedule_terms_limit_days_sql} ORDER BY ID DESC LIMIT {$limit}"
            );
        }

        if (empty($objects)) {
            return;
        }

        $current_post = 0;
        foreach ($objects as $object) {
            $current_post++;
            update_post_meta($object->ID, '_taxopress_autotermed', 1);

            foreach ($autoterms_to_run as $autoterm) {
                if (empty($autoterm['autoterm_for_schedule'])) {
                    continue;
                }

                $applied = $autoterm;
                if (isset($autoterm['schedule_terms_limit'])) {
                    $applied['terms_limit'] = $autoterm['schedule_terms_limit'];
                }
                if (isset($autoterm['schedule_autoterm_target'])) {
                    $applied['autoterm_target'] = $autoterm['schedule_autoterm_target'];
                }
                if (isset($autoterm['schedule_autoterm_word'])) {
                    $applied['autoterm_word'] = $autoterm['schedule_autoterm_word'];
                }
                if (isset($autoterm['schedule_autoterm_hash'])) {
                    $applied['autoterm_hash'] = $autoterm['schedule_autoterm_hash'];
                }
                if (isset($autoterm['schedule_replace_type'])) {
                    $applied['replace_type'] = $autoterm['schedule_replace_type'];
                }

                SimpleTags_Client_Autoterms::auto_terms_post(
                    $object,
                    isset($applied['taxonomy']) ? $applied['taxonomy'] : '',
                    $applied,
                    true,
                    $frequency . '_cron_schedule',
                    'st_autoterms'
                );
            }

            unset($object);

            if ($sleep > 0 && $current_post % $limit == 0) {
                sleep($sleep);
            }
        }
    }
}
