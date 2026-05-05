<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Master de Presentes</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 0; background: #f5f7fb; color: #1f2937; }
    .wrap { max-width: 1200px; margin: 24px auto; padding: 0 14px; }
    .card { background: #fff; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,.08); overflow: hidden; }
    .head { padding: 16px; border-bottom: 1px solid #e5e7eb; display:flex; justify-content:space-between; align-items:center; gap: 8px; flex-wrap: wrap; }
    .head h1 { margin: 0; font-size: 1.15rem; }
    .head-actions { display:flex; gap:8px; flex-wrap:wrap; }
    button { border: none; border-radius: 999px; padding: 8px 12px; cursor: pointer; color: #fff; background:#70A076; }
    button.danger { background:#b42318; }
    button.secondary { background:#475467; }
    button.warn { background:#b54708; }
    button:disabled { opacity: .65; cursor: not-allowed; }
    .content { padding: 12px; }
    .feedback { min-height: 24px; margin: 8px 0 0; font-size: .92rem; }
    .feedback.error { color:#b42318; }
    .feedback.ok { color:#067647; }
    .muted { color: #667085; }
    .table-wrap { width:100%; overflow:auto; border:1px solid #eaecf0; border-radius:10px; }
    table { width:100%; border-collapse: collapse; min-width: 980px; }
    th, td { padding: 10px 8px; border-bottom: 1px solid #eaecf0; text-align:left; font-size:.9rem; vertical-align: top; }
    th { background:#f9fafb; white-space: nowrap; }
    .badge { display:inline-block; padding:3px 8px; border-radius:999px; font-size:.78rem; font-weight:700; }
    .badge.blocked { background:#fee4e2; color:#b42318; }
    .badge.active { background:#dcfae6; color:#067647; }
    .row-actions { display:flex; flex-wrap: wrap; gap:6px; }
    .hidden { display:none; }
    .login-panel { max-width: 420px; margin: 70px auto; }
    .login-body { padding: 20px; display: flex; flex-direction: column; gap: 10px; }
    .login-body input { border: 1px solid #d0d5dd; border-radius: 8px; padding: 10px 12px; font-size: .95rem; }

    @media (max-width: 768px) {
      .head h1 { font-size: 1rem; }
      .head-actions { width: 100%; }
      .head-actions button { flex: 1; }
      table { min-width: 0; }
      thead { display:none; }
      tbody, tr, td { display:block; width:100%; }
      tr { padding:10px; border-bottom:1px solid #eaecf0; }
      td { border:none; padding:6px 0; }
      td::before { content: attr(data-label); display:block; font-size:.78rem; color:#667085; margin-bottom: 2px; }
      .row-actions button { flex: 1; }
    }
  </style>
</head>
<body>
<div class="wrap">
  <div id="login-panel" class="card login-panel hidden">
    <div class="head"><h1>Login Master</h1></div>
    <form id="login-form" class="login-body">
      <label for="username">Utilizador</label>
      <input id="username" type="text" placeholder="admin" required>
      <label for="password">Senha</label>
      <input id="password" type="password" placeholder="••••••••" required>
      <small id="login-feedback" class="feedback error"></small>
      <button type="submit">Entrar</button>
    </form>
  </div>

  <div id="master-card" class="card hidden">
    <div class="head">
      <h1>Painel Master de Presentes</h1>
      <div class="head-actions">
        <button id="refresh-btn" type="button">Atualizar</button>
        <button id="logout-btn" class="danger" type="button">Sair</button>
      </div>
    </div>
    <div class="content">
      <p class="muted">Aqui pode gerir presentes oferecidos: apagar registos e reativar/voltar a bloquear referências.</p>
      <div id="feedback" class="feedback"></div>
      <div id="table-container"></div>
      <h2 style="margin:16px 0 8px;">Produtos do site</h2>
      <p class="muted">Também pode marcar como <strong>Oferecido</strong> ou <strong>Anular</strong> para qualquer produto da lista principal.</p>
      <div id="products-container"></div>
    </div>
  </div>
</div>

<script>
  const loginPanel = document.getElementById('login-panel')
  const masterCard = document.getElementById('master-card')
  const loginForm = document.getElementById('login-form')
  const loginFeedback = document.getElementById('login-feedback')
  const feedbackEl = document.getElementById('feedback')
  const tableContainer = document.getElementById('table-container')
  const productsContainer = document.getElementById('products-container')
  const refreshBtn = document.getElementById('refresh-btn')
  const logoutBtn = document.getElementById('logout-btn')

  let itemsCache = []
  let blockedSet = new Set()
  let siteProductsCache = []

  const escapeHtml = value => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')

  const formatDate = iso => {
    if (!iso) return '-'
    const date = new Date(iso)
    if (Number.isNaN(date.getTime())) return iso
    return date.toLocaleString('pt-PT')
  }

  const setFeedback = (message, type = '') => {
    feedbackEl.textContent = message || ''
    feedbackEl.className = `feedback ${type}`.trim()
  }

  async function checkSession() {
    const response = await fetch('admin-auth.php?action=status', { cache: 'no-store' })
    const data = await response.json()
    return Boolean(response.ok && data.ok && data.logged_in)
  }

  async function login(username, password) {
    const response = await fetch('admin-auth.php?action=login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, password })
    })

    const data = await response.json().catch(() => ({ ok: false, message: 'Resposta inválida do servidor.' }))
    if (!response.ok || !data.ok) {
      throw new Error(data.message || 'Falha no login.')
    }
  }

  async function logout() {
    await fetch('admin-auth.php?action=logout', { method: 'POST' })
    showLogin()
  }

  const showLogin = () => {
    loginPanel.classList.remove('hidden')
    masterCard.classList.add('hidden')
  }

  const showMaster = () => {
    loginPanel.classList.add('hidden')
    masterCard.classList.remove('hidden')
  }

  function renderTable() {
    if (!itemsCache.length) {
      tableContainer.innerHTML = '<p class="muted">Ainda não há presentes enviados.</p>'
      return
    }

    const rows = itemsCache.map(item => {
      const reference = String(item.product_reference || '')
      const blocked = blockedSet.has(reference)
      return `
        <tr>
          <td data-label="Data">${escapeHtml(formatDate(item.submitted_at))}</td>
          <td data-label="Nome">${escapeHtml(item.sender_name)}</td>
          <td data-label="Presente">${escapeHtml(item.product_name)}</td>
          <td data-label="Referência">${escapeHtml(reference)}</td>
          <td data-label="Estado"><span class="badge ${blocked ? 'blocked' : 'active'}">${blocked ? 'Oferecido' : 'Ativo'}</span></td>
          <td data-label="Comprovativo"><a href="${escapeHtml(item.proof_url)}" target="_blank" rel="noopener">Ver PDF</a></td>
          <td data-label="Ações">
            <div class="row-actions">
              <button class="danger" data-action="delete" data-id="${escapeHtml(item.id)}">Apagar</button>
              ${blocked
                ? `<button class="secondary" data-action="reactivate" data-reference="${escapeHtml(reference)}">Reativar</button>`
                : `<button class="warn" data-action="deactivate" data-reference="${escapeHtml(reference)}">Bloquear</button>`}
            </div>
          </td>
        </tr>
      `
    }).join('')

    tableContainer.innerHTML = `
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Data</th>
              <th>Nome</th>
              <th>Presente</th>
              <th>Referência</th>
              <th>Estado</th>
              <th>Comprovativo</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>
      </div>
    `
  }

  const getGiftReferenceBaseCode = value => {
    const text = String(value || '')
    let hash = 0
    for (let index = 0; index < text.length; index += 1) {
      hash = (hash * 31 + text.charCodeAt(index)) % 9000
    }
    return String(hash + 1000).padStart(4, '0')
  }

  async function loadSiteProductsFromScript() {
    const response = await fetch('assets/js/script.js', { cache: 'no-store' })
    const source = await response.text()
    const match = source.match(/const\\s+defaultGiftProducts\\s*=\\s*\\[([\\s\\S]*?)\\]\\s*\\n\\s*const\\s+normalizeGiftProduct/)
    if (!match) return []
    const arrayCode = `[${match[1]}]`
    const list = Function(`"use strict"; return (${arrayCode});`)()
    if (!Array.isArray(list)) return []

    return list.map((product, index) => {
      const name = String((product && product.name) || `Produto-${index + 1}`)
      const reference = getGiftReferenceBaseCode(`${name}-${index}`)
      return {
        name,
        behavior: String((product && product.behavior) || 'popup'),
        price: Number((product && product.price) || 0),
        reference
      }
    })
  }

  function renderProductsTable() {
    if (!siteProductsCache.length) {
      productsContainer.innerHTML = '<p class="muted">Não foi possível carregar os produtos do site.</p>'
      return
    }

    const rows = siteProductsCache.map(item => {
      const blocked = blockedSet.has(item.reference)
      return `
      <tr>
        <td data-label="Produto">${escapeHtml(item.name)}</td>
        <td data-label="Tipo">${escapeHtml(item.behavior)}</td>
        <td data-label="Referência">${escapeHtml(item.reference)}</td>
        <td data-label="Estado"><span class="badge ${blocked ? 'blocked' : 'active'}">${blocked ? 'Oferecido' : 'Ativo'}</span></td>
        <td data-label="Ações">
          <div class="row-actions">
            ${blocked
              ? `<button class="secondary" data-action="reactivate" data-reference="${escapeHtml(item.reference)}">Anular</button>`
              : `<button class="warn" data-action="deactivate" data-reference="${escapeHtml(item.reference)}">Oferecido</button>`}
          </div>
        </td>
      </tr>
      `
    }).join('')

    productsContainer.innerHTML = `
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Produto</th>
              <th>Tipo</th>
              <th>Referência</th>
              <th>Estado</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>
      </div>
    `
  }

  async function loadData() {
    setFeedback('A carregar dados...')
    const response = await fetch('master-gifts.php', { cache: 'no-store' })
    const data = await response.json().catch(() => ({ ok: false, message: 'Resposta inválida do servidor.' }))

    if (!response.ok || !data.ok) {
      throw new Error(data.message || 'Falha ao carregar dados do master.')
    }

    itemsCache = Array.isArray(data.items) ? data.items : []
    blockedSet = new Set(Array.isArray(data.blocked_references) ? data.blocked_references.map(String) : [])
    renderTable()
    renderProductsTable()
    setFeedback('Dados atualizados com sucesso.', 'ok')
  }

  async function postAction(payload) {
    const response = await fetch('master-gifts.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })

    const data = await response.json().catch(() => ({ ok: false, message: 'Resposta inválida do servidor.' }))
    if (!response.ok || !data.ok) {
      throw new Error(data.message || 'Falha ao executar ação.')
    }

    return data
  }

  tableContainer.addEventListener('click', async event => {
    const button = event.target.closest('button[data-action]')
    if (!button) return

    const action = button.dataset.action
    const id = button.dataset.id || ''
    const reference = button.dataset.reference || ''

    try {
      button.disabled = true

      if (action === 'delete') {
        await postAction({ action: 'delete_submission', id })
        setFeedback('Registo apagado com sucesso.', 'ok')
      } else if (action === 'reactivate') {
        await postAction({ action: 'reactivate_reference', reference })
        setFeedback(`Presente ${reference} reativado com sucesso.`, 'ok')
      } else if (action === 'deactivate') {
        await postAction({ action: 'deactivate_reference', reference })
        setFeedback(`Presente ${reference} bloqueado com sucesso.`, 'ok')
      }

      await loadData()
      if (!siteProductsCache.length) {
        siteProductsCache = await loadSiteProductsFromScript()
        renderProductsTable()
      }
    } catch (error) {
      setFeedback(error.message || 'Erro inesperado.', 'error')
    } finally {
      button.disabled = false
    }
  })

  loginForm.addEventListener('submit', async event => {
    event.preventDefault()
    loginFeedback.textContent = ''

    const username = (document.getElementById('username').value || '').trim()
    const password = (document.getElementById('password').value || '').trim()

    try {
      await login(username, password)
      showMaster()
      await loadData()
      siteProductsCache = await loadSiteProductsFromScript()
      renderProductsTable()
    } catch (error) {
      loginFeedback.textContent = error.message || 'Credenciais inválidas.'
    }
  })

  refreshBtn.addEventListener('click', async () => {
    try {
      await loadData()
      if (!siteProductsCache.length) {
        siteProductsCache = await loadSiteProductsFromScript()
      }
      renderProductsTable()
    } catch (error) {
      setFeedback(error.message || 'Erro ao atualizar dados.', 'error')
    }
  })

  logoutBtn.addEventListener('click', logout)

  ;(async () => {
    const loggedIn = await checkSession().catch(() => false)
    if (!loggedIn) {
      showLogin()
      return
    }

    showMaster()
    try {
      await loadData()
      siteProductsCache = await loadSiteProductsFromScript()
      renderProductsTable()
    } catch (error) {
      setFeedback(error.message || 'Erro ao carregar dados.', 'error')
    }
  })()
</script>
</body>
</html>
