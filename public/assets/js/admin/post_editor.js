/* global bootstrap */

document.addEventListener('DOMContentLoaded', function () {
  'use strict';

  // ── Element refs ─────────────────────────────────────────────────────────
  const form          = document.getElementById('post-editor-form');
  const titleInput    = document.getElementById('field-title');
  const slugInput     = document.getElementById('field-slug');
  const bodyTextarea  = document.getElementById('field-body');
  const tagsInput     = document.getElementById('field-tags');
  const tagsEntry     = document.getElementById('field-tags-input');
  const tagBadges     = document.getElementById('tag-badges');
  const pubAtInput    = document.getElementById('field-published-at');
  const statusSelect  = document.getElementById('field-status');
  const charCount     = document.getElementById('body-char-count');

  const btnGenerateSlug = document.getElementById('btn-generate-slug');
  const btnCopySlug     = document.getElementById('btn-copy-slug');
  const btnQuickPublish = document.getElementById('btn-quick-publish');

  const previewUrl      = form.dataset.previewUrl;

  const previewLoading  = document.getElementById('preview-loading');
  const previewEmpty    = document.getElementById('preview-empty');
  const previewArticle  = document.getElementById('preview-article');
  const previewTitle    = document.getElementById('preview-title');
  const previewDate     = document.getElementById('preview-date');
  const previewBody     = document.getElementById('preview-body');
  const previewTagsWrap = document.getElementById('preview-tags-wrap');
  const previewTags     = document.getElementById('preview-tags');

  const uploadUrl = form ? form.dataset.uploadUrl : null;
  const removeUrl = form ? form.dataset.removeUrl : null;
  const featuredDropzone = document.getElementById('featured-dropzone');
  const featuredFileInput = document.getElementById('field-featured-file');
  const featuredInput = document.getElementById('field-featured-image');
  const featuredPreview = document.getElementById('featured-preview');
  const featuredThumb = document.getElementById('featured-thumb');
  const btnRemoveFeatured = document.getElementById('btn-remove-featured-image');

  const unsavedToast    = bootstrap.Toast.getOrCreateInstance(
    document.getElementById('unsaved-toast'),
  );

  // ── State ────────────────────────────────────────────────────────────────
  let isDirty        = false;
  let previewDirty   = true;  // preview needs a refresh
  let previewTimer   = null;
  let slugUserEdited = slugInput.value.trim() !== '';
  // tags array state (derived from hidden CSV input)
  let tagsArray = [];

  // ── Slug helpers ─────────────────────────────────────────────────────────
  function slugify(text) {
    return text
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')   // strip combining diacritics
      .replace(/[^a-z0-9\s-]/g, '')
      .trim()
      .replace(/[\s_]+/g, '-')
      .replace(/-{2,}/g, '-');
  }

  titleInput.addEventListener('input', function () {
    if (!slugUserEdited) {
      slugInput.value = slugify(titleInput.value);
    }
    markDirty();
    schedulePreview();
  });

  slugInput.addEventListener('input', function () {
    slugUserEdited = slugInput.value.trim() !== '';
    markDirty();
  });

  btnGenerateSlug.addEventListener('click', function () {
    slugInput.value = slugify(titleInput.value);
    slugUserEdited  = false;
    markDirty();
  });

  btnCopySlug.addEventListener('click', function () {
    const base = window.location.origin + '/posts/';
    const url  = base + (slugInput.value.trim() || slugify(titleInput.value));
    navigator.clipboard.writeText(url).then(function () {
      const icon = btnCopySlug.querySelector('i');
      icon.className = 'bi bi-clipboard-check';
      setTimeout(function () { icon.className = 'bi bi-clipboard'; }, 1500);
    });
  });

  // ── Body editor ──────────────────────────────────────────────────────────
  function updateCharCount() {
    const len = bodyTextarea.value.length;
    charCount.textContent = len.toLocaleString() + ' char' + (len === 1 ? '' : 's');
  }

  bodyTextarea.addEventListener('input', function () {
    updateCharCount();
    markDirty();
    schedulePreview();
  });

  // Tab key inserts spaces instead of shifting focus
  bodyTextarea.addEventListener('keydown', function (e) {
    // Helper to replace a range and set caret
    function replaceRange(el, start, end, text, caretOffsetAfter) {
      const before = el.value.substring(0, start);
      const after = el.value.substring(end);
      el.value = before + text + after;
      const pos = before.length + (caretOffsetAfter == null ? text.length : caretOffsetAfter);
      el.selectionStart = el.selectionEnd = pos;
    }

    // Tab: insert 4 spaces
    if (e.key === 'Tab') {
      e.preventDefault();
      const start = this.selectionStart;
      const end   = this.selectionEnd;
      replaceRange(this, start, end, '    ', 4);
      markDirty();
      updateCharCount();
      schedulePreview();
      return;
    }

    // Bold / Italic shortcuts: Ctrl/Cmd+B and Ctrl/Cmd+I
    if ((e.ctrlKey || e.metaKey) && !e.altKey) {
      const k = ('' + e.key).toLowerCase();
      if (k === 'b' || k === 'i') {
        e.preventDefault();
        const selStart = this.selectionStart;
        const selEnd = this.selectionEnd;
        const selected = this.value.substring(selStart, selEnd);
        const wrapper = k === 'b' ? ['**', '**'] : ['*', '*'];
        if (selStart === selEnd) {
          // insert markers and put caret between
          replaceRange(this, selStart, selEnd, wrapper[0] + wrapper[1], wrapper[0].length);
        } else {
          replaceRange(this, selStart, selEnd, wrapper[0] + selected + wrapper[1], wrapper[0].length + selected.length);
          // reselect the original text (without wrappers)
          this.selectionStart = selStart + wrapper[0].length;
          this.selectionEnd = selStart + wrapper[0].length + selected.length;
        }
        markDirty();
        updateCharCount();
        schedulePreview();
        return;
      }
    }

    // Backtick handling: wrap selection in inline code, or expand `` + ` -> fenced block
    if (e.key === '`' && !e.ctrlKey && !e.metaKey && !e.altKey) {
      const selStart = this.selectionStart;
      const selEnd = this.selectionEnd;
      const selected = this.value.substring(selStart, selEnd);

      if (selStart !== selEnd) {
        // wrap selection in single backticks
        e.preventDefault();
        replaceRange(this, selStart, selEnd, '`' + selected + '`', 1 + selected.length);
        this.selectionStart = selStart + 1;
        this.selectionEnd = selStart + 1 + selected.length;
        markDirty();
        schedulePreview();
        updateCharCount();
        return;
      }

      // If the two chars immediately before caret are `` then expand into a fenced block
      const prev2 = this.value.substring(Math.max(0, selStart - 2), selStart);
      if (prev2 === '``') {
        e.preventDefault();
        const before = this.value.substring(0, selStart - 2);
        const after = this.value.substring(selEnd);
        const insert = '```\n\n```';
        this.value = before + insert + after;
        // place caret on the blank line inside the fenced block
        const caretPos = before.length + 4; // 3 backticks + newline
        this.selectionStart = this.selectionEnd = caretPos;
        markDirty();
        schedulePreview();
        updateCharCount();
        return;
      }

      // otherwise allow the backtick to be typed normally
      return;
    }

    // Auto-continue lists when pressing Enter on a list item
    if (e.key === 'Enter' && !e.ctrlKey && !e.metaKey && !e.altKey) {
      const selStart = this.selectionStart;
      const selEnd = this.selectionEnd;
      const val = this.value;
      const lineStart = val.lastIndexOf('\n', selStart - 1) + 1;
      const line = val.substring(lineStart, selStart);
      // Match bullets (-, *, +), numbered lists (1.), and task lists (- [ ] / - [x])
      const m = line.match(/^(\s*)([-*+]|(\d+)\.)\s+(\[[ xX]\]\s*)?/);
      if (m) {
        e.preventDefault();
        const indent = m[1] || '';
        const marker = m[2];
        let nextMarker = marker;
        if (/^\d+\.$/.test(marker)) {
          // increment numbered list
          const num = parseInt(m[3], 10) || 0;
          nextMarker = (num + 1) + '.';
        }
        // preserve task-box prefix if present
        const task = m[4] || '';
        const insert = '\n' + indent + nextMarker + ' ' + task;
        replaceRange(this, selStart, selEnd, insert, insert.length);
        markDirty();
        schedulePreview();
        updateCharCount();
        return;
      }
    }
  });

  // ── Preview ──────────────────────────────────────────────────────────────
  function schedulePreview() {
    previewDirty = true;
    clearTimeout(previewTimer);
    previewTimer = setTimeout(function () {
      // Only auto-refresh if the preview pane is visible
      if (document.getElementById('pane-preview').classList.contains('show')) {
        fetchPreview();
      }
    }, 800);
  }

  function fetchPreview() {
    const markdown = bodyTextarea.value.trim();

    if (!markdown) {
      showPreviewEmpty();
      previewDirty = false;
      return;
    }

    showPreviewLoading();

    fetch(previewUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ markdown }),
    })
      .then(function (res) {
        if (!res.ok) { throw new Error('Preview request failed'); }
        return res.json();
      })
      .then(function (data) {
        renderPreview(data.body_html || '');
        previewDirty = false;
      })
      .catch(function () {
        renderPreview('<p class="text-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>Preview unavailable.</p>');
        previewDirty = false;
      });
  }

  function showPreviewLoading() {
    previewLoading.hidden  = false;
    previewEmpty.hidden    = true;
    previewArticle.hidden  = true;
  }

  function showPreviewEmpty() {
    previewLoading.hidden = true;
    previewEmpty.hidden   = false;
    previewArticle.hidden = true;
  }

  function renderPreview(bodyHtml) {
    previewLoading.hidden = true;
    previewEmpty.hidden   = true;
    previewArticle.hidden = false;

    // Title
    previewTitle.textContent = titleInput.value || '(No title)';

    // Date: use published_at if set, otherwise today
    let dateStr = '';
    if (pubAtInput && pubAtInput.value) {
      const d = new Date(pubAtInput.value);
      dateStr = d.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
      previewDate.setAttribute('datetime', d.toISOString());
    } else {
      const now = new Date();
      dateStr = now.toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
      previewDate.setAttribute('datetime', now.toISOString());
    }
    previewDate.textContent = dateStr;

    // Body HTML (server-rendered)
    // DOMPurify is not available in this project; the preview is admin-only trusted content
    previewBody.innerHTML = bodyHtml;

    // Tags
    const rawTags = tagsInput ? tagsInput.value : '';
    const tagList = rawTags.split(',').map(function (t) { return t.trim(); }).filter(Boolean);

    if (tagList.length > 0) {
      previewTags.innerHTML = tagList.map(function (t) {
        return '<span class="badge" style="background-color:#44475a;color:#f8f8f2;">' +
          t.replace(/[<>&"]/g, function (c) {
            return ({ '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;' })[c];
          }) + '</span>';
      }).join('');
      previewTagsWrap.hidden = false;
    } else {
      previewTags.innerHTML  = '';
      previewTagsWrap.hidden = true;
    }
  }

  // Refresh preview when switching to the Preview tab
  document.getElementById('tab-preview').addEventListener('shown.bs.tab', function () {
    if (previewDirty) {
      fetchPreview();
    }
  });

  // Also refresh preview when tags or published-at change
  // Initialize tags state from hidden input value
  if (tagsInput) {
    tagsArray = tagsInput.value ? tagsInput.value.split(',').map(function (t) { return t.trim(); }).filter(Boolean) : [];
    function renderTags(markDirty = true) {
      // update hidden CSV
      tagsInput.value = tagsArray.join(', ');
      // render badges
      if (tagBadges) {
        tagBadges.innerHTML = tagsArray.map(function (t) {
          return '<span class="badge bg-secondary me-1 mb-1 tag-badge" role="button" tabindex="0">' +
            t.replace(/[<>&\"]/g, function (c) { return ({ '<': '&lt;', '>': '&gt;', '&': '&amp;', '\"': '&quot;' })[c]; }) +
            '</span>';
        }).join('');
      }
      if (markDirty) {
        markDirty = false; // shadow var -> avoid clobbering function name
        isDirty = true;
        unsavedToast.show();
        previewDirty = true;
      }
    }

    function addTag(tag) {
      if (!tag) return;
      tag = tag.trim();
      if (!tag) return;
      if (tagsArray.indexOf(tag) !== -1) return;
      tagsArray.push(tag);
      renderTags(true);
    }

    function removeTagAtIndex(i) {
      if (i < 0 || i >= tagsArray.length) return;
      tagsArray.splice(i, 1);
      renderTags(true);
    }

    // Entry input: add tag on comma, Enter, or blur
    if (tagsEntry) {
      tagsEntry.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ',') {
          e.preventDefault();
          var raw = this.value || '';
          raw.split(',').forEach(function (part) { addTag(part); });
          this.value = '';
        }
      });
      tagsEntry.addEventListener('blur', function () {
        var raw = this.value || '';
        raw.split(',').forEach(function (part) { addTag(part); });
        this.value = '';
      });
    }

    // Click to remove tag (event delegation)
    if (tagBadges) {
      tagBadges.addEventListener('click', function (e) {
        var el = e.target;
        if (el.classList.contains('tag-badge')) {
          // find index by matching textContent
          var txt = el.textContent.trim();
          var idx = tagsArray.indexOf(txt);
          if (idx !== -1) removeTagAtIndex(idx);
        }
      });
    }

    // Initial render (do not mark dirty)
    renderTags(false);
  }
  if (pubAtInput) {
    pubAtInput.addEventListener('change', function () {
      markDirty();
      previewDirty = true;
    });
  }

  // ── Featured image upload/drop handling ───────────────────────────────
  (function () {
    if (!featuredDropzone || !featuredInput) return;

    const allowedTypes = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];

    function showUploadError(msg) {
      // simple feedback for now
      alert(msg);
    }

    function appendCsrfToFormData(fd) {
      if (!form) return;
      const csrfInput = form.querySelector('input[type="hidden"][name^="csrf"]');
      if (csrfInput) {
        fd.append(csrfInput.name, csrfInput.value);
      }
    }

    function uploadFile(file) {
      if (!file) return;
      if (allowedTypes.indexOf(file.type) === -1) {
        showUploadError('Invalid file type. Allowed: png, jpeg, webp, gif.');
        return;
      }

      const reader = new FileReader();
      reader.onload = function (e) {
        const img = new Image();
        img.onload = function () {
          if (img.naturalWidth !== 1200 || img.naturalHeight !== 630) {
            showUploadError('Image must be exactly 1200 × 630 pixels.');
            return;
          }

          if (!uploadUrl) {
            showUploadError('Upload URL not configured.');
            return;
          }

          const fd = new FormData();
          fd.append('featured_image', file, file.name);
          appendCsrfToFormData(fd);

          fetch(uploadUrl, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
          })
            .then(function (res) { return res.json(); })
            .then(function (data) {
              if (!data || !data.success) {
                showUploadError((data && data.error) || 'Upload failed');
                return;
              }
              // update hidden input and preview
              featuredInput.value = data.filename || '';
              if (featuredThumb) {
                featuredThumb.src = data.url || (window.location.origin + '/media/' + data.filename);
                featuredThumb.style.display = '';
              }
              if (featuredPreview) featuredPreview.style.display = '';
              if (btnRemoveFeatured) btnRemoveFeatured.style.display = '';
              markDirty();
            })
            .catch(function () { showUploadError('Upload failed'); });
        };
        img.onerror = function () { showUploadError('Unable to load image for validation.'); };
        img.src = e.target.result;
      };
      reader.readAsDataURL(file);
    }

    // Dropzone interactions
    featuredDropzone.addEventListener('click', function () { if (featuredFileInput) featuredFileInput.click(); });
    featuredDropzone.addEventListener('dragover', function (e) { e.preventDefault(); featuredDropzone.classList.add('border-primary'); });
    featuredDropzone.addEventListener('dragleave', function () { featuredDropzone.classList.remove('border-primary'); });
    featuredDropzone.addEventListener('drop', function (e) {
      e.preventDefault();
      featuredDropzone.classList.remove('border-primary');
      const f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
      if (f) uploadFile(f);
    });

    if (featuredFileInput) {
      featuredFileInput.addEventListener('change', function () {
        const f = this.files && this.files[0];
        if (f) uploadFile(f);
      });
    }

    // Remove handler
    if (btnRemoveFeatured) {
      btnRemoveFeatured.addEventListener('click', function () {
        const filename = featuredInput.value && featuredInput.value.trim();
        if (!filename) {
          // nothing to remove
          featuredInput.value = '';
          if (featuredThumb) featuredThumb.style.display = 'none';
          if (featuredPreview) featuredPreview.style.display = 'none';
          if (btnRemoveFeatured) btnRemoveFeatured.style.display = 'none';
          markDirty();
          return;
        }
        if (!removeUrl) {
          showUploadError('Remove URL not configured.');
          return;
        }

        const fd = new FormData();
        fd.append('filename', filename);
        appendCsrfToFormData(fd);

        fetch(removeUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(function (res) { return res.json(); })
          .then(function (data) {
            featuredInput.value = '';
            if (featuredThumb) featuredThumb.style.display = 'none';
            if (featuredPreview) featuredPreview.style.display = 'none';
            if (btnRemoveFeatured) btnRemoveFeatured.style.display = 'none';
            markDirty();
          })
          .catch(function () { showUploadError('Failed to remove image'); });
      });
    }

    // Choose existing featured image from media library
    const btnChooseExisting = document.getElementById('btn-choose-existing');
    const listUrl = form ? form.dataset.listUrl : null;
    const featuredModalEl = document.getElementById('featured-library-modal');
    const featuredLibraryGrid = featuredModalEl ? featuredModalEl.querySelector('#featured-library-grid') : null;
    // Move modal to document.body to avoid stacking-context / z-index issues
    if (featuredModalEl && featuredModalEl.parentNode !== document.body) {
      document.body.appendChild(featuredModalEl);
    }
    const featuredModalInstance = featuredModalEl ? new bootstrap.Modal(featuredModalEl) : null;

    if (btnChooseExisting && featuredModalEl && listUrl && featuredLibraryGrid && featuredModalInstance) {
      btnChooseExisting.addEventListener('click', function () {
        // show modal and fetch list
        featuredLibraryGrid.innerHTML = '<div class="col-12 text-center p-4 text-secondary">Loading…</div>';
        featuredModalInstance.show();

        fetch(listUrl, { credentials: 'same-origin' })
          .then(function (res) { if (!res.ok) throw new Error('Failed'); return res.json(); })
          .then(function (data) {
            featuredLibraryGrid.innerHTML = '';
            const files = (data && data.files) ? data.files : [];
            if (files.length === 0) {
              featuredLibraryGrid.innerHTML = '<div class="col-12 text-center p-4 text-muted">No images found.</div>';
              return;
            }

            files.forEach(function (f) {
              const col = document.createElement('div');
              col.className = 'col-6 col-md-4 col-lg-3';
              const wrap = document.createElement('div');
              wrap.className = 'fn-thumb-wrap rounded overflow-hidden border bg-white';
              wrap.style.cursor = 'pointer';
              wrap.tabIndex = 0;

              const img = document.createElement('img');
              img.className = 'fn-thumb';
              img.loading = 'lazy';
              img.alt = f.filename;
              img.src = f.url;

              wrap.appendChild(img);
              wrap.addEventListener('click', function () {
                featuredInput.value = f.filename;
                if (featuredThumb) {
                  featuredThumb.src = f.url;
                  featuredThumb.style.display = '';
                }
                if (featuredPreview) featuredPreview.style.display = '';
                if (btnRemoveFeatured) btnRemoveFeatured.style.display = '';
                markDirty();
                featuredModalInstance.hide();
              });

              col.appendChild(wrap);
              featuredLibraryGrid.appendChild(col);
            });
          })
          .catch(function () {
            featuredLibraryGrid.innerHTML = '<div class="col-12 text-center p-4 text-danger">Unable to load images.</div>';
          });
      });
    }
  })();

  // ── Dirty-state tracking ─────────────────────────────────────────────────
  function markDirty() {
    if (!isDirty) {
      isDirty = true;
      unsavedToast.show();
    }
  }

  // Listen for changes on all other form inputs
  form.querySelectorAll('input, textarea, select').forEach(function (el) {
    if (el === titleInput || el === slugInput || el === bodyTextarea || el === tagsInput || el === pubAtInput) {
      return; // already handled above
    }
    el.addEventListener('change', markDirty);
  });

  // Warn before navigating away with unsaved changes
  window.addEventListener('beforeunload', function (e) {
    if (isDirty) {
      e.preventDefault();
      e.returnValue = '';
    }
  });

  // Clear dirty flag on form submit
  form.addEventListener('submit', function () {
    isDirty = false;
    unsavedToast.hide();

    // If quick-publish button triggered the submit, set status to published
    const active = document.activeElement;
    if (btnQuickPublish && active === btnQuickPublish) {
      statusSelect.value = 'published';
      if (pubAtInput && !pubAtInput.value) {
        const pad  = function (n) { return String(n).padStart(2, '0'); };
        const now  = new Date();
        pubAtInput.value = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate())
          + 'T' + pad(now.getHours()) + ':' + pad(now.getMinutes());
      }
    }
  });

  // ── Sidebar: highlight active nav link ──────────────────────────────────
  const sidebarLinks = document.querySelectorAll('#sidebar .nav-link');
  // If the form contains a post id it's edit mode; otherwise it's a new post.
  (function updateSidebarActive() {
    const postId = form ? String(form.dataset.postId || '').trim() : '';
    if (!form) return;

    if (!postId) {
      // Creating a new post: mark the admin dashboard link as active.
      sidebarLinks.forEach(function (link) {
        if (link.getAttribute('href') === '/admin/posts/create') {
          link.classList.remove('text-white-50');
          link.classList.add('active');
        } else {
          link.classList.remove('active');
          if (!link.classList.contains('text-white-50')) {
            link.classList.add('text-white-50');
          }
        }
      });
    } else {
      // Editing an existing post: remove all active menu items.
      sidebarLinks.forEach(function (link) {
        link.classList.remove('active');
        if (!link.classList.contains('text-white-50')) {
          link.classList.add('text-white-50');
        }
      });
    }
  })();

  // ── Init ─────────────────────────────────────────────────────────────────
  updateCharCount();
});
