/* custom JavaScript codes can be added here.
 * This file is disabled by default, to enable it open your functions.php file and uncomment the necessary lines.
 */
jQuery(document).ready(function($) {
    $('#daypart select').change(function() {
        var selectedValue = $(this).val();
        
		console.log("dds");
    });
});