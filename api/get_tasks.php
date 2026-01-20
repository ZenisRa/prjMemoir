<?php
// api/get_tasks.php
// Carica tutti i promemoria e le liste dell'utente loggato

session_start();
header('Content-Type: application/json');

// Verifica che l'utente sia loggato
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Utente non autenticato']);
    exit;
}

include '../db_conn.php';

$id_utente = $_SESSION['id'];

// 1. Carica tutte le Liste dell'utente
$sql_liste = "SELECT id_lista, nome_lista FROM Lista WHERE id_utente = ? ORDER BY nome_lista";
$stmt_liste = $conn->prepare($sql_liste);
$stmt_liste->bind_param("i", $id_utente);
$stmt_liste->execute();
$result_liste = $stmt_liste->get_result();

$liste = [];
while ($row = $result_liste->fetch_assoc()) {
    $liste[] = [
        'id' => $row['id_lista'],
        'nome' => $row['nome_lista']
    ];
}
$stmt_liste->close();

// 2. Carica tutti i Promemoria dell'utente (tramite le sue Liste)
$sql_promemoria = "SELECT 
        p.id_promemoria,
        p.id_lista,
        p.titolo,
        p.priorita,
        p.descrizione,
        p.data_scad,
        l.nome_lista
FROM Promemoria p
INNER JOIN Lista l ON p.id_lista = l.id_lista
WHERE l.id_utente = ?
ORDER BY p.data_scad ASC, p.priorita DESC";

$stmt_promemoria = $conn->prepare($sql_promemoria);
$stmt_promemoria->bind_param("i", $id_utente);
$stmt_promemoria->execute();
$result_promemoria = $stmt_promemoria->get_result();

$promemoria = [];
while ($row = $result_promemoria->fetch_assoc()) {
    // Converti la data da DATETIME a formato compatibile con datetime-local
    $data_scad = null;
    if ($row['data_scad']) {
        // Rimuovi i secondi per datetime-local (formato: YYYY-MM-DDTHH:mm)
        $data_scad = date('Y-m-d\TH:i', strtotime($row['data_scad']));
    }
    
    $promemoria[] = [
        'id' => $row['id_promemoria'],
        'id_lista' => $row['id_lista'],
        'title' => $row['titolo'],
        'description' => $row['descrizione'] ?? '',
        'date' => $data_scad,
        'priority' => (string)$row['priorita'], // String per compatibilità con JS
        'list' => $row['nome_lista'],
        'completed' => false // Campo non presente nel DB, sempre false per ora
    ];
}
$stmt_promemoria->close();

// 3. Prepara i nomi delle liste per il JavaScript (solo i nomi)
$nomi_liste = array_map(function($l) { return $l['nome']; }, $liste);

// Se non ci sono liste, aggiungi una lista di default "Generale"
if (empty($nomi_liste)) {
    // Crea la lista "Generale" per questo utente
    $sql_insert_default = "INSERT INTO Lista (nome_lista, id_utente) VALUES ('Generale', ?)";
    $stmt_default = $conn->prepare($sql_insert_default);
    $stmt_default->bind_param("i", $id_utente);
    $stmt_default->execute();
    $id_lista_generale = $conn->insert_id;
    $stmt_default->close();
    
    $nomi_liste = ['Generale'];
    $liste = [['id' => $id_lista_generale, 'nome' => 'Generale']];
}

echo json_encode([
    'success' => true,
    'promemoria' => $promemoria,
    'liste' => $nomi_liste,
    'liste_complete' => $liste // Include anche gli ID per le operazioni
]);

$conn->close();
?>

