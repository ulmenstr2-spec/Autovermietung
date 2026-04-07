
function toggleFaq(btn) {
  var a = btn.nextElementSibling;
  var isOpen = btn.classList.contains('open');
  document.querySelectorAll('.faq-q').forEach(function(b){ b.classList.remove('open'); });
  document.querySelectorAll('.faq-a').forEach(function(a){ a.classList.remove('open'); });
  if (!isOpen) { btn.classList.add('open'); a.classList.add('open'); }
}



// Scroll reveal
var revealEls = document.querySelectorAll('.reveal');
var observer = new IntersectionObserver(function(entries) {
  entries.forEach(function(e) {
    if (e.isIntersecting) {
      e.target.classList.add('visible');
      observer.unobserve(e.target);
    }
  });
}, { threshold: 0.12 });
revealEls.forEach(function(el) { observer.observe(el); });

// Nav scroll shadow
var nav = document.querySelector('nav');
window.addEventListener('scroll', function() {
  if (window.scrollY > 20) {
    nav.classList.add('scrolled');
  } else {
    nav.classList.remove('scrolled');
  }
}, { passive: true });

// Smooth stat counter animation
function animateCount(el, target, suffix) {
  var start = 0;
  var duration = 1200;
  var startTime = null;
  function step(ts) {
    if (!startTime) startTime = ts;
    var progress = Math.min((ts - startTime) / duration, 1);
    var ease = 1 - Math.pow(1 - progress, 3);
    el.textContent = Math.floor(ease * target) + suffix;
    if (progress < 1) requestAnimationFrame(step);
  }
  requestAnimationFrame(step);
}

var statsObserver = new IntersectionObserver(function(entries) {
  entries.forEach(function(e) {
    if (e.isIntersecting) {
      var nums = e.target.querySelectorAll('.stat-number');
      nums.forEach(function(n) {
        var txt = n.textContent.trim();
        if (txt === '100+') animateCount(n, 100, '+');
        else if (txt === '5,0') { /* leave as is */ }
        else if (txt === 'Rostock') { /* leave as is */ }
      });
      statsObserver.unobserve(e.target);
    }
  });
}, { threshold: 0.5 });
var statsBar = document.querySelector('.stats-bar');
if (statsBar) statsObserver.observe(statsBar);



(function() {
  var params = new URLSearchParams(window.location.search);
  var status = params.get('status');
  if (!status) return;
  var anfrage = document.getElementById('anfrage');
  if (!anfrage) return;
  var msg = document.createElement('div');
  msg.style.cssText = 'background:' + (status === 'ok' ? '#e8f5e9' : '#fdecea') + ';border:1px solid ' + (status === 'ok' ? '#a5d6a7' : '#f5c6cb') + ';border-radius:4px;padding:1rem 1.5rem;margin-bottom:1.5rem;font-size:0.9rem;color:' + (status === 'ok' ? '#2e7d32' : '#c62828') + ';';
  msg.textContent = status === 'ok'
    ? 'Ihre Anfrage wurde erfolgreich gesendet. Ich melde mich in Kürze bei Ihnen.'
    : 'Beim Senden ist ein Fehler aufgetreten. Bitte schreiben Sie mir direkt per WhatsApp.';
  var form = anfrage.querySelector('form');
  if (form) form.parentNode.insertBefore(msg, form);
  window.history.replaceState({}, '', window.location.pathname + (window.location.hash || ''));
})();
