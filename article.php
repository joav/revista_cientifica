<?php include('includes/header.php'); ?>

	<form action="API/create/articulo/" method="post" enctype="multipart/form-data">
		<input type="text" name="tit" placeholder="Título">
		<textarea name="res" placeholder="Resumen"></textarea>
		<input type="text" name="palabra" placeholder="Palabras clave separadas por coma">
		<button onclick="addPal()">Add</button>
		<input type="text" name="organismo" placeholder="Organismos">
		<button onclick="addOrg()">Add</button>
		<input type="text" name="lang" placeholder="Idioma">
		<textarea name="ref" placeholder="Referencias"></textarea>
		<input type="file" name="doc_aut">
		<input type="file" name="doc_aut_x">
		<input type="file" name="der">
	</form>
	<script>
		function addPal() {
			event.preventDefault();
			var pal=$('[name=palabra]').val();
			$('[name=palabra]').val('');
			var elPal=$('[name=palabra]');
			if(pal!=''){
				$('<label>'+pal+'</label><br>').insertAfter(elPal);
				$('<input type=hidden name="pal[]" value='+pal+'>').insertAfter(elPal);
			}
		}
		function addOrg() {
			event.preventDefault();
			var pal=$('[name=organismo]').val();
			$('[name=organismo]').val('');
			var elPal=$('[name=organismo]');
			if(pal!=''){
				$('<label>'+pal+'</label><br>').insertAfter(elPal);
				$('<input type=hidden name="org[]" value='+pal+'>').insertAfter(elPal);
			}
		}
	</script>
<?php include('includes/footer.php'); ?>