<?php

    // Exit if accessed directly
    if (!defined('ABSPATH')) exit;

    // define('WP_DEBUG', true);
    error_reporting(E_ALL);
    ini_set('display_errors', 'on');


    // Create theme instance and call the theme class to initialize globally.
    include_once get_template_directory() . '/vendor/autoload.php';

    $template = \BvdB\Templates\Autoloader::get_instance();
    $utilities = \BvdB\Utilities\Autoloader::get_instance();
    $gutenberg = \BvdB\Gutenberg\Autoloader::get_instance();

    // Set ACF save path
    $utilities->acf->settings->set_save_load_paths( get_template_directory() . '/acf/' );

    // Register an optionspage
    $utilities->acf->optionspage->register(array(
        'page_title' => 'Site opties',
        'menu_title' => 'Site opties',
        'position' => 3
    ));

    // Register Nav menu
    $utilities->navigation->register( 'mainmenu', 'Hoofd navigatie' );

    // Add themesupport
    $utilities->themesupport->add('html5', array('comment-list', 'comment-form', 'search-form', 'gallery', 'caption'));
    $utilities->themesupport->add('post-thumbnails');
    $utilities->themesupport->add('title-tag');
    $utilities->themesupport->add('widgets', array());

    // Add theme support for Gutenberg
	$utilities->themesupport->add('editor-styles');
    $utilities->themesupport->add('align-wide');
    $utilities->themesupport->add('align-full');

    // Register new image size (ID, name, width, height, crop, show in admin)
    $utilities->images->add_image_size('bigthumb', 'Big thumbnail', 500, 500, true, true);

    // Change size of thumbnail
    $utilities->images->update_image_size('thumbnail', array(
        'size_w' => 300,
        'size_h' => 300
    ));

    // Register custom post type
    $utilities->posttype->register(
        'faq-item',
        'FAQ',
        'FAQ item', 
        'faq', 
        array('title'), 
        12, 
        'dashicons-format-chat'
    );  
    

    /**
     * Autoload all theme files such as scripts/styles and Gutenberg blocks
     * @see https://developer.wordpress.org/reference/hooks/after_setup_theme/
     */
    add_action('after_setup_theme', function() use ($utilities, $gutenberg) {
		$mixPublicPath = get_template_directory() . '/dist';

        // Setup the scripts to enqueue
		$utilities->assets->register('script', 'scripts', get_template_directory_uri()  . '/dist' . mix("scripts.js", $mixPublicPath), array(), false);

		// Setup styles to enque
		$utilities->assets->register('style', 'styles', get_template_directory_uri()  . '/dist' .  mix('/app.css', $mixPublicPath), array(), true);

        // Add ajaxurl als default param to scripts
        $utilities->assets->localize('scripts', 'theme', array(
            'ajaxurl' => admin_url('admin-ajax.php')
        ));

		// Editor styles		
		add_editor_style(get_template_directory_uri() . '/dist/editor-style.css');
		
		// Enqueue gutenberg assets
		add_action('enqueue_block_editor_assets', 'enqueue_block_editor_assets');
		
		function enqueue_block_editor_assets() {
			// wp_enqueue_style('fira-font', '//fonts.googleapis.com/css2?family=Fira+Sans:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');
			wp_enqueue_script('scripts-js', get_template_directory_uri() . '/dist/scripts.js');
			wp_enqueue_script('editor-js', get_template_directory_uri() . '/dist/editor.js', ['wp-blocks','wp-dom-ready', 'wp-edit-post' ]);
		}

        // Enqueue all assets (keep at end of file)
        $utilities->assets->load();
        
        // Include the blocks
        $blocks_loaded = $gutenberg->blocks->include_blocks();

    }, 1);

    // =================================================================================
    // Add WooCommerce support
    $utilities->themesupport->add('woocommerce', array());

    // Temp fix for WooCommerce
    add_filter( 'woocommerce_sort_countries', '__return_false' );

    // Remove WooCommerce template loader
    add_action('wp', function() {
        remove_filter('template_include', 'WC_Template_Loader::template_loader' );
    });
    // =================================================================================

	// Removes core block support
	remove_theme_support('core-block-patterns');

	// Mix manifest function 
	function mix($path, $manifestDirectory = '')
    {
        static $manifest;
        if ($manifestDirectory && strpos($manifestDirectory, '/') !== 0) {
            $manifestDirectory = "/{$manifestDirectory}";
        }

        if (! $manifest) {
            if (! file_exists($manifestPath = $manifestDirectory.'/mix-manifest.json')) {
                throw new Exception('The Mix manifest does not exist.');
            }
            $manifest = json_decode(file_get_contents($manifestPath), true);
        }

        if (strpos($path, '/') !== 0) {
            $path = "/{$path}";
        }

        if (! array_key_exists($path, $manifest)) {
            throw new Exception(
                "Unable to locate Mix file: {$path}. Please check your webpack.mix.js output paths and try again."
            );
        }

        return $manifest[$path];
    }