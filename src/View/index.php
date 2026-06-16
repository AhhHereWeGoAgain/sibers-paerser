<?php require __DIR__ . '/header.php'; ?>

<main class="container page">
    <section class="hero">
        <h1>External data parser</h1>
        <p>
            Choose a data source.
        </p>
    </section>

    <?php require __DIR__ . '/partials/source_form.php'; ?>

    <?php if (!empty($error)): ?>
        <?php require __DIR__ . '/partials/error_card.php'; ?>
    <?php endif; ?>

    <?php if (empty($error) && !empty($selected_source)): ?>

        <?php require __DIR__ . '/partials/items_list.php'; ?>

        <?php require __DIR__ . '/partials/pagination.php'; ?>
    <?php endif; ?>

    <?php //require __DIR__ . '/partials/meta_card.php'; ?>
</main>

<?php require __DIR__ . '/footer.php'; ?>