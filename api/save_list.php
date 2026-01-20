<?php
// api/save_list.php
// Salva o aggiorna una lista nel database

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

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Dati non validi']);
    exit;
}

$id_utente = $_SESSION['id'];
$nome_lista = trim($input['nome'] ?? '');

// Validazione
if (empty($nome_lista)) {
    echo json_encode(['success' => false, 'message' => 'Il nome della lista è obbligatorio']);
    $conn->close();
    exit;
}

// Verifica se esiste già una lista con lo stesso nome per questo utente
    $sql_check = "SELECT id_lista FROM Lista WHERE nome_lista = ? AND id_utente = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("si", $nome_lista, $id_utente);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        // Lista già esistente
        $row = $result_check->fetch_assoc();
        $stmt_check->close();
        echo json_encode([
            'success' => true, 
            'message' => 'Lista già esistente',
            'id' => $row['id_lista'],
            'nome' => $nome_lista
        ]);
    } else {
        // Crea nuova lista
        $sql_insert = "INSERT INTO Lista (nome_lista, id_utente) VALUES (?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("si", $nome_lista, $id_utente);
        
        if ($stmt_insert->execute()) {
            $nuovo_id = $conn->insert_id;
            echo json_encode([
                'success' => true,
                'message' => 'Lista creata',
                'id' => $nuovo_id,
                'nome' => $nome_lista
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Errore inserimento: ' . $conn->error]);
        }
        $stmt_insert->close();
    }

$conn->close();
?>

