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

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

require_once(  WP_PLUGIN_DIR . '/nationpress/libs/nationbuilder-api/nationbuilder.php' );

/*==========  Activation Hook  ==========*/
register_activation_hook( __FILE__, array( 'NationPress', 'install' ) );


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
		
		$this->errors = false;
		$this->notice = false;

		$this->options = $this->get_options();

		if ($this->options_exist()) {
			$this->nation = new Nation($this->options);
		}

		// Actions
		add_action('admin_menu', array($this, 'register_options_page'));
		add_action('wp_loaded', array($this , 'forms'));
		if ( is_multisite() ) {
			add_action('wpmu_activate_user', array($this, 'push_WPMU_user'), 10, 3);
		} else {
			add_action('user_register', array($this, 'push_WP_user'), 10, 1);
		}
				
	}

	public static function install() {

		/**
		*
		* Add methods here that should be run when the plugin is activated.
		*
		**/

	}


	/**
	 * Form processing
	 * 
	 * This function powers your form processing,
	 * based on the value of a hidden "plugin_starter_action" field
	 * 
	 * Route forms to other functions within this class.
	 */
	public function forms() {
		if (!isset($_POST['nationbuilder'])) return;

		$type = $_POST['nationbuilder']['type'];

		switch ($type) {
			case 'subscribe':
				$response = $this->push_subscriber();
				if ($response['errors'] > 0) {
					wp_redirect( '?subscribe=error' );
				} else {
					wp_redirect( '?subscribe=success' );
				}
				exit;
				break;
			default:
				break;
		}

	}


	/**
	 * Register options page
	 */
	public function register_options_page() {

		// main page
		add_options_page('Nationbuilder', 'Nationbuilder', 'manage_options', 'nationpress_options', array($this, 'include_options'));
		add_action('admin_init', array($this, 'plugin_options'));
		
	}


	/**
	 * Get options template
	 */
	public function include_options() { require('templates/options.php'); }


	/**
	 * Register plugin settings
	 * 
	 * Register each unique setting administered on your 
	 * options page in a new line in the array.
	 */
	public function plugin_options() {

		$keys = $this->get_option_keys();

		foreach ($keys as $key) {
			register_setting('nationpress_options', $key);
		}

	}


	/**
	 * 
	 * 
	 * 
	 * 
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
	 * 
	 * 
	 * 
	 * 
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
	 * 
	 * 
	 * 
	 * 
	*/

	public function options_exist() {

		$keys = $this->options;

		foreach ($keys as $key) {
			if ($key == '') {
				return false;
			}
		}

		return true;

	}

	/**
	 * Push subscriber (non WP user) details
	 *
	 *
	 *
	 */
	public function push_subscriber() {
		// get user data
		$email = $_POST['user_email'];
		$first_name = $_POST['first_name'];
		$last_name = $_POST['last_name'];
		// $name = $_POST['nationbuilder']['name'];

		// // explode name
		// if (strpos($name, ' ') !== false) {
		// 	$name_array = explode(' ', $name);
		// 	$first_name = $name_array[0];
		// 	$last_name = end($name_array);
		// } else {
		// 	$first_name = $name;
		// 	$last_name = '';
		// }

		$meta['first_name'] = $first_name;
		$meta['last_name'] = $last_name;

		// register user to WP
		$username = sanitize_user($email);
		$password = wp_generate_password();
		is_multisite() ? wpmu_signup_user( $username, $email, $meta ) : wp_create_user( $username, $password, $email );

		//wp_new_user_notification( $user_id, $password );

		$response = $this->push_to_NB( $email, $first_name, $last_name );

		return $response;
	}

	/**
	 * Push WP user details
	 *
	 *
	 *
	 */
	public function push_WP_user( $user_id ) {
		// get user data
		$email = $_POST['user_email'];
		$first_name = $_POST['first_name'];
		$last_name = $_POST['last_name'];

		$this->push_to_NB( $email, $first_name, $last_name );
	}

	/**
	 * Push WP user details on Multisite
	 *
	 *
	 *
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
	 *
	 *
	 *
	 */
	public function push_to_NB( $email, $first_name, $last_name ) {

		$tags = array();
		$tag_string = $this->options['tags'];
		$tags = explode(', ', $tag_string);

		// create response array
		$response = array();
		$response['errors'] = 0;

		// search for existing email
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
		
		// add tags
		if (!empty($tags)) {
			foreach ($tags as $tag) {
				$tag_response = $this->nation->tagPerson($person_id, $tag);
				if ($tag_response['code'] != 200) break;
			}
		}
		
		// set response
		if ($tag_response['code'] == 200) {
			$response['tag'] == true;
		} else {
			$response['tag'] == false;
		}	

		return $response;

	}


}


/**
 * @var class NationPress $nationpress
 */

require_once('nationpress-template-tags.php');
$nationpress = new NationPress();




