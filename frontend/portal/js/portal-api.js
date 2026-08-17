(function () {
  'use strict';
  const config = window.MBSH_PORTAL_CONFIG;
  let csrfToken = '';

  class PortalApiError extends Error {
    constructor(message, status, code, payload) {
      super(message); this.name = 'PortalApiError'; this.status = status;
      this.code = code; this.payload = payload;
    }
  }

  function endpoint(path) { return `${config.apiBase.replace(/\/$/, '')}/${path.replace(/^\//, '')}`; }
  async function decode(response) {
    const type = response.headers.get('content-type') || '';
    if (!type.includes('application/json')) throw new PortalApiError('The server returned an unreadable response.', response.status, 'invalid_response');
    const payload = await response.json();
    if (!response.ok) throw new PortalApiError(payload.message || friendlyError(payload.error), response.status, payload.error, payload);
    return payload;
  }
  function friendlyError(code) {
    const errors = {
      authentication_required: 'Please sign in to continue.', invalid_credentials: 'The email or password was not accepted.',
      too_many_attempts: 'Too many attempts. Please wait 15 minutes and try again.', csrf_invalid: 'Your session changed. Refresh and try again.',
      invalid_or_expired_token: 'This secure link is invalid or has expired.', weak_password: 'Use at least 12 characters with a letter and number.',
      validation_error: 'Please check the form and try again.', upload_error: 'The file could not be accepted.', not_found: 'That record was not found.'
    };
    return errors[code] || 'Something went wrong. Please try again.';
  }
  async function request(path, options = {}) {
    const headers = new Headers(options.headers || {});
    if (options.json !== undefined) { headers.set('Content-Type', 'application/json'); options.body = JSON.stringify(options.json); }
    if (options.csrf) {
      if (!csrfToken) await session();
      if (!csrfToken) throw new PortalApiError('Your secure session is unavailable. Sign in again.', 401, 'authentication_required');
      headers.set('X-CSRF-Token', csrfToken);
    }
    return decode(await fetch(endpoint(path), { method: options.method || 'GET', credentials: 'same-origin', headers, body: options.body, signal: options.signal }));
  }
  async function session() { const data = await request('session.php'); csrfToken = data.csrf_token || ''; return data; }

  const apiAdapter = {
    mode: 'api', session,
    register: data => request('register.php', {method:'POST', json:data}),
    login: data => request('login.php', {method:'POST', json:data}),
    logout: () => request('logout.php', {method:'POST', csrf:true}),
    forgotPassword: email => request('forgot-password.php', {method:'POST', json:{email}}),
    resetPassword: (token,password) => request('reset-password.php', {method:'POST', json:{token,password}}),
    verifyEmail: token => request(`verify-email.php?token=${encodeURIComponent(token)}`),
    profile: () => request('profile.php'), updateProfile: data => request('profile.php', {method:'PATCH', json:data, csrf:true}),
    preferences: () => request('preferences.php'), updatePreferences: data => request('preferences.php', {method:'PATCH', json:data, csrf:true}),
    tickets: () => request('tickets.php'), notifications: () => request('notifications.php'),
    markNotificationRead: id => request('notifications.php', {method:'PATCH', json:{id}, csrf:true}),
    media: () => request('media.php'),
    uploadMedia: formData => request('media.php', {method:'POST', body:formData, csrf:true}),
    updateMedia: data => request('media.php', {method:'PATCH', json:data, csrf:true}), withdrawMedia: id => request('media.php', {method:'DELETE', json:{id}, csrf:true}),
    suggestions: () => request('suggestions.php'),
    createSuggestion: data => request('suggestions.php', {method:'POST', json:data, csrf:true}),
    updateSuggestion: data => request('suggestions.php', {method:'PATCH', json:data, csrf:true}), closeSuggestion: id => request('suggestions.php', {method:'DELETE', json:{id}, csrf:true}),
    myEvent: () => request('my-event.php'), updateMyEvent: data => request('my-event.php',{method:'PATCH',json:data,csrf:true}),
    trivia: () => request('trivia.php'), triviaAction: data => request('trivia.php',{method:'POST',json:data,csrf:true}),
    conversations: () => request('conversations.php'), conversation: id => request(`conversations.php?id=${encodeURIComponent(id)}`), createConversation: data => request('conversations.php',{method:'POST',json:data,csrf:true}), replyConversation: (id,message) => request('conversations.php',{method:'POST',json:{conversation_id:id,message},csrf:true}), updateConversation: (id,status) => request('conversations.php',{method:'PATCH',json:{id,status},csrf:true}),
    staffDashboard: () => request('staff/dashboard.php'), staffReviewQueue: status => request(`staff/review-queue.php?status=${encodeURIComponent(status||'pending')}`),
    staffPeople: query => request(`staff/people.php?q=${encodeURIComponent(query||'')}`), staffInbox: () => request('staff/inbox.php'),
    staffCommunications: () => request('staff/communications.php'), staffOperations: () => request('staff/operations.php'),
    staffAction: data => request('staff/action.php', {method:'POST', json:data, csrf:true}),
    staffRecord: id => request(`staff/record.php?id=${encodeURIComponent(id)}`),
    staffConversation: id => request(`staff/conversation.php?id=${encodeURIComponent(id)}`), staffReply: (id,data) => request(`staff/conversation.php?id=${encodeURIComponent(id)}`,{method:'POST',json:data,csrf:true}),
    staffContent: () => request('staff/content.php'), ownerContent: () => request('owner/content.php'), ownerAudit: () => request('owner/audit.php'),
    harryQuestions: () => request('staff/harry.php'), harryRespond: data => request('staff/harry.php',{method:'POST',json:data,csrf:true}),
    staffTrivia: () => request('staff/trivia.php'), staffTriviaCreate: data => request('staff/trivia.php',{method:'POST',json:data,csrf:true}), staffTriviaUpdate: data => request('staff/trivia.php',{method:'PATCH',json:data,csrf:true}),
    ownerMemberships: () => request('owner/memberships.php'), ownerUpdateMembership: data => request('owner/memberships.php',{method:'POST',json:data,csrf:true})
  };

  const demoFixture = {authenticated:true,csrf_token:'explicit-demo-only',account:{public_id:'demo',email:'demo@example.test',status:'active',email_verified_at:'2026-08-16',first_name:'Demo',last_name:'Hi-Tide',phone:'',city_state:'Miami, FL',graduation_year:1996,display_in_directory:0}};
  const demoAdapter = {
    mode:'demo', session:async()=>demoFixture, login:async()=>({ok:true}), register:async()=>({ok:true}), logout:async()=>({ok:true}),
    forgotPassword:async()=>({ok:true}), resetPassword:async()=>({ok:true}), verifyEmail:async()=>({ok:true}), profile:async()=>({profile:demoFixture.account}),
    updateProfile:async d=>({ok:true,profile:{...demoFixture.account,...d}}), preferences:async()=>({preferences:{event_updates:1,memory_updates:1,promotional_email:0,sms_notifications:0}}),
    updatePreferences:async()=>({ok:true}), tickets:async()=>({tickets:[]}), notifications:async()=>({notifications:[]}), markNotificationRead:async()=>({ok:true}),
    media:async()=>({submissions:[]}), uploadMedia:async()=>({ok:true,status:'pending'}), suggestions:async()=>({suggestions:[]}), createSuggestion:async()=>({ok:true}),myEvent:async()=>({event_response:{attendance:'unknown',guest_count:0,guest_names:'',phone:'',meal_choice:'undecided',dietary_accessibility:'',status:'draft'}}),updateMyEvent:async d=>({ok:true,event_response:d}),conversations:async()=>({conversations:[]}),createConversation:async()=>({ok:true}),staffConversation:async()=>({conversation:{},messages:[]}),staffReply:async()=>({ok:true}),
    staffDashboard:async()=>({summary:{pending_reviews:0,unanswered_messages:0,active_attendees:0,operations_attention:0}}),staffReviewQueue:async()=>({items:[]}),staffPeople:async()=>({people:[]}),staffInbox:async()=>({threads:[]}),staffCommunications:async()=>({delivery:{}}),staffOperations:async()=>({summary:{pending_orders:0,active_tickets:0,checked_in:0,exceptions:0}}),staffAction:async()=>({ok:true}),staffRecord:async()=>({record:{}}),harryQuestions:async()=>({questions:[]}),harryRespond:async()=>({ok:true}),ownerMemberships:async()=>({memberships:[]}),ownerUpdateMembership:async()=>({ok:true})
  };
  window.PortalApi = config.mode === 'demo' ? demoAdapter : apiAdapter;
  window.PortalApiError = PortalApiError;
})();
