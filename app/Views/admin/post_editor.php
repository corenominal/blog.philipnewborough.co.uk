<?= $this->extend('templates/dashboard') ?>

<?= $this->section('content') ?>
<div class="container-fluid post-editor">

    <?php
        $post       = $post ?? null;
        $isNew      = $isNew ?? true;
        $postTitle  = $isNew ? 'New Post' : ('Edit: ' . esc($post['title']));
        $oldInput   = old('title') ? old('') : [];
        $val        = fn(string $field, $default = '') =>
            old($field) !== null ? old($field) : ($post[$field] ?? $default);
        $tagsValue  = '';
        if (!empty($post['tags_list'])) {
            $tagsValue = implode(', ', array_column($post['tags_list'], 'tag'));
        } elseif (!empty($post['tags'])) {
            $tagsValue = $post['tags'];
        }
        $pubAt = '';
        if (!empty($post['published_at']) && $post['published_at'] !== '0000-00-00 00:00:00') {
            $pubAt = date('Y-m-d\TH:i', strtotime($post['published_at']));
        }
    ?>

    <!-- ── Page heading ────────────────────────────────────────────────────── -->
    <div class="border-bottom border-1 mb-4 pb-4 d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <div class="d-flex align-items-center gap-2 min-w-0">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a class="text-decoration-none" href="<?= site_url('admin') ?>">Admin</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page"><?= $postTitle ?></li>
                </ol>
            </nav>
        </div>
        <?php if (!$isNew && !empty($post['slug']) && $post['status'] === 'published'): ?>
        <a href="<?= site_url('posts/' . esc($post['slug'])) ?>" target="_blank" class="btn btn-outline-secondary btn-sm flex-shrink-0">
            <i class="bi bi-box-arrow-up-right me-1"></i> View Live
        </a>
        <?php endif; ?>
    </div>

    <!-- ── Flash messages ──────────────────────────────────────────────────── -->
    <?php if (session()->getFlashdata('success')): ?>
    <div class="position-fixed end-0 p-3" style="z-index:1200; top: 60px;">
        <div id="flash-toast-success" class="toast align-items-center border-success" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-check-circle-fill me-2"></i><?= esc(session()->getFlashdata('success')) ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toastEl = document.getElementById('flash-toast-success');
            if (toastEl && typeof bootstrap !== 'undefined') {
                const toast = new bootstrap.Toast(toastEl);
                toast.show();
            }
        });
    </script>
    <?php endif; ?>
    <?php if (session()->getFlashdata('errors')): ?>
    <div id="flash-message-errors" class="alert text-body alert-dark border-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-1">
            <?php foreach (session()->getFlashdata('errors') as $error): ?>
            <li><?= esc($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <script>
        // Auto-hide error message after 5 seconds
        setTimeout(() => {
            const flashEl = document.getElementById('flash-message-errors');
            if (flashEl) {
                const alert = new bootstrap.Alert(flashEl);
                alert.close();
            }
        }, 5000);
    </script>
    <?php endif; ?>

    <!-- ── Form ────────────────────────────────────────────────────────────── -->
    <form
        id="post-editor-form"
        action="<?= esc($action) ?>"
        method="POST"
        data-post-id="<?= $isNew ? '' : (int) $post['id'] ?>"
        data-preview-url="<?= site_url('admin/posts/preview') ?>"
        data-upload-url="<?= site_url('admin/posts/upload_featured_image') ?>"
        data-remove-url="<?= site_url('admin/posts/remove_featured_image') ?>"
        data-list-url="<?= site_url('admin/posts/list_featured_images') ?>"
        data-image-upload-url="<?= site_url('admin/posts/upload_body_image') ?>"
        novalidate
    >
        <?= csrf_field() ?>

        <div class="row g-4">

            <!-- ── Main column ─────────────────────────────────────────────── -->
            <div class="col-12 col-xl-8 d-flex flex-column gap-3">

                <!-- Title -->
                <div>
                    <label for="field-title" class="form-label text-secondary small text-uppercase fw-semibold mb-1">Title</label>
                    <input
                        id="field-title"
                        type="text"
                        name="title"
                        class="form-control form-control-lg post-editor__title-input"
                        placeholder="Post title…"
                        value="<?= esc($val('title')) ?>"
                        required
                        autocomplete="off"
                    >
                </div>

                <!-- Slug -->
                <div>
                    <label for="field-slug" class="form-label text-secondary small text-uppercase fw-semibold mb-1">Slug</label>
                    <div class="input-group">
                        <span class="input-group-text text-secondary small">/posts/</span>
                        <input
                            id="field-slug"
                            type="text"
                            name="slug"
                            class="form-control font-monospace"
                            placeholder="auto-generated-from-title"
                            value="<?= esc($val('slug')) ?>"
                            autocomplete="off"
                        >
                        <button class="btn btn-outline-secondary btn-sm" type="button" id="btn-generate-slug" title="Re-generate slug from title">
                            <i class="bi bi-arrow-repeat"></i>
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" type="button" id="btn-copy-slug" title="Copy full URL">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>

                <!-- Body: Write / Preview tabs -->
                <div class="post-editor__body-wrapper">
                    <ul class="nav nav-tabs post-editor__tabs mb-0" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link active"
                                id="tab-write"
                                data-bs-toggle="tab"
                                data-bs-target="#pane-write"
                                type="button"
                                role="tab"
                                aria-controls="pane-write"
                                aria-selected="true"
                            >
                                <i class="bi bi-pencil me-1"></i> Write
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link"
                                id="tab-preview"
                                data-bs-toggle="tab"
                                data-bs-target="#pane-preview"
                                type="button"
                                role="tab"
                                aria-controls="pane-preview"
                                aria-selected="false"
                            >
                                <i class="bi bi-eye me-1"></i> Preview
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link"
                                id="tab-images"
                                data-bs-toggle="tab"
                                data-bs-target="#pane-images"
                                type="button"
                                role="tab"
                                aria-controls="pane-images"
                                aria-selected="false"
                            >
                                <i class="bi bi-images me-1"></i> Images
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button
                                class="nav-link"
                                id="tab-help"
                                data-bs-toggle="tab"
                                data-bs-target="#pane-help"
                                type="button"
                                role="tab"
                                aria-controls="pane-help"
                                aria-selected="false"
                            >
                                <i class="bi bi-question-circle me-1"></i> Help
                            </button>
                        </li>
                        <li class="ms-auto d-flex align-items-center pe-2">
                            <small class="text-secondary" id="body-char-count">0 chars</small>
                        </li>
                    </ul>

                    <div class="tab-content post-editor__tab-content border border-top-0 rounded-bottom">

                        <!-- Write pane -->
                        <div class="tab-pane fade show active" id="pane-write" role="tabpanel" aria-labelledby="tab-write">
                            <textarea
                                id="field-body"
                                name="body"
                                class="form-control font-monospace post-editor__body-textarea border-0 rounded-0 rounded-bottom"
                                placeholder="Write your post in Markdown…"
                                spellcheck="false"
                            ><?= esc($val('body')) ?></textarea>
                        </div>

                        <!-- Help pane -->
                        <div class="tab-pane fade" id="pane-help" role="tabpanel" aria-labelledby="tab-help">
                            <div class="p-4">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="card border">
                                            <div class="card-header bg-body-secondary small text-uppercase fw-semibold text-secondary">
                                                <i class="bi bi-keyboard me-1"></i> Keyboard Shortcuts
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-12 col-md-6">
                                                        <ul class="list-unstyled mb-0">
                                                            <li><kbd>Tab</kbd>: Insert four spaces</li>
                                                            <li><kbd>Ctrl</kbd> / <kbd>Cmd</kbd> + <kbd>B</kbd>: Toggle bold (wraps selection with <code>**</code>)</li>
                                                            <li><kbd>Ctrl</kbd> / <kbd>Cmd</kbd> + <kbd>I</kbd>: Toggle italic (wraps selection with <code>*</code>)</li>
                                                            <li><kbd>`</kbd> (with selection): Wrap selection in inline code (<code>`code`</code>)</li>
                                                            <li>Type <code>``</code> then <kbd>`</kbd>: Expand to fenced code block</li>
                                                        </ul>
                                                    </div>
                                                    <div class="col-12 col-md-6">
                                                        <ul class="list-unstyled mb-0">
                                                            <li><kbd>Enter</kbd>: Continue list items automatically</li>
                                                            <li><kbd>Ctrl</kbd> / <kbd>Cmd</kbd> + <kbd>S</kbd>: Save (or click the Save button)</li>
                                                            <li>Quick Publish: Use the <em>Save &amp; Publish</em> button</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                                <div class="mt-3 text-muted small">These shortcuts apply while the editor textarea is focused.</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="card border">
                                            <div class="card-header bg-body-secondary small text-uppercase fw-semibold text-secondary">
                                                <i class="bi bi-markdown me-1"></i> GitHub Flavored Markdown (Quick Guide)
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <h6 class="small fw-semibold">Headings</h6>
                                                        <pre class="small"><code># H1
## H2
### H3</code></pre>

                                                        <h6 class="small fw-semibold">Emphasis</h6>
                                                        <pre class="small"><code>**bold**  *italic*  ~~strikethrough~~</code></pre>

                                                        <h6 class="small fw-semibold">Code</h6>
                                                        <pre class="small"><code>`inline code`  
```
// fenced block
```</code></pre>

                                                        <h6 class="small fw-semibold">Lists</h6>
                                                        <pre class="small"><code>- Bulleted item
1. Numbered item
- [ ] Task item  - [x] Completed task</code></pre>

                                                        <h6 class="small fw-semibold">Links & Images</h6>
                                                        <pre class="small"><code>[Link text](https://example.com)
![Alt text](/path/to/img.jpg)</code></pre>

                                                        <h6 class="small fw-semibold">Blockquote & Table</h6>
                                                        <pre class="small"><code>> A quote

| Col 1 | Col 2 |
| ---- | ---- |
| A    | B    |</code></pre>
                                                    </div>
                                                </div>
                                                <div class="mt-2 text-muted small">See <a href="https://docs.github.com/en/get-started/writing-on-github/basic-writing-and-formatting-syntax" target="_blank">GitHub's Markdown docs</a> for full details.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Images pane -->
                        <div class="tab-pane fade" id="pane-images" role="tabpanel" aria-labelledby="tab-images">
                            <div class="p-3">
                                <!-- Drop zone -->
                                <div
                                    id="image-upload-dropzone"
                                    class="border rounded p-4 text-center mb-3"
                                    style="cursor: pointer; border-style: dashed !important;"
                                    tabindex="0"
                                    role="button"
                                    aria-label="Upload image"
                                >
                                    <input type="file" id="field-image-file" accept="image/png,image/jpeg,image/webp,image/gif" hidden multiple>
                                    <i class="bi bi-cloud-arrow-up fs-2 d-block mb-2 opacity-50"></i>
                                    <div class="small text-muted">Drop images here, or click to choose.<br>Allowed: png, jpeg, webp, gif. Images wider than 1920px are resized automatically.</div>
                                </div>
                                <!-- Upload progress -->
                                <div id="image-upload-progress" class="mb-3" hidden>
                                    <div class="d-flex align-items-center gap-2 text-secondary small">
                                        <div class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></div>
                                        Uploading&hellip;
                                    </div>
                                </div>
                                <!-- Upload error -->
                                <div id="image-upload-error" class="alert alert-danger alert-dismissible mb-3 small" role="alert" hidden>
                                    <span id="image-upload-error-msg"></span>
                                    <button type="button" class="btn-close" aria-label="Close"></button>
                                </div>
                                <!-- Uploaded images gallery -->
                                <div id="image-gallery" class="d-flex flex-column gap-3"></div>
                            </div>
                        </div>

                        <!-- Preview pane -->
                        <div class="tab-pane fade" id="pane-preview" role="tabpanel" aria-labelledby="tab-preview">
                            <div class="post-editor__preview-pane p-4">
                                <div id="preview-loading" class="text-center text-secondary py-5" hidden>
                                    <div class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></div>
                                    Rendering preview…
                                </div>
                                <div id="preview-empty" class="text-center text-secondary py-5">
                                    <i class="bi bi-file-text fs-1 d-block mb-2 opacity-25"></i>
                                    Start writing to see a preview.
                                </div>

                                <!-- mirrors app/Views/post.php article structure -->
                                <article class="post h-entry" id="preview-article" hidden>
                                    <header class="post__header mb-4 border-bottom pb-4">
                                        <time class="post__meta text-muted small dt-published d-block mb-3" id="preview-date"></time>
                                        <h1 class="post__title display-5 fw-bold lh-sm mb-0 p-name" id="preview-title"></h1>
                                    </header>
                                    <div class="post__body e-content" id="preview-body"></div>
                                    <div id="preview-tags-wrap" class="mt-4" hidden>
                                        <div class="post__tags d-flex flex-wrap gap-2" id="preview-tags"></div>
                                    </div>
                                </article>
                            </div>
                        </div>

                    </div><!-- /.tab-content -->
                </div><!-- /.post-editor__body-wrapper -->

                <!-- Excerpt -->
                <div>
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <label for="field-excerpt" class="form-label text-secondary small text-uppercase fw-semibold mb-0">Excerpt</label>
                        <small
                            id="excerpt-char-count"
                            class="text-secondary"
                            role="button"
                            tabindex="0"
                            data-bs-toggle="popover"
                            data-bs-trigger="click"
                            data-bs-placement="left"
                            data-bs-html="true"
                            data-bs-title="Excerpt Length Guide"
                            data-bs-content="<ul class='mb-0 ps-3 small'><li><span class='text-success fw-semibold'>Optimal (110–135):</span> Safe zone, unlikely to be truncated on mobile or desktop.</li><li class='mt-1'><span class='text-warning fw-semibold'>Acceptable (40–109 or 136–160):</span> Works, but may be cut short on some platforms.</li><li class='mt-1'><span class='text-danger fw-semibold'>Too short (&lt;40):</span> Preview looks empty and less professional.</li><li class='mt-1'><span class='text-danger fw-semibold'>Too long (&gt;160):</span> Almost certainly replaced with an ellipsis (&hellip;).</li></ul>"
                        >0 chars <i class="bi bi-info-circle"></i></small>
                    </div>
                    <textarea
                        id="field-excerpt"
                        name="excerpt"
                        class="form-control"
                        rows="3"
                        placeholder="Short summary shown in post listings and social shares…"
                    ><?= esc($val('excerpt')) ?></textarea>
                </div>

            </div><!-- /.col main -->

            <!-- ── Sidebar ──────────────────────────────────────────────────── -->
            <div class="col-12 col-xl-4">
                <div class="d-flex flex-column gap-3 post-editor__sidebar">

                    <!-- Save actions -->
                    <div class="card border bg-body-secondary">
                        <div class="card-header text-secondary small text-uppercase fw-semibold">
                            <i class="bi bi-cloud-upload me-1"></i> Publish
                        </div>
                        <div class="card-body d-flex flex-column gap-2">
                            <button type="submit" name="_save_action" value="save" class="btn btn-outline-primary w-100">
                                <i class="bi bi-floppy-fill me-1"></i> Save
                            </button>
                            <?php if ($isNew): ?>
                            <button type="submit" name="_save_action" value="publish" class="btn btn-outline-primary w-100" id="btn-quick-publish">
                                <i class="bi bi-send-fill me-1"></i> Save &amp; Publish
                            </button>
                            <?php endif; ?>
                            <a href="<?= site_url('admin') ?>" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-x-lg me-1"></i> Cancel
                            </a>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="card border bg-body-secondary">
                        <div class="card-header text-secondary small text-uppercase fw-semibold">
                            <i class="bi bi-toggle-on me-1"></i> Status
                        </div>
                        <div class="card-body d-flex flex-column gap-3">

                            <div>
                                <label for="field-status" class="form-label small text-secondary mb-1">Status</label>
                                <select id="field-status" name="status" class="form-select form-select-sm">
                                    <?php
                                        $statuses    = ['draft' => 'Draft', 'published' => 'Published', 'revision' => 'Revision', 'trashed' => 'Trashed'];
                                        $curStatus   = $val('status', 'draft');
                                    ?>
                                    <?php foreach ($statuses as $key => $label): ?>
                                    <option value="<?= $key ?>"<?= $curStatus === $key ? ' selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label for="field-visibility" class="form-label small text-secondary mb-1">Visibility</label>
                                <select id="field-visibility" name="visibility" class="form-select form-select-sm">
                                    <option value="0"<?= ((int) $val('visibility', 0)) === 0 ? ' selected' : '' ?>>Public</option>
                                    <option value="1"<?= ((int) $val('visibility', 0)) === 1 ? ' selected' : '' ?>>Private</option>
                                </select>
                            </div>

                            <div>
                                <label for="field-published-at" class="form-label small text-secondary mb-1">Published at</label>
                                <input
                                    id="field-published-at"
                                    type="datetime-local"
                                    name="published_at"
                                    class="form-control form-control-sm"
                                    value="<?= esc($pubAt) ?>"
                                >
                            </div>

                        </div>
                    </div>

                    <!-- Tags -->
                    <div class="card border bg-body-secondary">
                        <div class="card-header text-secondary small text-uppercase fw-semibold">
                            <i class="bi bi-tags me-1"></i> Tags
                        </div>
                            <div class="card-body">
                                <?php
                                    $tagOptions = $all_tags ?? [];
                                    $tagArray = [];
                                    if (!empty($tagsValue)) {
                                        $tagArray = array_filter(array_map('trim', explode(',', $tagsValue)));
                                    }
                                ?>

                                <!-- Visible input for entering a single tag; suggestions via datalist -->
                                <input
                                    id="field-tags-input"
                                    type="text"
                                    class="form-control form-control-sm"
                                    placeholder="Add a tag and press Enter or comma"
                                    list="tags-datalist"
                                    autocomplete="off"
                                >

                                <!-- Datalist populated with existing tags -->
                                <datalist id="tags-datalist">
                                    <?php foreach ($tagOptions as $opt): ?>
                                    <option value="<?= esc($opt) ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>

                                <!-- Hidden input that holds the comma-separated tags for form submission -->
                                <input
                                    id="field-tags"
                                    name="tags"
                                    type="hidden"
                                    value="<?= esc($tagsValue) ?>"
                                >

                                <!-- Badge list (click to remove) -->
                                <div id="tag-badges" class="mt-2">
                                    <?php foreach ($tagArray as $t): ?>
                                    <span class="badge bg-secondary me-1 mb-1 tag-badge" role="button" tabindex="0"><?= esc($t) ?></span>
                                    <?php endforeach; ?>
                                </div>

                                <div class="form-text">Click a tag to remove it.</div>
                            </div>
                    </div>

                    <!-- Featured Image -->
                    <div class="card border bg-body-secondary">
                        <div class="card-header text-secondary small text-uppercase fw-semibold">
                            <i class="bi bi-image me-1"></i> Featured Image
                        </div>
                        <div class="card-body">
                            <div
                                id="featured-dropzone"
                                class="border rounded p-4 text-center mb-2"
                                style="cursor: pointer; border-style: dashed !important;"
                                tabindex="0"
                                role="button"
                                aria-label="Upload featured image"
                            >
                                <input type="file" id="field-featured-file" accept="image/png,image/jpeg,image/webp,image/gif" hidden>
                                <input
                                    id="field-featured-image"
                                    type="hidden"
                                    name="featured_image"
                                    value="<?= esc($val('featured_image')) ?>"
                                >
                                <i class="bi bi-cloud-arrow-up fs-2 d-block mb-2 opacity-50"></i>
                                <div class="small text-muted">Drop an image here, or click to choose.<br>Must be exactly 1200 × 630px. Allowed: png, jpeg, webp, gif.</div>
                            </div>
                            <div id="featured-upload-error" class="alert alert-danger alert-dismissible mb-0 small" role="alert" hidden>
                                <span id="featured-upload-error-msg"></span>
                                <button type="button" class="btn-close" aria-label="Close"></button>
                            </div>
                            <div class="d-flex gap-2 mt-2">
                                <button type="button" id="btn-choose-existing" class="btn btn-outline-secondary btn-sm w-100">Choose existing</button>
                            </div>
                            <div id="featured-preview" class="mt-2" style="display: <?= empty($post['featured_image']) ? 'none' : 'block' ?>;">
                                <?php if (!empty($post['featured_image'])): ?>
                                <div class="position-relative d-block w-100">
                                    <img id="featured-thumb" src="<?= site_url('media/' . $post['featured_image']) ?>" alt="Featured image" style="width:100%; height:auto; object-fit:cover; display:block;" />
                                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2" id="btn-remove-featured-image" aria-label="Remove featured image">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                                <?php else: ?>
                                <div class="position-relative d-block w-100">
                                    <img id="featured-thumb" src="" alt="Featured image" style="width:100%; height:auto; object-fit:cover; display:none;" />
                                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2" id="btn-remove-featured-image" style="display:none;" aria-label="Remove featured image">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                            <!-- Featured image library modal -->
                            <div class="modal fade" id="featured-library-modal" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-xl">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Choose Featured Image</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <style>
                                                .fn-shimmer { background: linear-gradient(90deg,#f0f0f0 25%,#e6e6e6 37%,#f0f0f0 63%); background-size: 400% 100%; animation: fn-shimmer 1.2s ease-in-out infinite; }
                                                @keyframes fn-shimmer { 0%{background-position:100% 0} 100%{background-position:-100% 0} }
                                                .fn-thumb-wrap{ cursor:pointer; border-radius:.25rem; overflow:hidden; transition: transform .18s ease, box-shadow .18s ease; }
                                                .fn-thumb{ width:100%; height:auto; display:block; object-fit:cover; transition: transform .18s ease; transform-origin:center center; }
                                                .fn-thumb-wrap:focus{ outline: 2px solid rgba(13,110,253,0.25); outline-offset: 2px; }
                                                .fn-thumb-wrap:hover, .fn-thumb-wrap:focus{ transform: translateY(-4px) scale(1.03); box-shadow: 0 10px 30px rgba(0,0,0,0.28); z-index:1060; }
                                            </style>
                                            <div id="featured-library-grid" class="row g-2">
                                                <div class="col-6 col-md-4 col-lg-3">
                                                    <div class="ratio ratio-30x17 fn-shimmer" style="width:100%;padding:0;border-radius:.25rem;height:0;padding-bottom:52.5%;"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Post info (edit mode only) -->
                    <?php if (!$isNew): ?>
                    <div class="card border bg-body-secondary">
                        <div class="card-header text-secondary small text-uppercase fw-semibold">
                            <i class="bi bi-info-circle me-1"></i> Info
                        </div>
                        <div class="card-body">
                            <dl class="mb-0 small row g-0">
                                <dt class="col-5 text-secondary">ID</dt>
                                <dd class="col-7 font-monospace"><?= (int) $post['id'] ?></dd>
                                <dt class="col-5 text-secondary">UUID</dt>
                                <dd class="col-7 font-monospace text-truncate" title="<?= esc($post['uuid']) ?>"><?= esc($post['uuid']) ?></dd>
                                <dt class="col-5 text-secondary">Views</dt>
                                <dd class="col-7"><?= number_format((int) $post['hitcounter']) ?></dd>
                                <dt class="col-5 text-secondary">Created</dt>
                                <dd class="col-7"><?= esc(date('d M Y', strtotime($post['created_at']))) ?></dd>
                                <dt class="col-5 text-secondary">Updated</dt>
                                <dd class="col-7"><?= esc(date('d M Y H:i', strtotime($post['updated_at']))) ?></dd>
                            </dl>
                        </div>
                    </div>
                    <?php endif; ?>

                </div><!-- /.post-editor__sidebar -->
            </div><!-- /.col sidebar -->

        </div><!-- /.row -->
    </form>

</div><!-- /.post-editor -->

<!-- Unsaved-changes toast -->
<div
    id="unsaved-toast"
    class="toast align-items-center border-warning position-fixed end-0 m-3"
    role="alert"
    aria-live="assertive"
    aria-atomic="true"
    data-bs-autohide="false"
    style="z-index:1100; top: 60px;"
>
    <div class="d-flex">
        <div class="toast-body">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> You have unsaved changes.
        </div>
    </div>
</div>
<?= $this->endSection() ?>
