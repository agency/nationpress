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
					<?php $api_access = get_option('api_access'); ?>

                    <h3>Nationbuilder Details</h3>

                    <table class="form-table">
                    	<tbody>
                    		<tr valign="top">
								<th scope="row"><label for="api_access">API Access</label></th>
								<td>
									<select name="api_access" id="api_access">
										<option value="ent"<?php echo ($api_access == 'ent' ? ' selected' : '' ); ?>>Enterprise Account</option>
										<option value="app"<?php echo ($api_access == 'app' ? ' selected' : '' ); ?> >App</option>
									</select>
								</td>
							</tr>
                    	</tbody>
                    </table>

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

						</tbody>

					</table>


					<div<?php echo ($api_access == 'ent' ? ' style="display: none;"' : ''); ?>>
						<hr>
						<h3>App Authentication</h3>
						<p><b>To generate a new token:</b></p>
						<ol>
							<li>Next to <b>OAuth Authentiation Code</b> Click <b>Get Code</b>. This will prompt you to log in to nationbuilder.</li>
							<li>After logging into nationbuilder you will be redirected to a new page with no content that should look like <b>http://localhost/?code={Auth Code}</b></li>
							<li>Copy the letters and numbers in the url after where it says <b>?code=</b> into the text box and click save. This will be done for you if you are logged in and the redirect url in the box above and in NationBuilder is <b><?php echo site_url(); ?>/nationbuilder/oauth</b></li>
							<li>You should then be able to click <b>Regenerate Token</b> to create a new Access token</li>
						</ol>

						<table class="form-table">
							<tbody>

								<tr valign="top">
									<th scope="row"><label for="nb_client_id">App Client ID</label></th>
									<td>
										<input type="text" class="regular-text" id="nb_client_id" name="nb_client_id" value="<?php echo esc_attr( get_option('nb_client_id') ); ?>">
									</td>
								</tr>

								<tr valign="top">
									<th scope="row"><label for="nb_client_secret">App Client Secret</label></th>
									<td>
										<input type="text" class="regular-text" id="nb_client_secret" name="nb_client_secret" value="<?php echo esc_attr( get_option('nb_client_secret') ); ?>">
									</td>
								</tr>

								<tr valign="top">
									<th scope="row"><label for="nb_auth_code">OAuth Authentication Code</label></th>
									<td>
										<input type="text" class="regular-text" id="nb_auth_code" name="nb_auth_code" value="<?php echo esc_attr( get_option('nb_auth_code') ); ?>">
										<a class="button button-primary" js-regen-token>Get Code</a>
									</td>
								</tr>

							</tbody>
						</table>
					</div>

					<table class="form-table">
						<tbody>

							<tr valign="top">
								<th scope="row"><label for="access_token">Access Token</label></th>
								<td>
									<input type="text" class="regular-text" id="access_token" name="access_token" value="<?php echo esc_attr( get_option('access_token') ); ?>"<?php echo ($api_access == 'app' ? ' readonly' : ''); ?>>
									<span<?php echo ( $api_access == 'ent' ? ' style="display:none;"' : ''); ?>>
										<?php if ( get_option('nation_slug') && get_option('redirect_url') && get_option('nb_client_secret') && get_option('nb_client_id') && get_option('nb_auth_code') ) { ?>
											<a class="button button-primary" js-regen-token>Regenerate token</a>
										<?php } else { ?>
											<p class="error">Nation Slug, redirect Url, Client ID, Client Secret and Authentication Code required to regenerate access token.</p>
										<?php } ?>
									</span>
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
