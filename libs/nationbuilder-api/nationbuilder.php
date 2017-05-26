<?php
require_once('oauth2/Client.php');
require_once('oauth2/GrantType/IGrantType.php');
require_once('oauth2/GrantType/AuthorizationCode.php');

class Nation {

	protected $client;

	protected $client_id;
	protected $client_secret;

	protected $base_url;
	protected $redirect_url;
	protected $auth_url;
	protected $access_token_url;

	protected $code;
	protected $access_token;

	function __construct($options) {

		$this->client;

		$this->client_id = $options['client_id'];
		$this->client_secret = $options['client_secret'];

		$this->base_url = 'https://' . $options['nation_slug'] . '.nationbuilder.com';
		$this->redirect_url = $options['redirect_url'];
		$this->auth_url = 'https://' . $options['nation_slug'] . '.nationbuilder.com/oauth/authorize';
		$this->access_token_url = 'https://' . $options['nation_slug'] . '.nationbuilder.com/oauth/token';

		$this->code = $options['code'];
		$this->access_token = $options['access_token'];

		$this->client = new OAuth2\Client($this->client_id, $this->client_secret);
		
		if(isset($_GET['code']) && (strpos(strtok($_SERVER['REQUEST_URI'], '?'), 'nationbuilder') !== false) )
			$this->output("Add this \$code to your class: <code>".$_GET['code']."</code>");

		if(empty($this->code))
			header("Location: ".$this->client->getAuthenticationUrl($this->auth_url, $this->redirect_url));
		
		if(empty($this->access_token))
			$this->output("Access Token: ".$this->get_access_token());
		else
			$this->client->setAccessToken($this->access_token);
		
	}

	protected function get_access_token() {

		if (empty($this->code)) return false;
		
		if (empty($this->access_token)) {

			$params = array('code' => $this->code, 'redirect_uri' => $this->redirect_url);

			$response = $this->client->getAccessToken($this->access_token_url, 'authorization_code', $params);

			$this->access_token = $response['result']['access_token'];
			$this->client->setAccessToken($this->access_token);
			
			return $this->access_token;
		} else {
		
			$this->client->setAccessToken($this->access_token);
			return true;
		}
		
		//TODO: Cache this access token to speed up API transactions

	}

	function output($output, $format="json", $exit=true) {

		if ($format == "json")
			header('Content-type: application/json');

		print_r($output);
		
		if($exit) exit;

	}


	function findPeople($args=array(),$range=array(10,0)) {

		$args = array_merge($args, array("per_page"=>$range[0], "page"=>$range[1]));

		$response = $this->client->fetch($this->base_url . '/api/v1/people/search', $args);
		$people = array();
		foreach ($response['result']['results'] as $result) {

			$person = array();
			$person['first_name'] = $result['first_name'];
			$person['last_name'] = $result['last_name'];
			$person['url'] = $this->base_url . "/" . $result['id'];

			$people[] = $person;
		}
		
		return $people;

	}

	function findPeopleByTag($tag,$range=array(10,0)) {

		$tag = rawurlencode($tag);

		$response = $this->client->fetch($this->base_url . '/api/v1/tags/'.$tag.'/people');
		$people = array();

		if (empty($response['result']['results'])) return $people;

		foreach ($response['result']['results'] as $result) {

			$person = array();
			$person['first_name'] = $result['first_name'];
			$person['last_name'] = $result['last_name'];
			$person['url'] = $this->base_url."/".$result['id'];

			$people[] = $person;
		}
		
		return $people;

	}

	function matchPerson($email) {

		$response = $this->client->fetch($this->base_url . '/api/v1/people/match', array('email' => $email));
		return $response;

	}

	function getPerson($id) {

		$response = $this->client->fetch($this->base_url . '/api/v1/people/' . $id);
		return $response;

	}

	function updatePerson($id, $person) {

		$params = array('person' => $person);
		$headers = array('Content-Type' => 'application/json');

		$response = $this->client->fetch($this->base_url . '/api/v1/people/' . $id, $params, 'PUT', $headers);
		
		return $response;

	}

	function pushPerson($person) {

		$params = array('person' => $person);
		$headers = array('Content-Type' => 'application/json');

		print "<pre>";
		print "<b>".$this->base_url . '/api/v1/people/push'."</b>\n";
		print_r($params);
		print_r($headers);
		print "</pre>";
		die;

		$response = $this->client->fetch($this->base_url . '/api/v1/people/push', $params, 'PUT', $headers);
		
		return $response;

	}

	function tagPerson($id, $tag) {

		$params = array(
			'tagging' => array(
				'tag' => $tag,
			),
		);
		$headers = array('Content-Type' => 'application/json');

		$response = $this->client->fetch($this->base_url . '/api/v1/people/' . $id . '/taggings', $params, 'PUT', $headers);
		
		return $response;

	}

	function removeTag($id, $tag) {

		$tag = rawurlencode($tag);

		//$tag = 'test%20tag';

		$params = '';
		$headers = array('Content-Type' => 'application/json');
		$response = $this->client->fetch($this->base_url . '/api/v1/people/' . $id . '/taggings/' . $tag, $params, 'DELETE', $headers);

		return $response;

	}

	function createDonation($donation) {

		$params = array('donation' => $donation);
		$headers = array('Content-Type' => 'application/json');

		$response = $this->client->fetch($this->base_url . '/api/v1/donations', $params, 'POST', $headers);
		
		return $response;

	}

	function updateDonation($id, $donation) {

		$params = array('donation' => $donation);
		$headers = array('Content-Type' => 'application/json');

		$response = $this->client->fetch($this->base_url . '/api/v1/donations/' . $id, $params, 'PUT', $headers);
		
		return $response;

	}

	function createContact($id, $contact) {

		$headers = array('Content-Type' => 'application/json');
		$params = array(
			'contact' => $contact
		);

		$response = $this->client->fetch($this->base_url . '/api/v1/people/' . $id . '/contacts', $params, 'POST', $headers);
		return $response;

	}

}