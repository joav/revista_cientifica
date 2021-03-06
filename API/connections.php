<?php header('Content-Type: application/json');
function getRealIP() {
	if (!empty($_SERVER['HTTP_CLIENT_IP']))
		return $_SERVER['HTTP_CLIENT_IP'];
	if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
		return $_SERVER['HTTP_X_FORWARDED_FOR'];
	return $_SERVER['REMOTE_ADDR'];
}
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
session_id(md5(getRealIP()));
session_start();
extract($_GET);
$host="localhost";
$dbname="revista_cientifica";
$user="administrador_re";
$passDB='^1W34BW6i[%n';
$sCon="mysql:host=$host;dbname=$dbname;";
$resp=new stdClass();
$resp->message='';
$resp->results=[];
try {
	$db=new PDO($sCon,$user,$passDB,array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
	switch ($type) {
		case 'check':
			print_r($_SESSION);
		break;
		case 'usuario':
			switch ($action) {
				case 'create':
					$input=json_decode(file_get_contents('php://input'));
					if(isset($input->users)){
						$resp->newPass=[];
						$resp->id=[];
						$users=$input->users;
						$query="INSERT INTO usuario VALUES(null,?,?,MD5(?),?,?,?,?,?,?,?,?,?,?)";
						$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_SILENT);
						$db->exec("set names utf8");
						$st=$db->prepare($query);
						$select=$db->prepare("SELECT id_us FROM usuario WHERE email_us=?");
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
								$newPass=$values[]=randomPassword();
							}else{
								if(!isset($user->pass)){
									$resp->message.="La variable pass es obligatoria si no desea generar un password aleatorio.\n";
									$complete=false;
								}else{
									$newPass=$values[]=$user->pass;
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
							if($complete){
								if($st->execute($values)===false){
									$error=$st->errorInfo();
									if (strpos($error[2], 'Duplicate entry')!==false) {
										if($select->execute([$user->email])){
											$id_us=$select->fetch(PDO::FETCH_ASSOC)['id_us'];
											$resp->id[]=$id_us;
										}else{
											$error=$st->errorInfo();
											$resp->message.="No se pudo insertar el usuario $i. SQLSTATE[".$error[0]."]: ".$error[2]."\n";
										}
									}
								}else{
									$resp->id[]=$db->lastInsertId();
									$resp->newPass[]=$newPass;
								}
							}else{
								$resp->message.="No se pudo insertar el usuario $i.";
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
				case 'login':
					$input=json_decode(file_get_contents('php://input'));
					if (!$input->type) {
						$resp->message="Usuario ".$_SESSION['user']['nom_us']." desconectado";
						@session_destroy();
					}else{
						$query="SELECT * FROM usuario WHERE (email_us=? OR al_us=?) && pass_us=MD5(?)";
						$st=$db->prepare($query);
						if ($st->execute(array($input->user,$input->user,$input->pass))) {
							if (!$st->columnCount()) {
								$resp->message="Correo o contraseña incorrectos.";
							}else{
								$results=$st->fetch(PDO::FETCH_ASSOC);
								if($results){
									$resp->message=true;
									$resp->results=formation_utf8_encode($results);
									$_SESSION['user']=$resp->results;
								}else{
									$resp->message="Correo o contraseña incorrectos.";									
								}
							}
						} else {
							$resp->message="Ocurrio un error al buscar el usuario";
						}
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
					if(isset($_SESSION['user'])){
						extract($_POST);
						$query="INSERT INTO articulo VALUES(null,?,?,?,?,?,?,?,?,?,?,?,?)";
						$dirDest='../uploads/';
						$code=uniqid();
						$doc_aut='aut_'.$code.'.'.end(explode('.',$_FILES['doc_aut']['name']));
						$doc_aut_x=uniqid('aut_x_').'.'.end(explode('.',$_FILES['doc_aut_x']['name']));
						$der=uniqid('der_').'.'.end(explode('.',$_FILES['der']['name']));
						$fallo=false;
						if(!move_uploaded_file($_FILES['doc_aut']['tmp_name'], $dirDest.$doc_aut)){
							$resp->message.="Fallo subiendo el articulo con autores\n";
							$fallo=true;
						}
						if(!move_uploaded_file($_FILES['doc_aut_x']['tmp_name'], $dirDest.$doc_aut_x)){
							$resp->message.="Fallo subiendo el articulo sin autores\n";
							$fallo=true;
						}
						if(!move_uploaded_file($_FILES['der']['tmp_name'], $dirDest.$der)){
							$resp->message.="Fallo subiendo la sesion de darechos\n";
							$fallo=true;
						}
						if(!$fallo){
							$db->exec("set names utf8");
							$values=[];
							$values[]=$_SESSION['user']['id_us'];
							$values[]=date('ymd').'_'.$code;
							$values[]=$tit;
							$values[]=$res;
							$values[]=$pal;
							$values[]=$lang;
							$values[]=$org;
							$values[]=$ref;
							$values[]=date('Y-m-d H:i:s');
							$values[]=$doc_aut;
							$values[]=$doc_aut_x;
							$values[]=$der;
							$st=$db->prepare($query);
							if($st->execute($values)){
								$resp->message=true;
								$resp->id=$db->lastInsertId();
								$resp->aut=$_SESSION['user']['id_us'];
								$resp->code=$values[1];
							}
						}else{
							$resp->message="Fallo creando el artículo";
						}
					}
					else{
						$resp->message='No esta logueado';
					}
					break;
				case 'associate':
					$query="INSERT INTO autxart VALUES(?,?)";
					$st=$db->prepare($query);
					$input=json_decode(file_get_contents('php://input'));
					$users=$input->users;
					$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_SILENT);
					$currError=false;
					for ($i=0; $i < count($users); $i++) {
						if(!$st->execute([$input->art,$users[$i]])){
							$currError=true;
							$error=$st->errorInfo();
							$resp->message.="No se pudo asociar el usuario ".$users[$i]." de la posición $i con el artículo ".$input->art.". SQLSTATE[".$error[0]."]: ".$error[2]."\n";
						}
					}
					if(!$currError){
						$resp->message=true;
					}
					break;
				case 'assign':
					$user=$_SESSION['user'];
					if($user['tipo_us']==0){
						$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_SILENT);
						$query="INSERT INTO asigna VALUES(null,?,?,?,?,NULL,0)";
						$st=$db->prepare($query);
						$input=json_decode(file_get_contents('php://input'));
						$date=date('Y-m-d H:i:s');
						$complete=true;
						for ($i=0; $i < count($input->rev); $i++) { 
							$values=[];
							$values[]=$_SESSION['user']['id_us'];
							$values[]=$input->rev[$i];
							$values[]=$input->art;
							$values[]=$date;
							if(!$st->execute($values)){
								$complete=false;
								$resp->message.="Fallo en la asignación al revisor con id=".$input->rev[$i];
							}
						}
						if($complete)
							$resp->message=$complete;
					}else{
						$resp->message='El usuario no tiene los suficientes permisos';
					}
					break;
				case 'get':
					$fields=$fields!=''?$fields:'*';
					if($ids=='all'){
						if($c!=''){
							$offset=$c*($p-1);
							$c="LIMIT $offset,$c";
						}
						if ($assign==1) {
							if($_SESSION['user']['tipo_us']==0){
								$id=$_SESSION['user']['id_us'];
								$query="SELECT $fields FROM list_art WHERE editor=? $c";
								$count="(SELECT count(*) FROM list_art WHERE editor=?)";
								$values=[$id,$id];
							}elseif($_SESSION['user']['tipo_us']==1){
								$id=$_SESSION['user']['id_us'];
								$query="SELECT $fields FROM list_art WHERE revisor=? AND estado!=2 $c";
								$count="(SELECT count(*) FROM list_art WHERE revisor=?)";
								$values=[$id,$id];
							}else{
								$resp->message='No tienes los permisos suficientes';
							}
						}else{
							if($_SESSION['user']['tipo_us']==0){
								$query="SELECT $fields FROM list_art $c";
								$count="(SELECT count(*) FROM list_art)";
								$values=[];
							}elseif($_SESSION['user']['tipo_us']==2){
								$id=$_SESSION['user']['id_us'];
								$query="SELECT $fields FROM list_art WHERE autor=? $c";
								$count="(SELECT count(*) FROM list_art WHERE autor=?)";
								$values=[$id,$id];
							}else{
								$resp->message='No tienes los permisos suficientes';
							}
						}
					}else{
						$query="SELECT $fields FROM articulo WHERE id_art IN (?) $c";
						$count="(SELECT count(*) FROM articulo WHERE id_art IN (?))";
						$values=[$ids,$ids];
					}
					$query=str_replace(" FROM", ", $count as total FROM", $query);
					$st=$db->prepare($query);
					if($st->execute($values)!==false){
						$results=$st->fetchAll(PDO::FETCH_ASSOC);
						$resp->results=formation_utf8_encode($results);
						$resp->message=true;
					}else{
						$resp->message='Fallo en la consulta';
					}
					break;
				case 'search':
					$fields=$fields!=''?$fields:'*';
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
						$count="(SELECT COUNT(*) FROM list_art WHERE $where)";
						$query="SELECT $fields, $count as total FROM usuario WHERE $where $c";
						$values=array_merge($vfields,$vfields);
						$st=$db->prepare($query);
						if($st->execute($values)!==false){
							$results=$st->fetchAll(PDO::FETCH_ASSOC);
							$resp->results=formation_utf8_encode($results);
							$resp->message=true;
						}else{
							$resp->message='Fallo en la consulta';
						}
					}
					else{
						$resp->message='La cantidad de campos debe ser igual a la cantidad de valores a buscar.';
					}
					break;
				case 'accept':
					$user=$_SESSION['user'];
					if($user['tipo_us']==1){
						$date=date('Y-m-d H:i:s');
						$query="UPDATE asigna set dateac_as=?, state_as=? where rev_as=? and art_as=?";
						$st=$db->prepare($query);
						$input=json_decode(file_get_contents('php://input'));
						$values=[$date,$input->accept,$user['id_us'],$input->art];
						if($st->execute($values)){
							$resp->message=true;
						}else{
							$resp->message='Ha ocurrido un error inesperado aceptando o rechazando el artículo.';
						}
					}else{
						$resp->message='No tiene los permisos necesarios';
					}
					break;
				default:
					# code...
					break;
			}
			break;
		case 'autores':
			$fields=$fields!=''?$fields:'*';
			$query="SELECT $fields FROM autores WHERE articulo=?";
			$st=$db->prepare($query);
			if ($st->execute([$id])) {
				$resp->message=true;
				$results=$st->fetchAll(PDO::FETCH_ASSOC);
				$resp->results=formation_utf8_encode($results);
			}
			break;
		case 'revision':
			$user=$_SESSION['user'];
			if($user['tipo_us']==1){
				$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_SILENT);
				$input=json_decode(file_get_contents('php://input'));
				$query="SELECT dateac_as FROM asigna WHERE rev_as=? AND art_as=? AND dateac_as!=NULL";
				$st=$db->prepare($query);
				$values=[$_SESSION['user']['id_us'],$input->art];
				if(!$st->execute($values)){
					$resp->message='Ha ocurrido un error';
				}else{
					if($st->rowCount()>0){
						$asignacion=$st->fetch(PDO::FETCH_ASSOC);
						$now=date('Y-m-d H:i:s');
						$fecha=new DateTime($asignacion['dateac_as']);
						$fecha->modify('+15 day');
						if(strtotime($now)<strtotime($fecha->format('Y-m-d H:i:s'))){

						}else{
							$resp->message='Ha excedido el tiempo para la revisión del artículo';
						}
					}else{
						$resp->message='Este artículo no se le ha asignado';
					}
				}
			}else{
				$resp->message='No tiene los permisos necesarios';
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