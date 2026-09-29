<?php
/**
 * Saved Searches / My alerts — removed from PNG Market account UI.
 * Old bookmarks and rewrite URLs land on the dashboard instead.
 */
if (!function_exists('osc_user_dashboard_url')) {
    header('Location: ' . osc_base_url());
    exit;
}
header('Location: ' . osc_user_dashboard_url(), true, 302);
exit;
