<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo esc_html($title)?></title>
    <!-- Embed elements Elements via Web Component -->
    <script src="https://unpkg.com/@stoplight/elements/web-components.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/@stoplight/elements/styles.min.css">
</head>
<body>

<elements-api
    id="docs"
    router="hash"
    layout="sidebar"
    hideTryIt="true"
/>

<script>
    (async () => {
        const docs = document.getElementById('docs');
        docs.apiDescriptionDocument = <?php echo wp_json_encode($schema)?>
    })();
</script>
</body>
</html>