<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>
<div class="container">

    <?php if ($latestPost): ?>
    <div class="row mt-3">
        <div class="col-12 col-lg-8 offset-lg-2">
            <article class="post post--latest h-entry">
                <header class="post__header mb-4 border-bottom pb-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="badge text-uppercase ls-1" style="background-color:#6272a4;letter-spacing:.08em;">Latest post</span>
                        <time class="post__meta text-muted small dt-published" datetime="<?= esc(date('Y-m-d\TH:i:sP', strtotime($latestPost['published_at']))) ?>">
                            <?= esc(date('j F Y', strtotime($latestPost['published_at']))) ?>
                        </time>
                    </div>
                    <h1 class="post__title display-5 fw-bold lh-sm mb-0">
                        <a class="p-name u-url text-white text-decoration-none" href="<?= site_url('posts/' . esc($latestPost['slug'])) ?>">
                            <?= esc($latestPost['title_html'] ?? $latestPost['title']) ?>
                        </a>
                    </h1>
                </header>
                <div class="post__body e-content">
                    <?= $latestPost['body_html'] ?>
                </div>
                <?php if (!empty($latestPost['tags_list'])): ?>
                <footer class="post__footer mt-4">
                    <div class="post__tags d-flex flex-wrap gap-2">
                        <?php foreach ($latestPost['tags_list'] as $tag): ?>
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
    <?php endif; ?>

    <?php if (!empty($otherPosts)): ?>
    <div class="row mt-5 mb-5">
        <div class="col-12 col-lg-8 offset-lg-2">
            <h2 class="h6 text-uppercase text-muted fw-semibold mb-3" style="letter-spacing:.1em;">More posts</h2>
            <ol class="post-list list-unstyled mb-0">
                <?php foreach ($otherPosts as $index => $post): ?>
                <li class="post-list__item h-entry d-flex align-items-baseline gap-3 py-3<?= $index < count($otherPosts) - 1 ? ' border-bottom' : '' ?>">
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
        </div> <!-- /.col -->
    </div> <!-- /.row -->
    <?php endif; ?>

</div> <!-- /.container -->
<?= $this->endSection() ?>