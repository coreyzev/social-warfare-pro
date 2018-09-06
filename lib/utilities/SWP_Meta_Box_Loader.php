<?php

/**
 * Meta Box Loader Class
 *
 * A class to load up all of our custom meta boxes for things like the custom
 * Pinterest image, the Twitter description, etc.
 *
 * @package   SocialWarfare\Functions\Utilities
 * @copyright Copyright (c) 2018, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since  3.1.0 | 02 JUL 2018 | Created
 *
 */
class SWP_Meta_Box_Loader {

	public function __construct() {
		if ( true === is_admin() ) {
			add_filter( 'swpmb_meta_boxes', array( $this, 'load_meta_boxes') );
            add_action( 'swpmb_before_social_warfare', array( $this, 'before_meta_boxes') );
            add_action( 'swpmb_after_social_warfare', array( $this, 'after_meta_boxes' ) );
		}
	}

	/**
	 * Load Meta Boxes
	 *
	 * @since  3.1.0 | 02 JUL 2018 | Created
	 * @param  void
	 * @return void
	 *
	 */
	public function load_meta_boxes( $meta_boxes ) {
        global $swp_user_options;

    	$prefix = 'swp_';
    	$twitter_id = isset( $options['twitter_id'] ) ? $options['twitter_id'] : false;

    	$twitter_handle = $this->get_twitter_handle( $twitter_id );

        // Setup the Open Graph image.
        $open_graph_image = array(
            'name'  => __( 'Open Graph Image','social-warfare' ),
            'desc'  => __( 'Add an image that is optimized for maximum exposure on Facebook, Google+ and LinkedIn. We recommend 1,200px by 628px.','social-warfare' ),
            'id'    => $prefix . 'og_image',
            'type'  => 'image_advanced',
            'clone' => false,
            'class' => $prefix . 'small_image',
            'max_file_uploads' => 1,
        );

        // Setup the Open Graph title.
        $open_graph_title = array(
            'name'  => __( 'Open Graph Title','social-warfare' ),
            'placeholder'  => __( 'Add a title that will populate the open graph meta tag which will be used when users share your content onto Facebook, LinkedIn, and Google+. If nothing is provided here, we will use the post title as a backup.','social-warfare' ),
            'id'    => $prefix . 'og_title',
            'type'  => 'textarea',
            'class' => $prefix . 'og_title',
            'clone' => false,
        );

        // Setup the Open Graph description.
        $open_graph_description = array(
            'name'  => __( 'Open Graph Description','social-warfare' ),
            'placeholder'  => __( 'Add a description that will populate the open graph meta tag which will be used when users share your content onto Facebook, LinkedIn, and Google Plus.','social-warfare' ),
            'id'    => $prefix . 'og_description',
            'class' => $prefix . 'og_description',
            'type'  => 'textarea',
            'clone' => false,
        );

        // Setup the Open Graph image.
        $twitter_image = array(
            'name'  => __( 'Twitter Card Image','social-warfare' ),
            'desc'  => __( 'Add an image that is optimized for maximum exposure on Facebook, Google+ and LinkedIn. We recommend 1,200px by 628px.','social-warfare' ),
            'id'    => $prefix . 'twitter_card_image',
            'type'  => 'image_advanced',
            'clone' => false,
            'class' => $prefix . 'small_image',
            'max_file_uploads' => 1,
        );

        // Setup the Open Graph title.
        $twitter_title = array(
            'name'  => __( 'Twitter Card Title','social-warfare' ),
            'placeholder'  => __( 'Add a title that will populate the open graph meta tag which will be used when users share your content onto Facebook, LinkedIn, and Google+. If nothing is provided here, we will use the post title as a backup.','social-warfare' ),
            'id'    => $prefix . 'twitter_card_title',
            'type'  => 'textarea',
            'class' => $prefix . 'twitter_card_title',
            'clone' => false,
        );

        // Setup the Open Graph description.
        $twitter_description = array(
            'name'  => __( 'Twitter Card Description','social-warfare' ),
            'placeholder'  => __( 'Add a description that will populate the open graph meta tag which will be used when users share your content onto Facebook, LinkedIn, and Google Plus.','social-warfare' ),
            'id'    => $prefix . 'twitter_card_description',
            'class' => $prefix . 'twitter_card_description',
            'type'  => 'textarea',
            'clone' => false,
        );

        // Divider.
        $divider = array(
            'name' => 'divider',
            'id'   => 'divider',
            'type' => 'divider',
        );

        // Setup the pinterest optimized image.
        $pinterst_image = array(
            'name'  => __( 'Pinterest Image','social-warfare' ),
            'desc'  => __( 'Add an image that is optimized for maximum exposure on Pinterest. We recommend using an image that is formatted in a 2:3 aspect ratio like 735x1102.','social-warfare' ),
            'id'    => $prefix . 'pinterest_image',
            'class' => $prefix . 'large_image',
            'type'  => 'image_advanced',
            'clone' => false,
            'max_file_uploads' => 1,
        );

        // Setup the Custom Tweet box.
        $custom_tweet = array(
            'name'  => __( 'Custom Tweet','social-warfare' ),
            'placeholder' => '(if left empty, defaults to the following:) \'Open Graph Title\'' . the_permalink() . $twitter_handle ,
            'desc'  => ( $twitter_id ? sprintf( __( 'If this is left blank your post title will be used. Based on your username (@%1$s), <span class="tweetLinkSection">a link being added,</span> and the current content above, your tweet has %2$s characters remaining.','social-warfare' ),str_replace( '@','',$twitter_handle ),'<span class="counterNumber">140</span>' ) : sprintf( __( 'If this is left blank your post title will be used. <span ="tweetLinkSection">Based on a link being added, and</span> the current content above, your tweet has %s characters remaining.','social-warfare' ),'<span class="counterNumber">140</span>' )),
            'id'    => $prefix . 'custom_tweet',
            'class' => $prefix . 'custom_tweetWrapper',
            'type'  => 'textarea',
            'clone' => false,
        );

        $recover_shares_box = array(
            'name'  => __( 'Share Recovery','social-warfare' ),
            'desc'  => __( 'If you have changed the permalink for just this post, paste in the previous full URL for this post so we can recover shares for that link.','social-warfare' ),
            'id'    => 'swp_recovery_url',
            'class' => $prefix . 'share_recoveryWrapper',
            'type'  => 'text',
            'clone' => false
        );

        $pinterest_description = array(
            'name'  => __( 'Pinterest Description','social-warfare' ),
            'placeholder'  => __( 'Craft a customized description that will be used when this post is shared on Pinterest. Leave this blank to use the title of the post.','social-warfare' ),
            'id'    => $prefix . 'pinterest_description',
            'class' => $prefix . 'pinterest_descriptionWrapper',
            'type'  => 'textarea',
            'clone' => false,
        );

        // Setup the pinterest description.
        $pin_browser_extension = array(
            'name'    => __( 'Pin Image for Browser Extensions','social-warfare' ),
            'id'      => 'swp_pin_browser_extension',
            'type'    => 'select',
            'options' => array(
                'default' => __( 'Default','social-warfare' ),
                'on'      => __( 'On','social-warfare' ),
                'off'     => __( 'Off','social-warfare' ),
            ),
            'clone' => false,
            'std'   => 'default',
        );

        $pin_browser_extension_location = array(
            'name'    => __( 'Pin Browser Image Location','social-warfare' ),
            'id'      => 'swp_pin_browser_extension_location',
            'type'    => 'select',
            'options' => array(
                'default' => __( 'Default','social-warfare' ),
                'hidden'  => __( 'Hidden','social-warfare' ),
                'top'     => __( 'At the Top of the Post','social-warfare' ),
                'bottom'  => __( 'At the Bottom of the Post','social-warfare' ),
            ),
            'clone' => false,
            'std'   => 'default',
        );

        // Set up the location on post options.
        $post_location = array(
            'name'    =>  __( 'Horizontal Buttons Location','social-warfare' ),
            'id'      => $prefix . 'post_location',
            'type'    => 'select',
            'options' => array(
                'default' => __( 'Default','social-warfare' ),
                'above'   => __( 'Above the Content','social-warfare' ),
                'below'   => __( 'Below the Content','social-warfare' ),
                'both'    => __( 'Both Above and Below the Content','social-warfare' ),
                'none'    => __( 'None/Manual Placement','social-warfare' ),
            ),
            'clone' => false,
            'std'	=> 'default',
        );

        $float_location = array(
            'name'    =>  __( 'Floating Buttons','social-warfare' ),
            'id'      => $prefix . 'float_location',
            'type'    => 'select',
            'options' => array(
                'default' => __( 'Default','social-warfare' ),
                'on'      => __( 'On','social-warfare' ),
                'off'     => __( 'Off','social-warfare' ),
            ),
            'clone' => false,
            'std'   => 'default',
        );

        $divider2 = array(
            'name'  => 'divider2',
            'id'    => 'divider2',
            'type'  => 'divider',
        );

        $twitter_handle_box = array(
            'name'  => $twitter_handle,
            'id'    => 'twitter_id',
            'class' => 'twitterIDWrapper',
            'type'  => 'hidden',
            'std'   => $twitter_handle,
        );

        $heading = array(
            'name'  => 'Share Customization',
            'id'    => 'swp_meta_box_heading',
            'class' => '',
            'type'  => 'heading',
            'desc'  => 'Make sure your content is shared exactly the way you want it to be shred by customizing the fields below.',

        );

        $open_graph_twitter_card = new SWP_Option_Toggle( __( 'Use Open Graph for Twitter Cards' , 'social-warfare' ), 'swp_open_graph_twitter_card' );
        $open_graph_twitter_card->set_default( false )
            ->set_premium( 'pro' );

        $open_graph_toggle = array(
            'id'    => 'open_graph_toggle',
            'type'  => 'toggle',
            'desc'  =>  'Use Open Graph for Twitter Card?'
        );

    	// Setup our meta box using an array.
    	$meta_boxes[0] = array(
    		'id'       => 'social_warfare',
    		'title'    => __( 'Social Warfare Custom Options','social-warfare' ),
    		'pages'    => SWP_Utility::get_post_types(),
    		'context'  => 'normal',
    		'priority' => apply_filters( 'swp_metabox_priority', 'high' ),
    		'fields'   => array()
    	);


        $meta_boxes[0]['fields'][] = $heading;
        $meta_boxes[0]['fields'][] = $open_graph_image;
        $meta_boxes[0]['fields'][] = $open_graph_title;
        $meta_boxes[0]['fields'][] = $open_graph_description;
        $meta_boxes[0]['fields'][] = $divider;
        $meta_boxes[0]['fields'][] = $pinterst_image;
        $meta_boxes[0]['fields'][] = $open_graph_toggle;
        $meta_boxes[0]['fields'][] = $twitter_image;
        $meta_boxes[0]['fields'][] = $twitter_title;
        $meta_boxes[0]['fields'][] = $twitter_description;

        $meta_boxes[0]['fields'][] = $custom_tweet;

        if ( isset( $swp_user_options['recover_shares'] ) && true === $swp_user_options['recover_shares'] ) {
            $meta_boxes[0]['fields'][] = $recover_shares_box;
        }

        $meta_boxes[0]['fields'][] = $pinterest_description;
        $meta_boxes[0]['fields'][] = $pin_browser_extension;
        $meta_boxes[0]['fields'][] = $pin_browser_extension_location;
        $meta_boxes[0]['fields'][] = $post_location;
        $meta_boxes[0]['fields'][] = $float_location;
        $meta_boxes[0]['fields'][] = $divider2;
        $meta_boxes[0]['fields'][] = $twitter_handle_box;

    	return $meta_boxes;
	}


	public function get_twitter_handle( $fallback = '' ) {
		// Fetch the Twitter handle for the Post Author if it exists.
		if ( isset( $_GET['post'] ) ) {
			$user_id = SWP_User_Profile::get_author( absint( $_GET['post'] ) );
		} else {
			$user_id = get_current_user_id();
		}

		$twitter_handle = get_the_author_meta( 'swp_twitter', $user_id );

		if ( ! $twitter_handle ) {
			$twitter_handle = $fallback;
		}

		return $twitter_handle;
	}

    /**
     * Echoes content before any meta box fields are printed.
     *
     * You must echo your content immediately, and return the $meta_box.
     *
     * @param  object $meta_box The Rylwis meta_box object.
     * @return object $meta_box The (optionally) filtered meta box.
     *
     */
    public function before_meta_boxes( $meta_box  ) {
        // echo '';

        return $meta_box;
    }

    /**
     * Echoes content after the meta box fields are printed.
     *
     * You must echo your content immediately, and return the $meta_box.
     *
     * @param  object $meta_box The Rylwis meta_box object.
     * @return object $meta_box The (optionally) filtered meta box.
     *
     */
    public function after_meta_boxes( $meta_box ) {
        // echo '';

        return $meta_box;
    }
}
