<?php
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "", "gestionale");
if ($conn->connect_error) { echo json_encode([]); exit; }

$sql = "
SELECT 
    c.nome AS nome_cliente,
    c.cognome AS cognome_cliente,
    o.id_ordine,
    o.importo,
    a.nome AS nome_agente,
    a.cognome AS cognome_agente,
    i.nome_impiego,
    i.ral
FROM clienti c
LEFT JOIN ordini o ON c.id = o.id_cliente
LEFT JOIN agenti a ON o.id_agente = a.id_agente
LEFT JOIN impieghi i ON a.id_impiego = i.id_impiego
";

$res = $conn->query($sql);
$data = [];
while ($row = $res->fetch_assoc()) { $data[] = $row; }
echo json_encode($data);
?>