<?php
header("Content-Type: application/json");
$conn = new mysqli("localhost","root","","gestionale");
if($conn->connect_error){echo json_encode(["error"=>$conn->connect_error]); exit;}

$method = $_SERVER['REQUEST_METHOD'];

if($method==="GET"){
    $query = "SELECT * FROM ordini WHERE 1=1";
    $params=[]; $types="";
    foreach($_GET as $k=>$v){ if($v!==""){ $query.=" AND $k LIKE ?"; $params[]="%$v%"; $types.="s"; } }
    $stmt=$conn->prepare($query);
    if($params) $stmt->bind_param($types,...$params);
    $stmt->execute(); $res=$stmt->get_result();
    $data=[]; while($row=$res->fetch_assoc()) $data[]=$row;
    echo json_encode($data); $stmt->close();
}
elseif($method==="POST"){
    $input=json_decode(file_get_contents("php://input"),true);
    if(!$input){echo json_encode(["error"=>"Nessun input"]); exit;}
    $keys=array_keys($input); $vals=array_values($input);
    $ph=implode(",",array_map(fn($k)=>"?",$keys));
    $stmt=$conn->prepare("INSERT INTO ordini(".implode(",",$keys).") VALUES($ph)");
    $types=str_repeat("s",count($vals));
    $stmt->bind_param($types,...$vals);
    if($stmt->execute()) echo json_encode(["success"=>true,"id"=>$stmt->insert_id]);
    else echo json_encode(["error"=>$stmt->error]);
    $stmt->close();
}
elseif($method==="DELETE"){
    $input=json_decode(file_get_contents("php://input"),true);
    if(!$input || !isset($input['id_ordine'])){echo json_encode(["error"=>"ID mancante"]); exit;}
    $stmt=$conn->prepare("DELETE FROM ordini WHERE id_ordine=?");
    $stmt->bind_param("i",$input['id_ordine']);
    if($stmt->execute()) echo json_encode(["success"=>true]);
    else echo json_encode(["error"=>$stmt->error]);
    $stmt->close();
}
else{echo json_encode(["error"=>"Metodo non supportato"]);}
$conn->close();
?>