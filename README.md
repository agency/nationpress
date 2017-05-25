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

**nationpress_before_submit**
```
add_action('nationpress_before_submit','add_more_fields');
function add_more_fields(){
	
	?>
		<input type="text" name="myfield">

	<?php

}
```


# Endpoints

**POST: /nationpress/api/save**
```
@returns JSON
```


[Agency](http://agency.sc)

