<?php echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; ?><rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title><?= esc($title) ?></title>
        <link><?= esc($baseURL) ?></link>
        <description><?= esc($description) ?></description>
        <language>en-gb</language>
        <atom:link href="<?= esc($feedURL) ?>" rel="self" type="application/rss+xml" />
        <?php foreach ($posts as $post) : ?>
        <item>
            <title><?= esc($post['title']) ?></title>
            <link><?= esc($baseURL) ?>/posts/<?= esc($post['slug']) ?></link>
            <guid isPermaLink="true"><?= esc($baseURL) ?>/posts/<?= esc($post['slug']) ?></guid>
            <?php if (! empty($post['excerpt'])) : ?>
            <description><?= esc($post['excerpt']) ?></description>
            <?php endif; ?>
            <pubDate><?= date('r', strtotime($post['published_at'])) ?></pubDate>
        </item>
        <?php endforeach; ?>
    </channel>
</rss>
