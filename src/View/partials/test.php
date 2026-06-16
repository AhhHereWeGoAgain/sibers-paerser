<section class="section-header">
    <div>
        <h2>Parsed items</h2>

        <?php if (!empty($meta['source_urls_loaded'])): ?>
            <p class="muted">
                Loaded source URL:
                <?= escapeHtml(implode(', ', $meta['source_urls_loaded'])) ?>
            </p>
        <?php endif; ?>
    </div>

    <?php if (!empty($meta['shown_items_count'])): ?>
        <span class="badge">
            <?= (int) $meta['shown_items_count'] ?> items
        </span>
    <?php endif; ?>
</section>