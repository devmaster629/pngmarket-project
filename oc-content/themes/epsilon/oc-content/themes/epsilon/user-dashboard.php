<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo eps_language_dir(); ?>" lang="<?php echo str_replace('_', '-', osc_current_user_locale()); ?>">
<head>
  <?php osc_current_web_theme_path('head.php') ; ?>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="googlebot" content="noindex, nofollow" />
</head>
<body id="user-dashboard" class="body-ua">
  <?php osc_current_web_theme_path('header.php') ; ?>

  <div class="container primary">
    <div id="user-menu"><?php eps_user_menu(); ?></div>

    <?php 
      $user_id = osc_logged_user_id();
      $user = User::newInstance()->findByPrimaryKey($user_id); 
    ?>
    
    <div id="user-main">
      <div class="headers">
        <a href="<?php echo osc_user_profile_url(); ?>" class="img-container" title="<?php echo osc_esc_html(__('Upload profile picture', 'epsilon')); ?>">
          <img src="<?php echo eps_profile_picture($user_id, 'medium'); ?>" alt="<?php echo osc_esc_html(osc_logged_user_name()); ?>" width="36" height="36"/>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="28" height="28"><path d="M256 408c-66.2 0-120-53.8-120-120s53.8-120 120-120 120 53.8 120 120-53.8 120-120 120zm0-192c-39.7 0-72 32.3-72 72s32.3 72 72 72 72-32.3 72-72-32.3-72-72-72zm-24 72c0-13.2 10.8-24 24-24 8.8 0 16-7.2 16-16s-7.2-16-16-16c-30.9 0-56 25.1-56 56 0 8.8 7.2 16 16 16s16-7.2 16-16zm110.7-145H464v288H48V143h121.3l24-64h125.5l23.9 64zM324.3 31h-131c-20 0-37.9 12.4-44.9 31.1L136 95H48c-26.5 0-48 21.5-48 48v288c0 26.5 21.5 48 48 48h416c26.5 0 48-21.5 48-48V143c0-26.5-21.5-48-48-48h-88l-14.3-38c-5.8-15.7-20.7-26-37.4-26z"/></svg>
        </a>

        <h1>
          <?php echo sprintf(__('Hi %s', 'epsilon'), osc_logged_user_name()); ?>
          <img class="wave" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEgAAABICAMAAABiM0N1AAAAV1BMVEVHcEwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD/yD3UjQH3wDcw0fbemg4jGwfjsjbtsCUln7uvgx8acIMFEBJKOhEtweMJKjHHmy5sUxcOPUiRcB9IUWvxAAAACnRSTlMAMRr/81Lhpcd5+GBt8QAAAwBJREFUWMPtmNd2pSAUhqOiWOCIvb7/cw7tKCItOncz+yJxmfCt3YB/+/Pz3/6SpTlIQA6L+CWnSL4Go1f+JIrBF17lKigB6WMQoMtbPLX9LFDFOxCzlqNA8Sq0YesnjHv+/Nvo0gJCmJ7JHnrq1EAfsl9lPJJJzs7qJ/OEW167p0U/bJhEdEH9FME8y+EV0OK2H7hPGM9hLsUKYuy69QvCeNrYUy+C82Ypyk5OV1IjtUwPZW0iuDmkcApnLUuVRH2ZBv6rD4itEBGRZZUOqaSWIzYRG8icO5g5NCK2vEu6j0YSiAFPxw62+sNbh4jl3V5pJInA+Ig+N9dLdGDdyOVLpZPa9vxp9ymWeR6rL6mpjscjT8LqDqGutnSm9KeuKy+p5llE9BWA5i0x0vyQzkuS9eyMWWINPYp/+FQVcpLkXxHrAmPhlzKMVJ6g5AZiL4/lHpITxA7U6hNGcoJY0dZAkhPEd1kXRnKCYsAbLYjkBAmXrKQviJPcIHnWm0mKUZITxDt7JUjZqhZSVXuTzTsbeUj0nRMEjz2EPi7S5wzdB9JJKgYpHF9oGqlpbBwLKFIujgvJEpen/G6S9tICEkft6CA1WuZNIKpbgTizR2QlnRvFClKUi0pCmkMa+Q66SpiDdOugxge66taDdEs18oGAdvmZSZUfdF7E8jquiRFUhng04YELjtlKCggt54jt1GNGkjHZ4LZhhX76ik0TCenlJ0zz3so/Cz22MX0/mEl6Qy63Kzs+9ViyTWxWMJL0jtzvugYKX2ah79tJJKpetNi0XTPeJ5xIEZuqPF9cWSImfQRFcNOsKf2ltNtuUjX8ehyolO4PpzIPiZhnt1RKaTz1m2DlhSqq7jbeiq+eJLOc8HirOUm7dZiUZ9JMiyZBLlJnV8c/KbjkOT/gu8UfEAcMRnJsEalbtd1KRt+8HZ2oQi3CsCsosiYBk22cQv79ItUDHveFIESWfXw6/6sTnJLBJx83CqBjsoejf1xcvMoff4tgARY8d3RqLl59sfmH7Q97YGQsT2rqKAAAAABJRU5ErkJggg==" alt="wave">
        </h1>
        <h2><?php _e('Manage your listings, subscriptions or profile', 'epsilon'); ?></h2>
      </div>
      
      <?php osc_run_hook('user_dashboard_top'); ?>
      
      <div class="card-box">
        <?php if(function_exists('bpr_call_after_install') && (bpr_param('only_company_users') == 0 || (bpr_param('only_company_users') == 1 && $user['b_company'] == 1)) && bpr_company_url($user_id) !== false) { ?>
          <a class="card public" href="<?php echo bpr_company_url($user_id); ?>">
            <div class="icon">
              <i class="fas fa-briefcase"></i>
            </div>

            <div class="header"><?php _e('Business profile', 'epsilon'); ?></div>
            <div class="description"><?php _e('Your business profile visible to customers, where your information, address and listings are shown.', 'epsilon'); ?></div>
          </a>
        <?php } else { ?>
          <a class="card public" href="<?php echo osc_user_public_profile_url($user_id); ?>">
            <div class="icon">
              <i class="far fa-address-card"></i>
            </div>

            <div class="header"><?php _e('Public profile', 'epsilon'); ?></div>
            <div class="description"><?php _e('Your business profile visible to customers, where your information, address and listings are shown.', 'epsilon'); ?></div>
          </a>
        <?php } ?>
        
        <a class="card active" href="<?php echo eps_user_items_url('active'); ?>">
          <div class="icon">
            <i class="fas fa-check-double"></i>
            <span class="count"><?php echo eps_count_user_items($user_id, 'active'); ?></span>
          </div>

          <div class="header"><?php _e('Active listings', 'epsilon'); ?></div>
          <div class="description"><?php _e('Listings that are visible in front and customer can view and share them.', 'epsilon'); ?></div>
        </a>


        <a class="card not-validated" href="<?php echo eps_user_items_url('pending_validate'); ?>">
          <div class="icon">
            <i class="fas fa-history"></i>
            <span class="count"><?php echo eps_count_user_items($user_id, 'pending_validate'); ?></span>
          </div>

          <div class="header"><?php _e('Validation pending listings', 'epsilon'); ?></div>
          <div class="description"><?php _e('Listings that are hidden and waiting for yours or administrator\'s validation.', 'epsilon'); ?></div>
        </a>


        <a class="card expired" href="<?php echo eps_user_items_url('expired'); ?>">
          <div class="icon">
            <i class="fas fa-hourglass-end"></i>
            <span class="count"><?php echo eps_count_user_items($user_id, 'expired'); ?></span>
          </div>

          <div class="header"><?php _e('Expired listings', 'epsilon'); ?></div>
          <div class="description"><?php _e('Listings that are expired and are not visible in front. You can renew or recreate them.', 'epsilon'); ?></div>
        </a>


        <a class="card alerts" href="<?php echo osc_user_alerts_url(); ?>">
          <div class="icon">
            <svg width="32" height="32" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M459.7 194.6C482 174.1 496 144.7 496 112 496 50.1 445.9 0 384 0c-45.3 0-84.3 26.8-101.9 65.5-17.3-2-34.9-2-52.2 0C212.3 26.8 173.3 0 128 0 66.1 0 16 50.1 16 112c0 32.7 14 62.1 36.3 82.6C39.3 223 32 254.7 32 288c0 53.2 18.6 102.1 49.5 140.5L39 471c-9.4 9.4-9.4 24.6 0 33.9 9.4 9.4 24.6 9.4 33.9 0l42.5-42.5c81.5 65.7 198.7 66.4 281 0L439 505c9.4 9.4 24.6 9.4 33.9 0 9.4-9.4 9.4-24.6 0-33.9l-42.5-42.5c31-38.4 49.5-87.3 49.5-140.5.1-33.4-7.2-65.1-20.2-93.5zM384 48c35.3 0 64 28.7 64 64 0 15.1-5.3 29-14 39.9-26.2-34.2-62-60.6-103.3-75.2C342.1 59.4 361.7 48 384 48zM64 112c0-35.3 28.7-64 64-64 22.3 0 41.9 11.4 53.4 28.7-41.4 14.6-77.2 41-103.3 75.2C69.3 141 64 127.1 64 112zm192 352c-97.3 0-176-78.7-176-176 0-97 78.4-176 176-176 97.4 0 176 78.8 176 176 0 97.3-78.7 176-176 176zm46.2-95.7l-69-47.5c-3.3-2.2-5.2-5.9-5.2-9.9V180c0-6.6 5.4-12 12-12h32c6.6 0 12 5.4 12 12v107.7l50 34.4c5.5 3.8 6.8 11.2 3.1 16.7L319 365.2c-3.8 5.4-11.3 6.8-16.8 3.1z"/></svg>
            <span class="count"><?php echo count(Alerts::newInstance()->findByUser($user_id)); ?></span>
          </div>

          <div class="header"><?php _e('Subscriptions', 'epsilon'); ?></div>
          <div class="description"><?php _e('Notifications you have subscribed to based on specific search criteria.', 'epsilon'); ?></div>
        </a>


        <a class="card profile" href="<?php echo osc_user_profile_url(); ?>">
          <?php 
            $c = 0;
            if($user['s_phone_land'] == '' && $user['s_phone_mobile'] == '') { $c++; }
            if($user['s_website'] == '') { $c++; }
            if($user['s_country'] == '' && $user['s_region'] == '' && $user['s_city'] == '') { $c++; }
            if($user['s_address'] == '' && $user['s_zip'] == '') { $c++; }
          ?>

          <div class="icon">
            <svg width="32" height="32" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M358.9 433.3l-6.8 61c-1.1 10.2 7.5 18.8 17.6 17.6l60.9-6.8 137.9-137.9-71.7-71.7-137.9 137.8zM633 268.9L595.1 231c-9.3-9.3-24.5-9.3-33.8 0l-41.8 41.8 71.8 71.7 41.8-41.8c9.2-9.3 9.2-24.4-.1-33.8zM223.9 288c79.6.1 144.2-64.5 144.1-144.1C367.9 65.6 302.4.1 224.1 0 144.5-.1 79.9 64.5 80 144.1c.1 78.3 65.6 143.8 143.9 143.9zm-4.4-239.9c56.5-2.6 103 43.9 100.4 100.4-2.3 49.2-42.1 89.1-91.4 91.4-56.5 2.6-103-43.9-100.4-100.4 2.3-49.3 42.2-89.1 91.4-91.4zM134.4 352c14.6 0 38.3 16 89.6 16 51.7 0 74.9-16 89.6-16 16.7 0 32.2 5 45.5 13.3l34.4-34.4c-22.4-16.7-49.8-26.9-79.9-26.9-28.7 0-42.5 16-89.6 16-47.1 0-60.8-16-89.6-16C60.2 304 0 364.2 0 438.4V464c0 26.5 21.5 48 48 48h258.3c-3.8-14.6-2.2-20.3.9-48H48v-25.6c0-47.6 38.8-86.4 86.4-86.4z"/></svg>
            <span class="count">
              <?php if($c == 0) { ?><i class="fas fa-check"></i><?php } else { ?><i class="fas fa-exclamation"></i><?php } ?>
            </span>
          </div>

          <div class="header"><?php _e('My profile', 'epsilon'); ?></div>
          <div class="description">
            <?php if($c == 0) { ?>
              <?php _e('Your personal information, profile picture, location, business type and others', 'epsilon'); ?>
            <?php } else { ?>
              <?php echo osc_esc_html( sprintf(__('Your profile is not complete, you did not filled %s or more important data about you.', 'epsilon'), $c) ); ?>
            <?php } ?>
          </div>
        </a>

        <?php if(function_exists('bpr_call_after_install') && (bpr_param('only_company_users') == 0 || (bpr_param('only_company_users') == 1 && $user['b_company'] == 1))) { ?>
          <a class="card business-profile" href="<?php echo osc_route_url('bpr-profile'); ?>">
            <div class="icon">
              <i class="fas fa-user-edit"></i>
            </div>

            <div class="header"><?php _e('My business profile', 'epsilon'); ?></div>
            <div class="description"><?php _e('Business information, payment methods, opening hours, image gallery and more company related information.', 'epsilon'); ?></div>
          </a>
        <?php } ?>

        <?php if(function_exists('im_messages')) { ?>
          <a class="card messages" href="<?php echo osc_route_url('im-threads'); ?>">
            <div class="icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="24" height="24"><path d="M448 0H64C28.7 0 0 28.7 0 64v288c0 35.3 28.7 64 64 64h96v84c0 7.1 5.8 12 12 12 2.4 0 4.9-.7 7.1-2.4L304 416h144c35.3 0 64-28.7 64-64V64c0-35.3-28.7-64-64-64zm16 352c0 8.8-7.2 16-16 16H288l-12.8 9.6L208 428v-60H64c-8.8 0-16-7.2-16-16V64c0-8.8 7.2-16 16-16h384c8.8 0 16 7.2 16 16v288zm-96-216H144c-8.8 0-16 7.2-16 16v16c0 8.8 7.2 16 16 16h224c8.8 0 16-7.2 16-16v-16c0-8.8-7.2-16-16-16zm-96 96H144c-8.8 0-16 7.2-16 16v16c0 8.8 7.2 16 16 16h128c8.8 0 16-7.2 16-16v-16c0-8.8-7.2-16-16-16z"/></svg>
              <span class="count"><?php echo eps_count_messages($user_id); ?></span>
            </div>

            <div class="header"><?php _e('Messages', 'epsilon'); ?></div>
            <div class="description"><?php _e('Instant messages you have recieved & sent to other users.', 'epsilon'); ?></div>
          </a>
        <?php } ?>

        <?php if(function_exists('fi_make_favorite')) { ?>
          <a class="card favorite" href="<?php echo osc_route_url('favorite-lists'); ?>">
            <div class="icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" height="24" width="24"><path d="M287.9 0C297.1 0 305.5 5.25 309.5 13.52L378.1 154.8L531.4 177.5C540.4 178.8 547.8 185.1 550.7 193.7C553.5 202.4 551.2 211.9 544.8 218.2L433.6 328.4L459.9 483.9C461.4 492.9 457.7 502.1 450.2 507.4C442.8 512.7 432.1 513.4 424.9 509.1L287.9 435.9L150.1 509.1C142.9 513.4 133.1 512.7 125.6 507.4C118.2 502.1 114.5 492.9 115.1 483.9L142.2 328.4L31.11 218.2C24.65 211.9 22.36 202.4 25.2 193.7C28.03 185.1 35.5 178.8 44.49 177.5L197.7 154.8L266.3 13.52C270.4 5.249 278.7 0 287.9 0L287.9 0zM287.9 78.95L235.4 187.2C231.9 194.3 225.1 199.3 217.3 200.5L98.98 217.9L184.9 303C190.4 308.5 192.9 316.4 191.6 324.1L171.4 443.7L276.6 387.5C283.7 383.7 292.2 383.7 299.2 387.5L404.4 443.7L384.2 324.1C382.9 316.4 385.5 308.5 391 303L476.9 217.9L358.6 200.5C350.7 199.3 343.9 194.3 340.5 187.2L287.9 78.95z"></path></svg>
              <span class="count"><?php echo eps_count_favorite($user_id); ?></span>
            </div>

            <div class="header"><?php _e('Favorite listings', 'epsilon'); ?></div>
            <div class="description"><?php _e('Listings you\'ve marked as your favorite.', 'epsilon'); ?></div>
          </a>
        <?php } ?>

        <?php if(function_exists('osp_param')) { ?>
          <a class="card promote" href="<?php echo osc_route_url('osp-item'); ?>">
            <div class="icon">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="24" height="24"><path d="M288 0C305.7 0 320 14.33 320 32V96C320 113.7 305.7 128 288 128H208V160H424.1C456.6 160 483.5 183.1 488.2 214.4L510.9 364.1C511.6 368.8 512 373.6 512 378.4V448C512 483.3 483.3 512 448 512H64C28.65 512 0 483.3 0 448V378.4C0 373.6 .3622 368.8 1.083 364.1L23.76 214.4C28.5 183.1 55.39 160 87.03 160H143.1V128H63.1C46.33 128 31.1 113.7 31.1 96V32C31.1 14.33 46.33 0 63.1 0L288 0zM96 48C87.16 48 80 55.16 80 64C80 72.84 87.16 80 96 80H256C264.8 80 272 72.84 272 64C272 55.16 264.8 48 256 48H96zM80 448H432C440.8 448 448 440.8 448 432C448 423.2 440.8 416 432 416H80C71.16 416 64 423.2 64 432C64 440.8 71.16 448 80 448zM112 216C98.75 216 88 226.7 88 240C88 253.3 98.75 264 112 264C125.3 264 136 253.3 136 240C136 226.7 125.3 216 112 216zM208 264C221.3 264 232 253.3 232 240C232 226.7 221.3 216 208 216C194.7 216 184 226.7 184 240C184 253.3 194.7 264 208 264zM160 296C146.7 296 136 306.7 136 320C136 333.3 146.7 344 160 344C173.3 344 184 333.3 184 320C184 306.7 173.3 296 160 296zM304 264C317.3 264 328 253.3 328 240C328 226.7 317.3 216 304 216C290.7 216 280 226.7 280 240C280 253.3 290.7 264 304 264zM256 296C242.7 296 232 306.7 232 320C232 333.3 242.7 344 256 344C269.3 344 280 333.3 280 320C280 306.7 269.3 296 256 296zM400 264C413.3 264 424 253.3 424 240C424 226.7 413.3 216 400 216C386.7 216 376 226.7 376 240C376 253.3 386.7 264 400 264zM352 296C338.7 296 328 306.7 328 320C328 333.3 338.7 344 352 344C365.3 344 376 333.3 376 320C376 306.7 365.3 296 352 296z"/></svg>
            </div>

            <div class="header"><?php _e('Promotions', 'epsilon'); ?></div>
            <div class="description"><?php _e('Make your items more attractive, buy credits or membership.', 'epsilon'); ?></div>
          </a>
        <?php } ?>
        
        <a class="card contact" href="<?php echo osc_contact_url(); ?>">
          <div class="icon">
            <i class="fas fa-envelope"></i>
          </div>

          <div class="header"><?php _e('Contact us', 'epsilon'); ?></div>
          <div class="description"><?php _e('Do you have questions regarding our site or need help? Feel free to drop us a message.', 'epsilon'); ?></div>
        </a>
        
        <?php osc_run_hook('user_dashboard_links'); ?>
      </div>
      
      <?php osc_run_hook('user_dashboard_bottom'); ?>
    </div>
  </div>

  <?php osc_current_web_theme_path('footer.php') ; ?>
</body>
</html>