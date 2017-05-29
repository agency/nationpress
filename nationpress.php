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
if(!class_exists('Httpful\Bootstrap')) require_once('libs/httpful.phar');
require_once('classes/api.class.php');
require_once('classes/nation.class.php');


/**
 * Main NationPress Class
 *
 * @class NationPress
 * @version 0.1
 */
class NationPress {

	public $errors = false;
	public $notices = false;
	public $messages = false;
	public $slug = 'nationpress';

	function __construct() {

		$this->path = plugin_dir_path(__FILE__);
		$this->folder = basename($this->path);
		$this->dir = plugin_dir_url(__FILE__);
		$this->version = '1.0.0';

		// Plugin Options
		$this->plugin_options_keys = array(
			'nation_slug',
			'redirect_url',
			'access_token',
		);


		// Actions
		add_action('wp_loaded', array($this , 'forms'));
		add_action('parse_request', array($this , 'endpoints'));
		add_action('admin_menu', array($this, 'register_options_page'));
		add_action('admin_notices', array($this, 'admin_success'));
		add_action('admin_notices', array($this, 'admin_error'));

		// Custom Actions
		add_action('nationpress_messages', array($this,'frontend_messages'));

		// Shortcodes
		add_shortcode('nationpress-signup', array($this,'shortcode_signup'));

		// On User Creation
		if (is_multisite()) add_action('wpmu_activate_user', array($this, 'push_WPMU_user'), 10, 3);
		else add_action('user_register', array($this, 'push_WP_user'), 10, 1);

		// Get Options
		$this->options = $this->get_options();
		if ($this->options_exist()) $this->nation = new Nation($this->options);
			

	}


	// ------------------------------------------------------------------------
	//
	// Nationpress Processing
	//
	// ------------------------------------------------------------------------


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
	 * Save
	 * Process form submission
	 * @param array $vars 
	 * @param bool $return 
	 * @return array|null
	 */

	public function save($vars,$return = true){

		// Send to NationBuilder
		$response = $this->push($vars);
		
		// Process Response
		if($response['person_id']){
			
			$result = array('success'=>true,'response'=>$response);

		} else {
			
			$result = array('errors'=>true,'response'=>$response);

		}

		// Handle Return Action
		if($return == true) {
			if($vars['redirect']) $this->redirect($vars['redirect']);
			if($vars['success']) $this->messages[] = $vars['success'];
			if(!$vars['success']) $this->messages[] = "Thanks for signing up.";
			return $result;
		}

		// Display JSON
		$this->output_json($result);
		die;

	}

	/**
	 * Push
	 * Call the NationBuilder API
	 * @param array $vars 
	 * @return array
	 */

	public function push($vars){

		$response = array();

		// get NB response
		$response_person_push = $this->nation->push_person($vars['person']);

		// get ID created by NB
		$person_id = $response_person_push->body->person->id;

		if($person_id) {
			$response['person_id'] = $person_id;
		} else {

			$response['person_id'] = null;
			$response['result'] = $response_person_push;
			return $response;
		}
		
		// add tags
		if ( !empty( $vars['tags'] ) ) {

			// Split it up
			$vars['tags'] = explode(',', $vars['tags']);

			foreach ( $vars['tags'] as $tag ) {

				// Gaurd
				if(empty($tag)) continue;

				// Tag The Person
				$tag_response = $this->nation->tag_person( $person_id, $tag );
				if ( $tag_response->code != 200 ) break;

			}

		}

		$response['tag'] = ( ( isset($tag_response) && $tag_response->code == 200 ) ? 'true' : 'false' );

		do_action('nationpress_push_response', $response, $response_person_push, $tag_response);

		return $response;
	}

	// ------------------------------------------------------------------------
	//
	// Default Wordpress Plugin Setup
	//
	// ------------------------------------------------------------------------

	/**
	 * Form Processing
	 * Form processing for custom signup forms
	 * @return null
	 */

	public function forms() {

		if (!isset($_POST['nationpress_action'])) return;

		switch ($_POST['nationpress_action']) {

			case 'save':
				$this->save($_POST['nationpress']);
				break;

			default:
				break;
		}

	}


   /**
    * Endpoints
    * @param  $wp | Object
    * @return false
    **/

	public function endpoints($wp) {

		$pagename = (isset($wp->query_vars['pagename'])) ? $wp->query_vars['pagename'] : $wp->request;

		switch ($pagename) {

			case 'nationpress/api/save':
				$this->save($_POST['nationpress'],false);
				die;
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
	 * Get Options
	 * Get the saved values of the options from wordpress
	 * @return array
	 */

	public function get_options() {

		$options = array();

		foreach ($this->plugin_options_keys as $key) $options[$key] = get_option($key);

		return $options;

	}


	/**
	 * Options Exist
	 * Validate the saved options
	 * @return boolean
	 */

	public function options_exist() {

		$keys = $this->options;

		foreach ($keys as $key => $value) {
			
			if ($key == '' && $value != 'email_subject' && $value != 'email_message') {

				$this->errors[] = "NationPress: Complete your setup <a href=\"/wp-admin/admin.php?page=nationpress_options\">here</a>.";	
				return false;

			}

		}

		return true;

	}


	/**
	 * Plugin Options
	 * Register the plugin options with wordpress
	 * @return null
	 */

	public function plugin_options() {

		foreach ($this->plugin_options_keys as $option) {
			register_setting('nationpress_options', $option);
		}

	}


	/**
	 * Admin Error
	 * Display errors to the wordpress admin
	 * @return type
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
	 * Admin Success
	 * Display success messages to the admin
	 * @return type
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

	/**
	 * Frontend Messages
	 * Returned from the api or success messages
	 * @return null
	 */

	public function frontend_messages(){

		if(empty($this->messages)) return;

		foreach($this->messages as $message) {

			$this->template_include('message.php',$message,'message');

		}

	}

   /**
    * Email
    * ---------------------------------------------
    * @param  $to           | String | To email address
    * @param  $subject      | String | The email Subject
    * @param  $message      | String | The email body
    * @param  $replacements | Array  | Key=>Value string replacements
    * @return false
    * ---------------------------------------------
    **/

	public function email($to, $subject, $message, $replacements = array()) {

		//replacements
		foreach ($replacements as $variable => $replacement) {
			$message = str_replace($variable, $replacement, $message);
			$subject = str_replace($variable, $replacement, $subject);
		}

		//Send from the site email
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>'
		);

		//WP mail function
		wp_mail( $to, $subject, $message , $headers);

	}




	/**
	 * Template
	 * @param string $filename the name of the template file
	 * @return string the path to include
	 */

	public function template($filename) {

		// check theme
		$theme = get_template_directory() . '/'.$this->slug.'/' . $filename;

		if (file_exists($theme)) {
			$path = $theme;
		} else {
			$path = $this->path . 'templates/' . $filename;
		}
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
		include($path);
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
	 * Output JSON
	 * @param array|object $array 
	 * @return JSON
	 */

	public function output_json($array) {

		header('Content-type: application/json');
		echo json_encode($array);
		exit();

	}

}

// ------------------------------------
// Go
// ------------------------------------

$nationpress = new NationPress();
