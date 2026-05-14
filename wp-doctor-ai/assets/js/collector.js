(function () {
  'use strict';

  if (!window.WPDoctorAICollector || !window.WPDoctorAICollector.enabled) {
    return;
  }

  var payload = {
    manual: false,
    page_url: window.location.href,
    console_errors: [],
    ajax_failures: [],
    scripts: [],
    jquery_markers: {},
    elementor_events: []
  };

  function pushError(message, source, lineno, colno, error) {
    payload.console_errors.push({
      message: String(message || ''),
      source: source || '',
      line: lineno || '',
      column: colno || '',
      stack: error && error.stack ? String(error.stack).slice(0, 4000) : '',
      timestamp: new Date().toISOString()
    });
  }

  window.addEventListener('error', function (event) {
    pushError(event.message, event.filename, event.lineno, event.colno, event.error);
  });

  window.addEventListener('unhandledrejection', function (event) {
    pushError('Unhandled promise rejection: ' + (event.reason && event.reason.message ? event.reason.message : event.reason), '', '', '', event.reason);
  });

  function inspectScripts() {
    payload.scripts = Array.prototype.slice.call(document.scripts).map(function (script) {
      return { src: script.src || '', id: script.id || '', version: (script.src.match(/[?&]ver=([^&]+)/) || [])[1] || '' };
    });
    if (window.jQuery && window.jQuery.fn) {
      payload.jquery_markers.version = window.jQuery.fn.jquery || '';
    }
    if (window.$ && window.jQuery && window.$ !== window.jQuery) {
      payload.jquery_markers.no_conflict_error = 'Global $ differs from window.jQuery';
    }
  }

  var originalFetch = window.fetch;
  if (originalFetch) {
    window.fetch = function () {
      var args = arguments;
      return originalFetch.apply(this, args).then(function (response) {
        if (!response.ok && /admin-ajax\.php|\/wp-json\//.test(response.url)) {
          payload.ajax_failures.push({ url: response.url, status: response.status, status_text: response.statusText, timestamp: new Date().toISOString() });
        }
        return response;
      });
    };
  }

  var originalOpen = XMLHttpRequest.prototype.open;
  var originalSend = XMLHttpRequest.prototype.send;
  XMLHttpRequest.prototype.open = function (method, url) {
    this.__wpDoctorAiUrl = url;
    return originalOpen.apply(this, arguments);
  };
  XMLHttpRequest.prototype.send = function () {
    this.addEventListener('loadend', function () {
      var url = String(this.__wpDoctorAiUrl || '');
      if (this.status >= 400 && /admin-ajax\.php|\/wp-json\//.test(url)) {
        payload.ajax_failures.push({ url: url, status: this.status, status_text: this.statusText, timestamp: new Date().toISOString() });
      }
    });
    return originalSend.apply(this, arguments);
  };

  function inspectElementor() {
    if (/elementor/.test(document.body.className) || window.elementor || window.elementorFrontend) {
      var failedWidgets = Array.prototype.slice.call(document.querySelectorAll('.elementor-widget:not(.elementor-widget-text-editor)')).filter(function (widget) {
        return widget.textContent && /error|undefined|failed/i.test(widget.textContent);
      });
      failedWidgets.slice(0, 5).forEach(function (widget) {
        payload.elementor_events.push({ widget: widget.getAttribute('data-widget_type') || widget.className, message: 'Potential widget render failure', editor: !!window.elementor });
      });
    }
  }

  function send() {
    inspectScripts();
    inspectElementor();
    if (!payload.console_errors.length && !payload.ajax_failures.length && !payload.elementor_events.length) {
      return;
    }
    window.fetch(window.WPDoctorAICollector.restUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.WPDoctorAICollector.nonce },
      credentials: 'same-origin',
      body: JSON.stringify(payload)
    }).catch(function () {});
  }

  window.setTimeout(send, 2500);
}());
