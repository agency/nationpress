<?php


// function have_posttype() { 
	
// 	global $plugin_starter;

// 	if ($plugin_starter->posttype) {

// 		$plugin_starter->posttype = $plugin_starter->posttype ? next($plugin_starter->posttypes) : reset($plugin_starter->posttypes);
		
// 		if (! $plugin_starter->posttype) {

// 			$plugin_starter->posttypes = false;
// 			wp_reset_postdata();
// 			return false;

// 		}

// 		return true;

// 	} else {


// 		$plugin_starter->posttypes = get_posts( array(
// 			'post_type' => 'posttype',
// 			'posts_per_page' => -1
// 		));
// 		return count($plugin_starter->posttypes) > 0;

// 	}

// 	return false;

// }

// function the_posttype($post) {

// 	setup_postdata( $post );

// }