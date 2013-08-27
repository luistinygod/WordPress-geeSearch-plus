/**
 * Adapted from http://www.andornot.com/blog/post/Highlight-search-terms-with-jQuery.aspx
 */

function highlightTermsIn( elements , terms, highlight_color) {
	var wrapper = ">$1<span style='background-color:" + highlight_color + "'>$2</span>$3<";
	for (var i = 0; i < terms.length; i++) {
		var regex = new RegExp(">([^<]*)?("+terms[i]+")([^>]*)?<","ig");
		elements.each(function(i) {
			jQuery(this).html( jQuery(this).html().replace(regex, wrapper) );
		}); 
	};
}