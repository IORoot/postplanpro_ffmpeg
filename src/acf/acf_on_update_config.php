<?php

namespace postplanpro_ffmpeg\acf;


use postplanpro_ffmpeg\lib\send_webhook_github_action;
use postplanpro_ffmpeg\lib\update_publish_date;


/**
 * When the update button is pressed, do this.
 */
class acf_on_update_config
{

    public $page_name = 'config';
    public $post_id;



    public function __construct(){
        add_action( 'acf/save_post', [$this, 'update'], 20 );
    }



    public function update($post_id) {
        $this->post_id = $post_id;

        # Check that this is the correct page
        $screen = get_current_screen();
        if ($screen->id !== $this->page_name) { return; }

        # check that the config is not in the bin and is published
        if (!$this->check_published()){ return; };

        # Update published status & datetime
        new update_publish_date($this->post_id);

        # Send Webhook to Github
        new send_webhook_github_action($this->post_id);

        # Bin the config once complete
        $this->bin_config();
    }



    public function update_on_transition_status($post_id) {
        $this->post_id = $post_id;

        # check that the config is not in the bin and is published
        if (!$this->check_published()){ return; };

        // Update published status & datetime
        new update_publish_date($this->post_id);

        # Send Webhook to Github
        new send_webhook_github_action($this->post_id);

        # Bin the config once complete
        $this->bin_config();
    }


    /**
     * Check if the post published or not.
     * We should only be sending the webhook when it's published.
     */
    private function check_published()
    {
        $post = get_post($this->post_id);
        if ($post->post_status !== 'publish') {
            return false;
        }
        
        if (get_post_status($post_id) === 'trash') {
            return false;
        }

        return true;
    }




    /**
     * Move the config into the bin.
     */
    private function bin_config()
    {
        if (! $this->acf_fields["pppff_bin_after_processing"]){ return; }
        wp_trash_post($this->post_id);

        # Redirect back to the 'all' listing page
        wp_redirect(admin_url('edit.php?post_type=config'));

        # Finish before anything else happens
        exit;
    }



}
