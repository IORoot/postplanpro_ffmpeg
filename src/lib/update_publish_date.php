<?php

namespace postplanpro_ffmpeg\lib;


/**
 * Use schedule to update the publish date.
 */
class update_publish_date
{

    public $enabled;

    public $schedule_repeat;


    public function  __construct($post_id)
    {
        $this->post_id = $post_id;
        $this->get_acf_settings();
        $this->update_published_date();
    }


    private function get_acf_settings()
    {
        $this->enabled = get_field('pppff_schedule_webhook',$this->post_id);
        $this->schedule_repeat = get_field('pppff_schedule_repeat',$this->post_id);
    }


    // ╭──────────────────────────────────────────────────────────────────────────╮
    // │                                                                          │░
    // │          Update the status of the config, including date/time             │░
    // │                                                                          │░
    // ╰░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░

    private function update_published_date()
    {
        
        // If scheduling is disabled, skip.
        if (!$this->enabled){ return; }

        // Define the number of minutes to add
        if (!$this->schedule_repeat){ return; }
        
        // Get the current date and time
        $current_time = current_time('mysql'); // This gets the current time in MySQL datetime format
        
        // Convert current time to a DateTime object
        $date = new \DateTime($current_time);

        // Add the specified number of minutes
        $date->modify("+{$this->schedule_repeat} minutes");

        // Format the new date and time in MySQL datetime format
        $new_post_date = $date->format('Y-m-d H:i:s');

        // Prepare the post data
        $update_post = array(
            'ID' => $this->post_id,
            'post_date' => $new_post_date,
            'post_date_gmt' => get_gmt_from_date($new_post_date) // Convert the new date to GMT
        );

        // Update the post
        wp_update_post($update_post);
    }



    // Function to check if a date/time is already used
    private function is_date_taken($date, $releases) {

        foreach ($releases as $release) {
            $release_date = new \DateTime(get_post_field('post_date', $release->ID));
            if ($release_date->format('Y-m-d H:i:s') == $date->format('Y-m-d H:i:s')) {
                return true;
            }
        }

        return false;
    }
}