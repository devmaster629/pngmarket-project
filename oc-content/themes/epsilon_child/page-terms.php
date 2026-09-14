<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <meta name="robots" content="index, follow" />
  <meta name="googlebot" content="index, follow" />
</head>

<body id="page" class="page has-footer pngm-terms">
  <?php
    $contact = function_exists('pngm_footer_contact') ? pngm_footer_contact() : array();
    $site_name = !empty($contact['name']) ? $contact['name'] : 'PNGMarket';
    $email = !empty($contact['email']) ? $contact['email'] : '';
    $phone = !empty($contact['phone']) ? $contact['phone'] : '';
    $tel = !empty($contact['tel']) ? $contact['tel'] : preg_replace('/[^\d+]/', '', $phone);
    $address = !empty($contact['address']) ? $contact['address'] : '';

    $privacy_url = '#';
    if (class_exists('Page')) {
      $privacy = Page::newInstance()->findByInternalName('privacy');
      if (is_array($privacy) && !empty($privacy['pk_i_id']) && function_exists('osc_static_page_url_from_page')) {
        $privacy_url = osc_static_page_url_from_page($privacy);
      }
    }

    $toc = array(
      1  => __('Introduction', 'epsilon'),
      2  => __('Eligibility & User Accounts', 'epsilon'),
      3  => __('Listings & Content', 'epsilon'),
      4  => __('Buying & Selling', 'epsilon'),
      5  => __('Prohibited Activities', 'epsilon'),
      6  => __('Fees & Payments', 'epsilon'),
      7  => __('Privacy', 'epsilon'),
      8  => __('Limitation of Liability', 'epsilon'),
      9  => __('Changes to These Terms', 'epsilon'),
      10 => __('Contact Us', 'epsilon'),
    );
  ?>
  <?php osc_current_web_theme_path('header.php'); ?>

  <main class="pngm-terms-page">
    <div class="pngm-terms-inner">

      <nav class="pngm-terms-crumbs" aria-label="<?php echo osc_esc_html(__('Breadcrumb', 'epsilon')); ?>">
        <a href="<?php echo osc_base_url(); ?>"><?php _e('Home', 'epsilon'); ?></a>
        <span aria-hidden="true">›</span>
        <span><?php _e('Terms of Service', 'epsilon'); ?></span>
      </nav>

      <div class="pngm-terms-head">
        <h1><?php _e('Terms of Service', 'epsilon'); ?></h1>
        <div class="pngm-terms-meta">
          <span class="pngm-terms-chip"><?php _e('Effective Date: 24 May 2025', 'epsilon'); ?></span>
          <span class="pngm-terms-chip"><?php _e('Version: 1.0', 'epsilon'); ?></span>
        </div>
        <p class="pngm-terms-lead"><?php echo osc_esc_html(sprintf(__('Welcome to %s. By accessing or using our platform, you agree to these Terms of Service. Please read them carefully.', 'epsilon'), $site_name)); ?></p>
      </div>

      <aside class="pngm-terms-toc" aria-label="<?php echo osc_esc_html(__('Contents', 'epsilon')); ?>">
        <button type="button" class="pngm-terms-toc-toggle" id="pngm-terms-toc-toggle" aria-expanded="false" aria-controls="pngm-terms-toc-list">
          <span><?php _e('Contents', 'epsilon'); ?></span>
          <i class="fas fa-chevron-down" aria-hidden="true"></i>
        </button>
        <nav id="pngm-terms-toc-list" class="pngm-terms-toc-list">
          <p class="pngm-terms-toc-label"><?php _e('Contents', 'epsilon'); ?></p>
          <ol>
            <?php foreach ($toc as $num => $label) { ?>
              <li><a href="#terms-section-<?php echo (int) $num; ?>"><?php echo (int) $num; ?>. <?php echo osc_esc_html($label); ?></a></li>
            <?php } ?>
          </ol>
        </nav>
      </aside>

      <div class="pngm-terms-main">

        <section class="pngm-terms-section" id="terms-section-1">
          <h2>1. <?php echo osc_esc_html($toc[1]); ?></h2>
          <div class="pngm-terms-body">
            <p><?php echo osc_esc_html(sprintf(__('%s is an online marketplace that connects buyers and sellers across Papua New Guinea. These Terms govern your use of our website, apps, and related services. If you do not agree, please do not use the platform.', 'epsilon'), $site_name)); ?></p>
          </div>
          <button type="button" class="pngm-terms-more"><?php _e('Read more', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
        </section>

        <section class="pngm-terms-section" id="terms-section-2">
          <h2>2. <?php echo osc_esc_html($toc[2]); ?></h2>
          <div class="pngm-terms-body">
            <p><?php _e('You must be at least 18 years old (or the age of majority in your jurisdiction) to create an account. You agree to provide accurate registration details, keep your password secure, and take responsibility for activity under your account. We may suspend or close accounts that violate these Terms or appear fraudulent.', 'epsilon'); ?></p>
          </div>
          <button type="button" class="pngm-terms-more"><?php _e('Read more', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
        </section>

        <section class="pngm-terms-section" id="terms-section-3">
          <h2>3. <?php echo osc_esc_html($toc[3]); ?></h2>
          <div class="pngm-terms-body">
            <p><?php _e('You are solely responsible for listings, photos, descriptions, and messages you post. Content must be accurate, lawful, and not misleading. We may remove or edit content that breaches these Terms, infringes rights, or harms other users — without notice when needed to protect the community.', 'epsilon'); ?></p>
          </div>
          <button type="button" class="pngm-terms-more"><?php _e('Read more', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
        </section>

        <section class="pngm-terms-section" id="terms-section-4">
          <h2>4. <?php echo osc_esc_html($toc[4]); ?></h2>
          <div class="pngm-terms-body">
            <p><?php echo osc_esc_html(sprintf(__('%s is a venue only. Deals are between buyers and sellers. We do not own items listed, do not guarantee quality or delivery, and are not a party to any transaction. Meet safely, inspect goods, and never send money to strangers you have not verified.', 'epsilon'), $site_name)); ?></p>
          </div>
          <button type="button" class="pngm-terms-more"><?php _e('Read more', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
        </section>

        <section class="pngm-terms-section pngm-terms-no-clamp" id="terms-section-5">
          <h2>5. <?php echo osc_esc_html($toc[5]); ?></h2>
          <div class="pngm-terms-body">
            <p><?php _e('You must not use the platform to:', 'epsilon'); ?></p>
            <ul>
              <li><?php _e('Post illegal, stolen, counterfeit, or restricted goods or services', 'epsilon'); ?></li>
              <li><?php _e('Scam, harass, threaten, or defraud other users', 'epsilon'); ?></li>
              <li><?php _e('Upload malware, scrape data without permission, or disrupt the service', 'epsilon'); ?></li>
              <li><?php _e('Impersonate others or misuse another person’s account', 'epsilon'); ?></li>
              <li><?php _e('Post spam, adult content where prohibited, or hate speech', 'epsilon'); ?></li>
            </ul>
          </div>
          <button type="button" class="pngm-terms-more"><?php _e('Read more', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
        </section>

        <section class="pngm-terms-section" id="terms-section-6">
          <h2>6. <?php echo osc_esc_html($toc[6]); ?></h2>
          <div class="pngm-terms-body">
            <p><?php _e('Basic listing may be free. Optional paid features (such as promotions or premium placement) are charged as shown at checkout. Fees are generally non-refundable once the service is delivered, except where required by law or stated otherwise at purchase.', 'epsilon'); ?></p>
          </div>
          <button type="button" class="pngm-terms-more"><?php _e('Read more', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
        </section>

        <section class="pngm-terms-section" id="terms-section-7">
          <h2>7. <?php echo osc_esc_html($toc[7]); ?></h2>
          <div class="pngm-terms-body">
            <p>
              <?php
                echo sprintf(
                    __('How we collect and use personal information is described in our %s. By using the platform you also agree to that policy.', 'epsilon'),
                    '<a href="' . osc_esc_html($privacy_url) . '">' . osc_esc_html(__('Privacy Policy', 'epsilon')) . '</a>'
                );
              ?>
            </p>
          </div>
          <button type="button" class="pngm-terms-more"><?php _e('Read more', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
        </section>

        <section class="pngm-terms-section" id="terms-section-8">
          <h2>8. <?php echo osc_esc_html($toc[8]); ?></h2>
          <div class="pngm-terms-body">
            <p><?php echo osc_esc_html(sprintf(__('To the fullest extent permitted by law, %s and its operators are not liable for disputes between users, loss of goods or money from deals, listing inaccuracies, or service interruptions. Our total liability for any claim relating to the platform is limited to the fees you paid us (if any) in the three months before the claim.', 'epsilon'), $site_name)); ?></p>
          </div>
          <button type="button" class="pngm-terms-more"><?php _e('Read more', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
        </section>

        <section class="pngm-terms-section" id="terms-section-9">
          <h2>9. <?php echo osc_esc_html($toc[9]); ?></h2>
          <div class="pngm-terms-body">
            <p><?php _e('We may update these Terms from time to time. The Effective Date and version above will change when we do. Continued use after an update means you accept the revised Terms. If you do not agree, stop using the platform and close your account.', 'epsilon'); ?></p>
          </div>
          <button type="button" class="pngm-terms-more"><?php _e('Read more', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
        </section>

        <section class="pngm-terms-section pngm-terms-no-clamp" id="terms-section-10">
          <h2>10. <?php echo osc_esc_html($toc[10]); ?></h2>
          <div class="pngm-terms-body">
            <p><?php echo osc_esc_html(sprintf(__('Questions about these Terms? Contact %s:', 'epsilon'), $site_name)); ?></p>
            <ul class="pngm-terms-contact-list">
              <?php if ($email !== '') { ?>
                <li>
                  <i class="far fa-envelope" aria-hidden="true"></i>
                  <a href="mailto:<?php echo osc_esc_html($email); ?>"><?php echo osc_esc_html($email); ?></a>
                </li>
              <?php } ?>
              <?php if ($phone !== '') { ?>
                <li>
                  <i class="fas fa-phone-alt" aria-hidden="true"></i>
                  <a href="tel:<?php echo osc_esc_html($tel); ?>"><?php echo osc_esc_html($phone); ?></a>
                </li>
              <?php } ?>
              <?php if ($address !== '') { ?>
                <li>
                  <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                  <span><?php echo osc_esc_html($address); ?></span>
                </li>
              <?php } ?>
              <li>
                <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                <a href="<?php echo osc_contact_url(); ?>"><?php _e('Contact form', 'epsilon'); ?></a>
              </li>
            </ul>
          </div>
          <button type="button" class="pngm-terms-more"><?php _e('Read more', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
        </section>

      </div>
    </div>
  </main>

  <?php osc_current_web_theme_path('footer.php'); ?>

  <script type="text/javascript">
    (function ($) {
      var $toggle = $('#pngm-terms-toc-toggle');
      var $list = $('#pngm-terms-toc-list');

      $toggle.on('click', function () {
        var open = !$list.hasClass('is-open');
        $list.toggleClass('is-open', open);
        $toggle.attr('aria-expanded', open ? 'true' : 'false');
        $toggle.toggleClass('is-open', open);
      });

      $list.on('click', 'a', function () {
        if (window.matchMedia('(max-width: 900px)').matches) {
          $list.removeClass('is-open');
          $toggle.attr('aria-expanded', 'false').removeClass('is-open');
        }
      });

      $('.pngm-terms-more').on('click', function () {
        var $section = $(this).closest('.pngm-terms-section');
        var open = !$section.hasClass('is-expanded');
        $section.toggleClass('is-expanded', open);
        $(this).html(
          open
            ? '<?php echo osc_esc_js(__('Show less', 'epsilon')); ?> <i class="fas fa-chevron-up" aria-hidden="true"></i>'
            : '<?php echo osc_esc_js(__('Read more', 'epsilon')); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i>'
        );
      });
    })(jQuery);
  </script>
</body>
</html>
