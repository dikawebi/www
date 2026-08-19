document.getElementById("year").textContent = new Date().getFullYear();

async function loadApps() {
  const grid = document.getElementById("app-grid");

  try {
    const res = await fetch("data/apps.json", { cache: "no-store" });
    if (!res.ok) throw new Error("Failed to load apps.json");
    const apps = await res.json();

    if (!Array.isArray(apps) || apps.length === 0) {
      grid.innerHTML = '<p class="loading">No apps configured yet.</p>';
      return;
    }

    grid.innerHTML = apps.map(renderCard).join("");
  } catch (err) {
    grid.innerHTML = `<p class="loading">Could not load app list. (${err.message})</p>`;
    console.error(err);
  }
}

function renderCard(app) {
  const status = (app.status || "active").toLowerCase();
  const isDisabled = status === "maintenance" || status === "disabled";
  const tag = isDisabled ? "div" : "a";
  const hrefAttr = isDisabled ? "" : `href="${escapeAttr(app.url)}"`;
  const targetAttr = isDisabled ? "" : `target="_blank" rel="noopener noreferrer"`;
  const iconMarkup = app.logo
    ? `<img src="${escapeAttr(app.logo)}" alt="${escapeAttr(app.name)} logo" />`
    : escapeHtml(app.icon || "🔗");

  return `
    <${tag} class="app-card ${isDisabled ? "disabled" : ""}" ${hrefAttr} ${targetAttr}>
      <div class="app-icon">${iconMarkup}</div>
      <div class="app-name">${escapeHtml(app.name)}</div>
      <div class="app-desc">${escapeHtml(app.description || "")}</div>
      <span class="app-status ${status}">${status}</span>
    </${tag}>
  `;
}

function escapeHtml(str = "") {
  return str.replace(/[&<>"']/g, (c) => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
  }[c]));
}

function escapeAttr(str = "") {
  return escapeHtml(str);
}

loadApps();
