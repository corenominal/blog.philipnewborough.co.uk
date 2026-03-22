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
    <div class="alert border-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= esc(session()->getFlashdata('success')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('errors')): ?>
    <div class="alert border-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-1">
            <?php foreach (session()->getFlashdata('errors') as $error): ?>
            <li><?= esc($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
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
                    <label for="field-excerpt" class="form-label text-secondary small text-uppercase fw-semibold mb-1">Excerpt</label>
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
                            <div id="featured-dropzone" class="border rounded p-3 text-center" style="background:var(--bs-dark); cursor:pointer;">
                                <input type="file" id="field-featured-file" accept="image/png,image/jpeg,image/webp,image/gif" hidden>
                                <input
                                    id="field-featured-image"
                                    type="text"
                                    name="featured_image"
                                    class="form-control form-control-sm font-monospace mb-2"
                                    placeholder="Filename relative to /media/"
                                    value="<?= esc($val('featured_image')) ?>"
                                    autocomplete="off"
                                >
                                <div class="small text-muted">Drop an image here, or click to choose. Must be exactly 1200 × 630px. Allowed: png, jpeg, webp, jpg, gif.</div>
                            </div>
                            <div class="form-text mt-2">Filename relative to <code>/media/</code>.</div>

                            <div id="featured-preview" class="mt-2" style="display: <?= empty($post['featured_image']) ? 'none' : 'block' ?>;">
                                <?php if (!empty($post['featured_image'])): ?>
                                <div class="position-relative d-block w-100">
                                    <img id="featured-thumb" src="<?= site_url('media/' . $post['featured_image']) ?>" alt="Featured image" style="width:100%; height:auto; object-fit:cover; display:block;" />
                                    <button type="button" class="btn btn-outline-danger btn-sm position-absolute top-0 end-0 m-2" id="btn-remove-featured-image" aria-label="Remove featured image">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                                <?php else: ?>
                                <div class="position-relative d-block w-100">
                                    <img id="featured-thumb" src="" alt="Featured image" style="width:100%; height:auto; object-fit:cover; display:none;" />
                                    <button type="button" class="btn btn-outline-danger btn-sm position-absolute top-0 end-0 m-2 rounded-circle" id="btn-remove-featured-image" style="display:none;" aria-label="Remove featured image">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                                <?php endif; ?>
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
    class="toast align-items-center text-bg-warning border-0 position-fixed bottom-0 end-0 m-3"
    role="alert"
    aria-live="assertive"
    aria-atomic="true"
    data-bs-autohide="false"
    style="z-index:1100;"
>
    <div class="d-flex">
        <div class="toast-body">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> You have unsaved changes.
        </div>
    </div>
</div>
<?= $this->endSection() ?>
