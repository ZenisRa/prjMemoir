<?php
// Avvia la sessione corrente per poterla distruggere
session_start();

// Distrugge tutte le variabili di sessione e libera la sessione lato server
session_destroy();

// Reindirizza l'utente alla pagina di login
header("Location: login.php");

// Termina l'esecuzione dello script per evitare che venga eseguito altro codice dopo il redirect
exit;
?>