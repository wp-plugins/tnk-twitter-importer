<?php
require_once(plugin_dir_path(__FILE__)."createhtml.php");

$_tnkti_tzs = 0;

function tnkti_convert(&$r, &$a){
	global $_tnkti_tzs;
	
	$u = &$r["user"];
	$a["date"] = strtotime($r["created_at"]) + $_tnkti_tzs;
	$a["id"] = $r["id_str"];
	$a["text"] = $r["text"];
	$a["userid"] = $u["id_str"];
	$a["name"] = $u["name"];
	$a["sname"] = $u["screen_name"];
	$a["verified"] = ($u["verified"] == true);
	$a["url"] = $u["url"];
	$a["icon"] = $u["profile_image_url"];
	
	$es = &$r["entities"];
	$a["entity"] = array(); $ae = &$a["entity"]; $i = 0;
	$keys = array(
		"media" => "media_url",
		"urls" => "expanded_url",
		"user_mentions" => "id_str",
		"hashtags" => "text",
		"extended_entities" => "expanded_url"
	);
	foreach($keys as $k => $v){
		if(!isset($es[$k])) continue;
		$e = &$es[$k];
		for($j = 0, $m = count($e); $j < $m; $j++){
			$ae[$i++] = array(
				"type" => $k,
				"data" => $e[$j][$v],
				"spos" => $e[$j]["indices"][0],
				"epos" => $e[$j]["indices"][1],
			);
		}
	}
	usort($ae, function($a, $b){
		return $a["spos"] - $b["spos"];
	});
}

function tnkti_gettweet(){
	global $_tnkti_tzs, $_tnkti;

	$req = "statuses/user_timeline.json?user_id=".$_tnkti->setting["id"]
		."&include_rts=false&exclude_replies=true";
	if($_tnkti->setting["lastid"] != "") $req.="&since_id=".$_tnkti->setting["lastid"];
	$req.="&count=100";
	
	
	$res = $_tnkti->request($req);
	if($res === false) return false;
	
	$rc = count($res);
	if($rc == 0) return true;

	$a = array();
	for($i = 0; $i < $rc; $i++){
		if(isset($res[$i]["retweeted_status"])) continue;
		$c = array();
		tnkti_convert($res[$i], $c);
		array_push($a, $c);
	}

	return $a;
}

function tnkti_write($td, &$ts, $t1, $t2){
	global $_tnkti;
	
	$START_TAG = "<!-- tnkti-start -->";
	$END_TAG = "<!-- tnkti-end -->";

	$head = tnkti_beforeHtml($tweet).$START_TAG;
	$cont = "";
	$tsl = count($ts);
	for($i = $t1; $i <= $t2; $i++){
		$cont .= preg_replace_callback('/[^\x{0}-\x{FFFF}]/u', function($s) {
			return sprintf("&#x%X;", ((ord($s[0][0]) & 0x7) << 18) | ((ord($s[0][1]) & 0x3F) << 12) | ((ord($s[0][2]) & 0x3F) << 6) | (ord($s[0][3]) & 0x3F));
		}, tnkti_createHtml($ts[$i]));
	}
	$foot = $END_TAG.tnkti_afterHtml();

	$pdate = date("Y-m-d H:i:s", $ts[$t1]["date"]);
	
	// Find exists post
	$d = getdate($ts[$t1]["date"]);
	$mid = sprintf("%04d%02d%02d%02d",$d["year"],$d["mon"],$d["mday"],$td);
	$args = array(
		"meta_key" => "tnkti",
		"meta_value" => $mid,
		"ignore_sticky_posts" => true,
	);
	$wq = new WP_Query($args);
	if($wq->have_posts()){
		// update posts
		while ($wq->have_posts()){
			$p = $wq->next_post();
			
			$s = &$p->post_content;
			$p1 = strpos($s, $START_TAG);
			$p1 += strlen($START_TAG);
			$p2 = strpos($s, $END_TAG, $p1);
			
			wp_update_post(array(
				"ID" => $p->ID,
				"post_content" => $head.$cont.substr($s, $p1, $p2 - $p1).$foot,
				"post_date"=>$pdate,
			));
		}
	}else{
		// post new article
		$vc = $_tnkti->setting["collect"];
		$title = $_tnkti->setting["title"];
		echo "VC2:$vc/TD:$td<br>";
		$title = str_replace("{\$name}", $_tnkti->setting["name"], $title);
		$title = str_replace("{\$sname}", $_tnkti->setting["sname"], $title);
		$title = str_replace("{\$hour1}", $vc * $td, $title);
		$title = str_replace("{\$hour2}", $vc * ($td + 1), $title);
		$p = array(
			"post_title"=>$title,
			"post_content"=>$head.$cont.$foot,
			"post_status"=>($_tnkti->setting["draft"]) ? "draft" : "publish",
			"post_date"=>$pdate,
			"post_category"=>array($_tnkti->setting["category"]),
			"tags_input"=>"Twitter",
		);
		$pid = wp_insert_post($p);
		add_post_meta($pid, "tnkti", $mid);
	}
}

function tnkti_collect(&$ts){
	global $_tnkti;
	
	$vc = $_tnkti->setting["collect"];
	$tsl = count($ts); $lid = floatval($_tnkti->setting["lastid"]);
	$ld = -1; $t0 = 0; $t1 = $tsl - 1; $lmd = 0;
	for($i = 0; $i < $tsl; $i++){
		if($ts[$i]["reply"] == true) continue;
		if(floatval($ts[$i]["id"]) <= $lid){
			$t1 = $i - 1;
			break;
		}
		
		$d = getdate($ts[$i]["date"]);
		$td = floor($d["hours"] / $vc);
		if($ld == -1) $ld = $td;
		$md = $d["mday"];
		if($lmd != $md || $ld != $td){
			tnkti_write($ld, $ts, $t0, $i - 1);
			$t0 = $i;
			$ld = $td;
			$lmd = $md;
		}
	}
	
	if($t0 <= $t1) tnkti_write($ld, $ts, $t0, $t1);
}

function tnkti_post(){
	global $_tnkti_tzs, $_tnkti;

	$sn = $_tnkti->setting["sname"];
	if($sn == "") return;


	// initialize time
	$tz = get_option("timezone_string");
	if(!$tz){
		$_tnkti_tzs = get_option("gmt_offset") * 3600;
	}else{
		$_tnkti_tzs = new DateTime("now", new DateTimeZone(get_option("timezone_string")));
		$_tnkti_tzs = $_tnkti_tzs->getOffset();
	}

	// get recent tweets
	$ts = tnkti_gettweet();
	print_r($ts);
	if($ts === false) {
		return;
	}else if($ts !== true){
		// collect by hour
		tnkti_collect($ts);
		
		// update target state
		$_tnkti->setting["lastid"] = $ts[0]["id"];
	}

	update_option("tnkti_setting", $_tnkti->setting);
}
?>