// js/script.js

// ============================================================
// 1. VARIABILI GLOBALI (STATO DELL'APP)
// ============================================================

// Questi array sono VUOTI all'inizio.
// Verranno riempiti con i dati presi dal tuo Database SQL tramite PHP.
let elencoPromemoria = [];
let elencoListe = [];

// Variabili per ricordare cosa stiamo facendo
let idPromemoriaCorrente = null; // L'ID del compito che stiamo modificando
let filtroCorrente = "today"; // Cosa stiamo vedendo: 'today', 'tomorrow', 'upcoming', 'all' o nome lista

// ============================================================
// 2. AVVIO DELL'APPLICAZIONE
// ============================================================

document.addEventListener("DOMContentLoaded", function () {
  // 1. Imposta il tema (Chiaro/Scuro) salvato
  inizializzaTema();

  // 2. Chiede i dati al Server (Simulato per ora)
  caricaDatiDalServer();
});

/**
 * Carica i dati dal database MySQL tramite API PHP
 */
function caricaDatiDalServer() {
  console.log("Caricamento dati dal server...");

  fetch("api/get_tasks.php")
    .then((res) => res.json())
    .then((dati) => {
      if (dati.success) {
        elencoPromemoria = dati.promemoria || [];
        elencoListe = dati.liste || ["Generale"];
        aggiornaInterfaccia();
        console.log("Dati caricati con successo:", dati);
        console.log("Liste disponibili:", elencoListe);
      } else {
        console.error("Errore caricamento dati:", dati.message);
        elencoPromemoria = [];
        elencoListe = ["Generale"];
        aggiornaInterfaccia();
      }
    })
    .catch((err) => {
      console.error("Errore fetch:", err);
      elencoPromemoria = [];
      elencoListe = ["Generale"];
      aggiornaInterfaccia();
    });
}

/**
 * Ridisegna tutta la pagina con i dati attuali.
 * Si chiama ogni volta che aggiungi, modifichi o elimini qualcosa.
 */
function aggiornaInterfaccia() {
  renderSidebarLists(); // Aggiorna la barra sinistra
  renderAllTasks(); // Aggiorna la lista centrale
  updateListDropdown(); // Aggiorna il menu a tendina nel form
}

// ============================================================
// 3. VISUALIZZAZIONE COMPITI (FILTRI E LOGICA)
// ============================================================

/**
 * Funzione principale: Filtra i compiti e crea l'HTML per mostrarli.
 * Gestisce: Oggi, Domani, Prossimamente, Tutti e Ricerca.
 */
function renderAllTasks() {
  const container = document.getElementById("tasksContainer");
  container.innerHTML = ""; // Pulisce la lista

  // Legge i filtri dalla pagina
  const testoRicerca = document
    .getElementById("searchInput")
    .value.toLowerCase();
  const modoOrdinamento = document.getElementById("sortOrder").value;

  // Copia l'array originale per non rovinare i dati
  let compitiDaMostrare = [...elencoPromemoria];

  // Filtra i task non salvati senza titolo (non mostrare task vuoti non salvati)
  compitiDaMostrare = compitiDaMostrare.filter((task) => {
    // Se è un nuovo task senza titolo, non mostrarlo nella lista
    if (task.isNew && (!task.title || task.title.trim() === "")) {
      return false;
    }
    return true;
  });

  // --- A. APPLICA I FILTRI SMART (Data e Lista) ---
  compitiDaMostrare = compitiDaMostrare.filter((task) => {
    // Prepariamo le date per i confronti (senza orario)
    const dataTask = task.date ? new Date(task.date) : null;

    const oggi = new Date();
    oggi.setHours(0, 0, 0, 0);

    const domani = new Date(oggi);
    domani.setDate(oggi.getDate() + 1);

    // Data del task resettata a mezzanotte per confronto preciso
    let dataTaskZero = null;
    if (dataTask) {
      dataTaskZero = new Date(dataTask);
      dataTaskZero.setHours(0, 0, 0, 0);
    }

    // LOGICA DI SELEZIONE
    if (filtroCorrente === "today") {
      // Mostra SOLO se la data è Oggi
      if (!dataTaskZero) return false;
      return dataTaskZero.getTime() === oggi.getTime();
    } else if (filtroCorrente === "tomorrow") {
      // Mostra SOLO se la data è Domani
      if (!dataTaskZero) return false;
      return dataTaskZero.getTime() === domani.getTime();
    } else if (filtroCorrente === "upcoming") {
      // Mostra TUTTO ciò che è nel futuro (dopo oggi)
      if (!dataTaskZero) return false;
      return dataTaskZero.getTime() > oggi.getTime();
    } else if (filtroCorrente === "all") {
      // Mostra tutto
      return true;
    } else {
      // Mostra solo i compiti di una specifica lista (es. "Lavoro")
      return task.list === filtroCorrente;
    }
  });

  // --- B. APPLICA LA RICERCA ---
  if (testoRicerca) {
    compitiDaMostrare = compitiDaMostrare.filter((task) =>
      task.title.toLowerCase().includes(testoRicerca),
    );
  }

  // --- C. ORDINAMENTO ---
  if (modoOrdinamento === "priority") {
    // Ordina per priorità decrescente (3 -> 0), poi per data
    compitiDaMostrare.sort((a, b) => {
      const prioA = parseInt(a.priority) || 0;
      const prioB = parseInt(b.priority) || 0;
      if (prioB !== prioA) {
        return prioB - prioA; // Priorità decrescente
      }
      // Se stessa priorità, ordina per data
      if (!a.date) return 1;
      if (!b.date) return -1;
      return new Date(a.date) - new Date(b.date);
    });
  } else if (modoOrdinamento === "date") {
    // Ordina per data crescente (più vicini prima), poi per priorità
    compitiDaMostrare.sort((a, b) => {
      if (!a.date && !b.date) {
        // Entrambi senza data: ordina per priorità
        return (parseInt(b.priority) || 0) - (parseInt(a.priority) || 0);
      }
      if (!a.date) return 1; // Senza data vanno dopo
      if (!b.date) return -1;
      const dateA = new Date(a.date);
      const dateB = new Date(b.date);
      if (dateA.getTime() !== dateB.getTime()) {
        return dateA - dateB; // Data crescente
      }
      // Stessa data: ordina per priorità
      return (parseInt(b.priority) || 0) - (parseInt(a.priority) || 0);
    });
  } else if (modoOrdinamento === "name") {
    // Ordina per nome A-Z, poi per priorità
    compitiDaMostrare.sort((a, b) => {
      const nomeA = (a.title || "").toLowerCase();
      const nomeB = (b.title || "").toLowerCase();
      if (nomeA !== nomeB) {
        return nomeA.localeCompare(nomeB, "it"); // Ordine alfabetico italiano
      }
      // Stesso nome: ordina per priorità
      return (parseInt(b.priority) || 0) - (parseInt(a.priority) || 0);
    });
  }

  // --- D. DISEGNA A SCHERMO ---
  compitiDaMostrare.forEach((task) => {
    creaHtmlTask(task, container);
  });

  // Aggiorna il titolo in alto
  aggiornaTitoloHeader();
}

/**
 * Crea il blocchetto HTML per un singolo compito
 */
function creaHtmlTask(task, container) {
  let iconaPriorita = "";
  // Aggiunge i punti esclamativi in base alla priorità
  if (task.priority === "1")
    iconaPriorita = "<span class='priority-indicator prio-1'>!</span>";
  if (task.priority === "2")
    iconaPriorita = "<span class='priority-indicator prio-2'>!!</span>";
  if (task.priority === "3")
    iconaPriorita = "<span class='priority-indicator prio-3'>!!!</span>";

  const riga = document.createElement("div");
  riga.className = "task-row";
  // Al click apre i dettagli
  riga.onclick = function () {
    apriDettagli(task.id);
  };

  // Mostra un placeholder se il titolo è vuoto (per task non salvati)
  const titoloMostrato =
    task.title && task.title.trim() !== ""
      ? pulisciTestoHTML(task.title)
      : task.isNew
        ? "(Nuovo promemoria - non salvato)"
        : "Senza titolo";

  riga.innerHTML = `
        <input type="checkbox" ${task.completed ? "checked" : ""} onclick="event.stopPropagation(); cambiaStato(this, ${task.id})">
        <div class="task-info">
            <span class="task-title" style="${task.completed ? "text-decoration:line-through;opacity:0.6" : ""}${task.isNew ? ";font-style:italic;opacity:0.7" : ""}">
                ${titoloMostrato}
            </span>
            <span class="task-meta">
                ${iconaPriorita} ${pulisciTestoHTML(task.list)} 
                ${task.date ? " - " + formattaData(task.date) : ""}
            </span>
        </div>
        <button class="task-delete-btn glass-delete-btn" onclick="event.stopPropagation(); deleteTaskFromList(${task.id})" title="Elimina">
            🗑️
        </button>
    `;
  container.appendChild(riga);
}

// ============================================================
// 4. GESTIONE LISTE (BARRA SINISTRA)
// ============================================================

function renderSidebarLists() {
  const container = document.getElementById("customListsContainer");
  container.innerHTML = "";

  elencoListe.forEach((nomeLista) => {
    const div = document.createElement("div");
    // Aggiunge classe 'active' se è selezionata
    div.className = `list-item ${filtroCorrente === nomeLista ? "active" : ""}`;

    div.innerHTML = `
            <div style="display:flex; align-items:center; flex:1;" onclick="switchFilter('${nomeLista}', this.parentNode)">
                <span style="color: #007aff; margin-right:8px;">●</span> ${pulisciTestoHTML(nomeLista)}
            </div>
            <button class="btn-delete-list" onclick="deleteList('${nomeLista}')" title="Elimina Lista">×</button>
        `;
    container.appendChild(div);
  });
}

function addNewList() {
  const nome = prompt("Nome della nuova lista:");
  if (nome && nome.trim() !== "") {
    // Salva la lista nel database
    fetch("api/save_list.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ nome: nome.trim() }),
    })
      .then((res) => res.json())
      .then((result) => {
        if (result.success) {
          // Ricarica i dati dal server per ottenere le liste aggiornate
          caricaDatiDalServer();
          console.log("Lista salvata:", result);
        } else {
          alert(
            "Errore: " + (result.message || "Impossibile salvare la lista"),
          );
          console.error("Errore salvataggio lista:", result);
        }
      })
      .catch((err) => {
        console.error("Errore fetch:", err);
        alert("Errore di connessione al server.");
      });
  }
}

function deleteList(nomeLista) {
  if (confirm(`Eliminare la lista "${nomeLista}"?`)) {
    // Elimina la lista dal database
    fetch("api/delete_list.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ nome: nomeLista }),
    })
      .then((res) => res.json())
      .then((result) => {
        if (result.success) {
          // Se stavi guardando quella lista, torna a Oggi
          if (filtroCorrente === nomeLista) {
            switchFilter("today", document.getElementById("filter-today"));
          }
          // Ricarica i dati dal server per ottenere le liste aggiornate
          caricaDatiDalServer();
          console.log("Lista eliminata:", result);
        } else {
          alert(
            "Errore: " + (result.message || "Impossibile eliminare la lista"),
          );
          console.error("Errore eliminazione lista:", result);
        }
      })
      .catch((err) => {
        console.error("Errore fetch:", err);
        alert("Errore di connessione al server.");
      });
  }
}

function updateListDropdown() {
  const select = document.getElementById("detList");
  select.innerHTML = "";
  elencoListe.forEach((nome) => {
    const option = document.createElement("option");
    option.value = nome;
    option.text = nome;
    select.add(option);
  });
}

// ============================================================
// 5. NAVIGAZIONE (CAMBIO FILTRO)
// ============================================================

function switchFilter(filtroNome, elemento) {
  filtroCorrente = filtroNome;

  // Gestione grafica della selezione
  document
    .querySelectorAll(".list-item")
    .forEach((el) => el.classList.remove("active"));

  if (elemento) {
    // Se cliccato dall'utente
    if (elemento.classList.contains("list-item"))
      elemento.classList.add("active");
    else elemento.closest(".list-item").classList.add("active");
  } else {
    // Se cambiato via codice (es. all'avvio)
    if (filtroNome === "today")
      document.getElementById("filter-today")?.classList.add("active");
    else if (filtroNome === "tomorrow")
      document.getElementById("filter-tomorrow")?.classList.add("active");
    else if (filtroNome === "upcoming")
      document.getElementById("filter-upcoming")?.classList.add("active");
    else if (filtroNome === "all")
      document.getElementById("filter-all")?.classList.add("active");
  }

  renderAllTasks();
}

// Ascolta la digitazione nella barra di ricerca
document.getElementById("searchInput").addEventListener("input", function () {
  renderAllTasks();
});

// ============================================================
// 6. CREAZIONE E MODIFICA COMPITI
// ============================================================

function apriDettagli(idTask) {
  const pannello = document.getElementById("detailsPanel");
  idPromemoriaCorrente = idTask;
  pannello.classList.add("open"); // Mostra il pannello

  const task = elencoPromemoria.find((t) => t.id === idTask);

  if (task) {
    // Riempie il form con i dati
    document.getElementById("detTitle").value = task.title || "";
    document.getElementById("detDesc").value = task.description || "";
    document.getElementById("detDate").value = task.date || "";
    document.getElementById("detPriority").value = task.priority || "0";
    document.getElementById("detList").value =
      task.list || elencoListe[0] || "";
  }
}

function closeDetails() {
  // Se c'è un task corrente e non è ancora salvato (isNew), rimuovilo dall'array
  if (idPromemoriaCorrente) {
    const task = elencoPromemoria.find((t) => t.id == idPromemoriaCorrente);
    if (task && task.isNew) {
      // Rimuovi il task non salvato dall'array
      elencoPromemoria = elencoPromemoria.filter(
        (t) => t.id != idPromemoriaCorrente,
      );
      aggiornaInterfaccia();
    }
  }

  document.getElementById("detailsPanel").classList.remove("open");
  idPromemoriaCorrente = null;
  document.getElementById("detailsForm").reset();
}

function createNewTask() {
  const nuovoId = Date.now(); // ID temporaneo

  // Crea oggetto vuoto (NON salvato ancora nel database)
  const nuovoTask = {
    id: nuovoId,
    title: "", // Vuoto, l'utente deve inserirlo
    description: "",
    date: "",
    priority: "0",
    list: elencoListe[0] || "Generale",
    completed: false,
    isNew: true, // Flag per indicare che non è ancora salvato nel DB
  };

  // Aggiungi all'array locale (solo per visualizzazione)
  elencoPromemoria.push(nuovoTask);

  // Aggiorna l'interfaccia per mostrare il nuovo task
  aggiornaInterfaccia();

  // Apri il pannello di modifica
  apriDettagli(nuovoId);

  // Focus sul campo titolo
  setTimeout(() => {
    document.getElementById("detTitle").focus();
  }, 100);
}

// Salva le modifiche al task sul database MySQL
function saveTask() {
  if (!idPromemoriaCorrente) return;

  // Trova l'indice nell'array locale
  // Usa == per matching lasco (stringa vs numero)
  const indice = elencoPromemoria.findIndex(
    (t) => t.id == idPromemoriaCorrente,
  );

  if (indice > -1) {
    const taskCorrente = elencoPromemoria[indice];

    // 1. Raccogli i dati dal form
    const titolo = document.getElementById("detTitle").value.trim();

    // Validazione: il titolo è obbligatorio
    if (!titolo || titolo === "") {
      alert("Il titolo è obbligatorio!");
      document.getElementById("detTitle").focus();
      return;
    }

    const datiForm = {
      title: titolo,
      description: document.getElementById("detDesc").value,
      date: document.getElementById("detDate").value,
      priority: document.getElementById("detPriority").value,
      list: document.getElementById("detList").value,

      // Logica azione: se ha il flag isNew o l'ID è temporaneo (molto grande), è 'create'
      action:
        taskCorrente.isNew || taskCorrente.id > 2000000000
          ? "create"
          : "update",
      id: taskCorrente.id,
    };

    // 2. Aggiorna l'interfaccia subito (Optimistic UI)
    taskCorrente.title = datiForm.title;
    taskCorrente.description = datiForm.description;
    taskCorrente.date = datiForm.date;
    taskCorrente.priority = datiForm.priority;
    taskCorrente.list = datiForm.list;

    aggiornaInterfaccia();

    // 3. Invia al server
    // ATTENZIONE: Assicurati che il nome del file qui sotto corrisponda a quello che hai creato in PHP
    fetch("api/save_task.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(datiForm),
    })
      .then((response) => response.json())
      .then((result) => {
        if (result.success) {
          console.log("Salvataggio OK. ID DB:", result.id);

          // Se era un nuovo task, aggiorniamo l'ID temporaneo con quello vero del DB
          if (result.action_performed === "create") {
            taskCorrente.id = result.id;
            taskCorrente.isNew = false;
            // Aggiorniamo anche il puntatore corrente se siamo ancora su quel task
            if (idPromemoriaCorrente == datiForm.id) {
              idPromemoriaCorrente = result.id;
            }
            // Ridisegna per aggiornare gli onclick con il nuovo ID
            aggiornaInterfaccia();
          }
          // Opzionale: alert("Salvato!");
        } else {
          alert("Errore nel salvataggio: " + (result.message || "Sconosciuto"));
          console.error("Errore server:", result);
        }
      })
      .catch((err) => {
        console.error("Errore Fetch:", err);
        alert("Errore di connessione al server.");
      });
  }
}
// Elimina un task dal database e dall'app (dal pannello dettagli)
function deleteTask() {
  if (confirm("Eliminare definitivamente?")) {
    const taskId = idPromemoriaCorrente;

    // Rimuovi dall'array locale
    elencoPromemoria = elencoPromemoria.filter((t) => t.id !== taskId);

    // Elimina dal database
    fetch("api/delete_task.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id: taskId }),
    })
      .then((res) => res.json())
      .then((dati) => {
        if (dati.success) {
          console.log("Task eliminato dal database");
        } else {
          console.error("Errore eliminazione:", dati.message);
        }
      })
      .catch((err) => console.error("Errore fetch:", err));

    aggiornaInterfaccia();
    closeDetails();
  }
}

// Elimina un task direttamente dalla lista
function deleteTaskFromList(taskId) {
  if (confirm("Eliminare definitivamente?")) {
    // Rimuovi dall'array locale
    elencoPromemoria = elencoPromemoria.filter((t) => t.id !== taskId);

    // Se il task eliminato era quello aperto nei dettagli, chiudi il pannello
    if (idPromemoriaCorrente == taskId) {
      closeDetails();
    }

    // Elimina dal database
    fetch("api/delete_task.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id: taskId }),
    })
      .then((res) => res.json())
      .then((dati) => {
        if (dati.success) {
          console.log("Task eliminato dal database");
        } else {
          console.error("Errore eliminazione:", dati.message);
        }
      })
      .catch((err) => console.error("Errore fetch:", err));

    aggiornaInterfaccia();
  }
}

// ============================================================
// 7. FUNZIONI UTILI
// ============================================================

/**
 * Salva un promemoria sul database MySQL
 * @param {Object} task - Oggetto promemoria da salvare
 */
function salvaDatiSuDB(task) {
  // Prepara i dati da inviare
  const datiDaInviare = {
    id: task.id || null, // Se c'è l'ID, aggiorna; altrimenti crea nuovo
    title: task.title || "",
    description: task.description || "",
    date: task.date || "",
    priority: task.priority || "0",
    list: task.list || elencoListe[0] || "Generale",
  };

  fetch("api/save_task.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(datiDaInviare),
  })
    .then((res) => res.json())
    .then((dati) => {
      if (dati.success) {
        console.log("Promemoria salvato sul database:", dati.message);
        // Se è un nuovo promemoria o ha un ID temporaneo, aggiorna l'ID con quello del database
        if (dati.id && (!task.id || task.id > 1000000000)) {
          task.id = dati.id;
        }
      } else {
        console.error("Errore salvataggio:", dati.message);
        alert("Errore nel salvataggio: " + dati.message);
      }
    })
    .catch((err) => {
      console.error("Errore fetch:", err);
      alert("Errore di connessione al server");
    });
}

function cambiaStato(checkbox, id) {
  const task = elencoPromemoria.find((t) => t.id === id);
  if (task) {
    task.completed = checkbox.checked;
    aggiornaInterfaccia();
  }
}

// Pulisce il testo da caratteri pericolosi (Sicurezza)
function pulisciTestoHTML(testo) {
  if (!testo) return "";
  return testo
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;");
}

function formattaData(stringaData) {
  if (!stringaData) return "";
  const data = new Date(stringaData);
  return data.toLocaleDateString("it-IT", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function aggiornaTitoloHeader() {
  let titolo = filtroCorrente;
  if (filtroCorrente === "today") titolo = "Oggi";
  if (filtroCorrente === "tomorrow") titolo = "Domani";
  if (filtroCorrente === "upcoming") titolo = "Prossimamente";
  if (filtroCorrente === "all") titolo = "Tutti";
  document.getElementById("currentListTitle").innerText = titolo;
}

function logout() {
  window.location.href = "logout.php";
}

// Gestione Tema Notte/Giorno
function inizializzaTema() {
  const temaSalvato = localStorage.getItem("theme");
  const bottone = document.getElementById("themeBtn");
  if (temaSalvato === "dark") {
    document.body.setAttribute("data-theme", "dark");
    if (bottone) bottone.innerText = "☀️";
  }
}

function toggleTheme() {
  const body = document.body;
  const btn = document.getElementById("themeBtn");
  const isDark = body.getAttribute("data-theme") === "dark";

  if (isDark) {
    body.removeAttribute("data-theme");
    localStorage.setItem("theme", "light");
    btn.innerText = "🌙";
  } else {
    body.setAttribute("data-theme", "dark");
    localStorage.setItem("theme", "dark");
    btn.innerText = "☀️";
  }
}
