@php
try {
	$report->render(); 
} catch (Exception $e) {
	if ($e->getCode() === '42S21') {
		echo 'Error : Query contains duplicate columns. Please try again.<br>This page will close in 3 seconds.';
	}
	echo "
	<script>
		document.body.childNodes[1].innerHTML = '';
		setTimeout(function() { close(); }, 3000); 
	</script>";
}
@endphp