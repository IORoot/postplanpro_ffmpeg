<?php

namespace postplanpro_ffmpeg\acf;

/**
 * This will generate the parent page for postplanpro with 
 * and icon as well as all of the sub-pages underneath.
 */
class acf_options_page
{

    public function __construct(){
        add_action('acf/init', [$this,'initialise']);
    }



    public function initialise() {
        $this->add_ffmpeg_page();
    }



    public function add_ffmpeg_page()
    {    
        // Check if ACF function exists
        if (function_exists('acf_add_options_page') && function_exists('acf_add_options_sub_page')) {

            acf_add_options_sub_page(array(
                'menu_title'  => 'Config Settings',
                'page_title'  => 'Video Builder Settings',
                'parent_slug' => 'postplanpro',
            ));
        }
        
    }

}



