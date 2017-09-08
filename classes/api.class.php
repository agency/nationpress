<?php
/**
 * Kepla API Class
 * --------------------------------
 *
 */




class NationPressAPI {


	private static function base(){

		return 'https://'.get_option('nation_slug').'.nationbuilder.com/api/v1';

	}

	private static function token(){

		return get_option('access_token');
	}

	/**
	 * URL
	 * @param string $path the endpoint
	 * @return string
	 */

	private static function url($path){

		return self::base() . $path.'/?access_token='.self::token();

	}

	/**
	 * Headers
	 * Returns the default headers and authorization
	 * @return array
	 */

	private static function headers(){

		return array(
			'Content-Type' => 'application/json',
			'Accept' => 'application/json'
		);

	}

	/**
	 * Encode
	 * Encodes the data into the proper format
	 * @param * $data the data to encode
	 */

	private static function encode($data){

		return json_encode($data);

	}

	/**
	 * GET Request
	 * @param string $path
	 * @return obj Server response object
	 */

	public static function get($path, $data = null){

		$url = self::url($path);

		if($data) foreach($data as $key=>$value) $url .= '&'.$key.'='.$value;

		$response = \Httpful\Request::get( $url )
			->expectsJson()
			->addHeaders(self::headers())
			->send();

		return $response;

	}

	/**
	 * PUT Request
	 * @param string $path
	 * @param array|obj $data the data to send
	 * @return obj Server response object
	 */

	public static function put($path, $data){

		$response = \Httpful\Request::put( self::url($path) )
			->addHeaders( self::headers() )
			->body( self::encode($data) )
			->send();

		return $response;

	}

	/**
	 * POST Request
	 * @param string $path
	 * @param array|obj $data the data to send
	 * @return obj Server response object
	 */

	public static function post($path, $data){

		$response = \Httpful\Request::post( self::url($path) )
			->sendsJson()
			->addHeaders( self::headers() )
			->body( self::encode($data) )
			->send();

		return $response;

	}

	// TODO:
	public static function delete($path){

		$response = \Httpful\Request::delete( self::url($path) )
			->addHeaders( self::headers() )
			->send();

		return $response;

	}

}
?>
