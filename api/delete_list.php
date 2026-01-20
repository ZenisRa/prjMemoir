<?php
// api/delete_list.php
// Elimina una lista dal database (e tutti i suoi promemoria per CASCADE)

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

if (!$input || !isset($input['nome'])) {
    echo json_encode(['success' => false, 'message' => 'Nome lista non fornito']);
    exit;
}

$id_utente = $_SESSION['id'];
$nome_lista = trim($input['nome']);

// Verifica che la lista appartenga all'utente
    $sql_check = "SELECT id_lista FROM Lista WHERE nome_lista = ? AND id_utente = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("si", $nome_lista, $id_utente);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows === 0) {
        $stmt_check->close();
        echo json_encode(['success' => false, 'message' => 'Lista non trovata o non autorizzata']);
        exit;
    }
    
    $row = $result_check->fetch_assoc();
    $id_lista = $row['id_lista'];
    $stmt_check->close();
    
    // Non permettere di eliminare l'ultima lista (almeno una deve rimanere)
    $sql_count = "SELECT COUNT(*) as totale FROM Lista WHERE id_utente = ?";
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param("i", $id_utente);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $row_count = $result_count->fetch_assoc();
    $stmt_count->close();
    
    if ($row_count['totale'] <= 1) {
        echo json_encode(['success' => false, 'message' => 'Non puoi eliminare l\'ultima lista']);
        exit;
    }
    
    // Elimina la lista (CASCADE eliminerà automaticamente tutti i promemoria)
    $sql_delete = "DELETE FROM Lista WHERE id_lista = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id_lista);
    
    if ($stmt_delete->execute()) {
        echo json_encode(['success' => true, 'message' => 'Lista eliminata']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Errore eliminazione: ' . $conn->error]);
    }
    $stmt_delete->close();

$conn->close();
?>

