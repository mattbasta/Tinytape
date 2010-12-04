<?php
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php
$pages = view_manager::get_value("PAGES");
foreach($pages as $page) {
?>
<url>
<loc>http://tinytape.com<?php echo URL_PREFIX, $page; ?></loc>
<changefreq><?php echo view_manager::get_value("UPDATES"); ?></changefreq>
</url>
<?php
}
?>
</urlset>