// MBSH GA4 measurement: privacy-safe page views, CTAs, and confirmed outcomes.
(function () {
  'use strict';

  const measurementId = 'G-L6359T51HR';
  function safeLocation() {
    const url = new URL(window.location.href);
    url.search = '';
    url.hash = '';
    return {
      location: url.toString(),
      path: `${url.pathname}${url.search}`,
    };
  }

  function cleanProperties(properties) {
    return Object.fromEntries(Object.entries(properties || {})
      .filter(([, value]) => ['string', 'number', 'boolean'].includes(typeof value))
      .map(([key, value]) => [key, typeof value === 'string' ? value.slice(0, 100) : value]));
  }

  window.dataLayer = window.dataLayer || [];
  window.gtag = window.gtag || function gtag() {
    window.dataLayer.push(arguments);
  };

  window.gtag('consent', 'default', {
    ad_storage: 'denied',
    ad_user_data: 'denied',
    ad_personalization: 'denied',
  });
  window.gtag('js', new Date());
  window.gtag('config', measurementId, { send_page_view: false });

  const script = document.createElement('script');
  script.async = true;
  script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(measurementId)}`;
  document.head.appendChild(script);

  const analytics = {
    track(eventName, properties) {
      if (!/^[a-z][a-z0-9_]{1,39}$/.test(eventName)) return false;
      window.gtag('event', eventName, cleanProperties(properties));
      return true;
    },
  };
  window.mbshAnalytics = Object.freeze(analytics);

  const safe = safeLocation();
  analytics.track('page_view', {
    page_location: safe.location,
    page_path: safe.path,
    page_title: document.title,
    page_type: document.body.dataset.page || 'unknown',
  });

  document.addEventListener('click', (event) => {
    const target = event.target.closest('a, button');
    if (!target || (!target.matches('.cinema-button, .button, [data-analytics-cta]'))) return;
    const region = target.closest('[data-page-slot], section, header, footer, main');
    const href = target instanceof HTMLAnchorElement ? target.href : '';
    let destination = '';
    if (href) {
      const url = new URL(href, window.location.href);
      destination = url.origin === window.location.origin ? url.pathname : `${url.hostname}${url.pathname}`;
    }
    analytics.track('cta_clicked', {
      button_text: (target.textContent || target.getAttribute('aria-label') || 'Unlabeled action').trim(),
      location: region?.dataset.pageSlot || region?.id || region?.tagName.toLowerCase() || 'page',
      destination,
      page_type: document.body.dataset.page || 'unknown',
    });
  });
})();
