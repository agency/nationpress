<?php

/**
 *
 * @wordpress-plugin
 * Plugin Name: NationPress
 * Description: Connect Wordpress and Nationbuilder members
 * Author: Agency
 * Version: 1.0
 * Author URI: http://agency.sc/
 */

if (!defined('ABSPATH')) exit;

// Includes
require_once(  WP_PLUGIN_DIR . '/nationpress/libs/nationbuilder-api/nationbuilder.php' );

/**
 * Main Nationbuilder Push Class
 * 
 * @class NationPress
 * @version 0.1
 */

class NationPress {

	public $errors = false;
	public $notices = false;
	
	function __construct() {

		$this->path = plugin_dir_path(__FILE__);
		$this->folder = basename($this->path);
		$this->dir = plugin_dir_url(__FILE__);
		$this->version = '1.0';
		$this->slug = 'nationpress';
		
		$this->errors = false;
		$this->notice = false;
		$this->options = $this->get_options();
		
		// Actions
		add_action('admin_menu', array($this, 'register_options_page'));
		add_action('wp_loaded', array($this , 'forms'));

		// Notices (add these when you need to show the notice)
		add_action( 'admin_notices', array($this, 'admin_success'));
		add_action( 'admin_notices', array($this, 'admin_error'));

		// Default wordpress account creation hooks
		if (is_multisite()) add_action('wpmu_activate_user', array($this, 'push_WPMU_user'), 10, 3);
		else add_action('user_register', array($this, 'push_WP_user'), 10, 1);

		// Shortcodes
		add_shortcode('nationpress-signup', array($this,'shortcode_signup') );
		
		// Create Nation
		if ($this->options_exist()) $this->nation = new Nation($this->options);
				
	}

	public function save($vars){

		print "<pre>";
		var_dump($vars);
		print "</pre>";
		die;

		$response = $this->push_to_NB( $vars['email'], $vars['first_name', $vars['last_name'], $vars['tags']);
		
	}


	/**
	 * Shortcode Signup Form
	 * @param array $attr 
	 * @param string $content 
	 * @return null
	 */

	public function shortcode_signup($attr, $content){

		ob_start();

		if(!is_array($attr)) $attr = array();

		$this->template_include('signup.php',$attr,'attr');
		return ob_get_clean();

	}


	/**
	 * Form Processing
	 * Form processing for custom signup forms
	 * @return null
	 */

	public function forms() {
		
		if (!isset($_POST['nationpress'])) return;
		if(!wp_verify_nonce( $_POST['_wpnonce'], 'nationpress')){ $this->redirect($_POST['_wp_http_referer']); }


		// $type = $_POST['nationbuilder']['type'];

		switch ($_POST['nationpress']) {

			case 'subscribe':
				$response = $this->push_subscriber();
				if ($response['errors'] > 0) wp_redirect( '?subscribe=error' );
				else  wp_redirect( '?subscribe=success' );
				exit;
				break;

			case 'save':
				$this->save($_POST);
				break;

			default:
				break;
		}

	}


	/**
	 * Register Options Page
	 * @return null
	 */

	public function register_options_page() {

		// main page
		add_options_page('NationPress', 'NationPress', 'manage_options', 'nationpress_options', function(){ $this->template_include('options.php'); });
		add_action('admin_init', array($this, 'plugin_options'));
		
	}

	/**
	 * Plugin Options
	 * Register the plugin options with wordpress
	 * @return null
	 */

	public function plugin_options() {

		$keys = $this->get_option_keys();

		foreach ($keys as $key) register_setting('nationpress_options', $key);

	}


	/**
	 * Get Option Keys
	 * Define the required keys for the plugin
	 * @return array
	 */

	public function get_option_keys() {

		$option_keys = array(
			'nation_slug',
			'redirect_url',
			'client_id',
			'client_secret',
			'code',
			'access_token',
			'tags'
		);

		return $option_keys;

	}

	/**
	 * Get Options
	 * Get the saved values of the options from wordpress
	 * @return array
	 */

	public function get_options() {

		$options = array();

		$keys = $this->get_option_keys();

		foreach ($keys as $key) {
			$options[$key] = get_option($key);
		}

		return $options;

	}

	/**
	 * Options Exist
	 * Validate the saved options
	 * @return boolean
	 */

	public function options_exist() {


		$keys = $this->options;

		foreach ($keys as $key) {
			if ($key == '') {
				$this->errors[] = "NationPress: Complete your setup <a href=\"/wp-admin/admin.php?page=nationpress_options\">here</a>.";
				return false;
			}
		}

		return true;

	}

	/**
	 * Push Subscriber
	 * @return array/object
	 */

	public function push_subscriber() {
		

		$email = $_POST['user_email'];
		$first_name = $_POST['first_name'];
		$last_name = $_POST['last_name'];


		$meta['first_name'] = $first_name;
		$meta['last_name'] = $last_name;

		// register user to WP
		$username = sanitize_user($email);
		$password = wp_generate_password();
		is_multisite() ? wpmu_signup_user( $username, $email, $meta ) : wp_create_user( $username, $password, $email );

		// Send to Nationbuilder
		$response = $this->push_to_NB( $email, $first_name, $last_name );

		return $response;
	}

	/**
	 * New Wordpress User
	 * @param init $user_id 
	 * @return null
	 */
	public function push_WP_user( $user_id ) {
		// get user data
		$email = $_POST['user_email'];
		$first_name = $_POST['first_name'];
		$last_name = $_POST['last_name'];

		$this->push_to_NB( $email, $first_name, $last_name );
	}

	/**
	 * New Multisite Wordpress User
	 * @param init $user_id 
	 * @param string $password 
	 * @param array $meta 
	 * @return null
	 */

	public function push_WPMU_user( $user_id, $password, $meta ) {
		// get user data
		$userdata = get_userdata( $user_id );
		$email = $userdata->user_email;
		$first_name = $meta['first_name'];
		$last_name = $meta['last_name'];

		$this->push_to_NB( $email, $first_name, $last_name );
	}

	/**
	 * Push to Nationbuilder
	 * @param int $email 
	 * @param string $first_name 
	 * @param string $last_name 
	 * @return type
	 */

	public function push_to_NB( $email, $first_name = null, $last_name = null, $tags = null ) {

		// Tags
		if(!tags){
			$tags = array();
			$tag_string = $this->options['tags'];
			$tags = explode(', ', $tag_string);
		}

		// Response
		$response = array();
		$response['errors'] = 0;

		// Dedupe Email against Nationbuilder
		$match_response = $this->nation->matchPerson($email);

		// get ID if email exists, or add to NB if not
		if ($match_response['code'] == 200) {

			$person_id = $match_response['result']['person']['id'];

			// set response
			$response['duplicate'] = true;

		} else {

			$person = array();
			$person['email'] = $email;
			$person['first_name'] = $first_name;
			$person['last_name'] = $last_name;

			// get NB response
			$nb_response = $this->nation->pushPerson($person);

			// get ID created by NB
			$person_id = $nb_response['result']['person']['id'];

			// set response
			$response['duplicate'] = false;

		}
		
		// Add Tags
		if (!empty($tags)) {
			foreach ($tags as $tag) {
				$tag_response = $this->nation->tagPerson($person_id, $tag);
				if ($tag_response['code'] != 200) break;
			}
		}
		
		// Set Response
		if ($tag_response['code'] == 200) {
			$response['tag'] == true;
		} else {
			$response['tag'] == false;
		}

		// Apply Filters
		$response = apply_filters('nationpress_response',$response, $match_response, $tag_response);

		// Return 
		return $response;

	}


	/**
	 * Template
	 * @param string $filename the name of the template file
	 * @return string the path to include
	 */

	public function template($filename) {

		// check theme
		$theme = get_template_directory() . '/'.$this->slug.'/' . $filename;

		if (file_exists($theme)) $path = $theme;
		else $path = $this->path . 'templates/' . $filename;
		
		return $path;

	}


   	/**
   	 * Template Incluce
   	 * @param string $template The name of the template file
   	 * @param *|null $data Data available within the file
   	 * @param string|null $name the value to call the date
   	 * @return null
   	 */

	public function template_include($template,$data = null,$name = null){

		if(isset($name)){ ${$name} = $data; }
		$path = $this->template($template);
		require($path);

	}



	/**
	 * Redirect
	 * Simple wordpress redirect
	 * @param string|int $path 
	 * @return null
	 */

	public function redirect($path) {

		if(is_numeric($path)){ $path = get_permalink($path); }
		wp_safe_redirect( $path );
	  	exit();

	}

	/**
	 * Outputs a WordPress error notice
	 *
	 * Push your error to $this->errors then show with:
	 * add_action( 'admin_notices', array($this, 'admin_error'));
	 */
	public function admin_error() {

		if(!$this->errors) return;

		foreach($this->errors as $error) :

	?>

		<div class="error settings-error notice is-dismissible">

			<p><strong><?php print $error ?></strong></p>
			<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>

		</div>

	<?php

		endforeach;

	}

	/**
	 * Outputs a WordPress notice
	 *
	 * Push your error to $this->notices then show with:
	 * add_action( 'admin_notices', array($this, 'admin_success'));
	 */
	public function admin_success() {

		if(!$this->notices) return;

		foreach($this->notices as $notice) :

	?>

		<div class="updated settings-error notice is-dismissible">

			<p><strong><?php print $notice ?></strong></p>
			<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>

		</div>

	<?php

		endforeach;

	}


}


/**
 * @var class NationPress $nationpress
 */

$nationpress = new NationPress();




