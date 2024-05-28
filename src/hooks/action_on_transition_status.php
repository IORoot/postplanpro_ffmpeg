<?php

namespace postplanpro_ffmpeg\hooks;

use postplanpro_ffmpeg\acf\acf_on_update_config;

/**
 * This action monitors for when posts change their status.
 * If a 'release' switches to 'publish' status, send a
 * webhook off to the target with all the correct data.
 */
class action_on_transition_status
{

    public function __construct( )
    {
        add_action( 'transition_post_status', [$this,'run_to_publish'], 11, 3 );
    }



    /**
     * Checks and run.
     */
    public function run_to_publish($new_status, $old_status, $post) {
        
        # If this isn't a 'release', skip.
        if ('config' !== $post->post_type){ return; }

        # If this wasn't a scheduled post, skip.
        if ('future'  !== $old_status ){ return; }

        # If the new status isn't 'publish', skip.
        if ('publish' !== $new_status){ return; }

        # set class variable
        $config = new acf_on_update_config();
        $config->update_on_transition_status($post->ID);
    }


}
