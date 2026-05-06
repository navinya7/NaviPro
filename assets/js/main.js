// FlashRide - Main JavaScript

// Toast notifications
const Toast = {
  show(type, title, msg, duration = 4000) {
    const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
    const container = document.getElementById('toast-container') || (() => {
      const el = document.createElement('div'); el.id = 'toast-container'; document.body.appendChild(el); return el;
    })();
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<span class="toast-icon">${icons[type] || 'ℹ️'}</span><div><div class="toast-title">${title}</div><div class="toast-msg">${msg}</div></div>`;
    container.appendChild(toast);
    setTimeout(() => { toast.style.animation = 'toastIn .3s ease reverse'; setTimeout(() => toast.remove(), 300); }, duration);
  },
  success: (t, m) => Toast.show('success', t, m),
  error:   (t, m) => Toast.show('error', t, m),
  warning: (t, m) => Toast.show('warning', t, m),
  info:    (t, m) => Toast.show('info', t, m),
};

// Loading
const Loader = {
  show(msg = 'Please wait...') {
    let el = document.getElementById('loadingOverlay');
    if (!el) {
      el = document.createElement('div');
      el.id = 'loadingOverlay';
      el.className = 'loading-overlay';
      el.innerHTML = `<div class="spinner"></div><p class="loading-text">${msg}</p>`;
      document.body.appendChild(el);
    }
    el.querySelector('.loading-text').textContent = msg;
    el.classList.add('show');
  },
  hide() { document.getElementById('loadingOverlay')?.classList.remove('show'); }
};

// Modal
const Modal = {
  open(id) { document.getElementById(id)?.classList.add('open'); },
  close(id) { document.getElementById(id)?.classList.remove('open'); },
  init() {
    document.querySelectorAll('[data-modal]').forEach(btn => btn.addEventListener('click', () => Modal.open(btn.dataset.modal)));
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
      overlay.addEventListener('click', e => { if (e.target === overlay) overlay.classList.remove('open'); });
    });
    document.querySelectorAll('.modal-close').forEach(btn => btn.addEventListener('click', () => btn.closest('.modal-overlay').classList.remove('open')));
  }
};

// Tabs
function initTabs() {
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = btn.dataset.tab;
      btn.closest('.tabs').querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      document.querySelectorAll('[data-tab-content]').forEach(c => {
        c.classList.toggle('hidden', c.dataset.tabContent !== target);
      });
    });
  });
}

// Mobile sidebar toggle
function initSidebar() {
  const toggle = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  if (toggle && sidebar) {
    toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
    document.addEventListener('click', e => {
      if (!sidebar.contains(e.target) && !toggle.contains(e.target)) sidebar.classList.remove('open');
    });
  }
}

// Star rating
function initStarRating(containerId, inputId) {
  const container = document.getElementById(containerId);
  const input = document.getElementById(inputId);
  if (!container || !input) return;
  container.querySelectorAll('.star-btn').forEach((star, idx) => {
    star.addEventListener('click', () => {
      input.value = idx + 1;
      container.querySelectorAll('.star-btn').forEach((s, i) => s.classList.toggle('active', i <= idx));
    });
    star.addEventListener('mouseenter', () => {
      container.querySelectorAll('.star-btn').forEach((s, i) => s.style.color = i <= idx ? 'var(--warning)' : '');
    });
  });
  container.addEventListener('mouseleave', () => {
    const val = parseInt(input.value) || 0;
    container.querySelectorAll('.star-btn').forEach((s, i) => { s.style.color = ''; s.classList.toggle('active', i < val); });
  });
}

// Fare calculator (client-side preview)
const FareCalc = {
  calculate(distKm, durationMin, baseFare, perKm, perMin, minFare, surgeMultiplier = 1.0) {
    const raw = (baseFare + distKm * perKm + durationMin * perMin) * surgeMultiplier;
    return Math.max(raw, minFare);
  }
};

// Live map helpers
const MapHelper = {
  map: null,
  pickupMarker: null,
  dropMarker: null,
  driverMarker: null,
  routeLine: null,

  initMap(containerId, lat = 18.5204, lng = 73.8567, zoom = 13) {
    if (this.map) { this.map.remove(); this.map = null; }
    this.map = L.map(containerId, { zoomControl: false }).setView([lat, lng], zoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap',
      maxZoom: 19
    }).addTo(this.map);
    L.control.zoom({ position: 'bottomright' }).addTo(this.map);
    return this.map;
  },

  addMarker(lat, lng, type = 'pickup', label = '') {
    const colors = { pickup: '#00E676', drop: '#FF6B00', driver: '#00C8FF', user: '#7C3AED' };
    const color = colors[type] || '#FF6B00';
    const icon = L.divIcon({
      className: '',
      html: `<div style="width:14px;height:14px;background:${color};border-radius:50%;border:2px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.4)"></div>`,
      iconSize: [14, 14], iconAnchor: [7, 7]
    });
    return L.marker([lat, lng], { icon }).addTo(this.map).bindPopup(label);
  },

  drawRoute(coords) {
    if (this.routeLine) this.map.removeLayer(this.routeLine);
    this.routeLine = L.polyline(coords, { color: '#FF6B00', weight: 4, opacity: .9, dashArray: '8,4' }).addTo(this.map);
    this.map.fitBounds(this.routeLine.getBounds(), { padding: [40, 40] });
  },

  geocodeAddress(address, callback) {
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`)
      .then(r => r.json())
      .then(data => {
        if (data.length > 0) callback({ lat: parseFloat(data[0].lat), lng: parseFloat(data[0].lon), display: data[0].display_name });
        else callback(null);
      })
      .catch(() => callback(null));
  },

  reverseGeocode(lat, lng, callback) {
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
      .then(r => r.json())
      .then(data => callback(data.display_name || `${lat.toFixed(4)}, ${lng.toFixed(4)}`))
      .catch(() => callback(`${lat.toFixed(4)}, ${lng.toFixed(4)}`));
  },

  getUserLocation(callback) {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        pos => callback({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
        () => callback({ lat: 18.5204, lng: 73.8567 }) // fallback Pune
      );
    } else {
      callback({ lat: 18.5204, lng: 73.8567 });
    }
  }
};

// Auto-suggest address
function initAddressSuggest(inputId, resultsId, onSelect) {
  const input = document.getElementById(inputId);
  const results = document.getElementById(resultsId);
  if (!input || !results) return;
  let timer;
  input.addEventListener('input', () => {
    clearTimeout(timer);
    const q = input.value.trim();
    if (q.length < 3) { results.innerHTML = ''; results.style.display = 'none'; return; }
    timer = setTimeout(() => {
      fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}&limit=5&countrycodes=in`)
        .then(r => r.json())
        .then(data => {
          results.innerHTML = data.map(d => `<div class="suggest-item" data-lat="${d.lat}" data-lng="${d.lon}" data-name="${d.display_name}">${d.display_name}</div>`).join('');
          results.style.display = data.length ? 'block' : 'none';
          results.querySelectorAll('.suggest-item').forEach(item => {
            item.addEventListener('click', () => {
              input.value = item.dataset.name;
              results.innerHTML = ''; results.style.display = 'none';
              onSelect && onSelect({ lat: parseFloat(item.dataset.lat), lng: parseFloat(item.dataset.lng), address: item.dataset.name });
            });
          });
        });
    }, 400);
  });
  document.addEventListener('click', e => { if (!results.contains(e.target) && e.target !== input) { results.innerHTML = ''; results.style.display = 'none'; } });
}

// API helper
const API = {
  async post(url, data) {
    const res = await fetch(url, {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    return res.json();
  },
  async get(url) {
    const res = await fetch(url);
    return res.json();
  }
};

// Format currency
function fmtCurrency(n) { return '₹' + parseFloat(n).toFixed(2); }

// Countdown timer
function startCountdown(seconds, elementId, onEnd) {
  const el = document.getElementById(elementId);
  if (!el) return;
  let s = seconds;
  const tick = () => {
    const m = Math.floor(s / 60), sec = s % 60;
    el.textContent = `${m}:${sec.toString().padStart(2,'0')}`;
    if (s-- <= 0) { clearInterval(iv); onEnd && onEnd(); }
  };
  tick();
  const iv = setInterval(tick, 1000);
  return iv;
}

// Dark mode (always dark for FlashRide, but keep hook)
document.documentElement.classList.add('dark-mode');

// Init on DOM ready
document.addEventListener('DOMContentLoaded', () => {
  Modal.init();
  initTabs();
  initSidebar();
  // Toast container
  if (!document.getElementById('toast-container')) {
    const tc = document.createElement('div'); tc.id = 'toast-container'; document.body.appendChild(tc);
  }
  // Animate elements with data-animate
  const observer = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('fade-in'); observer.unobserve(e.target); } });
  }, { threshold: 0.1 });
  document.querySelectorAll('[data-animate]').forEach(el => observer.observe(el));
  // Address suggest styles
  const style = document.createElement('style');
  style.textContent = `.suggest-dropdown{position:absolute;top:100%;left:0;right:0;z-index:1000;background:var(--card);border:1px solid var(--border);border-radius:var(--radius-sm);max-height:220px;overflow-y:auto}.suggest-item{padding:.75rem 1rem;cursor:pointer;font-size:.88rem;border-bottom:1px solid var(--border);color:var(--text)}.suggest-item:hover{background:var(--dark-3);color:var(--primary)}.suggest-item:last-child{border-bottom:none}`;
  document.head.appendChild(style);
});

window.Toast = Toast;
window.Loader = Loader;
window.Modal = Modal;
window.MapHelper = MapHelper;
window.API = API;
window.FareCalc = FareCalc;
window.fmtCurrency = fmtCurrency;
window.startCountdown = startCountdown;
window.initAddressSuggest = initAddressSuggest;
window.initStarRating = initStarRating;

