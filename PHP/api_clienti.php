<?php
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "", "gestionale");
if ($conn->connect_error) { echo json_encode(["error"=>"Connessione fallita"]); exit; }

$method = $_SERVER['REQUEST_METHOD'];

// ------------------- GET -------------------
if($method === "GET"){
    $query = "SELECT id, nome, cognome, email FROM clienti WHERE 1=1";
    $params = [];
    $types = "";

    if(isset($_GET['nome']) && $_GET['nome']!==''){
        $query .= " AND nome LIKE ?";
        $params[] = "%".$_GET['nome']."%";
        $types.="s";
    }
    if(isset($_GET['cognome']) && $_GET['cognome']!==''){
        $query .= " AND cognome LIKE ?";
        $params[] = "%".$_GET['cognome']."%";
        $types.="s";
    }
    if(isset($_GET['email']) && $_GET['email']!==''){
        $query .= " AND email LIKE ?";
        $params[] = "%".$_GET['email']."%";
        $types.="s";
    }

    $stmt = $conn->prepare($query);
    if($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = [];
    while($row = $res->fetch_assoc()) $data[] = $row;
    echo json_encode($data);
    $stmt->close();
}

// ------------------- POST -------------------
elseif($method === "POST"){
    $raw = file_get_contents("php://input");
    $input = json_decode($raw,true);
    $nome = $input['nome'] ?? '';
    $cognome = $input['cognome'] ?? '';
    $email = $input['email'] ?? '';

    if(!$nome || !$cognome || !$email){ echo json_encode(["error"=>"Campi mancanti"]); exit; }

    $stmt = $conn->prepare("INSERT INTO clienti (nome,cognome,email) VALUES (?,?,?)");
    $stmt->bind_param("sss",$nome,$cognome,$email);
    if($stmt->execute()) echo json_encode(["success"=>true, "id"=>$stmt->insert_id]);
    else echo json_encode(["error"=>$stmt->error]);
    $stmt->close();
}

// ------------------- DELETE -------------------
elseif($method === "DELETE"){
    $raw = file_get_contents("php://input");
    $input = json_decode($raw,true);
    $id = $input['id'] ?? 0;
    if(!$id){ echo json_encode(["error"=>"ID mancante"]); exit; }

    $stmt = $conn->prepare("DELETE FROM clienti WHERE id=?");
    $stmt->bind_param("i",$id);
    if($stmt->execute()) echo json_encode(["success"=>true]);
    else echo json_encode(["error"=>$stmt->error]);
    $stmt->close();
}

$conn->close();
?>
