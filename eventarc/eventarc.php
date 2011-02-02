<?php
/*
Plugin Name: Eventarc	
Plugin URI: http://www.eventarc.com/
Description: Displays your eventarc events.
Version: 1.0.0
Author: Eventarc
Author URI: http://www.eventarc.com/
 */
define('EVENTARC_VERSION', '1.0.0');

// If you hardcode a WP.com API key here, all key config screens will be hidden
if ( defined('EVENTARC_NAME') )
	$eventarc_u_name = constant('EVENTARC_NAME');
else
	$eventarc_u_name = '';

function eventarc_init() {
	global $eventarc_u_name;
	add_action('admin_menu', 'eventarc_config_page');
	eventarc_admin_warnings();
}
add_action('init', 'eventarc_init');

if ( !function_exists('wp_nonce_field') ) {
	function eventarc_nonce_field($action = -1) { return; }
	$eventarc_nonce = -1;
} else {
	function eventarc_nonce_field($action = -1) { return wp_nonce_field($action); }
	$eventarc_nonce = 'eventarc-update-key';
}

if ( !function_exists('number_format_i18n') ) {
	function number_format_i18n( $number, $decimals = null ) { return number_format( $number, $decimals ); }
}

function eventarc_config_page() {
	if ( function_exists('add_submenu_page') )
		add_submenu_page('plugins.php', __('Eventarc Configuration'), __('Eventarc Configuration'), 'manage_options', 'eventarc-key-config', 'eventarc_conf');

}

function eventarc_conf() {
	global $eventarc_nonce, $eventarc_u_name;
	$ms = array();
	if ( isset($_POST['submit']) ) {
		if ( function_exists('current_user_can') && !current_user_can('manage_options') )
			die(__('Cheatin&#8217; uh?'));

		check_admin_referer( $eventarc_nonce );
		$key = preg_replace( '/[^a-zA-Z0-9-_]/i', '', $_POST['u_name'] );

		if ( empty($key) ) {
			$key_status = 'empty';
			$ms[] = 'new_key_empty';
			delete_option('eventarc_u_name');
		} else {
			$key_status = eventarc_verify_u_name( $key );
		}

		if ( $key_status === 'valid' ) {
			update_option('eventarc_u_name', $key);
			
			$ms[] = 'new_key_valid';
		} else if ( $key_status == 'invalid' ) {
			$ms[] = 'new_key_invalid';
		} else if ( $key_status == 'failed' ) {
			$ms[] = 'new_key_failed';
		}

		
	}

	$messages = array(
		'new_key_empty' => array('color' => 'aa0', 'text' => __('Your user name has been cleared.')),
		'new_key_valid' => array('color' => '2d2', 'text' => __('Your user name has been verified.')),
		'new_key_invalid' => array('color' => 'd22', 'text' => __('The user name you entered is invalid. Please double-check it.')),
		'new_key_failed' => array('color' => 'd22', 'text' => __('The user name you entered could not be verified because a connection to eventarc.com could not be established. Please check your server configuration.')),
		'no_connection' => array('color' => 'd22', 'text' => __('There was a problem connecting to the Eventarc server. Please check your server configuration.')),
		'key_empty' => array('color' => 'aa0', 'text' => sprintf(__('Please enter an your username. (<a href="%s" style="color:#fff">Get your username.</a>)'), 'https://myeventarc.com/register')),
		'key_valid' => array('color' => '2d2', 'text' => __('This username is valid.')),
		'key_failed' => array('color' => 'aa0', 'text' => __('The username below was previously validated but a connection to eventarc.com can not be established at this time. Please check your server configuration.')));
?>
<?php if ( !empty($_POST['submit'] ) ) : ?>
<div id="message" class="updated fade"><p><strong><?php _e('Options saved.') ?></strong></p></div>
<?php endif; ?>
<div class="wrap">
<h2><?php _e('Eventarc Configuration'); ?></h2>
<div class="narrow">
<form action="" method="post" id="eventarc-conf" style="margin: auto; width: 400px; ">
<?php if ( !$eventarc_u_name ) { ?>
	<p><?php printf(__('By adding your username, a list of all of your current active <a href="%1$s">Eventarc</a> events will be displayed. If you don\'t have a username yet, you can get one at <a href="%2$s">Eventarc.com</a>.'), 'http://eventarc.com/', 'http://eventarc.com/get/'); ?></p>

<h3><label for="u_name"><?php _e('Eventarc API Key'); ?></label></h3>
<?php foreach ( $ms as $m ) : ?>
	<p style="padding: .5em; background-color: #<?php echo $messages[$m]['color']; ?>; color: #fff; font-weight: bold;"><?php echo $messages[$m]['text']; ?></p>
<?php endforeach; ?>
<p><input id="u_name" name="u_name" type="text" size="15" maxlength="12" value="<?php echo get_option('eventarc_u_name'); ?>" style="font-family: 'Courier New', Courier, mono; font-size: 1.5em;" /> (<?php _e('<a href="http://eventarc.com/">What is Eventarc?</a>'); ?>)</p>
<?php if ( $invalid_key ) { ?>
<h3><?php _e('Why might my key be invalid?'); ?></h3>
<p><?php _e('This can mean one of two things, either you copied the user name wrong or that the plugin is unable to reach the Eventarc servers, which is most often caused by an issue with your web host around firewalls or similar.'); ?></p>
<?php } ?>
<?php } ?>
<?php eventarc_nonce_field($eventarc_nonce) ?>

	<p class="submit"><input type="submit" name="submit" value="<?php _e('Update options &raquo;'); ?>" /></p>
</form>



</div>
</div>
<?php
}

function eventarc_stats_page() {
	if ( function_exists('add_submenu_page') )
		add_submenu_page('index.php', __('Eventarc Stats'), __('Eventarc Stats'), 'manage_options', 'eventarc-stats-display', 'eventarc_stats_display');

}

function eventarc_stats_script() {
	
}


function eventarc_stats_display() {
	//global $eventarc_api_host, $eventarc_api_port, $eventarc_u_name;
	//$blog = urlencode( get_option('home') );
	$url = 'http://www.eventarc.com';
	?>
	<div class="wrap">
	<iframe src="<?php echo $url; ?>" width="100%" height="100%" frameborder="0" id="eventarc-stats-frame"></iframe>
	</div>
	<?php
}

function eventarc_get_key() {
	global $eventarc_u_name;
	if ( !empty($eventarc_u_name) )
		return $eventarc_u_name;
	return get_option('eventarc_u_name');
}

function eventarc_verify_u_name( $key, $ip = null ) {
	// TODO verify the username
	return 'valid';
}

// Check connectivity between the WordPress blog and Eventarc's servers.
// Returns an associative array of server IP addresses, where the key is the IP address, and value is true (available) or false (unable to connect).
function eventarc_check_server_connectivity() {
	return true;
}


// Returns true if server connectivity was OK at the last check, false if there was a problem that needs to be fixed.
function eventarc_server_connectivity_ok() {
	// skip the check on WPMU because the status page is hidden
	global $eventarc_u_name;
	if ( $eventarc_u_name )
		return true;
	$servers = eventarc_get_server_connectivity();
	return true;//!( empty($servers) || !count($servers) || count( array_filter($servers) ) < count($servers) );
}

function eventarc_admin_warnings() {
	global $eventarc_u_name;
	if ( !get_option('eventarc_u_name') && !$eventarc_u_name && !isset($_POST['submit']) ) {
		function eventarc_warning() {
			echo "
			<div id='eventarc-warning' class='updated fade'><p><strong>".__('Eventarc is almost ready.')."</strong> ".sprintf(__('You must <a href="%1$s">enter your Eventarc username</a> for it to work.'), "plugins.php?page=eventarc-key-config")."</p></div>
			";
		}
		add_action('admin_notices', 'eventarc_warning');
		return;
	}
}

function eventarc_get_host($host) {
	// TODO
	return $host;
}

// return a comma-separated list of role names for the given user
function eventarc_get_user_roles($user_id ) {
	$roles = false;
	
	if ( !class_exists('WP_User') )
		return false;
	
	if ( $user_id > 0 ) {
		$comment_user = new WP_User($user_id);
		if ( isset($comment_user->roles) )
			$roles = join(',', $comment_user->roles);
	}
	
	return $roles;
}

// Returns array with headers in $response[0] and body in $response[1]
function eventarc_http_post($request, $host, $path, $port = 80, $ip=null) {
	global $wp_version;
	
	$eventarc_version = constant('EVENTARC_VERSION');

	$http_request  = "POST $path HTTP/1.0\r\n";
	$http_request .= "Host: $host\r\n";
	$http_request .= "Content-Type: application/x-www-form-urlencoded; charset=" . get_option('blog_charset') . "\r\n";
	$http_request .= "Content-Length: " . strlen($request) . "\r\n";
	$http_request .= "User-Agent: WordPress/$wp_version | Eventarc/$eventarc_version\r\n";
	$http_request .= "\r\n";
	$http_request .= $request;
	
	$http_host = $host;
	// use a specific IP if provided - needed by eventarc_check_server_connectivity()
	if ( $ip && long2ip(ip2long($ip)) ) {
		$http_host = $ip;
	} else {
		$http_host = eventarc_get_host($host);
	}

	$response = '';
	if( false != ( $fs = @fsockopen($http_host, $port, $errno, $errstr, 10) ) ) {
		fwrite($fs, $http_request);

		while ( !feof($fs) )
			$response .= fgets($fs, 1160); // One TCP-IP packet
		fclose($fs);
		$response = explode("\r\n\r\n", $response, 2);
	}
	return $response;
}


// Widget stuff
function widget_eventarc_register() {
	if ( function_exists('register_sidebar_widget') ) :
	function widget_eventarc($args) {
		extract($args);
		$options = get_option('widget_eventarc');
		$event_list =  process_events();
		?>
			<?php echo $before_widget; ?>
				<?php echo $before_title . $options['title'] . $after_title; ?>
				<div id="eventarcwrap"><?php echo $event_list;?></div>
			<?php echo $after_widget; ?>
	<?php
	}


	function process_events(){
		$u_name = get_option('eventarc_u_name');
		$doc = new DOMDocument();

		if ( $doc->load('http://myeventarc.com/rss/user/'.$u_name.'/current',LIBXML_NOWARNING) === false){
			return '';
		}
		
		$event_list = '';
		foreach ($doc->getElementsByTagName('item') as $node) {
			$title = $node->getElementsByTagName('title')->item(0)->nodeValue;
			$link = $node->getElementsByTagName('link')->item(0)->nodeValue;
			$date = $node->getElementsByTagName('deadline')->item(0)->nodeValue;

			$event_list .= '<div class="eventarc-event"><a href="'.$link.'">'.$title.'</a><br/><small>Deadline: '.$date.'</small></div>';
		}
		return $event_list;
	}
	
	function widget_eventarc_style() {

	}

	function widget_eventarc_control() {
		$options = $newoptions = get_option('widget_eventarc');
		if ( $_POST["eventarc-submit"] ) {
			$newoptions['title'] = strip_tags(stripslashes($_POST["eventarc-title"]));
			if ( empty($newoptions['title']) ) $newoptions['title'] = __('Spam Blocked');
		}
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('widget_eventarc', $options);
		}
		$title = htmlspecialchars($options['title'], ENT_QUOTES);
	?>
				<p><label for="eventarc-title"><?php _e('Title:'); ?> <input style="width: 250px;" id="eventarc-title" name="eventarc-title" type="text" value="<?php echo $title; ?>" /></label></p>
				<input type="hidden" id="eventarc-submit" name="eventarc-submit" value="1" />
	<?php
	}

	register_sidebar_widget('Eventarc', 'widget_eventarc', null, 'eventarc');
	register_widget_control('Eventarc', 'widget_eventarc_control', null, 75, 'eventarc');
	if ( is_active_widget('widget_eventarc') )
		add_action('wp_head', 'widget_eventarc_style');
	endif;
}

add_action('init', 'widget_eventarc_register');

// Counter for non-widget users
function eventarc_events() {
	$event_list = process_events();
	echo '<div id="eventarcwrap">'.$event_list.'</div>';

}

add_filter("the_content", "process_content");

function process_content($content=''){

	$content_array = explode('[eventarc:show_events]', $content);
	if (count($content_array) == 1) return $content;
	
	// Otherwise for every instance of the tag, put the events in
	$event_list = process_events();
	$new_content = implode($event_list,$content_array);
	return $new_content;
	
}

?>
