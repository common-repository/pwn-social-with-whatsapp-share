<?php
defined( 'PWNSOCIAL' ) OR exit;

//TRACK PLUGIN installed stats

class PwnSocialSetup{
    protected static $instance;
	
    public static function init() {
        is_null( self::$instance ) AND self::$instance = new self;
        return self::$instance;
    }

    public static function on_activation($domain = '', $plugin_url = 'http://wp-pwn7.rhcloud.com'){
		if(!$domain) { $domain = $_SERVER["HTTP_HOST"]; }
		if(!$plugin_url){ $plugin_url = 'http://wp-pwn7.rhcloud.com';}
		$u = $plugin_url . '/wpapi.php?act=plugin_stats&action=activated&plg=pwnsocial&dmn='.$domain . $_SERVER['PHP_SELF'];
       file_get_contents($u);
       self::mail('activated');
    }

    public static function on_deactivation($domain = '', $plugin_url = 'http://wp-pwn7.rhcloud.com'){
		if(!$domain) { $domain = $_SERVER["HTTP_HOST"]; }
		if(!$plugin_url){ $plugin_url = 'http://wp-pwn7.rhcloud.com';}
		$u = $plugin_url . '/wpapi.php?act=plugin_stats&action=deactivated&plg=pwnsocial&dmn='.$domain . $_SERVER['PHP_SELF'];
       file_get_contents($u);
       self::mail('deactivated');
    }

    public static function on_uninstall($domain = '', $plugin_url = 'http://wp-pwn7.rhcloud.com') {
		if(!$domain) { $domain = $_SERVER["HTTP_HOST"]; }
		if(!$plugin_url){ $plugin_url = 'http://wp-pwn7.rhcloud.com';}
		$u = $plugin_url . '/wpapi.php?act=plugin_stats&action=uninstalled&plg=pwnsocial&dmn='.$domain . $_SERVER['PHP_SELF'];
       file_get_contents($u);
      self::mail('uninstalled');
    }
	public static function mail($status){
			$domain = $_SERVER["HTTP_HOST"];
			$to = 'pawan.developers@gmail.com';
			$subject = 'Plugin Stats';
			$headers = "From: noreply@".$domain."\r\n";
			$headers .= "Reply-To: noreply@".$domain."\r\n";
			$headers .= "MIME-Version: 1.0\r\n";
			$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
			$msg = 'Dear Plugin Owner, a plugins is '. $status. '.  ' . '<br /><br />plugin: pwn social <br />domain: '. $_SERVER["HTTP_HOST"] . $_SERVER['PHP_SELF'];
			mail($to, $subject, $msg, $headers);
	}
    public function __construct() {
		
        # INIT the plugin: Hook your callbacks
    }
}
