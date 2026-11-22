import Quill from 'quill';
import 'quill/dist/quill.snow.css';
import '../css/sjr-rich-editor.css';

window.Quill = Quill;

const FONTS = ['arial', 'georgia', 'times-new-roman', 'courier-new'];
const SIZES = ['10px', '12px', '14px', '16px', '18px', '24px', '32px'];

const Font = Quill.import('formats/font');
Font.whitelist = FONTS;
Quill.register(Font, true);

const Size = Quill.import('attributors/style/size');
Size.whitelist = SIZES;
Quill.register(Size, true);

const SOURCE_SELECTOR = 'textarea[data-spec-visible]';

function isHtml(value) {
  return /<[a-z][\s\S]*>/i.test(String(value || ''));
}

function syncTextarea(textarea, quill) {
  const html = quill.root.innerHTML === '<p><br></p>' ? '' : quill.root.innerHTML;
  textarea.value = html;
  textarea.dispatchEvent(new Event('input', { bubbles: true }));
  textarea.dispatchEvent(new Event('change', { bubbles: true }));

  const row = textarea.closest('.dynamic-material-row');
  const hidden = row && row.querySelector('.sjr-material-spec-hidden');
  if (hidden) hidden.value = html;
}

function createToolbarContainer() {
  const container = document.createElement('div');
  container.className = 'sjr-quill-toolbar';
  container.setAttribute('role', 'toolbar');
  container.setAttribute('aria-label', 'Toolbar spesifikasi');
  return container;
}

function addCustomControls(toolbar, quill, textarea) {
  // Color picker
  const colorFormats = document.createElement('span');
  colorFormats.className = 'ql-formats';
  colorFormats.innerHTML = `
    <label class="sjr-color-control">
      Warna <input type="color" class="ql-color" value="#172742" aria-label="Warna teks">
    </label>
  `;
  toolbar.appendChild(colorFormats);
  
  const colorPicker = colorFormats.querySelector('.ql-color');
  colorPicker.addEventListener('input', (e) => {
    const range = quill.getSelection();
    if (range) quill.setSelection(range, 'silent');
    quill.format('color', e.target.value, 'user');
    syncTextarea(textarea, quill);
  });

  // Alignment buttons
  const alignFormats = document.createElement('span');
  alignFormats.className = 'ql-formats';
  alignFormats.innerHTML = `
    <button type="button" class="ql-align" data-value="" aria-label="Rata kiri">☰</button>
    <button type="button" class="ql-align" data-value="center" aria-label="Rata tengah">≡</button>
    <button type="button" class="ql-align" data-value="right" aria-label="Rata kanan">☷</button>
  `;
  toolbar.appendChild(alignFormats);
  
  alignFormats.querySelectorAll('.ql-align').forEach((btn) => {
    btn.addEventListener('click', () => {
      const value = btn.dataset.value;
      const range = quill.getSelection();
      if (range) quill.setSelection(range, 'silent');
      quill.format('align', value || false, 'user');
      syncTextarea(textarea, quill);
    });
  });
}

function initEditor(textarea) {
  if (textarea.__sjrQuill) return;

  const existing = textarea.nextElementSibling;
  if (existing && existing.classList.contains('sjr-quill-shell')) {
    existing.remove();
  }

  textarea.classList.add('sjr-quill-source');

  const shell = document.createElement('div');
  shell.className = 'sjr-quill-shell';
  
  const toolbar = createToolbarContainer();
  const editor = document.createElement('div');
  editor.className = 'sjr-quill-editor';
  
  shell.appendChild(toolbar);
  shell.appendChild(editor);
  textarea.insertAdjacentElement('afterend', shell);

  // Configure Quill with native toolbar using array configuration
  const quill = new Quill(editor, {
    theme: 'snow',
    modules: {
      toolbar: {
        container: toolbar,
      },
    },
    formats: [
      'font', 'size', 'bold', 'italic', 'underline', 'strike',
      'color', 'align', 'list', 'link'
    ],
    placeholder: textarea.getAttribute('placeholder') || 'Tuliskan spesifikasi lengkap material di sini...',
  });

  // Configure the toolbar module with our desired structure
  const toolbarModule = quill.getModule('toolbar');
  if (toolbarModule) {
    toolbar.innerHTML = '';
    
    const toolbarConfig = [
      [{ font: FONTS }, { size: SIZES }],
      ['bold', 'italic', 'underline', 'strike'],
      [{ list: 'ordered' }, { list: 'bullet' }],
      ['link', 'clean']
    ];
    
    toolbarConfig.forEach(group => {
      const groupEl = document.createElement('span');
      groupEl.className = 'ql-formats';
      group.forEach(format => {
        if (typeof format === 'string') {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = `ql-${format}`;
          btn.setAttribute('aria-label', format);
          groupEl.appendChild(btn);
        } else if (typeof format === 'object') {
          Object.entries(format).forEach(([formatName, values]) => {
            if (formatName === 'font' || formatName === 'size') {
              const select = document.createElement('select');
              select.className = `ql-${formatName}`;
              values.forEach(value => {
                const option = document.createElement('option');
                option.value = value;
                let displayValue = value;
                if (formatName === 'font') {
                  displayValue = value.charAt(0).toUpperCase() + value.slice(1).replace('-', ' ');
                } else if (formatName === 'size') {
                  displayValue = value;
                }
                option.textContent = displayValue;
                select.appendChild(option);
              });
              groupEl.appendChild(select);
            } else if (formatName === 'list') {
              const btn = document.createElement('button');
              btn.type = 'button';
              btn.className = `ql-${formatName}`;
              btn.setAttribute('value', values);
              btn.setAttribute('aria-label', `Daftar ${values === 'ordered' ? 'bernomor' : 'bullet'}`);
              groupEl.appendChild(btn);
            }
          });
        }
      });
      toolbar.appendChild(groupEl);
    });
    
    // Re-attach handlers for the new elements
    const selects = toolbar.querySelectorAll('select');
    selects.forEach(select => toolbarModule.attach(select));
    const buttons = toolbar.querySelectorAll('button');
    buttons.forEach(button => toolbarModule.attach(button));
  }

  // Add our custom controls
  addCustomControls(toolbar, quill, textarea);

  if (textarea.value && textarea.value.trim()) {
    if (isHtml(textarea.value)) {
      quill.clipboard.dangerouslyPasteHTML(textarea.value, 'silent');
    } else {
      quill.setText(textarea.value, 'silent');
    }
  } else {
    quill.setText('', 'silent');
  }

  textarea.__sjrQuill = quill;

  quill.on('text-change', () => syncTextarea(textarea, quill));
  quill.root.addEventListener('blur', () => syncTextarea(textarea, quill));
  syncTextarea(textarea, quill);
}

function destroyEditor(textarea) {
  if (textarea.__sjrQuill) {
    const shell = textarea.nextElementSibling;
    if (shell && shell.classList.contains('sjr-quill-shell')) {
      shell.remove();
    }
    textarea.classList.remove('sjr-quill-source');
    textarea.__sjrQuill = null;
  }
}

function scan(root) {
  if (!root) return;
  if (root.matches && root.matches(SOURCE_SELECTOR)) initEditor(root);
  if (root.querySelectorAll) {
    root.querySelectorAll(SOURCE_SELECTOR).forEach((textarea) => {
      if (!textarea.__sjrQuill) initEditor(textarea);
    });
  }
}

function bindFormSubmit() {
  document.querySelectorAll('form[data-tech-form]').forEach((form) => {
    if (form.dataset.sjrQuillSubmitBound === '1') return;
    form.dataset.sjrQuillSubmitBound = '1';
    
    // Use submit event with capture to run before default submit
    form.addEventListener('submit', (e) => {
      // Sync all editors in this form before submit
      form.querySelectorAll(SOURCE_SELECTOR).forEach((textarea) => {
        if (textarea.__sjrQuill) {
          syncTextarea(textarea, textarea.__sjrQuill);
        }
      });
    }, true); // capture phase
  });
}

function start() {
  scan(document);
  if (!window.__sjrQuillObserver) {
    window.__sjrQuillObserver = new MutationObserver((records) => {
      records.forEach((record) => record.addedNodes.forEach((node) => {
        if (node.nodeType === 1) scan(node);
      }));
    });
    window.__sjrQuillObserver.observe(document.body, { childList: true, subtree: true });
  }
  bindFormSubmit();
  document.addEventListener('click', (e) => {
    const addBtn = e.target.closest('[data-add="materials"]');
    if (addBtn) {
      setTimeout(() => {
        document.querySelectorAll('form[data-tech-form]').forEach((form) => {
          form.querySelectorAll(SOURCE_SELECTOR).forEach((textarea) => {
            if (!textarea.__sjrQuill) initEditor(textarea);
          });
        });
      }, 0);
    }
  });
  console.info('[SJR] Quill V2 aktif:', document.querySelectorAll(SOURCE_SELECTOR).length, 'textarea spesifikasi.');
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', start, { once: true });
} else {
  start();
}

export { initEditor, destroyEditor, scan, syncTextarea };