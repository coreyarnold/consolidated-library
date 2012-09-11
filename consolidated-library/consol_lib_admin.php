<?php 
	if($_POST['consol_lib_hidden'] == 'Y') {
		//Form data sent
		$dbhost = $_POST['consol_lib_steam_name'];
		update_option('consol_lib_steam_name', $dbhost);
		
		$dbname = $_POST['consol_lib_steam_api_key'];
		update_option('consol_lib_steam_api_key', $dbname);
		?>
		<div class="updated"><p><strong><?php _e('Options saved.' ); ?></strong></p></div>
		<?php
	} else {
		//Normal page display
		$dbhost = get_option('consol_lib_steam_name');
		$dbname = get_option('consol_lib_steam_api_key');
	}
	
?>

<div class="wrap">
<?php    echo "<h2>" . __( 'Consolidated Library Settings', 'consol_lib_trdom' ) . "</h2>"; ?>

<form name="consol_lib_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
	<input type="hidden" name="consol_lib_hidden" value="Y">
	<p><?php _e("Steam Name: " ); ?><input type="text" name="consol_lib_steam_name" value="<?php echo $dbhost; ?>" size="20"></p>
	<p><?php _e("API Key: " ); ?><input type="text" name="consol_lib_steam_api_key" value="<?php echo $dbname; ?>" size="20"></p>

	<p class="submit">
	<input type="submit" name="Submit" value="<?php _e('Update Options', 'consol_lib_trdom' ) ?>" />
	</p>
</form>
</div>