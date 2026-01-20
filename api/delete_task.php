<?php
// api/delete_task.php
// Elimina un promemoria dal database

session_start();
header('Content-Type: application/json');

// Verifica che l'utente sia loggato
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Utente non autenticato']);
    exit;
}

include '../db_conn.php';

// Legge i dati JSON dal body della richiesta
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID promemoria non fornito']);
    exit;
}

$id_utente = $_SESSION['id'];
$id_promemoria = intval($input['id']);

// Verifica che il promemoria appartenga all'utente
    $sql_check = "SELECT p.id_promemoria 
                  FROM Promemoria p 
                  INNER JOIN Lista l ON p.id_lista = l.id_lista 
                  WHERE p.id_promemoria = ? AND l.id_utente = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $id_promemoria, $id_utente);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows === 0) {
        $stmt_check->close();
        echo json_encode(['success' => false, 'message' => 'Promemoria non trovato o non autorizzato']);
        exit;
    }
    $stmt_check->close();
    
    // Elimina il promemoria (CASCADE eliminerà automaticamente se necessario)
    $sql_delete = "DELETE FROM Promemoria WHERE id_promemoria = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id_promemoria);
    
    if ($stmt_delete->execute()) {
        echo json_encode(['success' => true, 'message' => 'Promemoria eliminato']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Errore eliminazione: ' . $conn->error]);
    }
    $stmt_delete->close();

$conn->close();
?>

