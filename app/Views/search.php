<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>
<div class="container">

    <div class="row mt-4 mb-5">
        <div class="col-12 col-lg-8 offset-lg-2">

            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a class="text-decoration-none" href="<?= site_url() ?>">Home</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Search</li>
                </ol>
            </nav>

            <form class="search-form mb-4" action="<?= site_url('search') ?>" method="get" role="search">
                <div class="input-group">
                    <input
                        class="form-control"
                        type="search"
                        name="q"
                        value="<?= esc($query) ?>"
                        placeholder="Search posts&hellip;"
                        aria-label="Search posts"
                        autofocus 
                        required
                    >
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                </div>
            </form>

            <?php if ($query !== ''): ?>

                <?php if (!empty($posts)): ?>
                <h2 class="h6 text-uppercase text-muted fw-semibold mb-3" style="letter-spacing:.1em;">
                    <?= count($posts) === 1 ? '1 result' : count($posts) . ' results' ?> for &ldquo;<?= esc($query) ?>&rdquo;
                </h2>
                <ol class="post-list list-unstyled mb-0">
                    <?php foreach ($posts as $index => $post): ?>
                    <li class="post-list__item h-entry d-flex align-items-baseline gap-3 py-3<?= $index < count($posts) - 1 ? ' border-bottom' : '' ?>">
                        <time class="post-list__date text-muted small text-nowrap flex-shrink-0 dt-published" datetime="<?= esc(date('Y-m-d\TH:i:sP', strtotime($post['published_at']))) ?>">
                            <?= esc(date('j M Y', strtotime($post['published_at']))) ?>
                        </time>
                        <div class="post-list__body">
                            <a class="post-list__title text-white fw-semibold text-decoration-none link-hover p-name u-url" href="<?= site_url('posts/' . esc($post['slug'])) ?>">
                                <?= esc($post['title_html'] ?? $post['title']) ?>
                            </a>
                            <?php if (!empty($post['excerpt'])): ?>
                            <p class="post-list__excerpt text-muted small mb-0 mt-1 p-summary">
                                <?= esc($post['excerpt']) ?>
                            </p>
                            <?php endif; ?>
                            <?php if (!empty($post['tags_list'])): ?>
                            <div class="post-list__tags d-flex flex-wrap gap-1 mt-2">
                                <?php foreach ($post['tags_list'] as $tag): ?>
                                <a class="badge text-decoration-none p-category" style="background-color:#44475a;color:#f8f8f2;" href="<?= site_url('tags/' . esc($tag['slug'])) ?>">
                                    <?= esc($tag['tag']) ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ol>
                <?php else: ?>
                <p class="text-muted">No posts found for &ldquo;<?= esc($query) ?>&rdquo;.</p>
                <?php endif; ?>

            <?php endif; ?>

        </div> <!-- /.col -->
    </div> <!-- /.row -->

</div> <!-- /.container -->
<?= $this->endSection() ?>
