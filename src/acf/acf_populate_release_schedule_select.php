<?php

namespace postplanpro_ffmpeg\acf;

/**
 * When you create a new release, you need to pick a 
 * schedule to apply to it. This populates the select box
 * with all schedules that have been created.
 */
class acf_populate_release_schedule_select
{


    public function __construct(){
        add_action('acf/load_field/name=pppff_release_schedule', [$this,'populate']);
    }


    /**
     * This will get a list of all entries in the schedules
     * repeater and use the schedule_name to populate
     * the select field in the template.
     */
    public function populate($field) {

        // reset choices
        $field['choices'] = array();

        // Check if the repeater field has rows
        if (have_rows('ppp_schedule', 'option')) {

            // Loop through each row in the repeater field
            while (have_rows('ppp_schedule', 'option')) {

                the_row();
                $schedule_name = get_sub_field('ppp_schedule_name');

                // Avoid duplicate entries
                if (!in_array($schedule_name, $field['choices'])) {
                    $field['choices'][$schedule_name] = $schedule_name;
                }
            }
        }
    
        // return the field
        return $field;
        
    }

}



