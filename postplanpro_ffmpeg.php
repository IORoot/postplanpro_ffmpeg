<?php

/*
 * @wordpress-plugin
 * Plugin Name:       _ANDYP - Post Plan Pro - FFMPEG
 * Plugin URI:        http://londonparkour.com
 * Description:       <strong>🎥 PostPlanPro FFMPEG</strong> | Extension to kick-off github action and run a script to build video.
 * Version:           1.0.0
 * Author:            Andy Pearson
 * Author URI:        https://londonparkour.com
 */

// ┌─────────────────────────────────────────────────────────────────────────┐
// │                         Use composer autoloader                         │
// └─────────────────────────────────────────────────────────────────────────┘
require __DIR__.'/vendor/autoload.php';

//  ┌─────────────────────────────────────────────────────────────────────────┐
//  │                           Register CONSTANTS                            │
//  └─────────────────────────────────────────────────────────────────────────┘
define( 'POSTPLANPRO_FFMPEG_PATH', __DIR__ );
define( 'POSTPLANPRO_FFMPEG_URL', plugins_url( '/', __FILE__ ) );
define( 'POSTPLANPRO_FFMPEG_FILE',  __FILE__ );

// ┌─────────────────────────────────────────────────────────────────────────┐
// │                        	   Initialise    		                     │
// └─────────────────────────────────────────────────────────────────────────┘
if (is_plugin_active('wp-plugin__postplanpro/postplanpro.php')) { 
    $cpt = new postplanpro_ffmpeg\initialise;
    $cpt->run();
}

class postplanpro_ffmpeg_notices {
    public function __construct() {
        // Add action to check required plugins on admin init
        add_action('admin_init', array($this, 'check_required_plugins'));
    }

    public function check_required_plugins() {
        // Check if ACF is active
        if (!is_plugin_active('advanced-custom-fields-pro/acf.php')) {
            add_action('admin_notices', array($this, 'acf_missing_notice'));
        }

        // Check if the parent plugin is active
        if (!is_plugin_active('wp-plugin__postplanpro/postplanpro.php')) {
            add_action('admin_notices', array($this, 'parent_plugin_missing_notice'));
        }
    }

    public function acf_missing_notice() {
        echo '<div class="error"><p><strong>PostPlanPro FFMPEG Plugin</strong> requires <strong>Advanced Custom Fields</strong> to be installed and active.</p></div>';
    }

    public function parent_plugin_missing_notice() {
        echo '<div class="error"><p><strong>PostPlanPro FFMPEG Plugin</strong> requires the <strong>PostPlanPro Plugin</strong> to be installed and active.</p></div>';
    }
}

// Initialize the plugin
new postplanpro_ffmpeg_notices();