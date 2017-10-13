<?php header('Content-Type: application/json');
extract($_GET);
$host="localhost";
$dbname="revista_cientifica";
$user="administrador_re";
$passDB='^1W34BW6i[%n';
$sCon="mysql:host=$host;dbname=$dbname;";
$resp=array('message'=>null);
try {
	$db=new PDO($sCon,$user,$passDB,array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
	switch ($type) {
		case 'usuario':
			switch ($action) {
				case 'create':
					$query="INSERT INTO usuario VALUES(null,'$al','$email','$pass','$tel','$nom','$ap','$gen','$pais','$bio','$lang','$inst','$int','$tipo')";
					if($db->query($query)===false){
						$resp['message']='Salio algo mal en la inserción';
					}else{
						$resp['message']=true;
						$resp['id']=$db->lastInsertId();
					}
					break;
				case 'get':
					if(is_numeric($req)){
						$query="SELECT * FROM usuario WHERE id_us IN ($req)";
					}elseif ($req=='all') {
						$query="SELECT * FROM usuario";
					}else{
						$query="SELECT * FROM usuario WHERE ";
					}
					$st=$db->prepare($query);
					if($st->execute()){
						$resp['results']=$st->fetchAll();
					}else{
						$resp['message']='Fallo en la consulta';
					}
					break;
				default:
					# code...
					break;
			}
			break;
		
		default:
			# code...
			break;
	}
} catch (PDOException $e) {
	$resp['message']=$e->getMessage();
}
echo json_encode($resp); ?>