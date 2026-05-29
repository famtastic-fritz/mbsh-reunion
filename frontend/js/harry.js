/**
 * harry.js — Hi-Tide Harry layered avatar component
 *
 * Vanilla JS, no framework or build step required.
 * Manages a stack of absolutely-positioned <img> layers inside a mount element.
 * CSS states (.harry--idle, .harry--listening, .harry--speaking) drive all animation.
 *
 * MOUNT SNIPPET — add to any page:
 * ─────────────────────────────────────────────────────────────────────────────
 *   // 1. Add to <head>:
 *   //    <link rel="stylesheet" href="css/harry.css">
 *   //
 *   // 2. Add a mount element anywhere in <body>:
 *   //    <div id="harry-mount" class="harry-mount--bottom-right"></div>
 *   //
 *   // 3. Load this script and init:
 *   //    <script src="js/harry.js"></script>
 *   //    <script>
 *   //      const harry = new HarryAvatar(document.getElementById('harry-mount'), {
 *   //        width:      200,
 *   //        height:     300,
 *   //        assetsBase: 'assets/premiere/characters/'
 *   //      });
 *   //
 *   //      // Change state:
 *   //      harry.listen();           // Harry watches and waits
 *   //      harry.speak(2000);        // Harry speaks for 2 seconds, then goes idle
 *   //      harry.idle();             // Return to breathing idle
 *   //      harry.setState('speaking'); // Direct state assignment
 *   //    </script>
 * ─────────────────────────────────────────────────────────────────────────────
 */

(function (root, factory) {
  /* UMD-lite: works as plain script tag, CommonJS, or AMD */
  if (typeof module !== 'undefined' && module.exports) {
    module.exports = factory();
  } else {
    root.HarryAvatar = factory();
  }
}(typeof globalThis !== 'undefined' ? globalThis : window, function () {
  'use strict';

  /* ── Asset layer definitions ──────────────────────────────── */
  var LAYERS = [
    { key: 'body',           file: 'harry-body.png',          className: 'harry-body',  alt: '' },
    { key: 'ring',           file: 'harry-ring-idle.png',     className: 'harry-ring',  alt: '' },
    { key: 'face',           file: null,                       className: 'harry-face',  alt: '' },
    { key: 'eyes',           file: 'harry-eyes-idle.png',     className: 'harry-eyes',  alt: '' },
    { key: 'mouth',          file: 'harry-mouth-idle.png',    className: 'harry-mouth', alt: '' },
  ];

  /* Assets that change per state */
  var STATE_ASSETS = {
    idle: {
      ring:  'harry-ring-idle.png',
      eyes:  'harry-eyes-idle.png',
      mouth: 'harry-mouth-idle.png',
    },
    listening: {
      ring:  'harry-ring-idle.png',
      eyes:  'harry-eyes-chase.png',
      mouth: 'harry-mouth-idle.png',
    },
    speaking: {
      ring:  'harry-ring-glow.png',
      eyes:  'harry-eyes-idle.png',
      mouth: 'harry-mouth-speaking.png',
    },
  };

  var VALID_STATES = ['idle', 'listening', 'speaking'];

  /* ── Constructor ──────────────────────────────────────────── */
  /**
   * @param {HTMLElement} mountEl   - Container element for the avatar
   * @param {Object}      [options]
   * @param {number}      [options.width=200]             - CSS width in px
   * @param {number}      [options.height=300]            - CSS height in px
   * @param {string}      [options.assetsBase]            - Path to character assets dir
   * @param {string}      [options.initialState='idle']   - Starting state
   */
  function HarryAvatar(mountEl, options) {
    if (!mountEl) {
      console.warn('[HarryAvatar] No mount element provided.');
      return;
    }

    this.el         = mountEl;
    this.opts       = Object.assign({
      width:        200,
      height:       300,
      assetsBase:   'assets/premiere/characters/',
      initialState: 'idle',
    }, options || {});

    /* Normalize assetsBase — ensure trailing slash */
    if (this.opts.assetsBase.slice(-1) !== '/') {
      this.opts.assetsBase += '/';
    }

    this._state       = null;
    this._speakTimer  = null;
    this._layers      = {};  /* key → img element */

    this._build();
    this.setState(this.opts.initialState);
  }

  /* ── Private: build DOM ───────────────────────────────────── */
  HarryAvatar.prototype._build = function () {
    var el   = this.el;
    var self = this;

    /* Style the mount container */
    el.classList.add('harry-avatar');
    el.style.setProperty('--harry-w', this.opts.width  + 'px');
    el.style.setProperty('--harry-h', this.opts.height + 'px');

    /* Create each layer img */
    LAYERS.forEach(function (def) {
      if (!def.file) return; /* skip null layers (e.g. face if using body) */

      var img       = document.createElement('img');
      img.className = def.className;
      img.alt       = def.alt;
      img.src       = self.opts.assetsBase + def.file;

      /* Gracefully hide broken images (asset not yet generated) */
      img.addEventListener('error', function () {
        img.classList.add('harry--broken');
      });
      img.addEventListener('load', function () {
        img.classList.remove('harry--broken');
      });

      el.appendChild(img);
      self._layers[def.key] = img;
    });
  };

  /* ── Private: swap a layer's asset src ───────────────────── */
  HarryAvatar.prototype._swapSrc = function (key, filename) {
    var img = this._layers[key];
    if (!img) return;
    var newSrc = this.opts.assetsBase + filename;
    if (img.getAttribute('src') !== newSrc) {
      img.src = newSrc;
    }
  };

  /* ── Public: setState ─────────────────────────────────────── */
  /**
   * Set avatar to one of: 'idle' | 'listening' | 'speaking'
   * @param {string} state
   */
  HarryAvatar.prototype.setState = function (state) {
    if (VALID_STATES.indexOf(state) === -1) {
      console.warn('[HarryAvatar] Unknown state:', state);
      return;
    }

    /* Clear any pending speak timer */
    if (this._speakTimer) {
      clearTimeout(this._speakTimer);
      this._speakTimer = null;
    }

    /* Swap state classes */
    var el = this.el;
    VALID_STATES.forEach(function (s) { el.classList.remove('harry--' + s); });
    el.classList.add('harry--' + state);

    /* Swap layer sources */
    var assets = STATE_ASSETS[state];
    if (assets) {
      var self = this;
      Object.keys(assets).forEach(function (key) {
        self._swapSrc(key, assets[key]);
      });
    }

    this._state = state;
  };

  /* ── Public: idle ─────────────────────────────────────────── */
  HarryAvatar.prototype.idle = function () {
    this.setState('idle');
  };

  /* ── Public: listen ───────────────────────────────────────── */
  HarryAvatar.prototype.listen = function () {
    this.setState('listening');
  };

  /* ── Public: speak ────────────────────────────────────────── */
  /**
   * Set speaking state, then return to idle after durationMs.
   * @param {number} [durationMs=2000] - How long Harry speaks
   */
  HarryAvatar.prototype.speak = function (durationMs) {
    var self     = this;
    var duration = (typeof durationMs === 'number' && durationMs > 0) ? durationMs : 2000;

    this.setState('speaking');

    this._speakTimer = setTimeout(function () {
      self._speakTimer = null;
      self.setState('idle');
    }, duration);
  };

  /* ── Public: getState ─────────────────────────────────────── */
  HarryAvatar.prototype.getState = function () {
    return this._state;
  };

  /* ── Public: destroy ──────────────────────────────────────── */
  /** Remove all generated DOM and clean up timers */
  HarryAvatar.prototype.destroy = function () {
    if (this._speakTimer) {
      clearTimeout(this._speakTimer);
      this._speakTimer = null;
    }
    this.el.innerHTML = '';
    this.el.classList.remove('harry-avatar');
    VALID_STATES.forEach(function (s) { this.el.classList.remove('harry--' + s); }.bind(this));
    this._layers = {};
    this._state  = null;
  };

  return HarryAvatar;

}));

/*
 * ─────────────────────────────────────────────────────────────────────────────
 * QUICK-MOUNT EXAMPLE (paste into any page's inline <script>):
 *
 *   const harry = new HarryAvatar(document.getElementById('harry-mount'), {
 *     width:      200,
 *     height:     300,
 *     assetsBase: 'assets/premiere/characters/'
 *   });
 *
 *   // Simulated chatbot integration:
 *   chatbot.on('listening', () => harry.listen());
 *   chatbot.on('response',  (msg, durationMs) => harry.speak(durationMs || 2500));
 *   chatbot.on('idle',      () => harry.idle());
 *
 * ─────────────────────────────────────────────────────────────────────────────
 */
