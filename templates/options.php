<?php
/**
 * Plugin Options Page
 *
 */
 ?>
<div class="wrap">

    <h2><?php _e('NationPress Settings'); ?></h2>

    <p>Read the <a href="http://nationbuilder.com/api_quickstart" target="_blank">Nationbuilder API quick start guide</a> for instructions on generating tokens.</p>

	<div id="post-body" class="columns-3">

		<div id="post-body-content">

			<div class="meta-box">

				<form method="post" action="options.php">

					<?php settings_fields('nationpress_options'); ?>
					<?php do_settings_sections('nationpress_options'); ?>

                    <h3>Nationbuilder Details</h3>

					<table class="form-table">

						<tbody>

							<tr valign="top">
								<th scope="row"><label for="nation_slug">Nation Slug</label></th>
								<td>
									<input type="text" class="regular-text" id="nation_slug" name="nation_slug" value="<?php echo esc_attr( get_option('nation_slug') ); ?>">
								</td>
							</tr>

							<tr valign="top">
								<th scope="row"><label for="redirect_url">Redirect URL</label></th>
								<td>
									<input type="text" class="regular-text" id="redirect_url" name="redirect_url" value="<?php echo esc_attr( get_option('redirect_url') ); ?>">
								</td>
							</tr>

							<tr valign="top">
								<th scope="row"><label for="access_token">Access Token</label></th>
								<td>
									<input type="text" class="regular-text" id="access_token" name="access_token" value="<?php echo esc_attr( get_option('access_token') ); ?>">
								</td>
							</tr>

						</tbody>

					</table>

					<?php submit_button(); ?>

				</form>

			</div>

		</div>

	</div>

</div>