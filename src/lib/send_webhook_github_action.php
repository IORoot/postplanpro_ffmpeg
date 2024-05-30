<?php

namespace postplanpro_ffmpeg\lib;


/**
 * Send a webhook to Github Actions
 */
class send_webhook_github_action
{


    public $post_id;
    public $github_token;
    public $acf_fields;
    public $headers;



    public function __construct($post_id)
    {
        $this->post_id = $post_id;

        # Get github settings
        if (!$this->get_github_settings()){ return; };

        # Get Config Settings
        if (!$this->get_config_settings()){ return; };

        # build the request headers
        $this->build_headers();

        # Base64 encode the config
        $this->build_payload();

        # Send the request to github
        $this->send_webhook();
    }



   /**
     * Ensure Github token set.
     */
    private function get_github_settings()
    {
        $this->github_token = get_field('pppff_github_token', $this->post_id);
        if ($this->github_token == ""){ 
            error_log( 'Error: Github Action Token not set. Exiting.' );
            return false;
        }
        
        return true;
    }


    /**
     * Check that Github settings are set.
     */
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


    
    /**
     * Build the headers for webhook
     */
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

        if ( ! function_exists( 'get_fields' ) ) { return; }

        # Base64 Encode because of cleartext
        $this->payload['client_payload']['pppff_control_config_b64'] = base64_encode($this->acf_fields['pppff_control_config']);

        # Remove the original from the config.
        $this->acf_fields['pppff_control_config'] = "";
        
        # repeats
        $this->payload['client_payload']['pppff_repeats'] = $this->acf_fields['pppff_repeats'];

        # Settings
        $this->payload['client_payload']['pppff_settings'] = base64_encode(json_encode($this->acf_fields));

        # JSON encode the payload
        $this->payload = json_encode( $this->payload );

    }



    /**
     * Prepare the request and send the webhook.
     */
    private function send_webhook()
    {
        
        $this->response = wp_remote_post( 
             $this->acf_fields["github_action_dispatch_url"], 
             [
                'headers' => $this->headers,
                'body'    => $this->payload,
            ]
        );

        if ( is_wp_error( $this->response ) ) {
            error_log( 'Webhook error: ' . $this->response->get_error_message() );
        }
    }

}