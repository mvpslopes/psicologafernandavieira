(function () {
  /* ---------- Sidebar mobile ---------- */
  var sidebar = document.getElementById("admin-sidebar");
  var toggle = document.getElementById("sidebar-toggle");
  var backdrop = document.getElementById("sidebar-backdrop");

  function closeSidebar() {
    if (!sidebar) return;
    sidebar.classList.remove("is-open");
    if (toggle) toggle.classList.remove("is-active");
    if (toggle) toggle.setAttribute("aria-expanded", "false");
    if (backdrop) backdrop.hidden = true;
    document.body.style.overflow = "";
  }

  function openSidebar() {
    if (!sidebar) return;
    sidebar.classList.add("is-open");
    if (toggle) toggle.classList.add("is-active");
    if (toggle) toggle.setAttribute("aria-expanded", "true");
    if (backdrop) backdrop.hidden = false;
    document.body.style.overflow = "hidden";
  }

  if (toggle && sidebar) {
    toggle.addEventListener("click", function () {
      if (sidebar.classList.contains("is-open")) closeSidebar();
      else openSidebar();
    });
  }
  if (backdrop) backdrop.addEventListener("click", closeSidebar);

  const root = document.getElementById("dashboard");
  if (!root) return;

  const statusEl = document.getElementById("dash-status");
  const kpiGrid = document.getElementById("kpi-grid");
  const notesEl = document.getElementById("dash-notes");
  const period = root.dataset.period || "30d";
  const charts = {};

  const palette = {
    sage: "#94a281",
    sageDark: "#565f49",
    rose: "#d99e8c",
    blush: "#fde9e1",
    muted: "#6f6455",
  };

  function formatDuration(seconds) {
    seconds = Math.max(0, Math.round(Number(seconds) || 0));
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    if (m <= 0) return s + "s";
    return m + "m " + String(s).padStart(2, "0") + "s";
  }

  function formatNumber(n) {
    return new Intl.NumberFormat("pt-BR").format(n);
  }

  function setStatus(text, type) {
    statusEl.hidden = false;
    statusEl.textContent = text;
    statusEl.classList.remove("is-error", "is-ok");
    if (type) statusEl.classList.add(type);
  }

  function destroyChart(key) {
    if (charts[key]) {
      charts[key].destroy();
      delete charts[key];
    }
  }

  function renderKpis(kpis, usingRealtime) {
    const cards = [
      {
        label: usingRealtime ? "Usuários agora" : "Visitantes Únicos",
        value: formatNumber(kpis.unique_visitors),
        sub: usingRealtime
          ? "Ativos nos últimos ~30 min"
          : "Total de visitas: " + formatNumber(kpis.sessions),
      },
      {
        label: "Visualizações",
        value: formatNumber(kpis.pageviews),
        sub: usingRealtime
          ? "Views em tempo real"
          : "Média por visita: " + kpis.avg_views_per_session,
      },
      {
        label: "Cliques WhatsApp",
        value: formatNumber(kpis.whatsapp_clicks || 0),
        sub: kpis.whatsapp_clicks
          ? "Eventos whatsapp_click"
          : "Aguardando eventos (já instrumentado no site)",
      },
      {
        label: "Tempo Médio de Sessão",
        value: formatDuration(kpis.avg_session_duration),
        sub: usingRealtime ? "Disponível no histórico (24–48h)" : "Duração média por sessão",
      },
      {
        label: "Taxa de Rejeição",
        value: kpis.bounce_rate + "%",
        sub: usingRealtime ? "Disponível no histórico (24–48h)" : "Sessões com pouco engajamento",
      },
      {
        label: "Taxa de Conversão",
        value: kpis.conversion_rate + "%",
        sub: "Cliques WhatsApp / sessões",
      },
      {
        label: "Páginas por Sessão",
        value: kpis.pages_per_session,
        sub: usingRealtime ? "Estimativa em tempo real" : "Média de páginas visitadas",
      },
      {
        label: "Eventos Totais",
        value: formatNumber(kpis.clicks),
        sub: usingRealtime ? "Eventos em tempo real" : "Interações registradas no GA4",
      },
    ];

    kpiGrid.hidden = false;
    kpiGrid.innerHTML = cards
      .map(
        (c) =>
          `<article class="kpi"><div class="label">${c.label}</div><div class="value">${c.value}</div><div class="sub">${c.sub}</div></article>`
      )
      .join("");
  }

  function renderBars(containerId, items, labelKey, valueKey) {
    const el = document.getElementById(containerId);
    if (!el) return;
    const max = Math.max(1, ...items.map((i) => Number(i[valueKey]) || 0));
    el.innerHTML = items
      .map((item) => {
        const value = Number(item[valueKey]) || 0;
        const pct = Math.round((value / max) * 100);
        return `<div class="bar-row"><span>${item[labelKey]}</span><div class="bar-track"><div class="bar-fill" style="width:${pct}%"></div></div><strong>${formatNumber(value)}</strong></div>`;
      })
      .join("");
  }

  function renderList(containerId, items, mapper) {
    const el = document.getElementById(containerId);
    if (!el) return;
    if (!items.length) {
      el.innerHTML = '<div class="list-item"><strong>Sem dados no período</strong></div>';
      return;
    }
    el.innerHTML = items.map(mapper).join("");
  }

  function makeBarChart(canvasId, key, labels, values, horizontal) {
    destroyChart(key);
    const ctx = document.getElementById(canvasId);
    if (!ctx || typeof Chart === "undefined") return;
    charts[key] = new Chart(ctx, {
      type: "bar",
      data: {
        labels,
        datasets: [
          {
            data: values,
            backgroundColor: palette.sage,
            borderRadius: 8,
            maxBarThickness: horizontal ? 18 : 28,
          },
        ],
      },
      options: {
        indexAxis: horizontal ? "y" : "x",
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: {
            grid: { display: horizontal, color: "rgba(74,66,56,0.06)" },
            ticks: { color: palette.muted, maxRotation: 0, autoSkip: true, maxTicksLimit: 12 },
          },
          y: {
            beginAtZero: true,
            grid: { color: "rgba(74,66,56,0.06)" },
            ticks: { color: palette.muted, precision: 0 },
          },
        },
      },
    });
  }

  function makeLineChart(canvasId, key, labels, values) {
    destroyChart(key);
    const ctx = document.getElementById(canvasId);
    if (!ctx || typeof Chart === "undefined") return;
    charts[key] = new Chart(ctx, {
      type: "line",
      data: {
        labels,
        datasets: [
          {
            data: values,
            borderColor: palette.sageDark,
            backgroundColor: "rgba(148,162,129,0.18)",
            fill: true,
            tension: 0.35,
            pointRadius: 3,
            pointBackgroundColor: palette.rose,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { ticks: { color: palette.muted, maxTicksLimit: 8 }, grid: { display: false } },
          y: { beginAtZero: true, ticks: { color: palette.muted, precision: 0 }, grid: { color: "rgba(74,66,56,0.06)" } },
        },
      },
    });
  }

  function makeDoughnut(canvasId, key, labels, values) {
    destroyChart(key);
    const ctx = document.getElementById(canvasId);
    if (!ctx || typeof Chart === "undefined") return;
    charts[key] = new Chart(ctx, {
      type: "doughnut",
      data: {
        labels,
        datasets: [
          {
            data: values,
            backgroundColor: [palette.sage, palette.rose, palette.blush, palette.sageDark],
            borderWidth: 0,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: "bottom", labels: { color: palette.muted, boxWidth: 12 } },
        },
        cutout: "62%",
      },
    });
  }

  function renderDashboard(data) {
    const usingRealtime = !!data.using_realtime;
    renderKpis(data.kpis || {}, usingRealtime);

    makeBarChart(
      "chart-hours",
      "hours",
      (data.peak_hours || []).map((i) => i.hour),
      (data.peak_hours || []).map((i) => i.value)
    );

    makeBarChart(
      "chart-weekdays",
      "weekdays",
      (data.weekdays || []).map((i) => i.day),
      (data.weekdays || []).map((i) => i.value)
    );

    makeLineChart(
      "chart-timeline",
      "timeline",
      (data.timeline || []).map((i) => i.date),
      (data.timeline || []).map((i) => i.users)
    );

    makeDoughnut(
      "chart-devices",
      "devices",
      (data.devices || []).map((i) => i.name),
      (data.devices || []).map((i) => i.value)
    );

    renderList("top-pages", data.top_pages || [], (item) => {
      return `<div class="list-item"><div><strong>${item.path}</strong><span class="meta">${formatDuration(item.avg_duration)} médio</span></div><span>${formatNumber(item.views)} visualizações</span></div>`;
    });

    renderList("devices-list", data.devices || [], (item) => {
      return `<div class="list-item"><strong>${item.name}</strong><span>${formatNumber(item.value)} · ${item.percent}%</span></div>`;
    });

    renderList("browsers-list", data.browsers || [], (item) => {
      return `<div class="list-item"><strong>${item.name}</strong><span>${formatNumber(item.value)}</span></div>`;
    });

    renderList("os-list", data.os || [], (item) => {
      return `<div class="list-item"><strong>${item.name}</strong><span>${formatNumber(item.value)}</span></div>`;
    });

    renderList("sources-list", data.sources || [], (item) => {
      return `<div class="list-item"><strong>${item.name}</strong><span>${formatNumber(item.value)}</span></div>`;
    });

    renderList("landings-list", data.landings || [], (item) => {
      return `<div class="list-item"><strong>${item.path}</strong><span>${formatNumber(item.sessions)} entradas</span></div>`;
    });

    renderList("exits-list", data.exits || [], (item) => {
      return `<div class="list-item"><strong>${item.path}</strong><span>${formatNumber(item.sessions)} · rejeição ${item.bounce_rate}%</span></div>`;
    });

    renderList("countries-list", data.countries || [], (item) => {
      return `<div class="list-item"><strong>${item.name}</strong><span>${formatNumber(item.sessions)} sessões · ${formatNumber(item.views)} views</span></div>`;
    });

    renderList("cities-list", data.cities || [], (item) => {
      return `<div class="list-item"><div><strong>${item.city}</strong><span class="meta">${item.country}</span></div><span>${formatNumber(item.sessions)} sessões · ${formatNumber(item.views)} views</span></div>`;
    });

    const notes = (data.notes || []).join(" ");
    notesEl.textContent =
      notes +
      (data.cached ? " Dados em cache (até 5 min)." : "") +
      (data.property_id ? " Propriedade GA4: " + data.property_id + "." : "");

    if (usingRealtime) {
      setStatus(
        "Tracking OK — exibindo tempo real (histórico do GA4 ainda processando, costuma levar 24–48h).",
        "is-ok"
      );
    } else {
      setStatus("Dados atualizados com sucesso.", "is-ok");
    }
  }

  async function load() {
    setStatus("Carregando dados do Google Analytics…");
    try {
      const apiBase = root.dataset.apiBase || "api/stats.php";
      const res = await fetch(apiBase + "?period=" + encodeURIComponent(period), {
        credentials: "same-origin",
      });
      const data = await res.json();
      if (!res.ok || !data.ok) {
        throw new Error(data.error || "Falha ao carregar estatísticas");
      }
      renderDashboard(data);
    } catch (err) {
      setStatus(
        (err && err.message ? err.message : "Erro ao carregar") +
          " — confira o acesso da service account na propriedade GA4 e o arquivo admin/data/service-account.json.",
        "is-error"
      );
    }
  }

  load();
})();
