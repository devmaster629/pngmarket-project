<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <meta name="robots" content="index, follow" />
  <meta name="googlebot" content="index, follow" />
</head>

<body id="page" class="page has-footer pngm-about">
  <?php
    $brand = function_exists('pngm_footer_contact') ? pngm_footer_contact() : array('name' => 'PNGMarket');
    $site_name = !empty($brand['name']) ? $brand['name'] : 'PNGMarket';
    $browse_url = osc_search_url(array('page' => 'search'));
    $post_url = osc_item_post_url();
    $safety_url = function_exists('pngm_safety_tips_url') ? pngm_safety_tips_url() : osc_contact_url();
    $hero_img = osc_current_web_theme_url('images/about-hero.png') . '?v=' . rawurlencode(PNGM_CHILD_VERSION);
  ?>
  <?php osc_current_web_theme_path('header.php'); ?>

  <main class="pngm-about-page">
    <section class="pngm-about-hero">
      <div class="pngm-about-inner pngm-about-hero-grid">
        <div class="pngm-about-hero-copy">
          <h1><?php echo osc_esc_html(sprintf(__('About %s', 'epsilon'), $site_name)); ?></h1>
          <p><?php echo osc_esc_html(sprintf(__('%s is Papua New Guinea’s trusted online marketplace — a simple place to buy and sell cars, phones, electronics, furniture, jobs, services and more.', 'epsilon'), $site_name)); ?></p>
          <p><?php _e('Whether you are searching locally in Port Moresby or reaching buyers nationwide, we help you connect with real people in your community.', 'epsilon'); ?></p>
          <div class="pngm-about-hero-actions">
            <a class="pngm-about-btn pngm-about-btn-primary" href="<?php echo osc_esc_html($browse_url); ?>"><?php _e('Browse Listings', 'epsilon'); ?></a>
            <a class="pngm-about-btn pngm-about-btn-secondary" href="<?php echo osc_esc_html($post_url); ?>"><?php _e('Post an Item', 'epsilon'); ?></a>
          </div>
        </div>
        <div class="pngm-about-hero-visual" aria-hidden="true">
          <span class="pngm-about-hero-glow"></span>
          <img src="<?php echo osc_esc_html($hero_img); ?>" alt="" width="640" height="400" decoding="async" />
        </div>
      </div>
    </section>

    <section class="pngm-about-values">
      <div class="pngm-about-inner pngm-about-values-grid">
        <article class="pngm-about-card">
          <span class="pngm-about-card-ico" aria-hidden="true"><i class="fas fa-bullseye"></i></span>
          <div class="pngm-about-card-body">
            <h2><?php _e('Our Mission', 'epsilon'); ?></h2>
            <p><?php _e('To empower Papua New Guineans by connecting communities through a trusted platform where buying and selling is simple, safe and accessible for everyone.', 'epsilon'); ?></p>
          </div>
        </article>

        <article class="pngm-about-card">
          <span class="pngm-about-card-ico" aria-hidden="true"><i class="far fa-eye"></i></span>
          <div class="pngm-about-card-body">
            <h2><?php _e('Our Vision', 'epsilon'); ?></h2>
            <p><?php _e('To be PNG’s most trusted marketplace, supporting local growth and digital opportunity.', 'epsilon'); ?></p>
          </div>
        </article>

        <article class="pngm-about-card">
          <span class="pngm-about-card-ico" aria-hidden="true"><i class="fas fa-shield-alt"></i></span>
          <div class="pngm-about-card-body">
            <h2><?php _e('Built on Trust', 'epsilon'); ?></h2>
            <ul class="pngm-about-checks">
              <li><?php _e('Verified listings and safer transactions', 'epsilon'); ?></li>
              <li><?php _e('Community focused and locally owned', 'epsilon'); ?></li>
              <li><?php _e('Secure, private and easy to use', 'epsilon'); ?></li>
            </ul>
          </div>
        </article>
      </div>
    </section>

    <section class="pngm-about-helps">
      <div class="pngm-about-inner">
        <div class="pngm-about-helps-head">
          <h2><?php echo osc_esc_html(sprintf(__('How %s Helps Buyers and Sellers', 'epsilon'), $site_name)); ?></h2>
          <p><?php _e('Everything you need in one place to buy, sell and connect with confidence.', 'epsilon'); ?></p>
        </div>

        <div class="pngm-about-helps-grid">
          <article class="pngm-about-help">
            <span class="pngm-about-help-ico" aria-hidden="true"><i class="fas fa-search"></i></span>
            <div class="pngm-about-help-body">
              <h3><?php _e('Find What You Need', 'epsilon'); ?></h3>
              <p><?php _e('Discover a wide variety of items and services from local sellers and businesses across PNG.', 'epsilon'); ?></p>
            </div>
          </article>

          <article class="pngm-about-help">
            <span class="pngm-about-help-ico" aria-hidden="true"><i class="fas fa-tag"></i></span>
            <div class="pngm-about-help-body">
              <h3><?php _e('Sell With Confidence', 'epsilon'); ?></h3>
              <p><?php _e('List your items quickly with photos and details that help serious buyers reach out.', 'epsilon'); ?></p>
            </div>
          </article>

          <article class="pngm-about-help">
            <span class="pngm-about-help-ico" aria-hidden="true"><i class="far fa-comment-dots"></i></span>
            <div class="pngm-about-help-body">
              <h3><?php _e('Connect Locally', 'epsilon'); ?></h3>
              <p><?php _e('Message buyers and sellers directly and arrange deals in your city or province.', 'epsilon'); ?></p>
            </div>
          </article>

          <article class="pngm-about-help">
            <span class="pngm-about-help-ico" aria-hidden="true"><i class="fas fa-lock"></i></span>
            <div class="pngm-about-help-body">
              <h3><?php _e('Safe and Secure', 'epsilon'); ?></h3>
              <p><?php _e('Follow our safety guidance and trade with community standards that put people first.', 'epsilon'); ?></p>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section class="pngm-about-safety">
      <div class="pngm-about-inner pngm-about-safety-banner">
        <div class="pngm-about-safety-copy">
          <span class="pngm-about-safety-ico" aria-hidden="true"><i class="fas fa-users"></i></span>
          <div>
            <h2><?php _e('Safety and Community Come First', 'epsilon'); ?></h2>
            <p><?php echo osc_esc_html(sprintf(__('%s is built for Papua New Guineans, by Papua New Guineans. We encourage respectful communication, honest listings and responsible trading to keep our community strong and trustworthy.', 'epsilon'), $site_name)); ?></p>
          </div>
        </div>
        <a class="pngm-about-btn pngm-about-btn-secondary" href="<?php echo osc_esc_html($safety_url); ?>"><?php _e('Learn Our Safety Tips', 'epsilon'); ?></a>
      </div>
    </section>
  </main>

  <?php osc_current_web_theme_path('footer.php'); ?>
</body>
</html>
