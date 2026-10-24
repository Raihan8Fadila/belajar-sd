/* Halaman hasil: konfeti dan bunyi bintang bila nilainya bagus. */
(function () {
  'use strict';

  var h = document.querySelector('.hasil');
  if (!h) { return; }
  var bintang = parseInt(h.getAttribute('data-bintang'), 10) || 1;

  window.setTimeout(function () {
    if (window.Suara) { window.Suara.bintang(); }
    if (bintang >= 2 && window.Confetti) { window.Confetti.ledak(bintang === 3 ? 140 : 80); }
    var m = h.querySelector('.maskot-gambar');
    if (m) { m.classList.add('melompat'); }
  }, 350);
})();
