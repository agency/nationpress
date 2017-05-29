<?php

class Nation {

	public $options = array();

	function __construct($options) {

		$this->options = $options;

	}

	public function pushPerson($person){

		$person['person'] = $person;
		return NationPressAPI::put('/people/push',$person);

	}

}

?>