<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>
<div class="container">

    <div class="row mt-3">
        <div class="col-12 col-lg-8 offset-lg-2">

            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a class="text-decoration-none" href="<?= site_url() ?>">Home</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?= esc($post['title']) ?>
                    </li>
                </ol>
            </nav>

            <article class="post h-entry">
                <header class="post__header mb-4 border-bottom pb-4">
                    <time class="post__meta text-muted small dt-published d-block mb-3" datetime="<?= esc(date('Y-m-d\TH:i:sP', strtotime($post['published_at']))) ?>">
                        <?= esc(date('j F Y', strtotime($post['published_at']))) ?>
                    </time>
                    <h1 class="post__title display-5 fw-bold lh-sm mb-0 p-name">
                        <?= esc($post['title_html'] ?? $post['title']) ?>
                    </h1>
                </header>
                <?php if ((time() - strtotime($post['published_at'])) > 365 * 24 * 60 * 60): ?>
                <div class="post__outdated alert alert-warning mb-4" role="alert">
                    <strong>Heads up:</strong> This post is over a year old and may be out of date.
                </div>
                <?php endif; ?>
                <div class="post__body e-content">
                    <?= $post['body_html'] ?>
                </div>
                <div class="post__formats mt-4 pt-3 border-top">
                    <span class="text-muted small me-2">View as:</span>
                    <a class="small text-decoration-none me-3" href="<?= site_url('posts/' . esc($post['slug']) . '/json') ?>"><i class="bi bi-filetype-json me-1"></i>JSON</a>
                    <a class="small text-decoration-none" href="<?= site_url('posts/' . esc($post['slug']) . '/markdown') ?>"><i class="bi bi-markdown me-1"></i>Markdown</a>
                </div>
                <?php if (!empty($post['tags_list'])): ?>
                <footer class="post__footer mt-4">
                    <div class="post__tags d-flex flex-wrap gap-2">
                        <?php foreach ($post['tags_list'] as $tag): ?>
                        <a class="badge text-decoration-none p-category" style="background-color:#44475a;color:#f8f8f2;" href="<?= site_url('tags/' . esc($tag['slug'])) ?>">
                            <?= esc($tag['tag']) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </footer>
                <?php endif; ?>
            </article>

        </div> <!-- /.col -->
    </div> <!-- /.row -->

    <?php if (!empty($similarPosts)): ?>
    <div class="row mt-5 mb-5">
        <div class="col-12 col-lg-8 offset-lg-2">
            <h2 class="h6 text-uppercase text-muted fw-semibold mb-3" style="letter-spacing:.1em;"><?= esc($similarHeading) ?></h2>
            <ol class="post-list list-unstyled mb-0">
                <?php foreach ($similarPosts as $index => $related): ?>
                <li class="post-list__item h-entry d-flex align-items-baseline gap-3 py-3<?= $index < count($similarPosts) - 1 ? ' border-bottom' : '' ?>">
                    <time class="post-list__date text-muted small text-nowrap flex-shrink-0 dt-published" datetime="<?= esc(date('Y-m-d\TH:i:sP', strtotime($related['published_at']))) ?>">
                        <?= esc(date('j M Y', strtotime($related['published_at']))) ?>
                    </time>
                    <div class="post-list__body">
                        <a class="post-list__title text-white fw-semibold text-decoration-none link-hover p-name u-url" href="<?= site_url('posts/' . esc($related['slug'])) ?>">
                            <?= esc($related['title_html'] ?? $related['title']) ?>
                        </a>
                        <?php if (!empty($related['excerpt'])): ?>
                        <p class="post-list__excerpt text-muted small mb-0 mt-1 p-summary">
                            <?= esc($related['excerpt']) ?>
                        </p>
                        <?php endif; ?>
                        <?php if (!empty($related['tags_list'])): ?>
                        <div class="post-list__tags d-flex flex-wrap gap-1 mt-2">
                            <?php foreach ($related['tags_list'] as $tag): ?>
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
        </div> <!-- /.col -->
    </div> <!-- /.row -->
    <?php endif; ?>

</div> <!-- /.container -->
<?= $this->endSection() ?>
