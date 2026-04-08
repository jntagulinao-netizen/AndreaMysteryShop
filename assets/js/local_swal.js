(function () {
  let overlay;

  function ensure() {
    if (overlay) return overlay;
    overlay = document.getElementById('localSwal');
    if (overlay) return overlay;

    overlay = document.createElement('div');
    overlay.id = 'localSwal';
    overlay.className = 'swal-overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-live', 'polite');
    overlay.innerHTML = '' +
      '<div class="swal-card">' +
      '  <div id="localSwalIcon" class="swal-icon success">✓</div>' +
      '  <div id="localSwalTitle" class="swal-title">Done</div>' +
      '  <div id="localSwalText" class="swal-text"></div>' +
      '  <div id="localSwalActions" class="swal-actions">' +
      '    <button type="button" id="localSwalConfirm" class="swal-btn primary">OK</button>' +
      '  </div>' +
      '</div>';
    document.body.appendChild(overlay);
    return overlay;
  }

  function iconSymbol(icon) {
    if (icon === 'error') return '!';
    if (icon === 'warning') return '!';
    return '✓';
  }

  function showAlert(icon, title, text) {
    if (typeof document === 'undefined') {
      // Non-DOM fallback.
      // eslint-disable-next-line no-alert
      alert(text || title || 'Notice');
      return Promise.resolve();
    }

    const root = ensure();
    const iconEl = document.getElementById('localSwalIcon');
    const titleEl = document.getElementById('localSwalTitle');
    const textEl = document.getElementById('localSwalText');
    const actionsEl = document.getElementById('localSwalActions');

    iconEl.className = 'swal-icon ' + (icon || 'success');
    iconEl.textContent = iconSymbol(icon || 'success');
    titleEl.textContent = title || 'Notice';
    textEl.textContent = text || '';

    actionsEl.className = 'swal-actions';
    actionsEl.innerHTML = '<button type="button" id="localSwalConfirm" class="swal-btn primary">OK</button>';

    root.classList.add('show');

    return new Promise((resolve) => {
      const close = () => {
        root.classList.remove('show');
        resolve();
      };
      document.getElementById('localSwalConfirm').onclick = close;
      root.onclick = (event) => {
        if (event.target === root) close();
      };
    });
  }

  function showConfirm(title, text, confirmText) {
    if (typeof document === 'undefined') {
      // eslint-disable-next-line no-alert
      return Promise.resolve(confirm(text || title || 'Confirm?'));
    }

    const root = ensure();
    const iconEl = document.getElementById('localSwalIcon');
    const titleEl = document.getElementById('localSwalTitle');
    const textEl = document.getElementById('localSwalText');
    const actionsEl = document.getElementById('localSwalActions');

    iconEl.className = 'swal-icon warning';
    iconEl.textContent = iconSymbol('warning');
    titleEl.textContent = title || 'Please Confirm';
    textEl.textContent = text || '';

    actionsEl.className = 'swal-actions two';
    actionsEl.innerHTML = '' +
      '<button type="button" id="localSwalCancel" class="swal-btn secondary">Cancel</button>' +
      '<button type="button" id="localSwalConfirm" class="swal-btn primary">' + (confirmText || 'Confirm') + '</button>';

    root.classList.add('show');

    return new Promise((resolve) => {
      const close = (answer) => {
        root.classList.remove('show');
        resolve(Boolean(answer));
      };
      document.getElementById('localSwalCancel').onclick = () => close(false);
      document.getElementById('localSwalConfirm').onclick = () => close(true);
      root.onclick = (event) => {
        if (event.target === root) close(false);
      };
    });
  }

  window.localSwalAlert = showAlert;
  window.localSwalConfirm = showConfirm;
})();
