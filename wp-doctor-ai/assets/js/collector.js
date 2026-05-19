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
    elementor_events: [],
    performance: {},
    images: [],
    stylesheets: [],
    dom: {}
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
    if (event.target && event.target !== window && event.target.tagName) {
      payload.console_errors.push({
        message: 'Resource failed to load: ' + event.target.tagName.toLowerCase(),
        source: event.target.src || event.target.href || '',
        line: '',
        column: '',
        stack: '',
        timestamp: new Date().toISOString()
      });
      return;
    }
    pushError(event.message, event.filename, event.lineno, event.colno, event.error);
  }, true);

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

  function inspectPerformance() {
    var perf = window.performance;
    if (!perf || !perf.getEntriesByType) return;

    var nav = (perf.getEntriesByType('navigation') || [])[0];
    var paints = perf.getEntriesByType('paint') || [];
    var firstContentfulPaint = paints.filter(function (entry) { return entry.name === 'first-contentful-paint'; })[0];
    var resources = (perf.getEntriesByType('resource') || []).map(function (entry) {
      return {
        name: entry.name || '',
        initiator_type: entry.initiatorType || '',
        duration: Math.round(entry.duration || 0),
        transfer_size: Math.round(entry.transferSize || 0),
        encoded_body_size: Math.round(entry.encodedBodySize || 0),
        render_blocking_status: entry.renderBlockingStatus || ''
      };
    }).filter(function (entry) {
      return entry.transfer_size > 150000 || entry.duration > 1000 || entry.render_blocking_status === 'blocking';
    }).slice(0, 25);

    payload.performance = {
      ttfb: nav ? Math.round(nav.responseStart || 0) : 0,
      dom_content_loaded: nav ? Math.round(nav.domContentLoadedEventEnd || 0) : 0,
      load_time: nav ? Math.round(nav.loadEventEnd || 0) : 0,
      first_contentful_paint: firstContentfulPaint ? Math.round(firstContentfulPaint.startTime || 0) : 0,
      largest_contentful_paint: payload.performance.largest_contentful_paint || 0,
      cumulative_layout_shift: payload.performance.cumulative_layout_shift || 0,
      slow_resources: resources
    };
  }

  if ('PerformanceObserver' in window) {
    try {
      var lcpObserver = new PerformanceObserver(function (list) {
        var entries = list.getEntries();
        var last = entries[entries.length - 1];
        if (last) payload.performance.largest_contentful_paint = Math.round(last.startTime || 0);
      });
      lcpObserver.observe({ type: 'largest-contentful-paint', buffered: true });
    } catch (ignore) {}

    try {
      var cls = 0;
      var clsObserver = new PerformanceObserver(function (list) {
        list.getEntries().forEach(function (entry) {
          if (!entry.hadRecentInput) cls += entry.value || 0;
        });
        payload.performance.cumulative_layout_shift = Math.round(cls * 1000) / 1000;
      });
      clsObserver.observe({ type: 'layout-shift', buffered: true });
    } catch (ignoreCls) {}
  }

  function hasPerformanceProblem() {
    var perf = payload.performance || {};
    return perf.ttfb > 800 || perf.first_contentful_paint > 1800 || perf.largest_contentful_paint > 2500 || perf.cumulative_layout_shift > 0.1 || (perf.slow_resources && perf.slow_resources.length);
  }

  function inspectFrontendOptimization() {
    payload.images = Array.prototype.slice.call(document.images || []).map(function (image) {
      var rect = image.getBoundingClientRect ? image.getBoundingClientRect() : { width: 0, height: 0 };
      return {
        src: image.currentSrc || image.src || '',
        alt: image.getAttribute('alt'),
        width: image.getAttribute('width') || '',
        height: image.getAttribute('height') || '',
        natural_width: image.naturalWidth || 0,
        natural_height: image.naturalHeight || 0,
        rendered_width: Math.round(rect.width || 0),
        rendered_height: Math.round(rect.height || 0),
        loading: image.getAttribute('loading') || '',
        fetchpriority: image.getAttribute('fetchpriority') || '',
        above_fold: rect.top < (window.innerHeight || 0) && rect.bottom > 0
      };
    }).filter(function (image) {
      return image.src && (!image.alt || !image.width || !image.height || (image.above_fold && image.loading === 'lazy') || image.natural_width > image.rendered_width * 2.5 || image.natural_height > image.rendered_height * 2.5);
    }).slice(0, 50);

    payload.stylesheets = Array.prototype.slice.call(document.querySelectorAll('link[rel="stylesheet"], link[rel="preload"][as="style"], style')).map(function (node) {
      var href = node.href || '';
      var media = node.getAttribute('media') || '';
      return {
        href: href,
        id: node.id || '',
        media: media,
        disabled: !!node.disabled,
        render_blocking: node.tagName.toLowerCase() === 'link' && node.rel === 'stylesheet' && (!media || media === 'all' || media === 'screen'),
        inline_size: node.tagName.toLowerCase() === 'style' ? (node.textContent || '').length : 0
      };
    }).filter(function (style) {
      return style.render_blocking || style.inline_size > 50000;
    }).slice(0, 50);

    payload.dom = {
      node_count: document.getElementsByTagName('*').length,
      iframes: document.getElementsByTagName('iframe').length,
      forms: document.forms ? document.forms.length : 0,
      viewport: document.querySelector('meta[name="viewport"]') ? 'present' : 'missing'
    };
  }

  function hasFrontendOptimizationProblem() {
    return (payload.images && payload.images.length) || (payload.stylesheets && payload.stylesheets.length) || (payload.dom && (payload.dom.node_count > 1500 || payload.dom.viewport === 'missing'));
  }

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
    inspectPerformance();
    inspectFrontendOptimization();
    inspectElementor();
    if (!payload.console_errors.length && !payload.ajax_failures.length && !payload.elementor_events.length && !hasPerformanceProblem() && !hasFrontendOptimizationProblem()) {
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
