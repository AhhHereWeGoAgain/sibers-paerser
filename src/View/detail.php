<?php require __DIR__ . '/header.php'; ?>

<main class="container page">
    <div class="detail-topbar">
        <a
            href="/?source=<?= urlencode((string) $selected_source) ?>&page=<?= (int) $page ?>"
            class="back-link"
        >
            ← Back to list
        </a>

        <?php if (!empty($detail_url)): ?>
            <a
                href="<?= escapeHtml($detail_url) ?>"
                target="_blank"
                rel="noopener noreferrer"
                class="button button-secondary"
            >
                Open original
            </a>
        <?php endif; ?>
    </div>

    <?php if (!empty($error)): ?>
        <?php require __DIR__ . '/partials/error_card.php'; ?>
    <?php else: ?>
        <?php
            $title = $article['title'] ?? 'Untitled item';
            $content = $article['content'] ?? $article['description'] ?? $article['summary'] ?? '';
            $image_url = getFirstImageUrl($article);
        ?>

        <article class="detail-card">
            <?php if ($image_url !== null): ?>
                <details class="image-details image-details--detail">
                    <summary class="image-details__summary">
                        <span class="image-details__icon">▸</span>
                        <span>Показать изображение</span>
                    </summary>

                    <div class="image-details__body">
                        <div class="detail-image-wrap">
                            <img
                                data-src="<?= escapeHtml($image_url) ?>"
                                alt="<?= escapeHtml($title) ?>"
                                class="detail-image lazy-image"
                                loading="lazy"
                            >
                        </div>
                    </div>
                </details>
            <?php endif; ?>

            <div class="detail-content">
                <h1><?= escapeHtml($title) ?></h1>

                <div class="detail-meta">
                    <?php if (!empty($article['published_at'])): ?>
                        <span><?= escapeHtml($article['published_at']) ?></span>
                    <?php endif; ?>

                    <?php if (!empty($article['price'])): ?>
                        <span><?= escapeHtml($article['price']) ?></span>
                    <?php endif; ?>

                    <?php if (!empty($article['availability'])): ?>
                        <span><?= escapeHtml($article['availability']) ?></span>
                    <?php endif; ?>
                </div>

                <?php if (trim((string) $content) !== ''): ?>
                    <div class="article-text">
                        <?= nl2br(escapeHtml($content)) ?>
                    </div>
                <?php else: ?>
                    <p class="empty-message">
                        Parsed detail content is empty.
                    </p>
                <?php endif; ?>

                <?php if (!empty($article['related_links']) && is_array($article['related_links'])): ?>
                    <div class="related-links">
                        <h2>Related links</h2>

                        <ul>
                            <?php foreach ($article['related_links'] as $link): ?>
                                <?php if (is_string($link) && trim($link) !== ''): ?>
                                    <li>
                                        <a href="<?= escapeHtml($link) ?>" target="_blank" rel="noopener noreferrer">
                                            <?= escapeHtml($link) ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </article>
    <?php endif; ?>

    <?php //require __DIR__ . '/partials/meta_card.php'; ?>
</main>

<?php require __DIR__ . '/footer.php'; ?>