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
extract($_GET);
$host="localhost";
$dbname="revista_cientifica";
$user="administrador_re";
$passDB='^1W34BW6i[%n';
$sCon="mysql:host=$host;dbname=$dbname;";
$resp=new stdClass();
$resp->message='';
$resp->id='';
$resp->results=[];
try {
	$db=new PDO($sCon,$user,$passDB,array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
	switch ($type) {
		case 'usuario':
			switch ($action) {
				case 'create':
					extract($_POST);
					$al=isset($al)?$al:'';
					$email=isset($email)?$email:'';
					$pass=isset($pass)?$pass:'';
					$tel=isset($tel)?$tel:'';
					$nom=isset($nom)?$nom:'';
					$ap=isset($ap)?$ap:'';
					$gen=isset($gen)?$gen:'';
					$pais=isset($pais)?$pais:'';
					$bio=isset($bio)?$bio:'';
					$lang=isset($lang)?$lang:'';
					$inst=isset($inst)?$inst:'';
					$int=isset($int)?$int:'';
					$tipo=isset($tipo)?$tipo:'';
					$query="INSERT INTO usuario VALUES(null,'$al','$email',MD5('$pass'),'$tel','$nom','$ap','$gen','$pais','$bio','$lang','$inst','$int','$tipo')";
					if($db->query($query)===false){
						$resp->message='Salio algo mal en la inserción';
					}else{
						$resp->message=true;
						$resp->id=$db->lastInsertId();
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
						$query="(SELECT COUNT(*) FROM usuario WHERE id_us IN ($req) AND ? $usert)";
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