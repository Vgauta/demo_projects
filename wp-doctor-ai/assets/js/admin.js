(function () {
  'use strict';

  var api = window.WPDoctorAIAdmin;
  if (!api) return;

  function request(path, options) {
    options = options || {};
    options.headers = Object.assign({ 'Content-Type': 'application/json', 'X-WP-Nonce': api.nonce }, options.headers || {});
    options.credentials = 'same-origin';
    return fetch(api.restUrl + path, options).then(function (response) { return response.json(); });
  }

  function selectedLanguage() {
    var custom = document.querySelector('[data-wpda-custom-language]');
    var picker = document.querySelector('[data-wpda-language]');
    return (custom && custom.value.trim()) || (picker && picker.value) || api.defaultLanguage || 'en';
  }

  function writeMessage(holder, className, title, lines, link) {
    var box = holder.querySelector('.' + className) || document.createElement('div');
    box.className = className;
    box.textContent = '';

    var strong = document.createElement('strong');
    strong.textContent = title || '';
    box.appendChild(strong);

    (lines || []).forEach(function (line) {
      if (!line) return;
      var paragraph = document.createElement('p');
      paragraph.textContent = line;
      box.appendChild(paragraph);
    });

    if (link) {
      var anchor = document.createElement('a');
      anchor.className = 'button button-primary';
      anchor.href = link;
      anchor.target = '_blank';
      anchor.rel = 'noopener noreferrer';
      anchor.textContent = api.strings && api.strings.upgradeCheckout ? api.strings.upgradeCheckout : 'Upgrade checkout';
      box.appendChild(anchor);
    }

    holder.appendChild(box);
  }

  function bindIssueToggles() {
    document.querySelectorAll('.wpda-issue-toggle').forEach(function (button) {
      button.addEventListener('click', function () { button.closest('.wpda-issue').classList.toggle('open'); });
    });
  }

  function bindFilters() {
    document.querySelectorAll('[data-filter]').forEach(function (control) {
      control.addEventListener('input', function () {
        var severity = (document.querySelector('[data-filter="severity"]') || {}).value || '';
        var type = (document.querySelector('[data-filter="issue_type"]') || {}).value || '';
        var plugin = ((document.querySelector('[data-filter="affected_plugin"]') || {}).value || '').toLowerCase();
        document.querySelectorAll('.wpda-issue').forEach(function (issue) {
          var show = (!severity || issue.dataset.severity === severity) && (!type || issue.dataset.type === type) && (!plugin || issue.dataset.plugin.indexOf(plugin) !== -1);
          issue.style.display = show ? '' : 'none';
        });
      });
    });
  }

  function bindExplain() {
    document.querySelectorAll('.wpda-explain-actions button[data-mode]').forEach(function (button) {
      button.addEventListener('click', function () {
        var holder = button.closest('.wpda-explain-actions');
        var issue = JSON.parse(holder.getAttribute('data-issue'));
        holder.setAttribute('data-last-mode', button.dataset.mode);
        request('/explain', { method: 'POST', body: JSON.stringify({ issue: issue, mode: button.dataset.mode, language: selectedLanguage(), advanced: false }) }).then(function (data) {
          writeMessage(holder, 'wpda-explanation', data.headline, [data.summary, data.probable_cause, data.safe_fix, data.impact]);
        });
      });
    });
  }

  function bindSolutions() {
    document.querySelectorAll('.wpda-one-click-solution').forEach(function (button) {
      button.addEventListener('click', function () {
        var holder = button.closest('.wpda-explain-actions');
        var issue = JSON.parse(holder.getAttribute('data-issue'));
        button.disabled = true;
        holder.setAttribute('data-last-solution', '1');
        request('/solution', { method: 'POST', body: JSON.stringify({ issue: issue, language: selectedLanguage() }) }).then(function (data) {
          var title = data.needs_api_key ? (api.strings && api.strings.apiKeyRequired ? api.strings.apiKeyRequired : 'API key required') : (data.premium_required ? (api.strings && api.strings.premiumRequired ? api.strings.premiumRequired : 'Premium required') : (api.strings && api.strings.oneClickSolution ? api.strings.oneClickSolution : 'One-click solution'));
          var lines = [data.message, data.payment_note].concat(data.safe_steps || []);
          writeMessage(holder, 'wpda-solution', title, lines, data.checkout_url || '');
        }).finally(function () {
          button.disabled = false;
        });
      });
    });
  }



  function bindLanguagePicker() {
    var picker = document.querySelector('[data-wpda-language]');
    var custom = document.querySelector('[data-wpda-custom-language]');
    if (!picker && !custom) return;

    function refreshMessages() {
      document.querySelectorAll('.wpda-explain-actions[data-last-mode]').forEach(function (holder) {
        var mode = holder.getAttribute('data-last-mode');
        var button = holder.querySelector('button[data-mode="' + mode + '"]');
        if (button) button.click();
      });

      document.querySelectorAll('.wpda-explain-actions[data-last-solution="1"]').forEach(function (holder) {
        var button = holder.querySelector('.wpda-one-click-solution');
        if (button) button.click();
      });
    }

    if (picker) picker.addEventListener('change', refreshMessages);
    if (custom) custom.addEventListener('change', refreshMessages);
  }

  function bindManualScan() {
    var run = document.querySelector('.wpda-run-scan');
    if (!run) return;
    run.addEventListener('click', function () {
      run.disabled = true;
      run.textContent = api.strings && api.strings.scanning ? api.strings.scanning : 'Scanning…';
      var scripts = Array.prototype.slice.call(document.scripts).map(function (script) { return { src: script.src || '', id: script.id || '' }; });
      request('/scan', { method: 'POST', body: JSON.stringify({ manual: true, page_url: window.location.href, scripts: scripts, console_errors: [], ajax_failures: [], elementor_events: [] }) }).then(function (data) {
        if (data && data.error) {
          window.alert(data.message || 'Scan limit reached.');
          if (data.checkout_url) window.open(data.checkout_url, '_blank', 'noopener');
          run.disabled = false;
          run.textContent = api.strings && api.strings.runScan ? api.strings.runScan : 'Run Browser Scan';
          return;
        }
        window.location.reload();
      }).catch(function () {
        run.disabled = false;
        run.textContent = api.strings && api.strings.runScan ? api.strings.runScan : 'Run Browser Scan';
      });
    });
  }

  bindIssueToggles();
  bindFilters();
  bindExplain();
  bindSolutions();
  bindLanguagePicker();
  bindManualScan();
}());
