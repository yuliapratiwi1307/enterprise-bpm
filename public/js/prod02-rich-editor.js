(function () {
  'use strict';
  const source = document.querySelector('textarea[name="project_description"][data-sms-rich-source]');
  const root = document.querySelector('[data-sms-rich-editor]');
  if (!source || !root) return;
  const editor = root.querySelector('[contenteditable]');
  const counter = root.querySelector('[data-sms-rich-count]');
  const error = root.querySelector('[data-sms-rich-error]');
  const form = source.closest('form');
  const limit = 10000;
  const prefix = 'SMSRICH1:';
  // The server allows these tags and strips attributes except HTTPS links.
  const allowed = new Set(['p', 'div', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'blockquote', 'h3', 'a', 'br']);
  const blocked = new Set(['script', 'style', 'iframe', 'svg', 'math', 'object', 'template', 'form']);
  const utf8len = s => new TextEncoder().encode(s).length;
  const plain = () => (editor.innerText || '').replace(/\n$/, '');

  // Build a separate clean DOM: avoid mutating the editor/caret during typing.
  // Markup copied from Word, websites, etc. often has thousands of extra bytes.
  function cleanNode(node, targetDoc) {
    if (node.nodeType === Node.TEXT_NODE) return targetDoc.createTextNode(node.nodeValue || '');
    if (node.nodeType !== Node.ELEMENT_NODE) return targetDoc.createDocumentFragment();
    const tag = node.tagName.toLowerCase();
    if (blocked.has(tag)) return targetDoc.createDocumentFragment();
    const result = allowed.has(tag) ? targetDoc.createElement(tag) : targetDoc.createDocumentFragment();
    if (tag === 'a' && result.nodeType === Node.ELEMENT_NODE) {
      const href = (node.getAttribute('href') || '').trim();
      if (/^https:\/\/[^\s<>"']+$/i.test(href)) result.setAttribute('href', href);
      else {
        const fragment = targetDoc.createDocumentFragment();
        node.childNodes.forEach(child => fragment.appendChild(cleanNode(child, targetDoc)));
        return fragment;
      }
    }
    node.childNodes.forEach(child => result.appendChild(cleanNode(child, targetDoc)));
    return result;
  }
  function compactHTML() {
    const holder = document.createElement('div');
    editor.childNodes.forEach(node => holder.appendChild(cleanNode(node, document)));
    return holder.innerHTML;
  }
  let selection = null;
  function sync() {
    const p = plain();
    const payload = prefix + JSON.stringify({v: 1, html: compactHTML()});
    const textLength = [...p].length;
    const bytes = utf8len(payload);
    const tooLong = textLength > limit || bytes > limit;
    counter.textContent = textLength.toLocaleString('id-ID') + ' / 10.000 karakter';
    error.hidden = !tooLong;
    if (tooLong) {
      error.textContent = textLength > limit
        ? 'Deskripsi melebihi batas 10.000 karakter.'
        : 'Format deskripsi masih melebihi batas 10.000 byte penyimpanan (' + bytes.toLocaleString('id-ID') + ' byte). Sederhanakan format atau kurangi isi.';
    }
    source.value = payload;
    return !tooLong;
  }
  document.addEventListener('selectionchange', () => {
    const sel = window.getSelection();
    if (sel && sel.rangeCount && editor.contains(sel.anchorNode)) selection = sel.getRangeAt(0).cloneRange();
  });
  const focus = () => { editor.focus(); if (selection) { const sel = window.getSelection(); sel.removeAllRanges(); sel.addRange(selection); } };
  root.querySelectorAll('[data-rich-cmd]').forEach(btn => btn.addEventListener('click', () => {
    focus(); document.execCommand(btn.dataset.richCmd, false, null); sync();
  }));
  root.querySelector('[data-rich-format]').addEventListener('change', e => {
    focus(); document.execCommand('formatBlock', false, e.target.value); sync();
  });
  root.querySelector('[data-rich-link]').addEventListener('click', () => {
    const input = window.prompt('Masukkan tautan HTTPS:');
    if (!input) return;
    try { const url = new URL(input); if (url.protocol !== 'https:') throw Error(); focus(); document.execCommand('createLink', false, url.href); sync(); }
    catch (_) { window.alert('Hanya tautan HTTPS yang diperbolehkan.'); }
  });
  editor.addEventListener('input', sync);
  if (form) form.addEventListener('submit', event => {
    if (!sync()) { event.preventDefault(); editor.focus(); }
  });
  sync();
})();
