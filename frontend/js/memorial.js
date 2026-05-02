// memorial.js — fetch /in-memory.php and render names; hide section if empty
(function () {
  'use strict';
  const list = document.getElementById('memorial-names');
  const empty = document.getElementById('memorial-empty');
  if (!list) return;

  async function load() {
    try {
      const url = window.__famHelpers.apiUrl('/in-memory.php');
      const res = await fetch(url);
      if (!res.ok) return;
      const names = await res.json();
      if (!Array.isArray(names) || names.length === 0) return;
      empty.hidden = true;
      list.innerHTML = '';
      names.forEach(n => {
        const li = document.createElement('li');
        li.className = 'memorial__name';
        const grad = n.graduation_year || 1996;
        const passed = n.year_passed ? ` – ${n.year_passed}` : '';
        li.innerHTML = `${n.full_name.replace(/[<>]/g,'')}<span class="memorial__year">Class of ${grad}${passed}</span>`;
        if (n.tribute) {
          li.style.cursor = 'pointer';
          li.addEventListener('click', () => alert(n.tribute));
        }
        list.appendChild(li);
      });
    } catch (e) { /* silent */ }
  }
  load();
})();
