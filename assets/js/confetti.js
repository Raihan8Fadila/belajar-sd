/* =====================================================
   KONFETI ringan (canvas, tanpa library).
   Pakai: Confetti.ledak();  atau Confetti.ledak(jumlah)
   ===================================================== */
(function () {
  'use strict';

  var WARNA = ['#ff6f91', '#ffd23f', '#2f6fed', '#1fa968', '#ff8a3d', '#a66cff'];

  function kurangiGerak() {
    return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  }

  function ledak(jumlah) {
    if (kurangiGerak()) { return; }
    var kanvas = document.createElement('canvas');
    kanvas.className = 'konfeti';
    kanvas.setAttribute('aria-hidden', 'true');
    var w = kanvas.width = window.innerWidth;
    var h = kanvas.height = window.innerHeight;
    document.body.appendChild(kanvas);
    var g = kanvas.getContext('2d');

    var n = jumlah || 90;
    var partikel = [];
    for (var i = 0; i < n; i++) {
      partikel.push({
        x: w / 2 + (Math.random() - 0.5) * w * 0.3,
        y: h * 0.45,
        vx: (Math.random() - 0.5) * 14,
        vy: -Math.random() * 14 - 4,
        ukuran: Math.random() * 8 + 6,
        putar: Math.random() * Math.PI,
        vputar: (Math.random() - 0.5) * 0.4,
        warna: WARNA[i % WARNA.length]
      });
    }

    var mulai = null;
    function gambar(waktu) {
      if (mulai === null) { mulai = waktu; }
      var lama = waktu - mulai;
      g.clearRect(0, 0, w, h);
      var hidup = 0;
      for (var k = 0; k < partikel.length; k++) {
        var p = partikel[k];
        p.vy += 0.38;
        p.vx *= 0.99;
        p.x += p.vx;
        p.y += p.vy;
        p.putar += p.vputar;
        if (p.y < h + 20) { hidup++; }
        g.save();
        g.translate(p.x, p.y);
        g.rotate(p.putar);
        g.globalAlpha = Math.max(0, 1 - lama / 2600);
        g.fillStyle = p.warna;
        g.fillRect(-p.ukuran / 2, -p.ukuran / 4, p.ukuran, p.ukuran / 2);
        g.restore();
      }
      if (hidup > 0 && lama < 2600) {
        window.requestAnimationFrame(gambar);
      } else if (kanvas.parentNode) {
        kanvas.parentNode.removeChild(kanvas);
      }
    }
    window.requestAnimationFrame(gambar);
  }

  window.Confetti = { ledak: ledak };
})();
