<section class="source-panel">
    <form method="get" action="/" class="source-form">
        <div class="form-group">
            <label for="source">Data source</label>

            <select name="source" id="source" required>
                <option value="">Choose source</option>

                <?php foreach (($sources ?? []) as $source_key => $source_name): ?>
                    <option
                        value="<?= escapeHtml($source_key) ?>"
                        <?= ($selected_source ?? null) === $source_key ? 'selected' : '' ?>
                    >
                        <?= escapeHtml($source_name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <input type="hidden" name="page" value="1">

        <button type="submit" class="button">
            Load data
        </button>
    </form>
</section>