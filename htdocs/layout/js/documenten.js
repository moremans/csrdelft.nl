/*
 *	Documentenketzerjavascriptcode.
 */

$(document).ready(function() {

	$("input[name='methode']").change(
		function(){
			methodenaam=$("input[name='methode']:checked").val();
			id="#Methode"+methodenaam.charAt(0).toUpperCase()+methodenaam.substr(1).toLowerCase();

			$(".keuze").fadeOut(150);
			$(id).fadeIn(150);
		});
});
