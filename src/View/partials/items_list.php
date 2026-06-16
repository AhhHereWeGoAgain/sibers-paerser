<?php
// Renders parsed source items as cards with metadata, lazy images and action links.
?>

<?php if (empty($items)): ?>
    <section class="empty-state">
        <h2>No items found</h2>
        <p>
            The selected source returned no parsed items for this page.
        </p>
    </section>
<?php else: ?>
    <section class="items-grid">
        <?php foreach ($items as $item): ?>
            <?php
                if (!is_array($item)) {
                    continue; // Skip invalid item structure.
                }

                $title = $item['title'] ?? 'Untitled item';
                $summary = $item['summary'] ?? $item['description'] ?? $item['content'] ?? '';
                $detail_url = $item['detail_url'] ?? null;
                $image_url = getFirstImageUrl($item);
            ?>

            <article class="item-card">
                <?php if ($image_url !== null): ?>
                    <details class="image-details image-details--card">
                        <summary class="image-details__summary">
                            <span class="image-details__icon">▸</span>
                            <span>Show image</span>
                        </summary>

                        <div class="image-details__body">
                            <div class="item-card__image-wrap">
                                <img
                                    data-src="<?= escapeHtml($image_url) ?>"
                                    alt="<?= escapeHtml($title) ?>"
                                    class="item-card__image lazy-image"
                                    loading="lazy"
                                >
                            </div>
                        </div>
                    </details>
                <?php endif; ?>

                <div class="item-card__body">
                    <h3><?= escapeHtml($title) ?></h3>

                    <div class="item-card__meta">
                        <?php if (!empty($item['published_at'])): ?>
                            <span><?= escapeHtml($item['published_at']) ?></span>
                        <?php endif; ?>

                        <?php if (!empty($item['category'])): ?>
                            <span><?= escapeHtml($item['category']) ?></span>
                        <?php endif; ?>

                        <?php if (!empty($item['price'])): ?>
                            <span><?= escapeHtml($item['price']) ?></span>
                        <?php endif; ?>

                        <?php if (!empty($item['availability'])): ?>
                            <span><?= escapeHtml($item['availability']) ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if (trim((string) $summary) !== ''): ?>
                        <p>
                            <?= escapeHtml(shortText($summary, 260)) ?>
                        </p>
                    <?php endif; ?>

                    <div class="item-card__actions">
                        <?php if (is_string($detail_url) && trim($detail_url) !== ''): ?>
                            <a
                                href="/?source=<?= urlencode((string) $selected_source) ?>&page=<?= (int) $page ?>&detail_url=<?= urlencode($detail_url) ?>"
                                class="button"
                            >
                                Open parsed item
                            </a>

                            <a
                                href="<?= escapeHtml($detail_url) ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="button button-secondary"
                            >
                                Open original
                            </a>
                        <?php else: ?>
                            <span class="muted">Detail URL is not available</span>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
