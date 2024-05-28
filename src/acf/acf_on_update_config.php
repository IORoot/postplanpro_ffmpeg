<?php

namespace postplanpro_ffmpeg\acf;



class acf_on_update_config
{

    public $page_name = 'config';
    public $post_id;

    public $enabled;
    public $github_token = "";
    public $acf_fields;

    public $headers = [];
    public $payload = [];

    public $response;


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

        # Get github settings
        if (!$this->get_github_settings()){ return; };

        // Update published status & datetime
        $this->update_published_date();

        # Get Config Settings
        if (!$this->get_config_settings()){ return; };

        # build the request headers
        $this->build_headers();

        # Base64 encode the config
        $this->build_payload();

        # Send the request to github
        $this->send_webhook();

        # Bin the config once complete
        $this->bin_config();
    }



    public function update_on_transition_status($post_id) {
        $this->post_id = $post_id;

        # check that the config is not in the bin and is published
        if (!$this->check_published()){ return; };

        # Get github settings
        if (!$this->get_github_settings()){ return; };

        // Update published status & datetime
        $this->update_published_date();

        # Get Config Settings
        if (!$this->get_config_settings()){ return; };

        # build the request headers
        $this->build_headers();

        # Base64 encode the config
        $this->build_payload();

        # Send the request to github
        $this->send_webhook();

        # Bin the config once complete
        $this->bin_config();
    }


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
     * Config / Github Settings
     */
    private function get_github_settings()
    {
        $this->github_token = get_field('github_token', 'option');
        if ($this->github_token == ""){ 
            error_log( 'Error: Github Action Token not set. Exiting.' );
            return false;
        }
        
        return true;
    }


    private function get_config_settings()
    {
        if (! function_exists( 'get_fields' ) ) { return false; }

        $this->acf_fields = get_fields( $this->post_id );

        if ($this->acf_fields["github_action_dispatch_url"] == ""){ 
            error_log( 'Error: Github Action Dispatch URL not set. Exiting.' );
            return false;
        }

        if ($this->acf_fields["github_action_dispatch_event"] == ""){ 
            error_log( 'Error: Github Action Dispatch Event not set. Exiting.' );
            return false;
        }

        if (false === $this->acf_fields["pppff_webhook_enabled"]) {
            return false;
        }

        return true;
    }


    
    private function build_headers()
    {
        $this->headers = [
            'Accept'        => 'application/vnd.github.v3+json',
            'Authorization' => 'token ' . $this->github_token,
            'Content-Type'  => 'application/json',
        ];
    }

    /**
     * Build the payload to send to the target.
     */
    private function build_payload()
    {

        $this->payload = [
            'event_type' => $this->acf_fields["github_action_dispatch_event"],
        ];

        if ( function_exists( 'get_fields' ) ) {
            $acf_fields = get_fields( $this->post_id );
            if ( $acf_fields['pppff_control_config'] ) {
                $this->payload['client_payload'] = [
                    'pppff_control_config_b64'           => base64_encode($acf_fields['pppff_control_config']),
                    'pppff_repeats'                     => $acf_fields['pppff_repeats'],
                    'pppff_release_rest_api_url'        => $acf_fields['pppff_release_rest_api_url'],
                    'pppff_release_rest_api_token'      => $acf_fields['pppff_release_rest_api_token'],
                    'pppff_release_schedule'            => $acf_fields['pppff_release_schedule'],
                    'pppff_google_drive_output_folder'  => $acf_fields['pppff_google_drive_output_folder']
                ];
            }
        }

        // JSON encode the payload
        $this->payload = json_encode( $this->payload );

    }


    /**
     * Prepare the request and send the webhook.
     */
    private function send_webhook()
    {
        
        // Run webhook request
        $this->response = wp_remote_post( 
             $this->acf_fields["github_action_dispatch_url"], 
             [
                'headers' => $this->headers,
                'body'    => $this->payload,
            ]
        );


        // Check for errors in the response
        if ( is_wp_error( $this->response ) ) {
            error_log( 'Webhook error: ' . $this->response->get_error_message() );
        }
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


    // ╭──────────────────────────────────────────────────────────────────────────╮
    // │                                                                          │░
    // │          Update the status of the config, including date/time             │░
    // │                                                                          │░
    // ╰░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░

    private function update_published_date()
    {
        
        # If scheduling is disabled, skip.
        $status = get_field('pppff_schedule_webhook',$this->post_id);
        if (!$status){ return; }

        // Get the current date and time
        $current_time = current_time('mysql'); // This gets the current time in MySQL datetime format

        // Define the number of minutes to add
        $minutes_to_add = get_field('pppff_schedule_repeat',$this->post_id); // Change this value as needed
        if (!$minutes_to_add){ return; }
        
        // Convert current time to a DateTime object
        $date = new \DateTime($current_time);

        // Add the specified number of minutes
        $date->modify("+{$minutes_to_add} minutes");

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
