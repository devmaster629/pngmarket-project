<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php'); ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
  <script type="text/javascript" src="<?php echo osc_current_web_theme_js_url('jquery.validate.min.js'); ?>"></script>
</head>

<body id="contact" class="pre-account contact has-footer pngm-contact">
  <?php UserForm::js_validation(); ?>
  <?php osc_current_web_theme_path('header.php'); ?>

  <?php
    $pngm_contact = function_exists('pngm_footer_contact') ? pngm_footer_contact() : array();
    $pngm_email = !empty($pngm_contact['email']) ? $pngm_contact['email'] : '';
    $pngm_phone = !empty($pngm_contact['phone']) ? $pngm_contact['phone'] : '';
    $pngm_tel = !empty($pngm_contact['tel']) ? $pngm_contact['tel'] : preg_replace('/[^\d+]/', '', $pngm_phone);
    $pngm_address = !empty($pngm_contact['address']) ? $pngm_contact['address'] : '';
    $privacy_url = '#';
    if (class_exists('Page')) {
        $privacy = Page::newInstance()->findByInternalName('privacy');
        if (is_array($privacy) && !empty($privacy['pk_i_id']) && function_exists('osc_static_page_url_from_page')) {
            $privacy_url = osc_static_page_url_from_page($privacy);
        }
    }
  ?>

  <div class="pngm-contact-hero">
    <div class="pngm-contact-hero-inner">
      <div class="pngm-contact-hero-text">
        <h1><?php _e('Contact us', 'epsilon'); ?></h1>
        <p><?php _e("We're here to help and happy to answer any questions you have.", 'epsilon'); ?></p>
      </div>
      <span class="pngm-contact-hero-art" aria-hidden="true">
        <svg viewBox="0 0 168 128" width="148" height="112">
          <rect x="8" y="78" width="30" height="20" rx="3" fill="#c8e8d3" transform="rotate(-18 23 88)"/>
          <rect x="128" y="14" width="26" height="18" rx="3" fill="#d4eedd" transform="rotate(22 141 23)"/>
          <path fill="#6fc48a" d="M18 22l1.6 4.2 4.4 1.6-4.4 1.6L18 33.6l-1.6-4.2-4.4-1.6 4.4-1.6z"/>
          <path fill="#8ed4a4" d="M152 86l1.2 3.2 3.4 1.2-3.4 1.2L152 95l-1.2-3.2-3.4-1.2 3.4-1.2z"/>
          <path fill="#7dcb94" d="M44 8l1 2.6 2.8 1-2.8 1L44 15.2l-1-2.6-2.8-1 2.8-1z"/>
          <path d="M112 26c18-14 36-4 42 16" fill="none" stroke="#5aad72" stroke-width="1.6" stroke-dasharray="3 4" stroke-linecap="round"/>
          <g transform="rotate(-16 92 48)">
            <rect x="68" y="12" width="58" height="50" rx="5" fill="#fff" stroke="#e4ebe6"/>
            <path d="M86 24l16 7-7 3-3 7z" fill="#16883f"/>
            <rect x="78" y="42" width="34" height="2.2" rx="1" fill="#d7e0e6"/>
            <rect x="78" y="48" width="26" height="2.2" rx="1" fill="#d7e0e6"/>
            <rect x="78" y="54" width="20" height="2.2" rx="1" fill="#d7e0e6"/>
          </g>
          <g transform="rotate(14 86 78)">
            <path d="M30 52h108v50H30z" fill="#146c35"/>
            <path d="M30 52l54 36 54-36" fill="#0e5a2c"/>
            <path d="M30 102l54-36 54 36" fill="#188043" opacity=".9"/>
          </g>
        </svg>
      </span>
    </div>
  </div>

  <section class="container pngm-contact-wrap">
    <div class="box pngm-contact-page">

      <aside class="pngm-contact-sidebar">
        <h2 class="pngm-contact-section-title"><?php _e('Get in touch', 'epsilon'); ?></h2>
        <p class="pngm-contact-section-lead"><?php _e('Reach our team using any of the details below.', 'epsilon'); ?></p>

        <?php if ($pngm_email !== '') { ?>
          <div class="pngm-contact-method">
            <span class="pngm-contact-ico" aria-hidden="true"><i class="far fa-envelope"></i></span>
            <div>
              <strong><?php _e('Email', 'epsilon'); ?></strong>
              <a href="mailto:<?php echo osc_esc_html($pngm_email); ?>"><?php echo osc_esc_html($pngm_email); ?></a>
              <em><?php _e('We usually reply within 24 hours.', 'epsilon'); ?></em>
            </div>
          </div>
        <?php } ?>

        <?php if ($pngm_phone !== '') { ?>
          <div class="pngm-contact-method">
            <span class="pngm-contact-ico" aria-hidden="true"><i class="fas fa-phone-alt"></i></span>
            <div>
              <strong><?php _e('Phone', 'epsilon'); ?></strong>
              <a href="tel:<?php echo osc_esc_html($pngm_tel); ?>"><?php echo osc_esc_html($pngm_phone); ?></a>
              <em><?php _e('Mon–Fri, 9:00am – 5:00pm (PNG time)', 'epsilon'); ?></em>
            </div>
          </div>
        <?php } ?>

        <?php if ($pngm_address !== '') { ?>
          <div class="pngm-contact-method">
            <span class="pngm-contact-ico" aria-hidden="true"><i class="fas fa-map-marker-alt"></i></span>
            <div>
              <strong><?php _e('Location', 'epsilon'); ?></strong>
              <span class="pngm-contact-plain"><?php echo osc_esc_html($pngm_address); ?></span>
            </div>
          </div>
        <?php } ?>

        <div class="pngm-contact-method">
          <span class="pngm-contact-ico" aria-hidden="true"><i class="far fa-clock"></i></span>
          <div>
            <strong><?php _e('Business Hours', 'epsilon'); ?></strong>
            <span class="pngm-contact-plain"><?php _e('Monday – Friday: 9:00am – 5:00pm', 'epsilon'); ?></span>
            <em><?php _e('Closed weekends and public holidays', 'epsilon'); ?></em>
          </div>
        </div>

        <div class="pngm-contact-privacy">
          <span class="pngm-privacy-ico" aria-hidden="true"><i class="fas fa-shield-alt"></i></span>
          <div>
            <strong><?php _e('We value your privacy', 'epsilon'); ?></strong>
            <span><?php _e('Your information is safe with us. We’ll never share your personal details with third parties.', 'epsilon'); ?></span>
          </div>
        </div>
      </aside>

      <form class="pngm-contact-form" action="<?php echo osc_base_url(true); ?>" method="post" name="contact_form" <?php if (osc_contact_attachment()) { ?>enctype="multipart/form-data"<?php } ?>>
        <input type="hidden" name="page" value="contact" />
        <input type="hidden" name="action" value="contact_post" />

        <h2 class="pngm-contact-section-title"><?php _e('Send us a message', 'epsilon'); ?></h2>
        <p class="pngm-contact-section-lead"><?php _e('Fill out the form and we’ll get back to you as soon as we can.', 'epsilon'); ?></p>

        <ul id="error_list"></ul>

        <div class="row r1">
          <label for="yourName"><?php _e('Full Name', 'epsilon'); ?> <span class="req">*</span></label>
          <div class="input-box">
            <input type="text" name="yourName" id="yourName" required value="" placeholder="<?php echo osc_esc_html(__('e.g. John Doe', 'epsilon')); ?>" autocomplete="name" />
          </div>
        </div>

        <div class="row r2">
          <label for="yourEmail"><?php _e('Email Address', 'epsilon'); ?> <span class="req">*</span></label>
          <div class="input-box">
            <input type="email" name="yourEmail" id="yourEmail" required value="" placeholder="<?php echo osc_esc_html(__('e.g. name@example.com', 'epsilon')); ?>" autocomplete="email" />
          </div>
        </div>

        <div class="row r3">
          <label for="subject"><?php _e('Subject', 'epsilon'); ?> <span class="req">*</span></label>
          <div class="input-box">
            <select name="subject" id="subject" required>
              <option value=""><?php _e('Select a subject', 'epsilon'); ?></option>
              <option value="<?php echo osc_esc_html(__('General question', 'epsilon')); ?>"><?php _e('General question', 'epsilon'); ?></option>
              <option value="<?php echo osc_esc_html(__('Account help', 'epsilon')); ?>"><?php _e('Account help', 'epsilon'); ?></option>
              <option value="<?php echo osc_esc_html(__('Listing / ads', 'epsilon')); ?>"><?php _e('Listing / ads', 'epsilon'); ?></option>
              <option value="<?php echo osc_esc_html(__('Report a problem', 'epsilon')); ?>"><?php _e('Report a problem', 'epsilon'); ?></option>
              <option value="<?php echo osc_esc_html(__('Other', 'epsilon')); ?>"><?php _e('Other', 'epsilon'); ?></option>
            </select>
          </div>
        </div>

        <div class="row r4">
          <label for="message"><?php _e('Message', 'epsilon'); ?> <span class="req">*</span></label>
          <div class="input-box last"><?php ContactForm::your_message(); ?></div>
        </div>

        <?php if (osc_contact_attachment()) { ?>
          <div class="row r5">
            <label for="attachment"><?php _e('Attachment', 'epsilon'); ?></label>
            <div class="input-box last2"><?php ContactForm::your_attachment(); ?></div>
          </div>
        <?php } ?>

        <?php osc_run_hook('contact_form'); ?>

        <label class="pngm-contact-consent">
          <input type="checkbox" name="pngm_contact_consent" id="pngm_contact_consent" value="1" required />
          <span>
            <?php
              echo sprintf(
                  __('I agree to be contacted about my message and accept the %s.', 'epsilon'),
                  '<a href="' . osc_esc_html($privacy_url) . '" target="_blank" rel="noopener">' . osc_esc_html(__('Privacy Policy', 'epsilon')) . '</a>'
              );
            ?>
          </span>
        </label>

        <?php eps_show_recaptcha(); ?>

        <button type="submit" class="btn complete-contact pngm-send-btn">
          <span><?php _e('Send Message', 'epsilon'); ?></span>
        </button>

        <?php osc_run_hook('admin_contact_form'); ?>
      </form>

    </div>
  </section>

  <?php ContactForm::js_validation(); ?>
  <?php osc_current_web_theme_path('footer.php'); ?>

  <script type="text/javascript">
    $(document).ready(function () {
      $('input[name="yourName"]').attr('placeholder', '<?php echo osc_esc_js(__('e.g. John Doe', 'epsilon')); ?>');
      $('input[name="yourEmail"]').attr('placeholder', '<?php echo osc_esc_js(__('e.g. name@example.com', 'epsilon')); ?>');
      $('textarea[name="message"]').attr('placeholder', '<?php echo osc_esc_js(__('How can we help you?', 'epsilon')); ?>');
    });
  </script>
</body>
</html>
