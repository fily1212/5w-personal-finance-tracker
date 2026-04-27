/* =========================================================
 * app.js — Personal Finance Tracker
 * Vanilla JS | fetch / async-await
 * ======================================================== */

'use strict';

const API = {
  dashboard:   'api/dashboard.php',
  conti:       'api/conti.php',
  categorie:   'api/categorie.php',
  transazioni: 'api/transazioni.php',
};

// ── Helpers ──────────────────────────────────────────────

const fmt = new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' });

function formatCurrency(value) {
  return fmt.format(Number(value));
}

function formatDate(dateStr) {
  const [y, m, d] = dateStr.split('-');
  return `${d}/${m}/${y}`;
}

function showAlert(message, type = 'danger') {
  const el = document.getElementById('global-alert');
  el.className = `alert alert-${type} alert-dismissible fade show`;
  el.innerHTML = `${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
  el.classList.remove('d-none');
  setTimeout(() => el.classList.add('d-none'), 5000);
}

async function apiFetch(url, options = {}) {
  const res = await fetch(url, options);
  const json = await res.json();
  if (!res.ok || json.status === 'error') {
    throw new Error(json.message || `HTTP error ${res.status}`);
  }
  return json.data;
}

// ── Dashboard ─────────────────────────────────────────────

async function loadDashboard() {
  try {
    const data = await apiFetch(API.dashboard);
    document.getElementById('stat-saldo').textContent   = formatCurrency(data.saldo_totale);
    document.getElementById('stat-entrate').textContent = formatCurrency(data.entrate_mese);
    document.getElementById('stat-uscite').textContent  = formatCurrency(data.uscite_mese);
  } catch (err) {
    showAlert('Errore nel caricamento del dashboard: ' + err.message);
  }
}

// ── Conti ─────────────────────────────────────────────────

async function loadConti() {
  try {
    const conti = await apiFetch(API.conti);
    renderConti(conti);
    populateContiSelect(conti);
  } catch (err) {
    showAlert('Errore nel caricamento dei conti: ' + err.message);
  }
}

function renderConti(conti) {
  const list = document.getElementById('lista-conti');

  if (!conti || conti.length === 0) {
    list.innerHTML = '<li class="list-group-item text-muted text-center py-3">Nessun conto presente</li>';
    return;
  }

  list.innerHTML = conti.map(c => `
    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
      <div class="d-flex align-items-center gap-2">
        <span class="conto-badge" style="background-color:${escHtml(c.colore)};"></span>
        <span class="fw-medium">${escHtml(c.nome_conto)}</span>
      </div>
      <span class="fw-semibold ${Number(c.saldo_attuale) >= 0 ? 'text-success' : 'text-danger'}">
        ${formatCurrency(c.saldo_attuale)}
      </span>
    </li>
  `).join('');
}

function populateContiSelect(conti) {
  const sel = document.getElementById('t-conto');
  const current = sel.value;
  sel.innerHTML = '<option value="">Seleziona...</option>' +
    (conti || []).map(c =>
      `<option value="${c.id}">${escHtml(c.nome_conto)}</option>`
    ).join('');
  if (current) sel.value = current;
}

async function handleContoSubmit(e) {
  e.preventDefault();
  const nome   = document.getElementById('conto-nome').value.trim();
  const saldo  = parseFloat(document.getElementById('conto-saldo').value) || 0;
  const colore = document.getElementById('conto-colore').value;

  if (!nome) {
    showAlert('Il nome del conto è obbligatorio');
    return;
  }

  try {
    await apiFetch(API.conti, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ nome_conto: nome, saldo_iniziale: saldo, colore }),
    });

    document.getElementById('form-conto').reset();
    document.getElementById('conto-colore').value = '#007bff';
    // Collapse the form
    bootstrap.Collapse.getOrCreateInstance(
      document.getElementById('form-nuovo-conto')
    ).hide();

    showAlert('Conto aggiunto con successo!', 'success');
    await refreshAll();
  } catch (err) {
    showAlert('Errore nella creazione del conto: ' + err.message);
  }
}

// ── Categorie ─────────────────────────────────────────────

async function loadCategorie() {
  try {
    const data = await apiFetch(API.categorie);
    populateCategorieSelect([...data.entrate, ...data.uscite]);
  } catch (err) {
    showAlert('Errore nel caricamento delle categorie: ' + err.message);
  }
}

function populateCategorieSelect(categorie) {
  const sel = document.getElementById('t-categoria');
  const current = sel.value;

  const entrate = categorie.filter(c => c.tipo === 'entrata');
  const uscite  = categorie.filter(c => c.tipo === 'uscita');

  sel.innerHTML = '<option value="">Seleziona...</option>' +
    (entrate.length ? `<optgroup label="Entrate">${entrate.map(c =>
      `<option value="${c.id}">${escHtml(c.nome)}</option>`
    ).join('')}</optgroup>` : '') +
    (uscite.length  ? `<optgroup label="Uscite">${uscite.map(c =>
      `<option value="${c.id}">${escHtml(c.nome)}</option>`
    ).join('')}</optgroup>` : '');

  if (current) sel.value = current;
}

// ── Transazioni ───────────────────────────────────────────

async function loadTransazioni() {
  try {
    const transazioni = await apiFetch(API.transazioni);
    renderTransazioni(transazioni);
  } catch (err) {
    showAlert('Errore nel caricamento delle transazioni: ' + err.message);
  }
}

function renderTransazioni(transazioni) {
  const tbody = document.getElementById('tbody-transazioni');

  if (!transazioni || transazioni.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Nessuna transazione</td></tr>';
    return;
  }

  tbody.innerHTML = transazioni.map(t => `
    <tr>
      <td class="text-nowrap">${formatDate(t.data)}</td>
      <td>${escHtml(t.descrizione || '—')}</td>
      <td>
        <span class="badge rounded-pill badge-${escHtml(t.categoria_tipo)} px-2">
          ${escHtml(t.categoria_nome)}
        </span>
      </td>
      <td>
        <span class="conto-badge me-1" style="background-color:${escHtml(t.conto_colore)};"></span>
        ${escHtml(t.nome_conto)}
      </td>
      <td class="text-end fw-semibold ${t.categoria_tipo === 'entrata' ? 'text-success' : 'text-danger'}">
        ${t.categoria_tipo === 'entrata' ? '+' : '-'}${formatCurrency(t.importo)}
      </td>
      <td class="text-end">
        <button
          class="btn btn-link btn-sm p-0 text-danger"
          data-id="${t.id}"
          title="Elimina transazione"
          aria-label="Elimina transazione"
        >
          <i class="bi bi-trash3"></i>
        </button>
      </td>
    </tr>
  `).join('');
}

async function handleDeleteTransazione(e) {
  const btn = e.target.closest('[data-id]');
  if (!btn) return;

  const id = btn.dataset.id;
  if (!confirm('Eliminare questa transazione?')) return;

  try {
    await apiFetch(`${API.transazioni}?id=${encodeURIComponent(id)}`, { method: 'DELETE' });
    showAlert('Transazione eliminata.', 'success');
    await refreshAll();
  } catch (err) {
    showAlert('Errore nell\'eliminazione: ' + err.message);
  }
}

async function handleTransazioneSubmit(e) {
  e.preventDefault();

  const contoId     = document.getElementById('t-conto').value;
  const categoriaId = document.getElementById('t-categoria').value;
  const importo     = parseFloat(document.getElementById('t-importo').value);
  const data        = document.getElementById('t-data').value;
  const descrizione = document.getElementById('t-descrizione').value.trim();

  if (!contoId || !categoriaId || !importo || importo <= 0 || !data) {
    const missing = [];
    if (!contoId)                   { missing.push('conto'); }
    if (!categoriaId)               { missing.push('categoria'); }
    if (!importo || importo <= 0)   { missing.push('importo (deve essere > 0)'); }
    if (!data)                      { missing.push('data'); }
    showAlert('Campi obbligatori mancanti o non validi: ' + missing.join(', ') + '.');
    return;
  }

  try {
    await apiFetch(API.transazioni, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        conto_id:     parseInt(contoId, 10),
        categoria_id: parseInt(categoriaId, 10),
        importo,
        data,
        descrizione,
      }),
    });

    document.getElementById('form-transazione').reset();
    setDefaultDate();
    showAlert('Transazione aggiunta con successo!', 'success');
    await refreshAll();
  } catch (err) {
    showAlert('Errore nell\'aggiunta della transazione: ' + err.message);
  }
}

// ── Utility ───────────────────────────────────────────────

function escHtml(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function setDefaultDate() {
  const today = new Date().toISOString().split('T')[0];
  document.getElementById('t-data').value = today;
}

async function refreshAll() {
  await Promise.all([loadDashboard(), loadConti(), loadTransazioni()]);
}

// ── Init ──────────────────────────────────────────────────

async function init() {
  setDefaultDate();

  // Event listeners
  document.getElementById('form-conto').addEventListener('submit', handleContoSubmit);
  document.getElementById('form-transazione').addEventListener('submit', handleTransazioneSubmit);
  document.getElementById('tbody-transazioni').addEventListener('click', handleDeleteTransazione);

  // Load data
  await Promise.all([
    loadDashboard(),
    loadConti(),
    loadCategorie(),
    loadTransazioni(),
  ]);

  // Hide loading overlay
  document.getElementById('loading-overlay').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', init);
