(function( $ ) {

$(document).ready(function() {
	/**
	 * Adapted from http://www.andornot.com/blog/post/Highlight-search-terms-with-jQuery.aspx
	 */
	
	var highlightTermsIn = function( elements , terms ) {
		var wrapper = '>$1<span class="gee-search-highlight">$2</span>$3<';
		for (var i = 0; i < terms.length; i++) {
			var regex = new RegExp(">([^<]*)?("+terms[i]+")([^>]*)?<","ig");
			elements.each(function(i) {
				jQuery(this).html( jQuery(this).html().replace(regex, wrapper) );
			}); 
		};
	}

	if( $( highlight_args.area ).length == 0 ) {
		highlight_args.area = "#content";
	}
	
	highlightTermsIn( $( highlight_args.area ) , highlight_args.search_terms );
	

}); // document ready end

}(jQuery));