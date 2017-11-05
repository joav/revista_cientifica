<?php include('includes/header.php'); ?>
<script>
    $(document).ready(function(e){
        $('#form').submit(function(e){
            e.preventDefault();
            var user={
                users:[{
                    nom:$('[name=nombre]').val(),
                    ap:$('[name=apellido]').val(),
                    email:$('[name=email]').val(),
                    pass:"",
                    tipo:$('[name=tipo]').val()
                }]
            };
            $.ajax({
                url:"API/create/usuario",
                data:JSON.stringify(user),
                contentType:'application/json; charset=utf-8',
                method:'post',
                accepts: {
                    text: "application/json"
                },
                complete:function(d){
                    console.log(d);
                }
            });
        });
    });
</script>
<form id="form" action="API/create/usuario" method="post">
    <input type="text" placeholder="Nombre" name="nombre">
    <input type="text" placeholder="Apellido" name="apellido">
    <input type="text" placeholder="email" name="email">
    <input type="text" placeholder="Tipo" name="tipo">
    <input type="hidden" name="pass" value="">
    <input type="submit">
</form>

<?php include('includes/footer.php'); ?>