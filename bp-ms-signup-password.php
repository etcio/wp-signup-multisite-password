<?php
/*
Plugin Name: Registration Password for Buddypress + Multisite
Plugin URI: https://github.com/etcio/wp-signup-multisite-password
Description: Lets users set a password on multisite + buddypress registration
Author: WPMUDEV -> khromov -> etc
Version: 0.1
Author URI: https://github.com/etcio/wp-signup-multisite-password
Network: true
Text Domain: multisite_password_registration
*/

/* Don't do anything unless multisite */
if(is_multisite())
{
	/** Load textdomain **/
	add_action('init', function()
	{
		load_plugin_textdomain('multisite_signup_pw', false, dirname(plugin_basename(__FILE__)).'/languages');
	});

	/** Extra signup field **/
	add_action('signup_extra_fields', function($errors)
	{
		//Find errors
		if($errors && method_exists($errors, 'get_error_message'))
			$error = $errors->get_error_message('password_1');
		else
			$error = false;
		?>

		<!-- Label for password_1 -->
		<label for="password_1"><?=__('Password', 'multisite_signup_pw')?>:</label>

		<!-- Errors -->
		<?=($error) ? "<p class=\"error\">{$error}</p>" : ''?>

		<!-- password_1 input -->
		<input name="password_1" type="password" id="password_1" value="" autocomplete="off" maxlength="20"/><br/>
		<?=__('Type in your password.', 'multisite_signup_pw')?>

		<!-- Label for password_2 -->
		<label for="password_2"><?=__('Confirm Password', 'multisite_signup_pw'); ?>:</label>

		<!-- password_2 input -->
		<input name="password_2" type="password" id="password_2" value="" autocomplete="off" maxlength="20"/><br/>
		<?=__('Type in your password again.', 'multisite_signup_pw')?>
		<?php
	}, 9); //Show early

	/** Perform field validation **/
	add_filter('wpmu_validate_user_signup', function($content)
	{
		$password_1 = isset($_POST['password_1']) ? $_POST['password_1'] : '';
		$password_2 = isset($_POST['password_2']) ? $_POST['password_2'] : '';

		if(isset($_POST['stage']) && $_POST['stage'] == 'validate-user-signup')
		{
			//No primary password entered
			if(trim($password_1) === '')
			{
				$content['errors']->add('password_1', __('You have to enter a password.', 'multisite_signup_pw'));
				return $content;
			}

			//Passwords do not match
			if($password_1 != $password_2)
			{
				$content['errors']->add('password_1', __('Passwords do not match.', 'multisite_signup_pw'));
				return $content;
			}
		}

		//No errors, yay!
		return $content;
	});

	/** Add password to temporary user meta **/
	add_filter('add_signup_meta', function($meta)
	{
		if(isset($_POST['password_1']))
		{
			$add_meta = array('password' => (isset($_POST['password_1_base64']) ? wp_hash_password(base64_decode($_POST['password_1'])) : wp_hash_password($_POST['password_1']))); //Store as base64 to avoid injections
			$meta = array_merge($add_meta, $meta);
		}
		//This should never happen.

		return $meta;
	}, 99);

	/** Pass the password through to the blog registration form **/
	add_action('signup_blogform', function()
	{
		if(isset($_POST['password_1']))
		{
			?>
			<!-- pass that we have base64 encoded the value -->
			<input type="hidden" name="password_1_base64" value="1" />
			<!-- don't base64 encode multiple times if user fails validation (in which case the flag will already be set) -->
			<input type="hidden" name="password_1" value="<?=(isset($_POST['password_1_base64']) ? $_POST['password_1'] : base64_encode($_POST['password_1']))?>" />
			<?php
		}
	});

}