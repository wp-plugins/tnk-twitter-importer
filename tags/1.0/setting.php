<?php
function tnkti_setting(){
	global $_tnkti;?>
<div class="wrap">
<h2>TNK Twitter importer</h2>
	<?php
	if($_tnkti->setting["allow"] != 1){
		?>
		This plugin accesses following sites or shows external links. Please click this button when you accept these.<br>
		<ul>
		<li>twitter.com
		<li>tnksoft.com(developer's web site)
		</ul>
		<form method="post" action=""><input type="hidden" name="plugin" value="tnkti"></input>
<input type="submit" name="allow" value="Accept those sites"></input></form>
		<?php
	}else{
		if(isset($_POST["reset"])) echo "<span style='color:blue;'>Schedule has reset.</span>";
		if($_tnkti->lasterr != "") echo "<span style='color:red;'>".$_tnkti->lasterr."</span>";
		?>
		<form method="post" action="" id="tnkti_form">
		<input type="hidden" name="plugin" value="tnkti"></input>
		<input type="hidden" name="setting" value="1"></input>
		<table class="form-table">
		<tr valign="top">
		<th scope="row">Twitter app</th>
		<td>
			<table>
			<tr><td>Key</td><td>:</td><td><input type="text" name="twitter_key" value="<?php echo $_tnkti->setting["twitter_key"]; ?>"></input></td></tr>
			<tr><td>Secret</td><td>:</td><td><input type="text" name="twitter_secret" value="<?php echo $_tnkti->setting["twitter_secret"]; ?>"></input></td></tr>
			</table>
			These are acquirable from <a href="https://apps.twitter.com/" target="_blank">Twitter apps</a> with your developer account. If you don't have an app, please create an any app.</td>
		</tr>
		<tr valign="top">
		<th scope="row">Post as draft</th>
		<td><input type="checkbox" name="draft" value="1"<?php if($_tnkti->setting["draft"] == 1) echo " checked"; ?>>
		</tr>
		<tr valign="top">
		<th scope="row">Post category</th>
		<td><?php wp_dropdown_categories(array("hierarchical"=>true, "echo"=>1, "selected"=>$_tnkti->setting["category"])); ?></td>
		</tr>

		<tr valign="top">
		<th scope="row">Twitter ID(number)</th>
		<td><input type="text" name="id" value="<?php echo htmlspecialchars($_tnkti->setting["id"]); ?>"></input><?php if(isset($_tnkti->setting["sname"])) echo "<br>(@".htmlspecialchars($_tnkti->setting["sname"]).")"; ?></td>
		</tr>
		<tr valign="top">
		<th scope="row">Title</th>
		<td><input type="text" name="title" value="<?php echo htmlspecialchars($_tnkti->setting["title"]); ?>"></input></td>
		</tr>
		<tr valign="top">
		<th scope="row">Collection</th>
		<td>Every <select name="collect"><?php $vs = array(24,12,8,6,4,3,2,1);
				for($i = 0, $l = count($vs); $i < $l; $i++){
					echo "<option value=\"$vs[$i]\"";
					if($_tnkti->setting["collect"] == $vs[$i]) echo " selected";
					echo ">$vs[$i]</option>";
				}
			?></select> hours</td>
		</tr>
		</table>

		<?php
		submit_button();
		?>
		<form method="post" action=""><input type="hidden" name="plugin" value="tnkti"></input>
<input type="submit" name="reset" value="Reset schedule" onclick="return confirm('Reset the schedule. Are you sure?');"></input></form>
		<?php
	}
}
?>