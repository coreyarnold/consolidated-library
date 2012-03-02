<?php
/*
Plugin Name: Consolidated Library
Version: 0.1
Plugin URI: http://www.coreyarnold.com/ConsolidatedLibrary/
Description: Provides a consolidated place to store libraries of things. DVDs, Books, Video Games, Board and Card Games, CDs, etc.
Author: Corey Arnold
Author URI: http://www.coreyarnold.com/
*/

function consol_lib_printbooklist() {
   echo "<p><b>Book List</b>&nbsp;";
   echo "this is where we print the books";
   echo "</p>";
}

register_activation_hook(__FILE__,'my_first_install');

register_deactivation_hook( __FILE__, 'my_first_remove' );

function my_first_install() {
   /* Creates new database field */
   add_option("my_first_data", 'Testing !! My Plugin is Working Fine.', 'This is my first plugin panel data.', 'yes');
}

function my_first_remove() {
	delete_option( "my_first_data" );
}

/* Admin Panel */
if ( is_admin() ){
   add_action('admin_menu', 'my_first_admin_menu');

   function my_first_admin_menu() {
      add_options_page('My First', 'Consolidated Library', 'administrator', 'consol-library', 'consol_library_plugin_page');
   }
}

/* Call the Plugin Interface Page Code */
 function consol_library_plugin_page() {
 ?>
 <div>
 <h2>Consolidated Library Plugin Options Page</h2>

 <form method="post" action="options.php">
 <?php wp_nonce_field('update-options'); ?>

 <table width="510">
 <tr valign="top">
 <th width="92" scope="row">Enter Text:</th>
 <td width="406">
 <input name="my_first_data" type="text" id="my_first_data"
 value="<?php echo get_option('my_first_data'); ?>" />
 </td>
 </tr>
 </table>

 <input type="hidden" name="action" value="update" />
 <input type="hidden" name="page_options" value="my_first_data" />

 <p>
 <input type="submit" value="<?php _e('Save Changes') ?>" />
 </p>

 </form>
 </div>
 <?php
}



?>
