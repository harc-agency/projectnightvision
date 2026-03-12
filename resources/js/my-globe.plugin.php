<?php
/*
Plugin Name: Hyper Globe [hyper-my-globe]
Plugin URI: https://hyper.ac/globe/
Description: Integrates a Hyper Globe via shortcode [hyper-my-globe]
Version: 1
Author: Hyper
Author URI: https://hyper.ac
*/

if( defined('ABSPATH') && function_exists('is_admin') && ! is_admin() ) {
	add_shortcode( 'hyper-my-globe',  function() {
		return '<script async src="'. plugin_dir_url(__FILE__) .'my-globe.loader.js?v1"></script>';
	} );
}