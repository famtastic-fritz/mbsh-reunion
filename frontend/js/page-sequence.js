// page-sequence.js — single source of truth for the reel order.
//
// Every page in the reunion site is one reel in a fixed program. This file
// owns that program. Scene markers, "Up Next" links, the home program
// bulletin, and any future cross-page navigation should read from here
// instead of hard-coding reel numbers or page links inline.
//
// Loaded eagerly (no `defer`) by every page's <head> so any script that
// runs after the DOM is parsed (premiere.js, etc.) can rely on
// `window.PAGE_SEQUENCE` and the helpers below being defined.
//
// PAGE SEQUENCE (locked):
//   home → rsvp → tickets → menu → survey → through-years → memorial → capsule → playlist
//   playlist's "next" wraps back to home.
//
// Each entry carries:
//   id            — short, stable identifier for the page
//   page          — the value of <body data-page="…"> on that page
//   slug          — the html filename (relative path; includes ".html")
//   reelRoman     — Roman numeral shown to the user (I…VII)
//   sceneLocation — the location string used in the scene-marker line.
//                   This is the label that appears between "Int." and the
//                   "— Night" suffix, e.g. "Lobby" → "Int. Lobby — Night".
//                   Only "home" currently renders a scene marker in the
//                   markup; the other entries supply a default location so
//                   the marker can be added to those pages later without
//                   editing this file.
//   title         — short human title used in the program bulletin
//   usher         — one-line usher copy used by the Up Next callout

(function () {
  'use strict';

  var PAGE_SEQUENCE = [
    {
      id: 'home',
      page: 'home',
      slug: 'index.html',
      reelRoman: 'I',
      sceneLocation: 'Lobby',
      title: 'Welcome — The Premiere',
      usher: 'Curtain up. Find your row, find your row of 1996.'
    },
    {
      id: 'rsvp',
      page: 'rsvp',
      slug: 'rsvp.html',
      reelRoman: 'II',
      sceneLocation: 'Box Office',
      title: 'Reserve Your Seat',
      usher: "Tell us you're coming. The night unlocks once we hear from you."
    },
    {
      id: 'tickets',
      page: 'tickets',
      slug: 'tickets.html',
      reelRoman: 'III',
      sceneLocation: 'Concession Stand',
      title: 'Tickets & Sponsorship',
      usher: 'Two ways in — secure a seat, or help fund the night.'
    },
    {
      id: 'menu',
      page: 'menu',
      slug: '/menu/',
      reelRoman: 'IV',
      sceneLocation: 'Dining Room',
      title: 'Dinner Preferences',
      usher: 'Vote the menu up front so the committee locks the strongest dinner lineup.'
    },
    {
      id: 'survey',
      page: 'survey',
      slug: 'survey.html',
      reelRoman: 'V',
      sceneLocation: 'Roll Call',
      title: 'Class Survey',
      usher: 'Give the committee the head count, guest signal, and planning intel they need.'
    },
    {
      id: 'through-years',
      page: 'through-years',
      slug: 'through-years.html',
      reelRoman: 'VI',
      sceneLocation: 'The Marquee',
      title: 'Through the Years',
      usher: 'One hundred years of Hi-Tides. The eras that built us.'
    },
    {
      id: 'memorial',
      page: 'memorial',
      slug: 'memorial.html',
      reelRoman: 'VII',
      sceneLocation: 'Memorial Hall',
      title: 'In Memory',
      usher: 'Forever Hi-Tides. Names we carry with us.'
    },
    {
      id: 'capsule',
      page: 'capsule',
      slug: 'capsule.html',
      reelRoman: 'VIII',
      sceneLocation: 'Projection Booth',
      title: 'Time Capsule',
      usher: "Send your younger self a note. We'll deliver on the day."
    },
    {
      id: 'playlist',
      page: 'playlist',
      slug: 'playlist.html',
      reelRoman: 'IX',
      sceneLocation: 'Sound Stage',
      title: 'The Soundtrack',
      usher: 'The songs that made us who we are. Curated, embedded, alive.'
    }
  ];

  // Annotate each entry with prev/next sibling references (wraps at the end
  // so playlist.next === home). Computed once, at module load.
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

  // Build "SCENE <reelRoman> · INT. <LOCATION> — NIGHT" using the entry's
  // own reelRoman and sceneLocation. Pure function — no DOM read.
  function formatSceneMarker(entry) {
    if (!entry) return '';
    return 'SCENE ' + entry.reelRoman + ' · INT. ' + entry.sceneLocation.toUpperCase() + ' — NIGHT';
  }

  // Bind any element marked with data-scene-marker to the current page's
  // scene marker text. Runs once on DOMContentLoaded; safe to invoke
  // manually if the marker is injected after that.
  function bindSceneMarkers(root) {
    var doc = root || document;
    var pageName = (document.body && document.body.dataset && document.body.dataset.page) || '';
    var entry = getEntry(pageName);
    if (!entry) return;
    var nodes = doc.querySelectorAll('[data-scene-marker]');
    for (var i = 0; i < nodes.length; i++) {
      nodes[i].textContent = formatSceneMarker(entry);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { bindSceneMarkers(); });
  } else {
    bindSceneMarkers();
  }

  // Expose on window so other scripts (premiere.js, etc.) can consume the
  // same source of truth instead of duplicating the table.
  window.PAGE_SEQUENCE = PAGE_SEQUENCE;
  window.PageSequence = {
    sequence: PAGE_SEQUENCE,
    indexOfPage: indexOfPage,
    getEntry: getEntry,
    nextEntry: nextEntry,
    prevEntry: prevEntry,
    formatSceneMarker: formatSceneMarker,
    bindSceneMarkers: bindSceneMarkers
  };
})();
