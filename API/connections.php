<?php header('Content-Type: application/json');
function formation_utf8_encode($dat)
{
    if (is_string($dat))
        return utf8_encode($dat);
    if (!is_array($dat))
        return $dat;
    $ret = array();
    foreach ($dat as $i => $d)
        $ret[$i] = formation_utf8_encode($d);
    return $ret;
}
function randomPassword() {
    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $pass = array();
    $alphaLength = strlen($alphabet) - 1;
    for ($i = 0; $i < 8; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass);
}
extract($_GET);
$host="localhost";
$dbname="revista_cientifica";
$user="administrador_re";
$passDB='^1W34BW6i[%n';
$sCon="mysql:host=$host;dbname=$dbname;";
$resp=new stdClass();
$resp->message='';
$resp->id=[];
$resp->results=[];
try {
	$db=new PDO($sCon,$user,$passDB,array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
	switch ($type) {
		case 'usuario':
			switch ($action) {
				case 'create':
					$input=json_decode(file_get_contents('php://input'));
					if(isset($input->users)){
						$users=$input->users;
						$query="INSERT INTO usuario VALUES(null,?,?,MD5(?),?,?,?,?,?,?,?,?,?,?)";
						$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_SILENT);
						$db->exec("set names utf8");
						$st=$db->prepare($query);
						for ($i=0; $i < count($users); $i++) { 
							$user=$users[$i];
							$values=[];
							$complete=true;
							$values[]=isset($user->al)?$user->al:'';
							if(!isset($user->email)){
								$resp->message.="La variable email es obligatoria.\n";
								$complete=false;
							}else{
								$values[]=$user->email;
							}
							if($user->pass==''){
								$values[]=randomPassword();
							}else{
								if(!isset($user->pass)){
									$resp->message.="La variable pass es obligatoria si no desea generar un password aleatorio.\n";
									$complete=false;
								}else{
									$values[]=$user->pass;
								}
							}
							$values[]=isset($user->tel)?$user->tel:'';
							if(!isset($user->nom)){
								$resp->message.="La variable nom es obligatoria.\n";
								$complete=false;
							}else{
								$values[]=$user->nom;
							}
							if(!isset($user->ap)){
								$resp->message.="La variable ap es obligatoria.\n";
								$complete=false;
							}else{
								$values[]=$user->ap;
							}
							$values[]=isset($user->gen)?$user->gen:'';
							$values[]=isset($user->pais)?$user->pais:'';
							$values[]=isset($user->bio)?$user->bio:'';
							$values[]=isset($user->lang)?$user->lang:'';
							$values[]=isset($user->inst)?$user->inst:'';
							$values[]=isset($user->int)?$user->int:'';
							if(!isset($user->tipo)){
								$resp->message.="La variable tipo es obligatoria.\n";
								$complete=false;
							}else{
								$values[]=$user->tipo;
							}
							if($st->execute($values)===false){
								$error=$st->errorInfo();
								$resp->message.="No se pudo insertar el usuario $i. SQLSTATE[".$error[0]."]: ".$error[2]."\n";
							}else{
								$resp->id[]=$db->lastInsertId();
							}
						}
						if($resp->message==''){
							$resp->message=true;
						}
					}else{
						$resp->message='No hay usuarios para insertar.';
					}
					break;
				case 'get':
					$fields=$fields!=''?$fields:'*';
					$usert=$usert!=''?"AND tipo_us IN($usert)":'';
					if($c!=''){
						$offset=$c*($p-1);
						$c="LIMIT $offset,$c";
					}
					$query='';
					$count='';
					if(is_numeric($req)){
						$req=[$req,$req];
						$query="SELECT $fields FROM usuario WHERE id_us IN (?) $usert $c";
						$count="(SELECT COUNT(*) FROM usuario WHERE id_us IN (?) $usert)";
					}elseif ($req=='all') {
						$req=[1,1];
						$query="SELECT $fields FROM usuario WHERE ?  $usert $c";
						$count="(SELECT COUNT(*) FROM usuario WHERE ?  $usert)";
					}else{
						$query="SELECT $fields FROM usuario WHERE id_us IN ($req) AND ? $usert $c";
						$count="(SELECT COUNT(*) FROM usuario WHERE id_us IN ($req) AND ? $usert)";
						$req=[1,1];
					}
					$query=str_replace(" FROM", ", $count as total FROM", $query);
					$st=$db->prepare($query);
					if($st->execute($req)!==false){
						$results=$st->fetchAll(PDO::FETCH_ASSOC);
						$resp->results=formation_utf8_encode($results);
					}else{
						$resp->message='Fallo en la consulta';
					}
					break;
				case 'search':
					$fields=$fields!=''?$fields:'*';
					$usert=$usert!=''?"AND tipo_us IN($usert)":'';
					if($c!=''){
						$offset=$c*($p-1);
						$c="LIMIT $offset,$c";
					}
					$sfields=explode(',', $sfields);
					$vfields=explode(',', $vfields);
					if(count($sfields)==count($vfields)){
						$where=[];
						$values=[];
						for ($i=0; $i < count($sfields); $i++) {
							$where[]=$sfields[$i].' LIKE CONCAT(\'%\',?,\'%\')';
						}
						$where=implode(' AND ', $where);
						$count="(SELECT COUNT(*) FROM usuario WHERE $where $usert)";
						$query="SELECT $fields, $count as total FROM usuario WHERE $where $usert $c";
						$values=array_merge($vfields,$vfields);
						$st=$db->prepare($query);
						if($st->execute($values)!==false){
							$results=$st->fetchAll(PDO::FETCH_ASSOC);
							$resp->results=formation_utf8_encode($results);
						}else{
							$resp->message='Fallo en la consulta';
						}
					}
					else{
						$resp->message='La cantidad de campos debe ser igual a la cantidad de valores a buscar.';
					}
					break;
				case 'update':
					$input=json_decode(file_get_contents('php://input'));
					$db->exec("set names utf8");
					$id=$input->id;
					unset($input->id);
					$cols=[];
					$vals=[];
					foreach ($input as $key => $value) {
						$cols[]=$key;
						if($key=='pass'){
							if($value==''){
								$vals[]=md5(randomPassword());
							}else{
								$vals[]=md5($value);
							}
							continue;
						}
						$vals[]=$value;
					}
					$vals[]=$id;
					$cols=implode("_us=?, ", $cols).'_us=?';
					$query="UPDATE usuario SET $cols WHERE id_us=?";
					$st=$db->prepare($query);
					if($st->execute($vals)){
						$resp->message=true;
						$resp->id[]=$id;
					}else{
						$resp->message='Fallo la edición del usuario '.$id;
					}
					break;
				default:
					# code...
					break;
			}
			break;
		case 'articulo':
			switch ($action) {
				case 'create':
					
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
	$resp->message=$e->getMessage();
}
echo json_encode($resp); ?>