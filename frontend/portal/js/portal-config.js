/* Production default: same-origin PHP API. A host may explicitly set mode:'demo'
   before portal-api.js for a non-production showroom build. */
const localDemo = ['127.0.0.1', 'localhost'].includes(window.location.hostname)
  && new URLSearchParams(window.location.search).get('mode') === 'demo';
window.MBSH_PORTAL_CONFIG = Object.assign({
  mode: 'api',
  apiBase: '/portal',
  loginPath: '/portal/login',
  portalPath: '/portal/',
  ownerAdminUrl: ['127.0.0.1', 'localhost'].includes(window.location.hostname)
    ? 'http://localhost:8096/wp-admin/'
    : '/wp-admin/'
}, localDemo ? {mode: 'demo'} : (window.MBSH_PORTAL_CONFIG || {}));
