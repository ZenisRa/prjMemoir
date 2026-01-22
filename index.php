<?php
// Avvia la sessione PHP
session_start();

// Controlla se l'utente è loggato
if (!isset($_SESSION['loggedin'])) {
    // Se non è loggato, reindirizza alla pagina di login
    header("Location: login.php");
    exit;
}

// Imposta il nome utente dalla sessione, altrimenti usa 'Utente' di default
$username = $_SESSION['username'] ?? 'Utente';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Memoir - Dashboard</title>
    <link rel="stylesheet" href="css/style.css">

    <!-- Stili per animazioni fade-in al login -->
    <style>
        /* Animazione fade-in per l'arrivo da login */
        body.fade-in {
            animation: fadeInPage 0.8s cubic-bezier(0.4, 0, 0.2, 1) both;
        }

        /* Definizione dell'animazione */
        @keyframes fadeInPage {
            from {
                opacity: 0;
                transform: translateY(20px) scale(0.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Nasconde il body inizialmente se c'è il flag di login */
        body.hide-on-load {
            opacity: 0;
        }
    </style>

    <!-- Piccoli aggiustamenti per icone, priorità e pulsanti -->
    <style>
        /* Piccoli aggiustamenti inline per icone e colori */
        .sort-select { cursor: pointer; border: 1px solid transparent; }
        .sort-select:hover { border-color: #ddd; }

        /* Indicatori di priorità colorati */
        .priority-indicator { font-weight: 900; margin-right: 5px; }
        .prio-1 { color: #007aff; }   /* Bassa */
        .prio-2 { color: #ff9f0a; }   /* Media */
        .prio-3 { color: #ff3b30; }   /* Alta */

        /* Pulsante elimina lista nascosto fino al hover */
        .btn-delete-list {
            background: none; border: none; color: #ff3b30;
            cursor: pointer; font-size: 1.2rem; margin-left: auto;
            opacity: 0; transition: opacity 0.2s; padding: 0 5px;
        }
        .list-item:hover .btn-delete-list { opacity: 1; }
    </style>
</head>
<body>

<div class="app-container">

    <!-- SIDEBAR SINISTRA -->
    <aside class="sidebar" id="sidebar">
        <div class="logo-container">
            <img src="logo.jpeg" alt="Logo">
        </div>

        <!-- Barra di ricerca -->
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Cerca ovunque...">
        </div>

        <!-- Gruppo delle liste -->
        <div class="lists-group">
            <h4>Smart Lists</h4>
            <!-- Lista "Oggi" con stato attivo -->
            <div class="list-item active" id="filter-today" onclick="switchFilter('today', this)">
                <span>Oggi</span>
            </div>
            <!-- Lista "Domani" -->
            <div class="list-item" id="filter-tomorrow" onclick="switchFilter('tomorrow', this)">
                <span>Domani</span>
            </div>
            <!-- Lista "Prossimamente" -->
            <div class="list-item" id="filter-upcoming" onclick="switchFilter('upcoming', this)">
                <span>Prossimamente</span>
            </div>
            <!-- Lista "Tutti" -->
            <div class="list-item" id="filter-all" onclick="switchFilter('all', this)">
                <span>Tutti</span>
            </div>

            <h4>Le mie Liste</h4>
            <div id="customListsContainer">
                <!-- Qui JavaScript caricherà le liste personalizzate -->
            </div>
        </div>

        <!-- Footer della sidebar con pulsanti -->
        <div class="sidebar-footer">
            <button class="glass-btn btn-add-list" onclick="addNewList()" title="Aggiungi Lista">
                <span>+</span>
                <span>Lista</span>
            </button>
            <button class="glass-btn btn-theme-glass" onclick="toggleTheme()" id="themeBtn" title="Cambia tema">🌙</button>
        </div>

        <!-- Crediti in fondo alla sidebar -->
        <div class="sidebar-credits">
            <div class="credits-content">
                <span>Alessio Bonn, Ali Frihat, Denis Ravanelli</span>
                <span class="credits-school">ITT BUONARROTI</span>
            </div>
        </div>
    </aside>

    <!-- LISTA CENTRALE -->
    <main class="main-list">

        <!-- Riquadro logout utente -->
        <div class="logout-panel">
            <div class="logout-content">
                <div class="logout-user">
                    <span class="logout-icon">👤</span>
                    <!-- Mostra nome utente in sicurezza -->
                    <span class="logout-username"><?php echo htmlspecialchars($username); ?></span>
                </div>
                <button class="logout-btn" onclick="logout()" title="Esci">
                    <span>Esci</span>
                    <span class="logout-arrow">→</span>
                </button>
            </div>
        </div>

        <!-- Header principale con titolo lista e pulsanti -->
        <header class="list-header">
            <h1 id="currentListTitle">Oggi</h1>
            <div class="header-actions" style="flex:1; display:flex; justify-content:flex-end; align-items:center; gap: 10px; flex-wrap: nowrap;">
                <!-- Bottone "+" per nuovo promemoria -->
                <button title="Nuovo Promemoria" onclick="createNewTask()" class="glass-btn btn-add-main">+</button>

                <!-- Selettore ordine di visualizzazione task -->
                <select id="sortOrder" class="sort-select" onchange="renderAllTasks()" style="display: block !important; visibility: visible !important; opacity: 1 !important; flex-shrink: 0; margin-right: 170px;">
                    <option value="manual">Ordina per...</option>
                    <option value="priority">Priorità</option>
                    <option value="date">Tempo</option>
                    <option value="name">Nome A-Z</option>
                </select>
            </div>
        </header>

        <!-- Contenitore per i task, caricati dinamicamente da JS -->
        <div id="tasksContainer" class="tasks-container"></div>
    </main>

    <!-- PANNELLO DETTAGLI DESTRA -->
    <aside id="detailsPanel" class="details-panel">
        <div class="details-header">
            <h3>Dettagli</h3>
            <button class="btn-close" onclick="closeDetails()">✕</button>
        </div>

        <!-- Form dettagli task -->
        <form id="detailsForm" onsubmit="return false;">
            <div class="detail-group">
                <input type="text" id="detTitle" style="font-size:1.2rem; font-weight:bold;" placeholder="Titolo">
            </div>
            <div class="detail-group">
                <label>Note</label>
                <textarea id="detDesc" placeholder="Note..."></textarea>
            </div>
            <div class="detail-group">
                <label>Data</label>
                <input type="datetime-local" id="detDate">
            </div>
            <div class="detail-group">
                <label>Priorità</label>
                <select id="detPriority">
                    <option value="0">Nessuna</option>
                    <option value="1">! Bassa</option>
                    <option value="2">!! Media</option>
                    <option value="3">!!! Alta</option>
                </select>
            </div>
            <div class="detail-group">
                <label>Lista</label>
                <select id="detList">
                    <!-- Caricato dinamicamente -->
                </select>
            </div>
            <!-- Pulsanti salva / elimina -->
            <button type="button" class="btn-primary" onclick="saveTask()">Salva Modifiche</button>
            <button type="button" onclick="deleteTask()" style="width:100%; margin-top:10px; color:#ff3b30; border:none; background:none; cursor:pointer;">Elimina</button>
        </form>
    </aside>

</div>

<!-- Script JS -->
<script src="js/script.js"></script>
<script>
    // Animazione fade-in quando si arriva da login
    <?php if(isset($_SESSION['just_logged_in']) && $_SESSION['just_logged_in']): ?>
        // Rimuove il flag per non rifare l'animazione al refresh
        <?php unset($_SESSION['just_logged_in']); ?>

        // Aggiunge classe per nascondere il body inizialmente
        document.body.classList.add('hide-on-load');

        // Dopo un breve delay, applica fade-in
        setTimeout(() => {
            document.body.classList.remove('hide-on-load');
            document.body.classList.add('fade-in');
        }, 50);
    <?php endif; ?>
</script>
</body>
</html>