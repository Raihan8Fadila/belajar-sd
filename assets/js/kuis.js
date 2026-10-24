/* =====================================================
   KUIS: satu soal per layar.
   Tipe soal: pilihan (pilihan ganda/tebak gambar), seret (drag & drop), cocokkan.
   Jawaban dicek di server (api/cek.php); kunci jawaban tidak ada di browser.
   Semua teks dari database dimasukkan lewat textContent (aman dari XSS).
   ===================================================== */
(function () {
  'use strict';

  var dataEl = document.getElementById('dataKuis');
  var root = document.getElementById('kuis');
  if (!dataEl || !root) { return; }

  var D = JSON.parse(dataEl.textContent);
  var API_CEK = root.getAttribute('data-api-cek');
  var API_SELESAI = root.getAttribute('data-api-selesai');
  var CSRF = document.body.getAttribute('data-csrf');

  var area = document.getElementById('areaSoal');
  var tombolLanjut = document.getElementById('lanjut');
  var noSoal = document.getElementById('noSoal');
  var batang = document.getElementById('batangProgres');
  var maskotTeks = document.getElementById('maskotKuis');
  var dengar = document.getElementById('dengarSoal');
  var maskotGambar = document.querySelector('.maskot-gambar');

  var total = D.soal.length;
  var idx = 0;
  var terkunci = false;       // true setelah soal dijawab benar
  var sibuk = false;          // true saat menunggu server

  var PUJI = ['Hebat sekali, NAMA! 🎉', 'Pintar! Jawabanmu benar! 🌟', 'Keren, NAMA! Terus ya! 🎈', 'Yeay, benar! 👏', 'Wah, NAMA pintar sekali! ⭐'];
  var COBA = ['Belum tepat 😊 Yuk coba lagi!', 'Hampir! Coba sekali lagi ya 😊', 'Tidak apa-apa, ayo coba lagi! 💪', 'Ayo, kamu pasti bisa! 😊'];

  function acak(arr) { return arr[Math.floor(Math.random() * arr.length)]; }
  function nama(t) { return t.replace(/NAMA/g, D.nama); }

  function el(tag, kelas, teks) {
    var e = document.createElement(tag);
    if (kelas) { e.className = kelas; }
    if (teks !== undefined && teks !== null) { e.textContent = teks; }
    return e;
  }

  function gambarEl(src, kelas) {
    var g = document.createElement('img');
    g.src = src; g.alt = ''; g.className = kelas || '';
    return g;
  }

  function maskotBilang(teks, melompat) {
    maskotTeks.textContent = teks;
    maskotTeks.classList.remove('ganti');
    void maskotTeks.offsetWidth;
    maskotTeks.classList.add('ganti');
    if (melompat && maskotGambar) {
      maskotGambar.classList.remove('melompat');
      void maskotGambar.offsetWidth;
      maskotGambar.classList.add('melompat');
    }
  }

  function kirim(url, badan) {
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
      body: JSON.stringify(badan)
    }).then(function (r) {
      return r.json().then(function (j) {
        if (!r.ok || !j.ok) { throw j; }
        return j;
      }, function () { throw {}; });
    });
  }

  function galat(e) {
    var teks = (e && e.pesan) ? e.pesan : 'Ups, ada masalah. Coba muat ulang halaman ya.';
    maskotBilang(teks, false);
  }

  function setProgres(selesaiN) {
    var persen = Math.round(selesaiN / total * 100);
    batang.style.width = persen + '%';
    var bar = batang.parentNode;
    bar.setAttribute('aria-valuenow', String(persen));
  }

  /* ------------------------------------------------ tampilkan satu soal */
  function tampil(i) {
    var s = D.soal[i];
    idx = i;
    terkunci = false;
    sibuk = false;
    noSoal.textContent = String(i + 1);
    setProgres(i);
    tombolLanjut.hidden = true;
    area.innerHTML = '';
    area.classList.remove('baru');
    void area.offsetWidth;
    area.classList.add('baru');

    if (s.gambar) { area.appendChild(gambarEl(s.gambar, 'soal-gambar')); }
    if (s.emoji) { area.appendChild(el('div', 'soal-visual', s.emoji)); }
    area.appendChild(el('p', 'soal-tanya', s.pertanyaan));

    dengar.setAttribute('data-baca', s.pertanyaan);

    if (s.tipe === 'seret') { buatSeret(s); }
    else if (s.tipe === 'cocokkan') { buatCocokkan(s); }
    else { buatPilihan(s); }

    if (i === 0) { maskotBilang('Yuk, kita mulai, ' + D.nama + '!', false); }
    else { maskotBilang('Soal berikutnya, ayo semangat!', false); }
  }

  /* ------------------------------------------------ jawaban benar (soal selesai) */
  function soalBenar(res) {
    terkunci = true;
    setProgres(idx + 1);
    maskotBilang(nama(acak(PUJI)), true);
    if (window.Suara) { window.Suara.benar(); }
    if (window.Confetti) { window.Confetti.ledak(res && res.pertama ? 90 : 45); }
    tombolLanjut.textContent = (idx < total - 1) ? 'Lanjut ➡️' : 'Lihat Hasil 🎉';
    tombolLanjut.hidden = false;
    tombolLanjut.focus({ preventScroll: false });
  }

  function jawabanSalah() {
    maskotBilang(acak(COBA), false);
    if (window.Suara) { window.Suara.salah(); }
  }

  /* ------------------------------------------------ tipe: pilihan */
  function buatPilihan(s) {
    var grid = el('div', 'opsi-grid');
    s.opsi.forEach(function (o) {
      var b = el('button', 'opsi');
      b.type = 'button';
      if (o.gambar) { b.appendChild(gambarEl(o.gambar)); }
      if (o.emoji) { b.appendChild(el('span', 'o-emoji', o.emoji)); }
      if (o.teks) { b.appendChild(el('span', o.emoji || o.gambar ? 'o-teks' : '', o.teks)); }
      b.addEventListener('click', function () {
        if (terkunci || sibuk) { return; }
        sibuk = true;
        kirim(API_CEK, { soal_id: s.id, pilihan_id: o.id }).then(function (res) {
          sibuk = false;
          if (res.benar) {
            b.classList.add('benar');
            var semua = grid.querySelectorAll('.opsi');
            for (var k = 0; k < semua.length; k++) { semua[k].disabled = true; }
            soalBenar(res);
          } else {
            b.classList.add('salah');
            b.disabled = true;
            jawabanSalah();
          }
        }).catch(function (e) { sibuk = false; galat(e); });
      });
      grid.appendChild(b);
    });
    area.appendChild(grid);
  }

  /* ------------------------------------------------ tipe: seret & lepas */
  function buatSeret(s) {
    var kotak = el('div', 'kotak-jawab');
    kotak.appendChild(el('small', '', 'Taruh jawaban di sini'));
    area.appendChild(kotak);

    var wadah = el('div', 'chips');
    s.opsi.forEach(function (o) {
      var c = el('button', 'chip');
      c.type = 'button';
      if (o.gambar) { c.appendChild(gambarEl(o.gambar)); }
      if (o.emoji) { c.appendChild(el('span', '', o.emoji)); }
      if (o.teks) { c.appendChild(el('span', '', o.teks)); }
      pasangSeret(c, o, s, kotak);
      wadah.appendChild(c);
    });
    area.appendChild(wadah);
  }

  // Kirim jawaban seret ke server lalu perbarui tampilan.
  function jawabSeret(chip, o, s, kotak, kembalikan) {
    sibuk = true;
    kirim(API_CEK, { soal_id: s.id, pilihan_id: o.id }).then(function (res) {
      sibuk = false;
      if (res.benar) {
        chip.style.transform = '';
        kotak.innerHTML = '';
        if (o.gambar) { kotak.appendChild(gambarEl(o.gambar)); }
        kotak.appendChild(el('span', '', ((o.emoji ? o.emoji + ' ' : '') + (o.teks || '')).trim()));
        kotak.classList.add('benar');
        var semua = area.querySelectorAll('.chip');
        for (var k = 0; k < semua.length; k++) { semua[k].disabled = true; }
        soalBenar(res);
      } else {
        kembalikan();
        chip.classList.remove('salah'); void chip.offsetWidth; chip.classList.add('salah');
        kotak.classList.remove('salah'); void kotak.offsetWidth; kotak.classList.add('salah');
        jawabanSalah();
      }
    }).catch(function (e) { sibuk = false; kembalikan(); galat(e); });
  }

  function pasangSeret(chip, o, s, kotak) {
    function didalam(x, y) {
      var r = kotak.getBoundingClientRect();
      var m = 24; // toleransi supaya jari anak tidak harus tepat
      return x >= r.left - m && x <= r.right + m && y >= r.top - m && y <= r.bottom + m;
    }
    function kembalikan() {
      chip.classList.add('kembali');
      chip.style.transform = '';
    }

    chip.addEventListener('pointerdown', function (ev) {
      if (terkunci || sibuk || chip.disabled) { return; }
      ev.preventDefault();
      try { chip.setPointerCapture(ev.pointerId); } catch (e) { /* abaikan */ }
      var sx = ev.clientX, sy = ev.clientY, geser = false;
      chip.classList.remove('kembali');

      function selesaiGerak() {
        chip.removeEventListener('pointermove', gerak);
        chip.removeEventListener('pointerup', lepas);
        chip.removeEventListener('pointercancel', batal);
        kotak.classList.remove('sorot');
        chip.classList.remove('diseret');
      }
      function gerak(e) {
        var dx = e.clientX - sx, dy = e.clientY - sy;
        if (!geser && Math.sqrt(dx * dx + dy * dy) > 8) { geser = true; chip.classList.add('diseret'); }
        if (geser) {
          chip.style.transform = 'translate(' + dx + 'px,' + dy + 'px) scale(1.1)';
          kotak.classList.toggle('sorot', didalam(e.clientX, e.clientY));
        }
      }
      function lepas(e) {
        var jatuhDiKotak = !geser || didalam(e.clientX, e.clientY);   // ketuk = langsung jawab
        selesaiGerak();
        if (jatuhDiKotak) { jawabSeret(chip, o, s, kotak, kembalikan); } else { kembalikan(); }
      }
      function batal() { selesaiGerak(); kembalikan(); }

      chip.addEventListener('pointermove', gerak);
      chip.addEventListener('pointerup', lepas);
      chip.addEventListener('pointercancel', batal);
    });

    // Keyboard (Enter/Spasi) untuk aksesibilitas: sama seperti mengetuk.
    chip.addEventListener('keydown', function (ev) {
      if ((ev.key === 'Enter' || ev.key === ' ') && !terkunci && !sibuk) {
        ev.preventDefault();
        jawabSeret(chip, o, s, kotak, kembalikan);
      }
    });
  }

  /* ------------------------------------------------ tipe: cocokkan gambar & kata */
  function buatCocokkan(s) {
    var grid = el('div', 'cocok-grid');
    var kiriKol = el('div', 'kolom');
    var kananKol = el('div', 'kolom');
    var terpilih = null;

    var kanan = s.opsi.slice();
    for (var i = kanan.length - 1; i > 0; i--) {       // acak kolom kanan
      var j = Math.floor(Math.random() * (i + 1));
      var t = kanan[i]; kanan[i] = kanan[j]; kanan[j] = t;
    }
    // Hindari kebetulan urutan kanan = urutan kiri
    var sama = kanan.every(function (o, k) { return o.id === s.opsi[k].id; });
    if (sama && kanan.length > 1) { kanan.push(kanan.shift()); }


    s.opsi.forEach(function (o) {
      var b = el('button', 'tile gambar');
      b.type = 'button';
      b.setAttribute('aria-label', 'Gambar ' + (o.teks || ''));
      if (o.gambar) { b.appendChild(gambarEl(o.gambar)); }
      else { b.textContent = o.emoji || '❓'; }
      b.addEventListener('click', function () {
        if (terkunci || sibuk || b.disabled) { return; }
        if (terpilih) { terpilih.classList.remove('dipilih'); }
        terpilih = { id: o.id, el: b };
        b.classList.add('dipilih');
        if (window.Suara) { window.Suara.klik(); }
        maskotBilang('Sekarang pilih katanya ya!', false);
      });
      kiriKol.appendChild(b);
    });

    kanan.forEach(function (o) {
      var b = el('button', 'tile', o.teks);
      b.type = 'button';
      b.addEventListener('click', function () {
        if (terkunci || sibuk || b.disabled) { return; }
        if (!terpilih) { maskotBilang('Pilih gambarnya dulu ya 😊', false); return; }
        var kiriId = terpilih.id;
        var kiriEl = terpilih.el;
        sibuk = true;
        kirim(API_CEK, { soal_id: s.id, kiri: kiriId, kanan: o.id }).then(function (res) {
          sibuk = false;
          kiriEl.classList.remove('dipilih');
          terpilih = null;
          if (res.benar) {
            kiriEl.classList.add('cocok'); kiriEl.disabled = true;
            b.classList.add('cocok'); b.disabled = true;
            if (res.selesai) { soalBenar(res); }
            else {
              if (window.Suara) { window.Suara.cocok(); }
              maskotBilang('Cocok! Lanjut cari pasangan lainnya!', true);
            }
          } else {
            [kiriEl, b].forEach(function (x) { x.classList.remove('salah'); void x.offsetWidth; x.classList.add('salah'); });
            window.setTimeout(function () { kiriEl.classList.remove('salah'); b.classList.remove('salah'); }, 450);
            jawabanSalah();
          }
        }).catch(function (e) { sibuk = false; galat(e); });
      });
      kananKol.appendChild(b);
    });

    grid.appendChild(kiriKol);
    grid.appendChild(kananKol);
    area.appendChild(grid);
  }

  /* ------------------------------------------------ tombol lanjut / selesai */
  tombolLanjut.addEventListener('click', function () {
    if (idx < total - 1) {
      tampil(idx + 1);
      return;
    }
    tombolLanjut.disabled = true;
    kirim(API_SELESAI, {}).then(function (res) {
      document.body.classList.add('keluar');
      window.setTimeout(function () { window.location.href = res.url; }, 170);
    }).catch(function (e) { tombolLanjut.disabled = false; galat(e); });
  });

  tampil(0);
})();
