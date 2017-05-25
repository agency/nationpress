# NationPress by Agency

Connect your WordPress site to your Nation.

------

# Shortcode

**[nationpress-signup]**
Will signup the user and apply the default tags.


**[nationpress-signup tags="wpsignup,again"]]**
User the listed tags instead of the default ones.

------

# Hooks & Filters

**nationpress_response**

```
$response = apply_filters('nationpress_response', $response, $match_response, $tag_response);
add_filter('nationpress_response','your_custom_function');

function your_custom_function($response, $person_response, $tag_response){
	
	//... do as you wish.

	// Must Return the Response
	return $response;
}

```



[Agency](http://agency.sc)

