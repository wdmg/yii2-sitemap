<?php

use yii\helpers\Html;

?>
<?php echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($items as $item): ?>
<?php if (isset($item['url']) && isset($item['updated_at'])) : ?>
    <url>
        <loc><?= Html::encode($item['url']); ?></loc>
        <lastmod><?= $item['updated_at']; ?></lastmod>
<?php if (isset($item['changefreq'])) : ?>
        <changefreq><?= $item['changefreq']; ?></changefreq>
<?php endif; ?>
<?php if (isset($item['priority'])) : ?>
        <priority><?= $item['priority']; ?></priority>
<?php endif; ?>
    </url>
<?php endif; ?><?php endforeach; ?>
</urlset>