jQuery(document).ready(function() {
	
	jQuery('#pgeek-cs-title').show();
	jQuery('#pgeek-cs-title-cloned').hide();
	jQuery('#pgeek-cs-title-copied').hide();

	jQuery('#pgeek-cs-content').show();
	jQuery('#pgeek-cs-content-cloned').hide();
	jQuery('#pgeek-cs-content-copied').hide();
	
	jQuery('#pgeek-cs-copy-from').change(function(){
		if (jQuery('#pgeek-cs-copy-from').val() == "-1") {
			jQuery('#pgeek-cs-content').show();
			jQuery('#pgeek-cs-content-copied').hide();
	    	jQuery('#pgeek-cs-title').show();
	    	jQuery('#pgeek-cs-title-copied').hide();
		} else {
			jQuery('#pgeek-cs-content').hide();
			jQuery('#pgeek-cs-content-copied').show();
	    	jQuery('#pgeek-cs-title').hide();
	    	jQuery('#pgeek-cs-title-copied').show();
		}  
	});
	
	jQuery('#pgeek-cs-display-from').change(function(){
		if (jQuery('#pgeek-cs-display-from').val() == "-1") {
			jQuery('#pgeek-cs-copy-from').attr("hidden", false);
			jQuery('#pgeek-cs-copy-from-hidden').hide();
			jQuery('#pgeek-cs-content').show();
			jQuery('#pgeek-cs-content-cloned').hide();
	    	jQuery('#pgeek-cs-title').show();
	    	jQuery('#pgeek-cs-title-cloned').hide();
		} else {
			jQuery('#pgeek-cs-copy-from').attr("hidden", true);
			jQuery('#pgeek-cs-copy-from-hidden').show();
			jQuery('#pgeek-cs-copy-from').val("-1");
			jQuery('#pgeek-cs-content').hide();
			jQuery('#pgeek-cs-content-copied').hide();
			jQuery('#pgeek-cs-content-cloned').show();
	    	jQuery('#pgeek-cs-title').hide();
	    	jQuery('#pgeek-cs-title-copied').hide();
	    	jQuery('#pgeek-cs-title-cloned').show();
		}  
	});
	
	jQuery('#pgeek-cs-display-from').trigger('change');
});