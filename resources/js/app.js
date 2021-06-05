require('./bootstrap');
$(document).ready(function() {
	$.fn.select2.defaults.set( "theme", "bootstrap4" );
	$('.select2').select2();
})