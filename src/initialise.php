<?php

namespace postplanpro_ffmpeg;

class initialise
{

    private $config;

    public function run()
    {
        $this->acf_init();
    }

    public function acf_init()
    {
        
        # Create the CPT for the FFMPEG configs
        new acf\acf_cpt_configs();

        # Run the webhook to github on update
        new acf\acf_on_update_config();

        # Pre-populate the select box to list schedules
        new acf\acf_populate_release_schedule_select();

        # When changing from scheduled to publish
        new hooks\action_on_transition_status();
    }


}