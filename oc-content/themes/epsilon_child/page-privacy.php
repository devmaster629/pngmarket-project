<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <meta name="robots" content="index, follow" />
  <meta name="googlebot" content="index, follow" />
</head>

<body id="page" class="page has-footer pngm-privacy">
  <?php
    $contact = function_exists('pngm_footer_contact') ? pngm_footer_contact() : array();
    $site_name = !empty($contact['name']) ? $contact['name'] : 'PNGMarket';
    $email = !empty($contact['email']) ? $contact['email'] : '';
    $phone = !empty($contact['phone']) ? $contact['phone'] : '';
    $tel = !empty($contact['tel']) ? $contact['tel'] : preg_replace('/[^\d+]/', '', $phone);
    $address = !empty($contact['address']) ? $contact['address'] : '';

    $toc = array(
      1  => __('Information We Collect', 'epsilon'),
      2  => __('How We Use Information', 'epsilon'),
      3  => __('Cookies and Analytics', 'epsilon'),
      4  => __('How We Share Information', 'epsilon'),
      5  => __('Public Listings & Profiles', 'epsilon'),
      6  => __('Data Retention', 'epsilon'),
      7  => __('Security', 'epsilon'),
      8  => __('Your Rights', 'epsilon'),
      9  => __('Children’s Privacy', 'epsilon'),
      10 => __('Third-Party Links', 'epsilon'),
      11 => __('International Users', 'epsilon'),
      12 => __('Changes to This Policy', 'epsilon'),
      13 => __('Contact Us', 'epsilon'),
      14 => __('Definitions', 'epsilon'),
    );
  ?>
  <?php osc_current_web_theme_path('header.php'); ?>

  <main class="pngm-privacy-page">
    <div class="pngm-privacy-inner">

      <div class="pngm-privacy-head">
        <h1><?php _e('Privacy Policy', 'epsilon'); ?></h1>
        <p class="pngm-privacy-meta"><?php _e('Effective Date: 24 May 2025', 'epsilon'); ?> <span aria-hidden="true">|</span> <?php _e('Version: 1.0', 'epsilon'); ?></p>
      </div>

      <div class="pngm-privacy-intro">
        <span class="pngm-privacy-intro-ico" aria-hidden="true"><i class="fas fa-shield-alt"></i></span>
        <p><?php echo osc_esc_html(sprintf(__('%s respects your privacy. This policy explains what information we collect, how we use it, and the choices you have when you use our marketplace. By using the platform, you agree to this Privacy Policy.', 'epsilon'), $site_name)); ?></p>
      </div>

      <aside class="pngm-privacy-side" aria-label="<?php echo osc_esc_html(__('Privacy navigation', 'epsilon')); ?>">
        <div class="pngm-privacy-toc">
          <button type="button" class="pngm-privacy-toc-toggle" id="pngm-privacy-toc-toggle" aria-expanded="false" aria-controls="pngm-privacy-toc-list">
            <span><?php _e('CONTENTS', 'epsilon'); ?></span>
            <i class="fas fa-chevron-down" aria-hidden="true"></i>
          </button>
          <nav id="pngm-privacy-toc-list" class="pngm-privacy-toc-list">
            <p class="pngm-privacy-toc-label"><?php _e('CONTENTS', 'epsilon'); ?></p>
            <ol>
              <?php foreach ($toc as $num => $label) { ?>
                <li><a href="#privacy-section-<?php echo (int) $num; ?>"><?php echo (int) $num; ?>. <?php echo osc_esc_html($label); ?></a></li>
              <?php } ?>
            </ol>
          </nav>
        </div>

        <div class="pngm-privacy-key">
          <p class="pngm-privacy-key-label"><?php _e('KEY DISTINCTION', 'epsilon'); ?></p>
          <div class="pngm-privacy-key-item">
            <span class="pngm-privacy-key-ico" aria-hidden="true"><i class="fas fa-globe"></i></span>
            <div>
              <strong><?php _e('Public Profile & Contact Exposure', 'epsilon'); ?></strong>
              <p><?php _e('Name, listing details, and contact methods you choose to show can be seen by other users.', 'epsilon'); ?></p>
            </div>
          </div>
          <div class="pngm-privacy-key-item">
            <span class="pngm-privacy-key-ico" aria-hidden="true"><i class="fas fa-lock"></i></span>
            <div>
              <strong><?php _e('Private Account & Message Data', 'epsilon'); ?></strong>
              <p><?php _e('Login credentials, private messages, and account settings stay private and are never listed publicly.', 'epsilon'); ?></p>
            </div>
          </div>
        </div>
      </aside>

      <div class="pngm-privacy-cols">

          <section class="pngm-privacy-section" id="privacy-section-1">
            <h2>1. <?php echo osc_esc_html($toc[1]); ?></h2>
            <div class="pngm-privacy-body">
              <p><?php echo osc_esc_html(sprintf(__('We collect information you provide and information generated when you use %s, including:', 'epsilon'), $site_name)); ?></p>
              <ul>
                <li><strong><?php _e('Account information:', 'epsilon'); ?></strong> <?php _e('name, email, phone number, password, and profile details you submit when you register or update your account.', 'epsilon'); ?></li>
                <li><strong><?php _e('Listing information:', 'epsilon'); ?></strong> <?php _e('titles, descriptions, photos, prices, categories, location, and contact preferences you include in ads.', 'epsilon'); ?></li>
                <li><strong><?php _e('Usage information:', 'epsilon'); ?></strong> <?php _e('pages viewed, searches, device/browser type, IP address, and approximate location used to operate and improve the service.', 'epsilon'); ?></li>
                <li><strong><?php _e('Messages:', 'epsilon'); ?></strong> <?php _e('content you send through our messaging or contact forms so buyers and sellers can communicate.', 'epsilon'); ?></li>
                <li><strong><?php _e('Payment information:', 'epsilon'); ?></strong> <?php _e('if you buy premium features, limited billing details may be processed by our payment providers (we do not store full card numbers).', 'epsilon'); ?></li>
              </ul>
            </div>
            <button type="button" class="pngm-privacy-more"><?php _e('Read more', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
          </section>

          <section class="pngm-privacy-section pngm-privacy-no-clamp" id="privacy-section-2">
            <h2>2. <?php echo osc_esc_html($toc[2]); ?></h2>
            <div class="pngm-privacy-body">
              <p><?php _e('We use information to:', 'epsilon'); ?></p>
              <ul>
                <li><?php _e('Operate, maintain, and improve the marketplace', 'epsilon'); ?></li>
                <li><?php _e('Create and manage your account and listings', 'epsilon'); ?></li>
                <li><?php _e('Enable communication between buyers and sellers', 'epsilon'); ?></li>
                <li><?php _e('Send service notices, security alerts, and (where allowed) product updates', 'epsilon'); ?></li>
                <li><?php _e('Detect fraud, abuse, and policy violations', 'epsilon'); ?></li>
                <li><?php _e('Comply with legal obligations and enforce our Terms', 'epsilon'); ?></li>
              </ul>
            </div>
            <button type="button" class="pngm-privacy-more"><?php _e('Read more', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
          </section>

          <section class="pngm-privacy-section" id="privacy-section-3">
            <h2>3. <?php echo osc_esc_html($toc[3]); ?></h2>
            <div class="pngm-privacy-body">
              <p><?php _e('We use cookies and similar technologies to keep you signed in, remember preferences, measure traffic, and improve performance. You can control cookies through your browser settings. Disabling some cookies may limit certain features.', 'epsilon'); ?></p>
            </div>
            <button type="button" class="pngm-privacy-more"><?php _e('Read more', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
          </section>

          <section class="pngm-privacy-section" id="privacy-section-4">
            <h2>4. <?php echo osc_esc_html($toc[4]); ?></h2>
            <div class="pngm-privacy-body">
              <p><?php _e('We do not sell your personal information. We may share information with:', 'epsilon'); ?></p>
              <ul>
                <li><?php _e('Other users, when you publish a listing or choose to share contact details', 'epsilon'); ?></li>
                <li><?php _e('Service providers who help us host, email, analyse, or process payments', 'epsilon'); ?></li>
                <li><?php _e('Authorities when required by law or to protect rights, safety, or the platform', 'epsilon'); ?></li>
              </ul>
            </div>
            <button type="button" class="pngm-privacy-more"><?php _e('Read more', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
          </section>

          <section class="pngm-privacy-section" id="privacy-section-5">
            <h2>5. <?php echo osc_esc_html($toc[5]); ?></h2>
            <div class="pngm-privacy-body">
              <p><?php _e('Listings and public profile fields you publish are visible to visitors. Think carefully before including personal phone numbers, home addresses, or sensitive details in ads. You can edit or remove listings from your account at any time.', 'epsilon'); ?></p>
            </div>
            <button type="button" class="pngm-privacy-more"><?php _e('Read more', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
          </section>

          <div class="pngm-privacy-callout">
            <span class="pngm-privacy-callout-ico" aria-hidden="true"><i class="fas fa-lock"></i></span>
            <p><strong><?php _e('Private account and message data is never made public.', 'epsilon'); ?></strong> <?php _e('Only information you choose to include in listings or your public profile can appear to other users.', 'epsilon'); ?></p>
          </div>

          <section class="pngm-privacy-section" id="privacy-section-6">
            <h2>6. <?php echo osc_esc_html($toc[6]); ?></h2>
            <div class="pngm-privacy-body">
              <p><?php _e('We keep account and listing data for as long as your account is active or as needed to provide the service, resolve disputes, enforce policies, and meet legal requirements. You may request deletion of your account; some records may be retained where the law requires.', 'epsilon'); ?></p>
            </div>
            <button type="button" class="pngm-privacy-more"><?php _e('Read more', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
          </section>

          <section class="pngm-privacy-section" id="privacy-section-7">
            <h2>7. <?php echo osc_esc_html($toc[7]); ?></h2>
            <div class="pngm-privacy-body">
              <p><?php _e('We use reasonable administrative, technical, and organisational measures to protect personal data. No online service is completely secure — please use a strong password and keep your login details private.', 'epsilon'); ?></p>
            </div>
            <button type="button" class="pngm-privacy-more"><?php _e('Read more', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
          </section>

          <section class="pngm-privacy-section" id="privacy-section-8">
            <h2>8. <?php echo osc_esc_html($toc[8]); ?></h2>
            <div class="pngm-privacy-body">
              <p><?php _e('Depending on applicable law, you may request to access, correct, update, or delete personal information we hold about you. You can update most account details in your profile settings, or contact us using the details below.', 'epsilon'); ?></p>
            </div>
            <button type="button" class="pngm-privacy-more"><?php _e('Read more', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
          </section>

          <section class="pngm-privacy-section" id="privacy-section-9">
            <h2>9. <?php echo osc_esc_html($toc[9]); ?></h2>
            <div class="pngm-privacy-body">
              <p><?php echo osc_esc_html(sprintf(__('%s is not directed at children under 16. We do not knowingly collect personal information from children. If you believe a child has provided us data, contact us and we will take appropriate steps.', 'epsilon'), $site_name)); ?></p>
            </div>
            <button type="button" class="pngm-privacy-more"><?php _e('Read more', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
          </section>

          <section class="pngm-privacy-section" id="privacy-section-10">
            <h2>10. <?php echo osc_esc_html($toc[10]); ?></h2>
            <div class="pngm-privacy-body">
              <p><?php _e('Our site may link to third-party websites or services. Their privacy practices are their own. We encourage you to read their policies before sharing information with them.', 'epsilon'); ?></p>
            </div>
            <button type="button" class="pngm-privacy-more"><?php _e('Read more', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
          </section>

          <section class="pngm-privacy-section" id="privacy-section-11">
            <h2>11. <?php echo osc_esc_html($toc[11]); ?></h2>
            <div class="pngm-privacy-body">
              <p><?php _e('PNGMarket is operated for users in Papua New Guinea. If you access the service from outside PNG, you understand that your information may be processed in PNG or other locations where our providers operate.', 'epsilon'); ?></p>
            </div>
            <button type="button" class="pngm-privacy-more"><?php _e('Read more', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
          </section>

          <section class="pngm-privacy-section" id="privacy-section-12">
            <h2>12. <?php echo osc_esc_html($toc[12]); ?></h2>
            <div class="pngm-privacy-body">
              <p><?php _e('We may update this Privacy Policy from time to time. The “Effective Date” and version at the top will change when we do. Continued use of the platform after an update means you accept the revised policy.', 'epsilon'); ?></p>
            </div>
            <button type="button" class="pngm-privacy-more"><?php _e('Read more', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
          </section>

          <section class="pngm-privacy-section pngm-privacy-no-clamp" id="privacy-section-13">
            <h2>13. <?php echo osc_esc_html($toc[13]); ?></h2>
            <div class="pngm-privacy-body">
              <p><?php echo osc_esc_html(sprintf(__('Questions about privacy or this policy? Contact %s:', 'epsilon'), $site_name)); ?></p>
              <ul class="pngm-privacy-contact-list">
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
              </ul>
            </div>
            <button type="button" class="pngm-privacy-more"><?php _e('Read more', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
          </section>

          <section class="pngm-privacy-section" id="privacy-section-14">
            <h2>14. <?php echo osc_esc_html($toc[14]); ?></h2>
            <div class="pngm-privacy-body">
              <ul>
                <li><strong><?php _e('Personal information:', 'epsilon'); ?></strong> <?php _e('information that identifies or can reasonably identify you.', 'epsilon'); ?></li>
                <li><strong><?php _e('Public information:', 'epsilon'); ?></strong> <?php _e('content you choose to publish in listings or your public profile.', 'epsilon'); ?></li>
                <li><strong><?php _e('Private information:', 'epsilon'); ?></strong> <?php _e('account credentials, private messages, and settings not shown publicly.', 'epsilon'); ?></li>
              </ul>
            </div>
            <button type="button" class="pngm-privacy-more"><?php _e('Read more', 'epsilon'); ?> <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
          </section>

      </div>

    </div>
  </main>

  <?php osc_current_web_theme_path('footer.php'); ?>

  <script type="text/javascript">
    (function ($) {
      var $toggle = $('#pngm-privacy-toc-toggle');
      var $list = $('#pngm-privacy-toc-list');

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

      $('.pngm-privacy-more').on('click', function () {
        var $section = $(this).closest('.pngm-privacy-section');
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
