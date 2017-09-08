<?php

class Nation {

	public $options = array();

	function __construct($options) {

		$this->options = $options;

	}

	/**
	 * Get Person
	 * @param int $person_id
	 * @return object
	 */

	public function get_person($person_id){

		return NationPressAPI::get('/people/'.$person_id);

	}

	/**
	 * Match Person
	 * @param string $email
	 * @return object
	 */

	public function match_person($email){

		return NationPressAPI::get('/people/match',array('email'=>$email));

	}

	/**
	 * Push Person
	 * @param array $person
	 * @return object
	 */

	public function push_person($person){

		$params = array('person' => $person);
		return NationPressAPI::put('/people/push',$params);

	}

	/**
	 * Update Person
	 * @param int $person_id
	 * @param array $person
	 * @return object
	 */

	public function update_person($person_id, $person){

		$params = array('person' => $person);
		return NationPressAPI::put('/people/'.$person_id, $params);

	}

	/**
	 * Tag Person
	 * @param int $person_id
	 * @param string $tag
	 * @return object
	 */

	public function tag_person($person_id, $tag){

		$params = array('tagging' => array('tag' => $tag));
		return NationPressAPI::put('/people/'.$person_id.'/taggings', $params);

	}

	/**
	 * Remove Tag
	 * @param int $person_id
	 * @param string $tag
	 * @return object
	 */

	public function remove_tag($person_id, $tag){

		return NationPressAPI::delete('/people/'.$person_id.'/taggings/'.$tag);

	}

}
?>
