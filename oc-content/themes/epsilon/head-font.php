<?php $font_urls = array_filter(array_map('trim', explode(',', eps_param('font_url')))); ?>
<?php if(count($font_urls) > 0) { foreach($font_urls as $url) { ?><link href="<?php echo $url; ?>" rel="stylesheet"><?php echo PHP_EOL; ?><?php } } ?>
<style>body,html,input,select,textarea,button {font-family:<?php echo eps_param('font_name'); ?>}</style>