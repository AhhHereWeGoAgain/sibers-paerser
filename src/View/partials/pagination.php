<?php

$current_page = (int) ($meta['app_page'] ?? $page ?? 1);
$previous_page = $meta['previous_page'] ?? null;
$next_page = $meta['next_page'] ?? null;

$has_previous_page = !empty($meta['has_previous_page']);
$has_next_page = !empty($meta['has_next_page']);

$selected_source_value = (string) ($selected_source ?? '');
?>

<?php if (!empty($selected_source_value)): ?>
    <nav class="pagination" aria-label="Pagination">
        <?php if ($has_previous_page && $previous_page !== null): ?>
            <a
                href="/?source=<?= urlencode($selected_source_value) ?>&page=<?= (int) $previous_page ?>"
                class="pagination__link"
            >
                ← Previous
            </a>
        <?php else: ?>
            <span class="pagination__link pagination__link--disabled">
                ← Previous
            </span>
        <?php endif; ?>

        <span class="pagination__current">
            Page <?= $current_page ?>
        </span>

        <?php if ($has_next_page && $next_page !== null): ?>
            <a
                href="/?source=<?= urlencode($selected_source_value) ?>&page=<?= (int) $next_page ?>"
                class="pagination__link"
            >
                Next →
            </a>
        <?php else: ?>
            <span class="pagination__link pagination__link--disabled">
                Next →
            </span>
        <?php endif; ?>

        <form method="get" action="/" class="pagination__jump-form">
            <input type="hidden" name="source" value="<?= escapeHtml($selected_source_value) ?>">

            <label for="pagination-page" class="pagination__label">
                Go to page
            </label>

            <input
                type="number"
                id="pagination-page"
                name="page"
                value="<?= $current_page ?>"
                min="1"
                class="pagination__input"
            >

            <button type="submit" class="pagination__button">
                Go
            </button>
        </form>
    </nav>
<?php endif; ?>