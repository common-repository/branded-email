<?php
/*
Plugin Name: Branded Email
Plugin URI: http://www.vagabumming.com/branded-email/
Description: Sends your logo with WordPress generated emails.
Version: 1.0
Author: Will Brubaker
Author URI: http://www.vagabumming.com
License: GPL2
*/
/*  Copyright 2011  Will Brubaker  (email : will@vagabumming.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class Add_email_logo{
static private $vagabumming_plugin_values = array( 
											'name'=>'Branded Email',
											'version'=>'1.0', //hate using a string value here, but need it to hold non-numeric values
											'slug'=>'branded-email',
											'dbversion'=>'',
											'supplementary'=>array(//a place to put things in the future..
																)
											);
function Add_email_logo(){
global $vagabumming_link_back;
add_filter('plugin_action_links',array(&$this,'my_plugin_action_links'),10,2);
add_action('wp_ajax_set-branded-email-logo',array(&$this,'set_logo'));;
add_action('phpmailer_init',array(&$this,'add_logo'));
add_action('admin_menu',array(&$this,'admin_menu'));
add_action('admin_enqueue_scripts',array(&$this,'options_scripts'));
if( get_option('vagabumming_link_back') == 'linkback' && (! $vagabumming_link_back) )
	add_action('wp_footer',array(&$this,'linkback'));
if(get_option('vagabumming_' . self::$vagabumming_plugin_values['slug'] . '_plugin_version') != self::$vagabumming_plugin_values['version']){
	add_action('admin_init',array(&$this,'initialize_plugin'));
	}
register_deactivation_hook(__FILE__,array(&$this,'remove_plugin'));


add_filter('wp_mail_content_type',create_function('', 'return "text/html"; '));
add_filter('wp_mail',array(&$this,'modify_email'),9999);
add_filter('retrieve_password_message',array(&$this,'format_lp_message'),0);

}
//vagabumming functions
function initialize_plugin(){
//if this isn't a public blog on a public server, don't call home:
if((get_option('blog_public') == 1) && filter_var($_SERVER['SERVER_ADDR'],FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)){
	$this_site = urlencode(get_site_url());
	$this_plugin = urlencode(self::$vagabumming_plugin_values['name']);
	$url = 'http://www.vagabumming.com/mypluginusers.php?plugin_name=' . $this_plugin . '&site=' . $this_site;
	file_get_contents($url);
}

$installed_plugins = get_option('vagabumming_plugins_installed');
$plugin_name = self::$vagabumming_plugin_values['name'];
if(!in_array($plugin_name,$installed_plugins)){
	$installed_plugins[] = $plugin_name;
	update_option('vagabumming_plugins_installed',$installed_plugins);
	}

//give plugin users another chance to show the love!
if(get_option('vagabumming_link_back') == 'nolinkback') delete_option('vagabumming_link_back');
//put the new version information in the db to keep this function from ever running again
update_option('vagabumming_' . self::$vagabumming_plugin_values['slug'] . '_plugin_version',self::$vagabumming_plugin_values['version']);
	
}
function admin_menu(){
add_filter('media_upload_tabs',array(&$this,rm_media_tab));
add_options_page(self::$vagabumming_plugin_values['name'] . ' Options', self::$vagabumming_plugin_values['name'], 'manage_options',self::$vagabumming_plugin_values['slug'],array(&$this,'set_plugin_options'));
}
function options_scripts(){

wp_enqueue_script('media-upload');
wp_enqueue_script('thickbox');
wp_enqueue_style('thickbox');

}
function message($msg){
$msg1 = '<p>Thank you for using the \'' . self::$vagabumming_plugin_values['name'] . '\' plugin.  You now have the ability to strengthen your brand!  To say thanks for these powers, Please <a title="set a link back to the plugin developer\'s site.  Only shows on your home page" href="' . $_SERVER['REQUEST_URI'] . '&set_link=yes">link back</a> to the developer\'s site (only shows an unobtrusive link on your front page).  Of course you can use this code without linking back, freeloading is o.k., nobody will know. <a href="' . $_SERVER['REQUEST_URI'] . '&set_link=no">I\'m a freeloader and don\'t want to link back</a>.  (either option will make this message go away)</p>';
$msg2 = '<p>By default, sites using this plugin will be linked from the developer\'s site.  You can, of course, <a href="' . $_SERVER['REQUEST_URI'] . '&opt_out">opt out</a> of this option.</p>';
	if(isset($msg)){
	if($msg == 1){
		$msg = $msg1;
		}elseif($msg == 2){
		$msg = $msg2;
		}
	return $msg . "\n";
	}
	}
function linkback (){
global $vagabumming_link_back;
	if(is_front_page()){
	echo '<div id=vagabumming_link_back style="text-align: center; margin: 0px, auto;"><a href="http://www.vagabumming.com"	title="world travel blog"	>Global Travel</a></div>';
		$vagabumming_link_back = true;
		}
}
function my_plugin_action_links($links,$file){
static $this_plugin;
	if(! $this_plugin) {
		$this_plugin = plugin_basename(__FILE__);
		}
	if(isset($_REQUEST['set_link'])){
		if($_REQUEST['set_link'] == 'yes'){
		update_option('vagabumming_link_back','linkback');
		}else{
		update_option('vagabumming_link_back','nolinkback');
		}
	}
	if($file == $this_plugin){
		if(get_option('vagabumming_link_back') == 'linkback'){
			$link = '<a title="remove the link to the developer\'s page" href="?set_link=no">Remove Linkback</a><br>';
			}
			else{
			$link = '<a title="set a link from your homepage to the plugin developer\'s page <3" href="?set_link=yes">Set Linkback</a><br>';
			}
		array_unshift($links, $link);
	$hire_me_link = '<a title="hire me for custom wordpress development needs" href="http://www.vagabumming.com/hire-me-for-your-website-development-needs/">Hire Me!</a>';
	array_unshift($links, $hire_me_link);
	}
return $links;
}
function remove_plugin(){
delete_option('vagabumming_' . self::$vagabumming_plugin_values['slug'] . '_plugin_version');
delete_option(self::$vagabumming_plugin_values['slug'] . '-logo');
$x = count(get_option('vagabumming_plugins_installed'));
	if($x <= 1){//this is the last (or only) vagabumming plugin installed, remove ALL traces of vagabumming plugins
		delete_option('vagabumming_opt_out');
		delete_option('vagabumming_plugins_installed');
		delete_option('vagabumming_link_back');
	}else{//this plugin is the only one we're uninstalling.  Let's just remove it from the array.
	$plugins_installed = get_option('vagabumming_plugins_installed');
	foreach($plugins_installed as $plugin){
		if ($plugin != self::$vagabumming_plugin_values['name']){
			$tmp[] = $plugin;
			}
		}
	update_option('vagabumming_plugins_installed',$tmp);
	}

}
//plugin specific functions
function rm_media_tab($tabs){
if(! strpos($_SERVER['REQUEST_URI'], self::$vagabumming_plugin_values['slug']) ) return $tabs;
//if our options page is being viewed, we don't want to show the 'from url' tab.
unset($tabs['type_url']);
return $tabs;
}
function set_logo(){
$logo_url = $_POST['logo'];
$x = parse_url($logo_url);
$output = "Your current logo is shown below\n
<p></p>\n
<p>\n
<img src=\"" . $logo_url. "\">\n
</p>";
$logo_path = ltrim($x['path'],'/');
$logo_path = ABSPATH . $logo_path;
$vals = array($logo_url,$logo_path);
update_option(self::$vagabumming_plugin_values['slug'] . '-logo',$vals);
exit($output);
}
function set_plugin_options(){
if (!current_user_can('manage_options'))
    {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }
require_once('./admin-header.php');
require_once('./admin.php');
global $wpdb;//need DB access


    if(! get_option('vagabumming_link_back') && !isset($_REQUEST['set_link'])){
	$msg = 1;
	echo '<div id="message" class="updated">' . self::message($msg) . '</div>';
	}
	if(get_option('blog_public') == 1 && !get_option('vagabumming_opt_out')){
		
		if(!isset($_REQUEST['opt_out'])){
			$msg = 2;
			echo '<div id="message" class="updated">' . self::message($msg) . '</div>';
			}
		}
	if(isset($_REQUEST['opt_out'])){
		$this_site = urlencode(get_site_url());
		$url = 'http://www.vagabumming.com/mypluginusers.php?opt_out&site=' . $this_site;
		file_get_contents($url);
		update_option('vagabumming_opt_out',1);
		}
	if(isset($_REQUEST['set_link'])){
		if($_REQUEST['set_link'] == 'yes'){
		update_option('vagabumming_link_back','linkback');
		}else{
		update_option('vagabumming_link_back','nolinkback');
		}
	}


?>
<div id="message" class="widefat" style="padding: 0.6em; margin-right: 0.6em; width: 95%;">
<p>This is the <?php echo self::$vagabumming_plugin_values['name'] . ' plugin  version ' . self::$vagabumming_plugin_values['version'] ?></p>
<h3>What it does, how it works</h3>
<p>It's pretty straightforward, really.  Simply set a logo in the options below and from that point forward, as long as this plugin is active, the logo that you set
will be sent with all emails that are processed by the wp_mail function (this includes new user registration, password reset, comment follow-ups and more).
This plugin is probably especially handy for e-commerce sites that want to strenghten their brand.
</p>  

<p>Please keep in mind, this is free software that has no guarantee that it is suitable for any purpose</p>
<hr>
<div id="icon-options-general" class="icon32"></div><br /><h2>Options:</h2><p>&nbsp;</p>
<h3>Here is where you set your logo!</h3><p>  Either upload a new image or select an image from your media library.  Don't forget to click the "Insert Into Post" button.</p><br />
<form id="branded-email-logo">
<input id="upload_logo_button" type="button" value="Select Logo" class="button-primary" />
</label>
</form>
<p> <h3>That's it!  Only one option to set, easy right?</h3></p>
<p>
<div id="logo-container">
<?php
$logo = get_option(self::$vagabumming_plugin_values['slug'] . '-logo',$logo_path);
if($logo){?>
	Your current logo is shown below</p>
	<p><img src="<?php echo $logo[0]; ?>"></p>
	<?php } else { ?>
	Please use the button above to set your logo</p>
	
	<?php }
?>
</div>
<div style="width:100%; text-align:center;"><p><a href="http://www.vagabumming.com/hire-me-for-your-website-development-needs/">WordPress expert for hire!</a></p></div>
</div>

<script type="text/javascript">
jQuery(document).ready(function() {

jQuery('#upload_logo_button').click(function() {
 tb_show('', 'media-upload.php?branded-email=true&amp;type=image&amp;TB_iframe=true');
 return false;
});

window.send_to_editor = function(html) {
 logo = jQuery('img',html).attr('src');
 	jQuery.post(ajaxurl,{action: 'set-branded-email-logo', logo: logo},function(html){
 		jQuery('#logo-container').empty().append(html);
 		});
tb_remove();
}

});
</script>
<?php
}
function add_logo(&$phpmailer){
global $alt_body;
$logo = get_option(self::$vagabumming_plugin_values['slug'] . '-logo');
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo,$logo[1]);
finfo_close($finfo);
$logo = $logo[1];
$phpmailer->ContentType = 'text/html';
$phpmailer->AddEmbeddedImage($logo,get_option('blogname').'-logo',basename($logo),'base64',$mime);
$phpmailer->AltBody = $alt_body;
}
function modify_email($data){
//create a global variable to store the original message in for later use as PHPmailer's AltBody
global $alt_body;
$msg = $data['message'];
$alt_body = $msg;
$addme = '<html><div><p><a href="' . home_url() . '"><img src="cid:' . get_option('blogname'). '-logo"></a></p>' . $msg . '</div></html>';
$data['message'] = $addme;
return $data;
}
function format_lp_message($data){
$chars = array('<','>');
$newmsg = str_replace($chars,'',$data);
return($newmsg);
}
}

$add_email_logo_plugin = new Add_email_logo;
?>