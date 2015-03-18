<?php
/*
Plugin Name: TNK Twitter importer
Plugin URI: http://www.tnksoft.com/soft/internet/tweetimport/
Description: Post your tweets to your wordpress blog.
Version: 1.0
Author: TNK Software(Tanaka Yusuke)
Author URI: http://www.tnksoft.com/
License: GPLv2

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
*/

define("TNKTI_LOCAL_CACHE", false);	// (for debug)get json from a local file.
define("TNKTI_UPDATE", 60);	// update interval(min)

class tnkti{
	private $setting_default = array(
		"allow"=>0,
		
		"twitter_key"=>"",
		"twitter_secret"=>"",

		"draft"=>0,
		"category"=>0,
		
		"id"=>"",
		"title"=>"Today {\$name}'s tweets({\$hour1}:00-{\$hour2}:00)",
		"collect"=>24,
		
		// Internal use.
		"twitter_token"=>"",
		"schedule_index"=>0,
		"name"=>"",
		"sname"=>"",
		"lastid"=>"",
	);
	public $setting;
	public $lasterr = "";
	
	public function updateToken(){
		// authenticaion
		$bare = base64_encode(urlencode($this->setting["twitter_key"]).':'.urlencode($this->setting["twitter_secret"]));
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://api.twitter.com/oauth2/token/");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			"POST /oauth2/token HTTP/1.1",
			"Host: api.twitter.com",
			"User-Agent: TNK Twitter importer",
			"Authorization: Basic ".$bare,
			"Content-Type: application/x-www-form-urlencoded;charset=UTF-8",
			"Content-Length: 29"
		));
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$res = curl_exec($ch);
		$this->lasterr = ($res === false) ? curl_error($ch) : "";
		curl_close($ch);
		if ($res !== false) {
			$res = json_decode($res, true);
			if(isset($res["errors"]) == false){
				$this->setting["twitter_token"] = $res["access_token"];
				return true;
			}else{
				$this->lasterr = $res["errors"][0]["message"];
			}
		}
		return false;
	}
	
	public function createCurl($url){
		$this->lasterr = "";
		
		$surl = "/1.1/".$url;
		$headers = array(
			"GET $surl HTTP/1.1",
			"Host: api.twitter.com",
			"User-Agent: TNK Twitter importer",
			"Authorization: Bearer ".$this->setting["twitter_token"]
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://api.twitter.com".$surl);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		return $ch;
	}
	
	public function request($url, $retry = 0){
		if($retry >= 3) return false;
		
		if(TNKTI_LOCAL_CACHE == true){
			$path = plugin_dir_path(__FILE__)."/cache/".md5($url).".json";
			if(file_exists($path)
			&& (time() - filectime($path)) / 60 < TNKTI_UPDATE
			&& ($res = @file_get_contents($path)) !== false){
				return json_decode($res, true);
			}
		}
		
		$ch = $this->createCurl($url);
		$res = curl_exec($ch);
		if ($res === false) $this->lasterr = curl_error($ch);
		curl_close($ch);
		
		if($res === false) {
			$resj = false;
		}else{
			$resj = json_decode($res, true);
		}
		if($resj === false || isset($resj["errors"])){
			// reauthenticaion
			if($this->updateToken() == true){
				return $this->request($url, ++$retry);
			}else{
				return false;
			}
		}
		
		$this->writeCache(md5($url), $res);
		
		return $resj;
	}
	
	public function uninstall() {
		delete_option("tnkti_setting");
	}
	
	public function cron_tnkti($sc){
		$sc["tnkti"] = array(
			"interval" => TNKTI_UPDATE * 60
		);
		return $sc;
	}
	
	public function writeCache($name, &$data){
		if(TNKTI_LOCAL_CACHE == false) return;

		$d = plugin_dir_path(__FILE__)."/cache/";
		@mkdir($d);
		@file_put_contents($d.$name.".json", preg_replace_callback('|\\\\u([0-9a-f]{4})|i',
			function ($matches) {
				return mb_convert_encoding(pack('H*', $matches[1]), 'UTF-8', 'UTF-16');
			}, $data));
	}
	
	public function __construct(){
		register_uninstall_hook(__FILE__, array("tnkti", "uninstall"));
		
		// load setting
		$s = get_option("tnkti_setting");
		if($s === false){
			$s = $this->setting_default;
		}else{
			$s = $s + $this->setting_default;
		}
		
		if (isset($_POST["plugin"]) && $_POST["plugin"] == "tnkti") {
			if(isset($_POST["allow"])){
				$s["allow"] = 1;
			}else if(isset($_POST["reset"])){
				$s["lastid"] = "";
			}else if(isset($_POST["submit"])){
				$s["allow"] = 1;
				
				$s["twitter_key"] = sanitize_text_field($_POST["twitter_key"]);
				$s["twitter_secret"] = sanitize_text_field($_POST["twitter_secret"]);
				
				$s["draft"] = (isset($_POST["draft"]) && $_POST["draft"] == "1") ? 1 : 0;
				$s["category"] = (int)$_POST["cat"];
				
				$s["id"] = sanitize_text_field($_POST["id"]);
				$s["title"] = sanitize_text_field($_POST["title"]);
				$s["collect"] = (int)$_POST["collect"];
				$s["name"] = $s["sname"] = "";
				if($s["id"] == "" || ctype_digit($s["id"]) == false){
					$this->lasterr = "ID({$s["id"]}) is not a number.";
				}else{
					// check user id
					$this->setting = $s;
					$res = $this->request("users/lookup.json?user_id=".$s["id"]);
					if($res !== false && !isset($res["errors"])){
						$s["name"] = $res[0]["name"];
						$s["sname"] = $res[0]["screen_name"];
						$s["twitter_token"] = $this->setting["twitter_token"];
					}else{
						$this->lasterr = "ID({$s["id"]}) is not found in Twitter.";
					}
				}
			}
			update_option("tnkti_setting", $s);
		}
		$this->setting = $s;
		
		// extend stylesheet
		add_action("wp_enqueue_scripts", function(){
			wp_enqueue_style("tnkti", plugins_url("tnkti.css", __FILE__));
		});

		// append setting menu
		add_action("admin_menu", function(){
			require_once(plugin_dir_path(__FILE__)."setting.php");
			wp_enqueue_style("tnkti_setting", plugins_url("setting.css", __FILE__));
			add_plugins_page("TNK Twitter importer", "TNK TI", "administrator", __FILE__, "tnkti_setting");
		});

		add_filter("plugin_action_links_" . plugin_basename(__FILE__), function($links){
			$mylinks = array(
			'<a href="' . admin_url('plugins.php?page=tnkti/tnkti.php') . '">Settings</a>',
			);
			return array_merge( $links, $mylinks );
		});
		
		// register schedule
		add_action("tnkti_schedule", "tnkti_schedule_exec");
		add_filter("cron_schedules", array(&$this, "cron_tnkti"));
		if (!wp_next_scheduled("tnkti_schedule")) {
			wp_schedule_event(time(), "tnkti", "tnkti_schedule");
		}
	}
}

$_tnkti = new tnkti();

function tnkti_schedule_exec(){
	require_once(plugin_dir_path(__FILE__)."post.php");
	tnkti_post();
}
?>