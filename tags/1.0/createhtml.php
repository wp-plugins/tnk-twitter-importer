<?php
function tnkti_gettext(&$t){
	$d = getdate($t["date"]);
	$text = ""; $media = "";
	$es = $t["entity"]; $s = 0;
	for($i = 0, $l = count($es); $i < $l; $i++){
		$e = &$es[$i];
		$type = $e["type"];
		if($type == "media"){
			$media .=
<<< DOC
<a href="{$e["data"]}" target="_blank"><img class="media-img" src="{$e["data"]}:thumb"></a>
DOC;
		}
		$sp = $e["spos"]; $ep = $e["epos"];
		$ahtml = "";
		if($type == "user_mentions" || $type == "hashtags"){
			if($type == "user_mentions"){
				$url = "https://twitter.com/intent/user?user_id=".$e["data"];
			}else{
				$url = "https://twitter.com/hashtag/".rawurlencode($e["data"])."?src=hash";
			}
			$ahtml = '<a href="'.$url.'" target="_blank" class="link">'.mb_substr($t["text"], $sp, $ep - $sp).'</a>';
		}else if($type != "media"){
			$ahtml = '<a href="'.$e["data"].'" target="_blank" class="icon link">&#61829;</a>';
		}
		$text .= mb_substr($t["text"], $s, $sp - $s) . $ahtml;
		$s = $ep;
	}
	$text .= mb_substr($t["text"], $s);
	
	return array(
		"verify" => ($t["verified"]) ? '<span class="icon verified">&#61593;</span>' : '',
		"time" => sprintf("%d:%02d", $d["hours"], $d["minutes"]),
		"tweet" => $text,
		"media" => $media,
		"reply" => "https://twitter.com/intent/tweet?in_reply_to=" . $t["id"],
		"retweet" => "https://twitter.com/intent/retweet?tweet_id=" . $t["id"],
		"favorite" => "https://twitter.com/intent/favorite?tweet_id=" . $t["id"]
	);
}

function tnkti_beforeHtml(&$tweet){
	$html =
<<< DOC
<div class="tnkti">
DOC;
	return $html;
}

function tnkti_afterHtml(){
	$html =
<<< DOC
</div>
DOC;
	return $html;
}

function tnkti_createHtml($t){
	$data = tnkti_gettext($t);

	$html =
<<< DOC
<div class="out" data-tweetid="{$t["id"]}">
  <div class="in">
    <div class="left">
      <img class="user" src="{$t["icon"]}">
    </div>
    <div class="right">
      <div class="state">
        <div class="name"><a href="https://twitter.com/intent/user?user_id={$t["userid"]}" target="_blank"><span class="iname">{$t["name"]}</span>{$data["verify"]}&nbsp;<span class="sname">@{$t["sname"]}</span></a></div> &#183; <div class="date">{$data["time"]}</div>
      </div>
      <div class="tweet">{$data["tweet"]}</div>
      <div class="media">{$data["media"]}</div>
      <div class="tool">
        <a class="icon reply" href="{$data["reply"]}" target="_blank">&#61777;</a>
        <a class="icon retweet" href="{$data["retweet"]}" target="_blank">&#61778;</a>
        <a class="icon favorite" href="{$data["favorite"]}" target="_blank">&#61767;</a>
      </div>
    </div>
  </div>
</div>
DOC;

	return $html;
}
?>