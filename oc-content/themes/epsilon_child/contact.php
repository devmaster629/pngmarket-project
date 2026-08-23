<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
  <script type="text/javascript" src="<?php echo osc_current_web_theme_js_url('jquery.validate.min.js') ; ?>"></script>
</head>

<body id="contact" class="pre-account contact has-footer pngm-contact">
  <?php UserForm::js_validation(); ?>
  <?php osc_current_web_theme_path('header.php') ; ?>

  <?php
    $pngm_contact = function_exists('pngm_footer_contact') ? pngm_footer_contact() : array();
    $pngm_wa_url = function_exists('pngm_site_whatsapp_url') ? pngm_site_whatsapp_url() : '';
    $pngm_email = !empty($pngm_contact['email']) ? $pngm_contact['email'] : '';
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

  <section class="container">
    <div class="box pngm-contact-page">

      <aside class="pngm-contact-sidebar">
        <h2 class="pngm-contact-section-title"><?php _e('Get in touch', 'epsilon'); ?></h2>

        <?php if ($pngm_email !== '') { ?>
          <div class="pngm-contact-side-item">
            <div class="pngm-contact-email-row">
              <span class="pngm-contact-ico" aria-hidden="true"><i class="far fa-envelope"></i></span>
              <div>
                <strong><?php _e('Email us', 'epsilon'); ?></strong>
                <a href="mailto:<?php echo osc_esc_html($pngm_email); ?>"><?php echo osc_esc_html($pngm_email); ?></a>
                <em><?php _e('We usually reply within 24 hours.', 'epsilon'); ?></em>
              </div>
            </div>
          </div>
        <?php } ?>

        <?php if ($pngm_wa_url !== '') { ?>
          <div class="pngm-contact-side-item">
            <div class="pngm-contact-wa-block">
              <div class="pngm-contact-wa-head">
                <span class="pngm-wa-logo" aria-hidden="true"><i class="fab fa-whatsapp"></i></span>
                <span class="pngm-wa-copy">
                  <strong><?php _e('Prefer WhatsApp?', 'epsilon'); ?></strong>
                  <span><?php _e('Chat with us directly on WhatsApp.', 'epsilon'); ?></span>
                </span>
              </div>
              <a class="pngm-wa-btn" href="<?php echo osc_esc_html($pngm_wa_url); ?>" target="_blank" rel="noopener noreferrer">
                <i class="fab fa-whatsapp" aria-hidden="true"></i>
                <?php _e('Chat on WhatsApp', 'epsilon'); ?>
              </a>
            </div>
          </div>
        <?php } ?>

        <div class="pngm-contact-side-item pngm-contact-side-item-last">
          <div class="pngm-contact-privacy">
            <span class="pngm-privacy-ico" aria-hidden="true"><i class="fas fa-shield-alt"></i></span>
            <div>
              <strong><?php _e('Your information is safe with us', 'epsilon'); ?></strong>
              <span><?php _e('We respect your privacy and will never share your details.', 'epsilon'); ?></span>
            </div>
          </div>
        </div>
      </aside>

      <form class="pngm-contact-form" action="<?php echo osc_base_url(true) ; ?>" method="post" name="contact_form" <?php if(osc_contact_attachment()) { ?>enctype="multipart/form-data"<?php } ?>>
        <input type="hidden" name="page" value="contact" />
        <input type="hidden" name="action" value="contact_post" />

        <ul id="error_list"></ul>

        <div class="pngm-contact-form-top">
          <div class="row r1">
            <label for="yourName"><i class="far fa-user" aria-hidden="true"></i> <?php _e('Your name', 'epsilon'); ?> <span class="req">*</span></label>
            <div class="input-box">
              <input type="text" name="yourName" <?php if(osc_is_web_user_logged_in()) { ?>readonly<?php } ?> required value="<?php echo osc_esc_html( osc_logged_user_name() ); ?>" />
            </div>
          </div>

          <div class="row r2">
            <label for="yourEmail"><i class="far fa-envelope" aria-hidden="true"></i> <?php _e('Email', 'epsilon'); ?> <span class="req">*</span></label>
            <div class="input-box">
              <input type="email" name="yourEmail" <?php if(osc_is_web_user_logged_in()) { ?>readonly<?php } ?> required value="<?php echo osc_logged_user_email();?>" />
            </div>
          </div>
        </div>

        <div class="row r3">
          <label for="subject">
            <svg class="pngm-field-svg" viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round">
              <path d="M20.6 13.4 12.7 21.3a2 2 0 0 1-2.8 0l-6.2-6.2a2 2 0 0 1 0-2.8l7.9-7.9H20.6v8.8z"/>
              <circle cx="16.2" cy="7.8" r="1.3"/>
            </svg>
            <?php _e('Subject', 'epsilon'); ?> <span class="req">*</span>
          </label>
          <div class="input-box"><?php ContactForm::the_subject(); ?></div>
        </div>

        <div class="row r4">
          <label for="message"><i class="far fa-comment-alt" aria-hidden="true"></i> <?php _e('Message', 'epsilon'); ?> <span class="req">*</span></label>
          <div class="input-box last"><?php ContactForm::your_message(); ?></div>
        </div>

        <?php if(osc_contact_attachment()) { ?>
          <div class="row r5">
            <label for="attachment"><?php _e('Attachment', 'epsilon'); ?></label>
            <div class="input-box last2"><?php ContactForm::your_attachment(); ?></div>
          </div>
        <?php } ?>

        <?php osc_run_hook('contact_form'); ?>

        <?php eps_show_recaptcha(); ?>

        <button type="submit" class="btn complete-contact pngm-send-btn">
          <span><?php _e('Send message', 'epsilon'); ?></span>
          <svg class="pngm-send-ico" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 2 11 13"/>
            <path d="M22 2 15 22l-4-9-9-4 20-7z"/>
          </svg>
        </button>

        <?php osc_run_hook('admin_contact_form'); ?>
      </form>

    </div>
  </section>

  <?php ContactForm::js_validation() ; ?>
  <?php osc_current_web_theme_path('footer.php') ; ?>

  <script type="text/javascript">
    $(document).ready(function(){
      $('input[name="yourName"]').attr('placeholder', '<?php echo osc_esc_js(__('First name, Last name', 'epsilon')); ?>');
      $('input[name="yourEmail"]').attr('placeholder', '<?php echo osc_esc_js(__('your.email@dot.com', 'epsilon')); ?>');
      $('input[name="subject"]').attr('placeholder', '<?php echo osc_esc_js(__('Summarize your question', 'epsilon')); ?>');
      $('textarea[name="message"]').attr('placeholder', '<?php echo osc_esc_js(__('Your question with all relevant details ...', 'epsilon')); ?>');
    });
  </script>
</body>
</html>
