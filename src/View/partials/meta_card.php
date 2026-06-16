<?php if (!empty($meta)): ?>
    <details class="meta-card">
        <summary>Request meta</summary>

        <pre><?= escapeHtml(print_r($meta, true)) ?></pre>
    </details>
<?php endif; ?>