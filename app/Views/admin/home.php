<?= $this->extend('templates/dashboard') ?>

<?= $this->section('content') ?>
<div class="container-fluid">

    <!-- ── Page heading ──────────────────────────────────────────────────── -->
    <div class="border-bottom border-1 mb-4 pb-4 d-flex align-items-center justify-content-between gap-3">
        <h2 class="mb-0">Admin Dashboard</h2>
        <a href="<?= site_url() ?>" class="btn btn-outline-secondary btn-sm" target="_blank">
            <i class="bi bi-box-arrow-up-right me-1"></i> View Site
        </a>
    </div>

    <!-- ── Stats cards ───────────────────────────────────────────────────── -->
    <div class="row g-3 mb-5">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 stat-card border-0 bg-body-secondary text-center">
                <div class="card-body py-4">
                    <div class="stat-card__icon mb-2 text-primary">
                        <i class="bi bi-file-text-fill fs-2"></i>
                    </div>
                    <div class="stat-card__value display-6 fw-bold" data-stat="total_posts"><?= number_format($stats['total_posts']) ?></div>
                    <div class="stat-card__label text-secondary small text-uppercase fw-semibold mt-1">Total Posts</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 stat-card border-0 bg-body-secondary text-center">
                <div class="card-body py-4">
                    <div class="stat-card__icon mb-2 text-success">
                        <i class="bi bi-check-circle-fill fs-2"></i>
                    </div>
                    <div class="stat-card__value display-6 fw-bold" data-stat="published_posts"><?= number_format($stats['published_posts']) ?></div>
                    <div class="stat-card__label text-secondary small text-uppercase fw-semibold mt-1">Published</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 stat-card border-0 bg-body-secondary text-center">
                <div class="card-body py-4">
                    <div class="stat-card__icon mb-2 text-secondary">
                        <i class="bi bi-pencil-fill fs-2"></i>
                    </div>
                    <div class="stat-card__value display-6 fw-bold" data-stat="draft_posts"><?= number_format($stats['draft_posts']) ?></div>
                    <div class="stat-card__label text-secondary small text-uppercase fw-semibold mt-1">Drafts</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 stat-card border-0 bg-body-secondary text-center">
                <div class="card-body py-4">
                    <div class="stat-card__icon mb-2 text-danger">
                        <i class="bi bi-trash3-fill fs-2"></i>
                    </div>
                    <div class="stat-card__value display-6 fw-bold" data-stat="trashed_posts"><?= number_format($stats['trashed_posts']) ?></div>
                    <div class="stat-card__label text-secondary small text-uppercase fw-semibold mt-1">Trashed</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 stat-card border-0 bg-body-secondary text-center">
                <div class="card-body py-4">
                    <div class="stat-card__icon mb-2 text-info">
                        <i class="bi bi-tags-fill fs-2"></i>
                    </div>
                    <div class="stat-card__value display-6 fw-bold" data-stat="total_tags"><?= number_format($stats['total_tags']) ?></div>
                    <div class="stat-card__label text-secondary small text-uppercase fw-semibold mt-1">Tag Entries</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 stat-card border-0 bg-body-secondary text-center">
                <div class="card-body py-4">
                    <div class="stat-card__icon mb-2 text-warning">
                        <i class="bi bi-eye-fill fs-2"></i>
                    </div>
                    <?php
                        $v = $stats['total_views'];
                        if ($v >= 1_000_000) { $fmt = round($v / 1_000_000, 1) . 'm'; }
                        elseif ($v >= 1_000)  { $fmt = round($v / 1_000, 1) . 'k'; }
                        else                  { $fmt = number_format($v); }
                    ?>
                    <div class="stat-card__value display-6 fw-bold" data-stat="total_views"><?= $fmt ?></div>
                    <div class="stat-card__label text-secondary small text-uppercase fw-semibold mt-1">Total Views</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Recent posts ──────────────────────────────────────────────────── -->
    <?php if (!empty($recent_posts)): ?>
    <div class="mb-5">
        <h5 class="mb-3 text-secondary text-uppercase fw-semibold small">Recently Added</h5>
        <div class="list-group list-group-flush">
            <?php foreach ($recent_posts as $post): ?>
            <div class="list-group-item bg-body-secondary border-bottom d-flex align-items-center justify-content-between gap-3 px-3 py-2">
                <div class="d-flex align-items-center gap-2 overflow-hidden">
                    <span class="badge text-bg-<?= $post['status'] === 'published' ? 'success' : ($post['status'] === 'draft' ? 'secondary' : 'warning') ?> flex-shrink-0"><?= esc($post['status']) ?></span>
                    <span class="text-truncate"><?= esc($post['title']) ?></span>
                </div>
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <small class="text-secondary"><?= date('d M Y', strtotime($post['created_at'])) ?></small>
                    <?php if ($post['status'] === 'published'): ?>
                    <a href="<?= site_url('posts/' . esc($post['slug'])) ?>" target="_blank" class="btn btn-outline-secondary btn-sm py-0"><i class="bi bi-box-arrow-up-right"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Posts datatable ───────────────────────────────────────────────── -->
    <div class="row">
        <div class="col-12">

            <div class="border-bottom border-1 mb-4 pb-3 d-flex align-items-center justify-content-between gap-3">
                <h5 class="mb-0 text-secondary text-uppercase fw-semibold small">Manage Posts</h5>
                <div class="btn-group" role="group" aria-label="Posts table actions">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle" id="btn-status-filter" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-funnel-fill"></i><span class="d-none d-lg-inline"> Status: All</span>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="btn-status-filter">
                            <li><a class="dropdown-item status-filter-item active" href="#" data-value="">All</a></li>
                            <li><a class="dropdown-item status-filter-item" href="#" data-value="published">Published</a></li>
                            <li><a class="dropdown-item status-filter-item" href="#" data-value="draft">Draft</a></li>
                            <li><a class="dropdown-item status-filter-item" href="#" data-value="revision">Revision</a></li>
                            <li><a class="dropdown-item status-filter-item" href="#" data-value="trashed">Trashed</a></li>
                        </ul>
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btn-datatable-refresh">
                        <i class="bi bi-arrow-clockwise"></i><span class="d-none d-lg-inline"> Refresh</span>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" id="btn-delete" disabled>
                        <i class="bi bi-trash3-fill"></i><span class="d-none d-lg-inline"> Delete</span>
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="posts-table" class="table table-bordered table-striped table-hover align-middle" style="width:100%">
                    <thead>
                        <tr>
                            <th></th>
                            <th>#</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Tags</th>
                            <th>Views</th>
                            <th>Published</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="modal-delete-confirm" tabindex="-1" aria-labelledby="modal-delete-confirm-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-delete-confirm-label">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="delete-modal-body">
                Are you sure you want to delete <strong id="delete-modal-count">0</strong> selected post(s)? They will be soft-deleted and can be recovered from the database.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="btn-delete-confirm">Delete</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>