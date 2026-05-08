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
    document.querySelectorAll('.wpda-explain-actions button').forEach(function (button) {
      button.addEventListener('click', function () {
        var holder = button.closest('.wpda-explain-actions');
        var issue = JSON.parse(holder.getAttribute('data-issue'));
        request('/explain', { method: 'POST', body: JSON.stringify({ issue: issue, mode: button.dataset.mode, language: 'en', advanced: false }) }).then(function (data) {
          var box = holder.querySelector('.wpda-explanation') || document.createElement('div');
          box.className = 'wpda-explanation';
          box.innerHTML = '<strong>' + data.headline + '</strong><p>' + data.summary + '</p><p>' + data.safe_fix + '</p>';
          holder.appendChild(box);
        });
      });
    });
  }

  function bindManualScan() {
    var run = document.querySelector('.wpda-run-scan');
    if (!run) return;
    run.addEventListener('click', function () {
      run.disabled = true;
      run.textContent = api.strings && api.strings.scanning ? api.strings.scanning : 'Scanning…';
      var scripts = Array.prototype.slice.call(document.scripts).map(function (script) { return { src: script.src || '', id: script.id || '' }; });
      request('/scan', { method: 'POST', body: JSON.stringify({ manual: true, page_url: window.location.href, scripts: scripts, console_errors: [], ajax_failures: [], elementor_events: [] }) }).then(function () {
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
  bindManualScan();
}());
