<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo esc_html($title)?></title>
    <?php
        global $wp_scripts;
        global $wp_styles;
        $wp_scripts->print_inline_script('elements-js');
        $wp_styles->print_inline_style('elements-style');
    ?>
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