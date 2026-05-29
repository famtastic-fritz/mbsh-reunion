// page-sequence.js — single source of truth for the reel order.
//
// Every page in the reunion site is one reel in a fixed program. This file
// owns that program. Scene markers, "Up Next" links, the home program
// bulletin, and future cross-page navigation should read from here instead
// of hard-coding reel numbers or page links inline.
//
// Loaded eagerly (no `defer`) by every page's <head> so any script that
// runs after the DOM is parsed can rely on `window.PAGE_SEQUENCE`.

(function () {
  'use strict';

  var PAGE_SEQUENCE = [
    {
      id: 'home',
      page: 'home',
      slug: 'index.html',
      reelRoman: 'I',
      sceneNumber: '01',
      sceneLocation: 'Lobby',
      sceneTitle: 'The Lobby Opens',
      title: 'Welcome — The Premiere',
      heroTitle: 'The Lobby Opens',
      heroKicker: 'Welcome back, Class of ’96',
      heroSub: 'The curtain rises on a guided premiere built from memory, music, and everybody who made 1996 feel like home.',
      mood: 'cinematic welcome',
      usher: 'Curtain up. Find your row, find your row of 1996.'
    },
    {
      id: 'rsvp',
      page: 'rsvp',
      slug: 'rsvp.html',
      reelRoman: 'II',
      sceneNumber: 'II',
      sceneLocation: 'Auditorium',
      sceneTitle: 'Lock Your Seat',
      title: 'Reserve Your Seat',
      heroTitle: 'Reserve Your Seat',
      heroKicker: 'The guest list is open.',
      heroSub: 'Your RSVP gives the committee the count to plan the room, guests, food, and flow.',
      heroImage: 'assets/heroes/rsvp/01-environment.png',
      heroHarry: '15-seated-usher.png',
      heroTier: 'tier-1-photoreal',
      heroHarryPhoto: 'assets/heroes/rsvp/02-harry-usher-transparent.png',
      heroBridge: 'red-carpet-checkin',
      heroBridgeLabel: 'Red carpet, velvet rope, guest-list desk, and spotlight reflection carry the guest directly into the RSVP form.',
      heroHarryAlt: 'Hi-Tide Harry, premiere usher, opening the velvet rope',
      heroAction: 'The RSVP is the check-in moment: head count, guests, food notes, and the signal that the room is real.',
      companion: { kind: 'countdown', eyebrow: 'Check-in desk', headline: 'The room unlocks when you RSVP.', sub: 'RSVP first so the committee can plan head count, guests, dietary notes, room flow, and ticket timing with confidence.', stat: 'Guest list' },
      mood: 'red carpet check-in guest list velvet rope',
      usher: "Tell us you're coming. The night unlocks once we hear from you."
    },
    {
      id: 'tickets',
      page: 'tickets',
      slug: 'tickets.html',
      reelRoman: 'III',
      sceneNumber: '03',
      sceneLocation: 'Box Office',
      sceneTitle: 'Claim Your Ticket',
      title: 'Tickets & Sponsorship',
      heroTitle: 'Claim Your Ticket',
      heroKicker: 'Official entry, clear next steps',
      heroSub: 'Hold your place, understand the tiers, and help fund the night without losing the trust of the transaction.',
      heroImage: 'assets/premiere/bg-tickets.jpg',
      heroHarry: '18-presenting.png',
      heroTier: 'tier-1-photoreal',
      heroHarryPhoto: 'assets/premiere/characters/harry-photoreal-mascot-f1.png',
      heroBridge: 'ticket-strip',
      heroBridgeLabel: 'Ticket perforations and box-office bulbs push the eye into the ticket tiers.',
      heroHarryAlt: 'Hi-Tide Harry presenting ticket options at the box office',
      heroAction: 'Harry is at the box office, presenting a ticket stub and pointing to the clean purchase path.',
      companion: { kind: 'box-office', eyebrow: 'Box office status', headline: 'Seats first. Payment when ticketing opens.', sub: 'Early Bird and Regular tiers stay transparent; sponsorship is handled through the patron inquiry.', stat: 'Admit one' },
      mood: 'box office trust purchase clarity',
      usher: 'Two ways in — secure a seat, or help fund the night.'
    },
    {
      id: 'through-years',
      page: 'through-years',
      slug: 'through-years.html',
      reelRoman: 'IV',
      sceneNumber: '04',
      sceneLocation: 'Projection Booth',
      sceneTitle: 'Roll the Memory Reel',
      title: 'Through the Years',
      heroTitle: 'Roll the Memory Reel',
      heroKicker: 'The reel turns from 1926 to us',
      heroSub: 'A projection-room pause for the hallways, photos, programs, and stories that made Hi-Tides out of all of us.',
      heroImage: 'assets/premiere/wave1/scenes/through-years-projection-booth.png',
      heroHarry: '22-walk-frame.png',
      heroTier: 'tier-1-photoreal',
      heroHarryPhoto: 'assets/premiere/characters/harry-photoreal-mascot-f1.png',
      heroBridge: 'film-reel',
      heroBridgeLabel: 'A film-strip ribbon and projector cone introduce the memory reel.',
      heroHarryAlt: 'Hi-Tide Harry walking through the memory reel',
      heroAction: 'Harry is beside the projector, ushering classmates into the reel of old photos and stories.',
      companion: { kind: 'reel-counter', eyebrow: 'Reel counter', headline: '1926 → 2026', sub: 'The archive is still being cut. The page stays open so classmates can send the memories only they have.', stat: 'Memory reel' },
      mood: 'nostalgic memory reel',
      usher: 'One hundred years of Hi-Tides. The eras that built us.'
    },
    {
      id: 'memorial',
      page: 'memorial',
      slug: 'memorial.html',
      reelRoman: 'V',
      sceneNumber: '05',
      sceneLocation: 'Memorial Hall',
      sceneTitle: 'Hold the Light',
      title: 'In Memory',
      heroTitle: 'Hold the Light',
      heroKicker: 'A quiet light for every Hi-Tide we carry',
      heroSub: 'This scene slows the movie down so the names, the families, and the love have room to breathe.',
      heroImage: 'assets/premiere/wave1/scenes/memorial-candle-still.png',
      heroHarry: '17-respectful.png',
      heroTier: 'tier-1-photoreal',
      heroHarryPhoto: 'assets/premiere/characters/harry-photoreal-mascot-f1.png',
      heroBridge: 'candle-light',
      heroBridgeLabel: 'A quiet candle glow carries the scene into the memorial names.',
      heroHarryAlt: 'Hi-Tide Harry standing respectfully in candlelight',
      heroAction: 'Harry stands quietly in candlelight, hat lowered, giving the names room to breathe.',
      companion: { kind: 'tribute-light', eyebrow: 'Tribute light', headline: 'Slow the reel down.', sub: 'This scene stays gentle on purpose. Corrections and additions go through committee care.', stat: 'Forever Hi-Tides' },
      mood: 'reverent gentle restrained',
      usher: 'Forever Hi-Tides. Names we carry with us.'
    },
    {
      id: 'capsule',
      page: 'capsule',
      slug: 'capsule.html',
      reelRoman: 'VI',
      sceneNumber: '06',
      sceneLocation: 'Archive Room',
      sceneTitle: 'Seal the Time Capsule',
      title: 'Time Capsule',
      heroTitle: 'Seal the Time Capsule',
      heroKicker: 'A message from now to reunion night',
      heroSub: 'Write the memory, the song, the person, or the feeling. We seal it like a letter and let the future open it.',
      heroImage: 'assets/premiere/wave1/scenes/capsule-envelope-wax-seal.png',
      heroHarry: '14-wax-stamping.png',
      heroTier: 'tier-1-photoreal',
      heroHarryPhoto: 'assets/premiere/characters/harry-photoreal-mascot-f1.png',
      heroBridge: 'wax-seal',
      heroBridgeLabel: 'Envelope ribbon, wax seal, and gold dust trail into the capsule form.',
      heroHarryAlt: 'Hi-Tide Harry sealing the time capsule envelope',
      heroAction: 'Harry seals the envelope with wax, protecting the message until reunion day.',
      companion: { kind: 'wax-seal', eyebrow: 'Sealed message', headline: 'Write it now. Open it then.', sub: 'The capsule is private; the feeling is the point. The wax seal is your submit moment.', stat: 'Sealed for reunion day' },
      mood: 'intimate reflective future-facing',
      usher: "Send your younger self a note. We'll deliver on the day."
    },
    {
      id: 'playlist',
      page: 'playlist',
      slug: 'playlist.html',
      reelRoman: 'VII',
      sceneNumber: '07',
      sceneLocation: 'Sound Stage',
      sceneTitle: 'Drop the Soundtrack',
      title: 'The Soundtrack',
      heroTitle: 'Drop the Soundtrack',
      heroKicker: 'Press play on the room we remember',
      heroSub: 'The tracks, the dances, the rides home, the radio moments. This is where the movie gets its rhythm.',
      heroImage: 'assets/premiere/wave1/scenes/playlist-curtain-confetti-still.png',
      heroHarry: '16-conducting.png',
      heroTier: 'tier-1-photoreal',
      heroHarryPhoto: 'assets/premiere/characters/harry-photoreal-mascot-f1.png',
      heroBridge: 'soundwave',
      heroBridgeLabel: 'Stage beams, record arc, and soundwave energy drop into the playlist.',
      heroHarryAlt: 'Hi-Tide Harry conducting the reunion soundtrack',
      heroAction: 'Harry is conducting the soundtrack from the curtain line while the room starts moving.',
      companion: { kind: 'soundcheck', eyebrow: 'Soundcheck', headline: 'The night needs its bass line.', sub: 'Use the starter tracklist, then suggest the song that puts you right back in 1996.', stat: 'Now cueing' },
      mood: 'fun celebratory soundtrack energy',
      usher: 'The songs that made us who we are. Curated, embedded, alive.'
    }
  ];

  PAGE_SEQUENCE.forEach(function (entry, i) {
    entry.prev = PAGE_SEQUENCE[(i - 1 + PAGE_SEQUENCE.length) % PAGE_SEQUENCE.length];
    entry.next = PAGE_SEQUENCE[(i + 1) % PAGE_SEQUENCE.length];
  });

  function indexOfPage(pageName) {
    for (var i = 0; i < PAGE_SEQUENCE.length; i++) {
      if (PAGE_SEQUENCE[i].page === pageName) return i;
    }
    return -1;
  }

  function getEntry(pageName) {
    var i = indexOfPage(pageName);
    return i < 0 ? null : PAGE_SEQUENCE[i];
  }

  function nextEntry(pageName) {
    var entry = getEntry(pageName);
    return entry ? entry.next : null;
  }

  function prevEntry(pageName) {
    var entry = getEntry(pageName);
    return entry ? entry.prev : null;
  }

  function formatSceneMarker(entry) {
    if (!entry) return '';
    return 'SCENE ' + entry.sceneNumber + ' · ' + entry.sceneTitle.toUpperCase();
  }

  function formatScriptScene(entry) {
    if (!entry) return '';
    return 'SCENE ' + entry.reelRoman + ' · INT. ' + entry.sceneLocation.toUpperCase() + ' — NIGHT';
  }

  function bindSceneMarkers(root) {
    var doc = root || document;
    var pageName = (document.body && document.body.dataset && document.body.dataset.page) || '';
    var entry = getEntry(pageName);
    if (!entry) return;
    var nodes = doc.querySelectorAll('[data-scene-marker]');
    for (var i = 0; i < nodes.length; i++) {
      nodes[i].textContent = formatSceneMarker(entry);
    }
    var scriptNodes = doc.querySelectorAll('[data-script-scene-marker]');
    for (var j = 0; j < scriptNodes.length; j++) {
      scriptNodes[j].textContent = formatScriptScene(entry);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { bindSceneMarkers(); });
  } else {
    bindSceneMarkers();
  }

  window.PAGE_SEQUENCE = PAGE_SEQUENCE;
  window.PageSequence = {
    sequence: PAGE_SEQUENCE,
    indexOfPage: indexOfPage,
    getEntry: getEntry,
    nextEntry: nextEntry,
    prevEntry: prevEntry,
    formatSceneMarker: formatSceneMarker,
    formatScriptScene: formatScriptScene,
    bindSceneMarkers: bindSceneMarkers
  };

  /* ── SCENE_MAP — swarm/premiere-revival, marquee-scenes stream ──
     Canonical scene label per page slug (without .html).
     Matches the reelRoman values in PAGE_SEQUENCE above.          */
  var SCENE_MAP = {
    'home':         'SCENE I',
    'rsvp':         'SCENE II',
    'tickets':      'SCENE III',
    'playlist':     'SCENE IV',
    'capsule':      'SCENE V',
    'memorial':     'SCENE VI',
    'through-years': 'SCENE VII'
  };

  /* initSceneMarker — lightweight alias that uses SCENE_MAP
     to set [data-scene-marker] text on the current page.
     Called by premiere.js and also on DOMContentLoaded below. */
  function initSceneMarker () {
    var pageName = (document.body && document.body.dataset && document.body.dataset.page) || '';
    var label = SCENE_MAP[pageName];
    if (!label) return;
    var markers = document.querySelectorAll('[data-scene-marker]');
    for (var k = 0; k < markers.length; k++) {
      /* Prefer the full formatted label from PAGE_SEQUENCE if available */
      var entry = getEntry(pageName);
      markers[k].textContent = entry ? formatSceneMarker(entry) : label;
    }
  }

  window.SCENE_MAP = SCENE_MAP;
  window.initSceneMarker = initSceneMarker;
  window.PageSequence.SCENE_MAP = SCENE_MAP;
  window.PageSequence.initSceneMarker = initSceneMarker;
})();

