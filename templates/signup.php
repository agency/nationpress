
<?php do_action('nationpress_messages'); ?>

<form class="signup" action="<?php echo get_permalink(); ?>" method="post">

	<input type="hidden" name="nationpress" value="save">
	<?php wp_nonce_field( 'nationpress' ); ?>

	<div class="input-wrap">
		<label class="label" for="first_name">First Name</label>
		<input class="input" type="text" name="first_name" placeholder="First name" required="required">
	</div>

	<div class="input-wrap">
		<label class="label" for="last_name">Last Name</label>
		<input class="input" type="text" name="last_name" placeholder="Last name" required="required">
	</div>

	<span class="input-wrap">
		<label class="label" for="email">Email</label>
		<input class="input" type="email" name="email" placeholder="Email" required="required">
	</span>

	<?php do_action('nationpress_before_submit'); ?>

	<?php if($attr['tags']) : ?><input type="hidden" name="tags" value="<?php echo $attr['tags'];?>"><?php endif; ?>
	<?php if($attr['redirect']) : ?><input type="hidden" name="redirect" value="<?php echo $attr['redirect']; ?>"><?php endif; ?>
	<?php if($attr['success']) : ?><input type="hidden" name="success" value="<?php echo $attr['success']; ?>"><?php endif; ?>

	<button class="button-primary" type="submit" name="button"><?php echo ($attr['buttontext']) ? $attr['buttontext'] : 'Sign Up'; ?></button>

</form>
