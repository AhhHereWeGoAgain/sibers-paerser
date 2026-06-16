<?php
// Renders a safe error block for failed source requests or invalid user actions.
?>

<section class="error-card">
    <div class="error-card__icon">!</div>

    <div>
        <h2>Request error</h2>

        <p>
            <?= escapeHtml($error['message'] ?? 'Unknown error.') ?>
        </p>

        <?php if (!empty($error['code'])): ?>
            <p class="error-code">
                Code: <?= escapeHtml($error['code']) ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($error['details'])): ?>
            <p class="error-details">
                <?= escapeHtml($error['details']) ?>
            </p>
        <?php endif; ?>

        <?php if (($error['code'] ?? null) === 'HTTP_BAD_STATUS'): ?>
            <p class="hint">
                The external source returned a non-success HTTP status.
                The application handled this safely instead of crashing.
            </p>
        <?php endif; ?>

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
</section>
