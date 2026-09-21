<?php

// DISABLE DARK MODE
if(@$_GET['ajaxDarkMode'] != '') {
  if(@$_GET['ajaxDarkMode'] == 'disable') {
    eps_set_cookie('epsDarkMode', 'disable');
  } else {
    eps_set_cookie('epsDarkMode', 'enable');
  }
}


// CLEAN ALL RECENTLY VIEWED ITEMS
if(@$_GET['ajaxCleanRecentlyViewedAll'] == 1) {
  eps_set_cookie('epsItemRecent', rawurlencode(json_encode(array())));
  exit;
}


// GET LOCATIONS FOR LOCATION PICKER VIA AJAX
if(@$_GET['ajaxCat'] == 1 && @$_GET['term'] <> '') {
  $term = trim(osc_esc_js(Params::getParam('term')));
  $type = trim(osc_esc_html(Params::getParam('dataType')));
  $limit = 24;

  $data = ModelEPS::newInstance()->findCategories($term, $limit);
 
  $output = '';
  if(is_array($data) && count($data) > 0) {
    foreach($data as $d) {
      $parent = false;
      if($d['fk_i_parent_id'] <= 0) {
        $parent = true;
      }
      
      if($type == 'LINK') {
        $output .= '<a class="option category direct' . ($parent ? ' parent' : '') . '" href="' . osc_search_url(array('page' => 'search', 'sCategory' => $d['pk_i_id'])) . '">';
      } else {
        $output .= '<div class="option category' . ($parent ? ' parent' : '') . '" data-category="' . $d['pk_i_id'] . '">';
      }
      
      $output .= '<span>' . $d['s_name'] . '</span>';
      
      if(@$d['s_name_parent'] != '') {
        $output .= '<em>' . $d['s_name_parent'] . '</em>';
      }
      
      if($type == 'LINK') {
        $output .= '</a>';
      } else {
        $output .= '</div>';
      }
    }
  }
  
  echo $output;
  exit;
}

// GET PATTERN RESULTS
if(@$_GET['ajaxPatternSearch'] == 1) {
  $term = trim(osc_esc_js(Params::getParam('term')));

  // Keyword autocomplete is always nationwide — ignore default-location loc-inp.
  $pngm_search_base = array('page' => 'search');

  if(strlen($term) >= 1) {
    // PNGMARKET: LISTING AUTOCOMPLETE (global)
    // Match title + description + category. Rank exact/prefix title matches first.
    if(function_exists('mb_strlen') ? mb_strlen($term, 'UTF-8') >= 2 : strlen($term) >= 2) {
      $normalizedTerm = function_exists('mb_strtolower')
        ? mb_strtolower($term, 'UTF-8')
        : strtolower($term);

      $normalizedTerm = trim(preg_replace('/\s+/u', ' ', $normalizedTerm));
      $termParts = preg_split('/[\s\-_]+/u', $normalizedTerm, -1, PREG_SPLIT_NO_EMPTY);

      // Use the longest typed fragment for the database pre-filter.
      $lookupPart = '';

      foreach($termParts as $part) {
        if(
          (function_exists('mb_strlen') ? mb_strlen($part, 'UTF-8') : strlen($part))
          >
          (function_exists('mb_strlen') ? mb_strlen($lookupPart, 'UTF-8') : strlen($lookupPart))
        ) {
          $lookupPart = $part;
        }
      }

      if($lookupPart != '') {
        $dao = Item::newInstance()->dao;
        $dao->select('i.*, d.s_title, d.s_description, cd.s_name AS s_category_name, loc.s_city, loc.s_region');
        $dao->from(DB_TABLE_PREFIX . 't_item i');
        $dao->join(DB_TABLE_PREFIX . 't_item_description d', 'd.fk_i_item_id = i.pk_i_id', 'INNER');
        $dao->join(DB_TABLE_PREFIX . 't_category_description cd', 'cd.fk_i_category_id = i.fk_i_category_id', 'LEFT');
        $dao->join(DB_TABLE_PREFIX . 't_item_location loc', 'loc.fk_i_item_id = i.pk_i_id', 'LEFT');
        $dao->where('i.b_active', 1);
        $dao->where('i.b_enabled', 1);
        $dao->where('i.b_spam', 0);
        // 30-day policy: hide soft-expired ads (premium included).
        $dao->where(sprintf("i.dt_expiration >= '%s'", date('Y-m-d H:i:s')));

        // SEARCH-01: match title, description or category name (partial), nationwide.
        $dao->where(sprintf(
          "(d.s_title LIKE '%%%1\$s%%' OR d.s_description LIKE '%%%1\$s%%' OR cd.s_name LIKE '%%%1\$s%%')",
          $dao->escapeStr($lookupPart)
        ));
        $dao->orderBy('i.dt_pub_date', 'DESC');
        $dao->limit(60);

        $result = $dao->get();
        $listingCandidates = $result ? $result->result() : array();
        $rankedListings = array();
        $seenListingIds = array();

        if(is_array($listingCandidates)) {
          foreach($listingCandidates as $item) {
            $itemId = (int)$item['pk_i_id'];

            // Avoid duplicates when an item has descriptions in several locales.
            if(isset($seenListingIds[$itemId])) {
              continue;
            }

            $title = trim($item['s_title']);
            $description = trim(strip_tags(@$item['s_description']));
            $categoryName = trim(@$item['s_category_name']);
            $normalizedTitle = function_exists('mb_strtolower') ? mb_strtolower($title, 'UTF-8') : strtolower($title);
            $normalizedTitle = trim(preg_replace('/\s+/u', ' ', $normalizedTitle));
            $normalizedDescription = function_exists('mb_strtolower') ? mb_strtolower($description, 'UTF-8') : strtolower($description);
            $normalizedDescription = trim(preg_replace('/\s+/u', ' ', $normalizedDescription));
            $normalizedCategory = function_exists('mb_strtolower') ? mb_strtolower($categoryName, 'UTF-8') : strtolower($categoryName);
            $haystack = trim($normalizedTitle . ' ' . $normalizedDescription . ' ' . $normalizedCategory);

            // Every typed fragment must occur in title, description or category.
            $allPartsFound = true;
            $matchedInTitle = true;
            foreach($termParts as $part) {
              $inTitle = (function_exists('mb_strpos') ? mb_strpos($normalizedTitle, $part, 0, 'UTF-8') : strpos($normalizedTitle, $part));
              $inHaystack = (function_exists('mb_strpos') ? mb_strpos($haystack, $part, 0, 'UTF-8') : strpos($haystack, $part));
              if($inHaystack === false) {
                $allPartsFound = false;
                break;
              }
              if($inTitle === false) {
                $matchedInTitle = false;
              }
            }

            if(!$allPartsFound) {
              continue;
            }

            $score = $matchedInTitle ? 50 : 70;

            // 1. Exact title.
            if($normalizedTitle === $normalizedTerm) {
              $score = 0;

            // 2. Entire title starts with the complete query.
            } elseif((function_exists('mb_strpos') ? mb_strpos($normalizedTitle, $normalizedTerm, 0, 'UTF-8') : strpos($normalizedTitle, $normalizedTerm)) === 0) {
              $score = 10;

            } else {
              $titleWords = preg_split('/[\s\-_]+/u', $normalizedTitle, -1, PREG_SPLIT_NO_EMPTY);
              $completeQueryStartsWord = false;
              $allPartsStartWords = true;

              foreach($titleWords as $word) {
                if((function_exists('mb_strpos') ? mb_strpos($word, $normalizedTerm, 0, 'UTF-8') : strpos($word, $normalizedTerm)) === 0) {
                  $completeQueryStartsWord = true;
                  break;
                }
              }

              foreach($termParts as $part) {
                $partStartsWord = false;

                foreach($titleWords as $word) {
                  if((function_exists('mb_strpos') ? mb_strpos($word, $part, 0, 'UTF-8') : strpos($word, $part)) === 0) {
                    $partStartsWord = true;
                    break;
                  }
                }

                if(!$partStartsWord) {
                  $allPartsStartWords = false;
                  break;
                }
              }

              // 3. Any word starts with the complete query.
              if($completeQueryStartsWord) {
                $score = 20;

              // 4. Every entered fragment starts some word.
              } elseif($allPartsStartWords) {
                $score = 25;

              // 5. Complete phrase occurs inside the title.
              } elseif((function_exists('mb_strpos') ? mb_strpos($normalizedTitle, $normalizedTerm, 0, 'UTF-8') : strpos($normalizedTitle, $normalizedTerm)) !== false) {
                $score = 30;

              // 6. All fragments occur somewhere in the title.
              } else {
                $score = 40;
              }
            }

            $item['_pngmarket_score'] = $score;
            $rankedListings[] = $item;
            $seenListingIds[$itemId] = true;
          }
        }

        usort($rankedListings, function($a, $b) {
          if($a['_pngmarket_score'] == $b['_pngmarket_score']) {
            return strcmp($b['dt_pub_date'], $a['dt_pub_date']);
          }

          return $a['_pngmarket_score'] - $b['_pngmarket_score'];
        });

        $rankedListings = array_slice($rankedListings, 0, 8);

        if(count($rankedListings) > 0) {
          $firstListingUrl = osc_item_url_from_item($rankedListings[0]);

          echo '<div class="row pngmarket-listings" data-listing-count="' . count($rankedListings) . '" data-first-url="' . osc_esc_html($firstListingUrl) . '">';
          echo '<div class="lead">' . __('Listings', 'epsilon') . '</div>';

          foreach($rankedListings as $item) {
            $title = osc_esc_html($item['s_title']);
            $url = osc_item_url_from_item($item);
            $meta = array();
            $categoryName = isset($item['s_category_name']) ? trim($item['s_category_name']) : '';
            $imageUrl = '';

            // Load the first item image and use its thumbnail.
            $resourceDao = ItemResource::newInstance()->dao;
            $resourceDao->select('*');
            $resourceDao->from(DB_TABLE_PREFIX . 't_item_resource');
            $resourceDao->where('fk_i_item_id', (int)$item['pk_i_id']);
            $resourceDao->orderBy('pk_i_id', 'ASC');
            $resourceDao->limit(1);
            $resourceResult = $resourceDao->get();
            $resources = $resourceResult ? $resourceResult->result() : array();

            if(is_array($resources) && isset($resources[0])) {
              $resource = $resources[0];
              $resourcePath = isset($resource['s_path']) ? $resource['s_path'] : '';

              if($resourcePath != '' && substr($resourcePath, -1) != '/') {
                $resourcePath .= '/';
              }

              $imageUrl = osc_base_url() . $resourcePath . $resource['pk_i_id'] . '_thumbnail.' . $resource['s_extension'];
              $imageUrl = osc_apply_filter('resource_path', $imageUrl);
            }

            if(isset($item['i_price']) && isset($item['fk_c_currency_code'])) {
              $meta[] = osc_format_price($item['i_price'], $item['fk_c_currency_code']);
            }

            if(isset($item['s_city']) && trim($item['s_city']) != '') {
              $meta[] = osc_esc_html($item['s_city']);
            } elseif(isset($item['s_region']) && trim($item['s_region']) != '') {
              $meta[] = osc_esc_html($item['s_region']);
            }

            echo '<a class="option direct pngmarket-listing-option" href="' . osc_esc_html($url) . '">';

            echo '<span class="pngmarket-listing-image">';
            if($imageUrl != '') {
              echo '<img src="' . osc_esc_html($imageUrl) . '" alt="" loading="lazy">';
            } else {
              echo '<span class="pngmarket-listing-placeholder"><i class="fas fa-image"></i></span>';
            }
            echo '</span>';

            echo '<span class="pngmarket-listing-content">';
            echo '<span class="pngmarket-listing-title">' . eps_highlight_term($title, $term) . '</span>';

            if($categoryName != '') {
              echo '<span class="pngmarket-listing-category">' . osc_esc_html($categoryName) . '</span>';
            }

            if(count($meta) > 0) {
              echo '<em class="pngmarket-listing-meta">' . implode(' · ', $meta) . '</em>';
            }

            echo '</span>';
            echo '<i class="fas fa-chevron-right pngmarket-listing-arrow"></i>';
            echo '</a>';
          }

          $pngm_view_all = $pngm_search_base;
          $pngm_view_all['sPattern'] = $term;
          echo '<a class="option direct pngmarket-view-all" href="' . osc_search_url($pngm_view_all) . '">';
          echo '<i class="fas fa-search"></i><span>' . sprintf(__('Show all results for “%s”', 'epsilon'), osc_esc_html($term)) . '</span><i class="fas fa-arrow-right"></i>';
          echo '</a>';
          echo '</div>';
        } else {
          echo '<div class="row pngmarket-listings pngmarket-no-exact">';
          echo '<div class="lead">' . __('Listings', 'epsilon') . '</div>';
          echo '<div class="pngmarket-no-exact-msg">' . __('No exact results found', 'epsilon') . '</div>';
          $pngm_view_all = $pngm_search_base;
          $pngm_view_all['sPattern'] = $term;
          echo '<a class="option direct pngmarket-view-all" href="' . osc_search_url($pngm_view_all) . '">';
          echo '<i class="fas fa-search"></i><span>' . sprintf(__('Show all results for “%s”', 'epsilon'), osc_esc_html($term)) . '</span><i class="fas fa-arrow-right"></i>';
          echo '</a>';
          echo '</div>';
        }
      }
    }

    // SAVED RECENT SEARCHES
    $patterns = array_reverse(eps_get_recent_patterns($term));

    if(is_array($patterns) && count($patterns) > 0) {
      echo '<div class="row patterns">';
      echo '<div class="lead">' . __('Your recent search', 'epsilon') . '</div>';
      
      foreach($patterns as $p) {
        $pngm_pattern_url = $pngm_search_base;
        $pngm_pattern_url['sPattern'] = $p;
        echo '<a class="option direct" href="' . osc_search_url($pngm_pattern_url) . '" data-pattern="' . osc_esc_html($p) . '">' . eps_highlight_term($p, $term) . '</a>';
      }
      
      echo '</div>';
    }

    // LATEST SEARCHES BY OTHER USERS
    $searches = ModelEPS::newInstance()->findLatestSearches($term, 6);

    if(is_array($searches) && count($searches) > 0) {
      echo '<div class="row searches">';
      echo '<div class="lead">' . __('Other people searched', 'epsilon') . '</div>';
      
      foreach($searches as $s) {
        $pngm_pattern_url = $pngm_search_base;
        $pngm_pattern_url['sPattern'] = $s['s_search'];
        echo '<a class="option direct" href="' . osc_search_url($pngm_pattern_url) . '" data-pattern="' . osc_esc_html($s['s_search']) . '">' . eps_highlight_term($s['s_search'], $term) . '</a>';
      }
      
      echo '</div>';
    }

    // CATEGORIES
    $categories = ModelEPS::newInstance()->findCategories($term, 6);
    
    if(is_array($categories) && count($categories) > 0) {
      echo '<div class="row categories">';
      echo '<div class="lead">' . __('Categories', 'epsilon') . '</div>';
      
      foreach($categories as $c) {
        $pngm_cat_url = $pngm_search_base;
        $pngm_cat_url['sCategory'] = $c['pk_i_id'];
        echo '<a class="option direct" href="' . osc_search_url($pngm_cat_url) . '" data-category="' . osc_esc_html($c['pk_i_id']) . '">' . eps_highlight_term(($c['s_name_parent'] <> '' ? $c['s_name_parent'] . ' > ' : '') . $c['s_name'], $term) . '</a>';
      }
      
      echo '</div>';
    }
    
    // COUNTRIES
    $countries = ModelEPS::newInstance()->findCountries($term, 6);
    
    if(is_array($countries) && count($countries) > 0) {
      echo '<div class="row countries locations">';
      echo '<div class="lead">' . __('Countries', 'epsilon') . '</div>';
      
      foreach($countries as $c) {
        echo '<a class="option direct" href="' . osc_search_url(array('page' => 'search', 'sCountry' => $c['fk_c_country_code'])) . '" data-country="' . osc_esc_html($c['fk_c_country_code']) . '">' . eps_highlight_term(osc_location_native_name_selector($c, 's_name'), $term) . '</a>';
      }
      
      echo '</div>';
    }
    
    // REGIONS
    $regions = ModelEPS::newInstance()->findRegions($term, 6);
    
    if(is_array($regions) && count($regions) > 0) {
      echo '<div class="row regions locations">';
      echo '<div class="lead">' . __('Regions', 'epsilon') . '</div>';
      
      foreach($regions as $r) {
        echo '<a class="option direct" href="' . osc_search_url(array('page' => 'search', 'sRegion' => $r['fk_i_region_id'])) . '" data-region="' . osc_esc_html($r['fk_i_region_id']) . '">' . eps_highlight_term(osc_location_native_name_selector($r, 's_name'), $term) . '</a>';
      }
      
      echo '</div>';
    }
    
    // CITIES — fetch more, then put main cities / capitals first.
    $cities = ModelEPS::newInstance()->findCities($term, 20);
    if (function_exists('pngm_sort_cities_main_first') && is_array($cities)) {
      $cities = pngm_sort_cities_main_first($cities, null, 's_name');
      $cities = array_slice($cities, 0, 8);
    }
    
    if(is_array($cities) && count($cities) > 0) {
      echo '<div class="row cities locations">';
      echo '<div class="lead">' . __('Cities', 'epsilon') . '</div>';
      
      foreach($cities as $c) {
        echo '<a class="option direct" href="' . osc_search_url(array('page' => 'search', 'sCity' => $c['fk_i_city_id'])) . '" data-city="' . osc_esc_html($c['fk_i_city_id']) . '">' . eps_highlight_term(osc_location_native_name_selector($c, 's_name_top') . ' > ' . osc_location_native_name_selector($c, 's_name'), $term) . '</a>';
      }
      
      echo '</div>';
    }
  }
  
  exit;
}


// FIND CLOSEST CITY BY LATITTUDE AND LONGITUDE
if(@$_GET['ajaxFindCity'] == 1) {
  $latitude = isset($_GET['latitude']) ? osc_esc_html(Params::getParam('latitude')) : NULL;
  $longitude = isset($_GET['longitude']) ? osc_esc_html(Params::getParam('longitude')) : NULL;
  
  if($latitude != NULL && $latitude != '' && $longitude != NULL && $longitude != '') {
    $city = ModelEPS::newInstance()->findClosestCity($latitude, $longitude);
    $city_lat = isset($city['d_coord_lat']) ? (float) $city['d_coord_lat'] : 0;
    $city_lon = isset($city['d_coord_long']) ? (float) $city['d_coord_long'] : 0;
    $too_far = isset($city['d_distance']) && (float) $city['d_distance'] > 800;

    if ((!isset($city['fk_i_city_id']) || !$city['fk_i_city_id'] || ($city_lat == 0 && $city_lon == 0) || $too_far)
        && function_exists('pngm_nearest_city_from_coords')
    ) {
      $fallback = pngm_nearest_city_from_coords($latitude, $longitude);
      if (is_array($fallback) && !empty($fallback['fk_i_city_id'])) {
        $city = $fallback;
      }
    }

    if(isset($city['fk_i_city_id']) && $city['fk_i_city_id']) {
      $location = json_encode(array(
        'success' => true,
        'message' => 'GEOLOCATION',
        's_location' => sprintf(__('Located in %s, %s, situated %dkm from your position', 'epsilon'), osc_location_native_name_selector($city, 's_city'), osc_location_native_name_selector($city, 's_region'), round($city['d_distance_precise'])),
        's_region' => $city['s_region'],
        's_region_native' => isset($city['s_region_native']) ? $city['s_region_native'] : '',
        's_city' => $city['s_city'],
        's_city_native' => isset($city['s_city_native']) ? $city['s_city_native'] : '',
        's_name' => $city['s_city'] . ($city['s_region'] <> '' ? ', ' . $city['s_region'] : ''),
        's_name_native' => isset($city['s_city_native']) ? $city['s_city_native'] : ''  . (@$city['s_region_native'] <> '' ? ', ' . $city['s_region_native'] : ''),
        'fk_i_city_id' => $city['fk_i_city_id'],
        'fk_i_region_id' => $city['fk_i_region_id'],
        'fk_c_country_code' => $city['fk_c_country_code'],
        's_slug' => @$city['s_slug'],
        'd_coord_lat' => number_format((float)$city['d_coord_lat'], 5),
        'd_coord_long' => number_format((float)$city['d_coord_long'], 5),
        'd_device_coord_lat' => number_format((float)$latitude, 5),
        'd_device_long' => number_format((float)$longitude, 5),
        'd_distance' => number_format((float)$city['d_distance'], 4),
        'd_distance_precise' => number_format((float)$city['d_distance_precise'], 2),
        'dt_date' => date('Y-m-d H:i:s')
      ));
    } else {
      $location = json_encode(array(
        'success' => false,
        'message' => __('No close city has been found', 'epsilon')
      ));
    }
  } else {
    $location = json_encode(array(
      'success' => false,
      'message' => __('Invalid latitude or longitude', 'epsilon')
    ));
  }
  
  echo $location;
  eps_location_to_cookies($location);
  if (function_exists('pngm_touch_recent_location')) {
    $decoded = json_decode($location, true);
    if (is_array($decoded) && !empty($decoded['success'])) {
      pngm_touch_recent_location($decoded);
    }
  }
  exit;
}


// GET LOCATIONS FOR LOCATION PICKER VIA AJAX
// Allow empty term so filters/modals can show the main-cities list on focus.
if (@$_GET['ajaxLoc'] == 1) {
  $term = trim((string) Params::getParam('term'));
  $term = trim(osc_esc_js($term));
  $type = trim(osc_esc_html(Params::getParam('dataType')));
  $max = 20;

  // Empty / very short term → main cities list (Port Moresby, Lae, Mt Hagen, …).
  if ($term === '' || (function_exists('mb_strlen') ? mb_strlen($term, 'UTF-8') : strlen($term)) < 1) {
    $cities = function_exists('pngm_get_popular_cities')
      ? pngm_get_popular_cities(12)
      : array();
    $output = '';
    if (is_array($cities) && count($cities) > 0) {
      $output .= '<div class="lead pngm-loc-ajax-lead">' . osc_esc_html(__('Main cities', 'epsilon')) . '</div>';
      foreach ($cities as $c) {
        $name = isset($c['s_name']) ? $c['s_name'] : '';
        $name_top = isset($c['s_name_top']) ? $c['s_name_top'] : '';
        if ($type == 'COOKIE') {
          $hash = rawurlencode(base64_encode(json_encode(array(
            'fk_i_city_id' => @$c['fk_i_city_id'],
            'fk_i_region_id' => @$c['fk_i_region_id'],
            'fk_c_country_code' => @$c['fk_c_country_code'],
            's_name' => $name,
            's_name_native' => @$c['s_name_native'],
            's_name_top' => $name_top,
            's_name_top_native' => @$c['s_name_top_native'],
            's_slug' => @$c['s_slug'],
            'd_coord_lat' => @$c['d_coord_lat'],
            'd_coord_long' => @$c['d_coord_long'],
          ))));
          $output .= '<a class="option city direct" href="' . eps_create_url(array('manualCookieLocation' => 1, 'hash' => $hash)) . '">';
        } else {
          $output .= '<div class="option city" data-country="' . osc_esc_html(@$c['fk_c_country_code']) . '" data-region="' . (int) @$c['fk_i_region_id'] . '" data-city="' . (int) @$c['fk_i_city_id'] . '">';
        }
        $output .= '<span>' . osc_esc_html($name) . '</span>';
        if (trim($name_top) !== '') {
          $output .= '<em>' . osc_esc_html($name_top) . '</em>';
        }
        $output .= ($type == 'COOKIE') ? '</a>' : '</div>';
      }
    }
    echo $output;
    exit;
  }

  // Contains match (not prefix-only) so "Moresby" / "Hagen" / "Mt" still find cities.
  $like = '%' . $term . '%';
  $like_esc = City::newInstance()->dao->escapeStr($like);

  if(osc_get_current_user_locations_native() == 1) {
    $sql = '
      (SELECT "country" as type, s_name as name, s_name_native as name_native, "" as name_top, "" as name_top_native, null as city_id, null as region_id, pk_c_code as country_code, s_slug, NULL as d_coord_lat, NULL as d_coord_long FROM ' . DB_TABLE_PREFIX . 't_country WHERE s_name like "' . $like_esc . '" OR s_name_native like "' . $like_esc . '")
      UNION ALL
      (SELECT "region" as type, r.s_name as name, r.s_name_native as name_native, c.s_name as name_top, c.s_name_native as name_top_native, null as city_id, r.pk_i_id  as region_id, r.fk_c_country_code as country_code, r.s_slug, NULL as d_coord_lat, NULL as d_coord_long FROM ' . DB_TABLE_PREFIX . 't_region r, ' . DB_TABLE_PREFIX . 't_country c WHERE r.fk_c_country_code = c.pk_c_code AND (r.s_name like "' . $like_esc . '" OR r.s_name_native like "' . $like_esc . '"))
      UNION ALL
      (SELECT "city" as type, c.s_name as name, c.s_name_native as name_native, r.s_name as name_top, r.s_name_native as name_top_native, c.pk_i_id as city_id, c.fk_i_region_id as region_id, c.fk_c_country_code as country_code, c.s_slug, d_coord_lat, d_coord_long FROM ' . DB_TABLE_PREFIX . 't_city c, ' . DB_TABLE_PREFIX . 't_region r WHERE (c.s_name like "' . $like_esc . '" OR c.s_name_native like "' . $like_esc . '") AND c.fk_i_region_id = r.pk_i_id limit ' . $max . ')
    ';
  } else {
    $sql = '
      (SELECT "country" as type, s_name as name, "" as name_top, null as city_id, null as region_id, pk_c_code as country_code, s_slug, NULL as d_coord_lat, NULL as d_coord_long FROM ' . DB_TABLE_PREFIX . 't_country WHERE s_name like "' . $like_esc . '")
      UNION ALL
      (SELECT "region" as type, r.s_name as name, c.s_name as name_top, null as city_id, r.pk_i_id  as region_id, r.fk_c_country_code as country_code, r.s_slug, NULL as d_coord_lat, NULL as d_coord_long FROM ' . DB_TABLE_PREFIX . 't_region r, ' . DB_TABLE_PREFIX . 't_country c WHERE r.fk_c_country_code = c.pk_c_code AND r.s_name like "' . $like_esc . '")
      UNION ALL
      (SELECT "city" as type, c.s_name as name, r.s_name as name_top, c.pk_i_id as city_id, c.fk_i_region_id as region_id, c.fk_c_country_code as country_code, c.s_slug, c.d_coord_lat, c.d_coord_long FROM ' . DB_TABLE_PREFIX . 't_city c, ' . DB_TABLE_PREFIX . 't_region r WHERE c.s_name like "' . $like_esc . '" AND c.fk_i_region_id = r.pk_i_id limit ' . $max . ')
    ';
  }

  $result = City::newInstance()->dao->query($sql);
  if(!$result) {
    $data = array();
  } else {
    $data = $result->result();
  }

  // Main cities / provincial capitals first within city matches.
  if (function_exists('pngm_sort_ajax_loc_results')) {
    $data = pngm_sort_ajax_loc_results($data);
  }

  // Always surface matching main cities first (even if DB order was wrong).
  if (function_exists('pngm_get_popular_cities') && $term !== '') {
    $term_key = function_exists('pngm_location_key') ? pngm_location_key($term) : strtolower($term);
    $aliases = array(
      'mt hagen' => 'mount hagen',
      'mt. hagen' => 'mount hagen',
      'pom' => 'port moresby',
      'moresby' => 'port moresby',
    );
    if (isset($aliases[$term_key])) {
      $term_key = $aliases[$term_key];
    }
    $popular = pngm_get_popular_cities(20);
    $extra = array();
    if (is_array($popular)) {
      foreach ($popular as $c) {
        $cname = isset($c['s_name']) ? $c['s_name'] : '';
        $ckey = function_exists('pngm_location_key') ? pngm_location_key($cname) : strtolower($cname);
        if ($term_key !== '' && (strpos($ckey, $term_key) !== false || strpos($term_key, $ckey) !== false
            || stripos($cname, $term) !== false)) {
          $extra[] = array(
            'type' => 'city',
            'name' => $cname,
            'name_native' => @$c['s_name_native'],
            'name_top' => @$c['s_name_top'],
            'name_top_native' => @$c['s_name_top_native'],
            'city_id' => @$c['fk_i_city_id'],
            'region_id' => @$c['fk_i_region_id'],
            'country_code' => @$c['fk_c_country_code'],
            's_slug' => @$c['s_slug'],
            'd_coord_lat' => @$c['d_coord_lat'],
            'd_coord_long' => @$c['d_coord_long'],
          );
        }
      }
    }
    if (count($extra) > 0) {
      $seen = array();
      $merged = array();
      foreach (array_merge($extra, $data) as $row) {
        $sid = ($row['type'] === 'city' ? 'c' . $row['city_id'] : $row['type'] . $row['name']);
        if (isset($seen[$sid])) {
          continue;
        }
        $seen[$sid] = true;
        $merged[] = $row;
      }
      $data = $merged;
    }
  }

  $output = '';
  if(is_array($data) && count($data) > 0) {
    foreach($data as $d) {
      if($d['type'] == 'country') {
        $d['name_top'] = osc_esc_html(__('All regions', 'epsilon'));
        $d['name_top_native'] = $d['name_top'];
      }

      if($type == 'COOKIE') {
        $hash = rawurlencode(base64_encode(json_encode(array('fk_i_city_id' => $d['city_id'], 'fk_i_region_id' => $d['region_id'], 'fk_c_country_code' => $d['country_code'], 's_name' => $d['name'], 's_name_native' => @$d['name_native'], 's_name_top' => $d['name_top'], 's_name_top_native' => @$d['name_top_native'], 's_slug' => @$d['s_slug'], 'd_coord_lat' => @$d['d_coord_lat'], 'd_coord_long' => @$d['d_coord_long']))));
        $output .= '<a class="option ' . $d['type'] . ' direct" href="' . eps_create_url(array('manualCookieLocation' => 1, 'hash' => $hash)) . '">';
      } else {
        $output .= '<div class="option ' . $d['type'] . '" data-country="' . $d['country_code'] . '" data-region="' . $d['region_id'] . '" data-city="' . $d['city_id'] . '">';
      }

      $output .= '<span>' . osc_location_native_name_selector($d, 'name') . '</span>';
      $output .= (trim(osc_location_native_name_selector($d, 'name_top')) != '' ? '<em>' . osc_location_native_name_selector($d, 'name_top') . '</em>' : '');

      if($type == 'COOKIE') {
        $output .= '</a>';
      } else {
        $output .= '</div>';
      }
    }
  }

  echo $output;
  exit;
}


// POPULAR CITIES for default-location modal — main PNG cities first.
if (@$_GET['ajaxPngmPopularCities'] == 1) {
  $cities = function_exists('pngm_get_popular_cities')
    ? pngm_get_popular_cities(7)
    : ModelEPS::newInstance()->getPopularCities(7, 0);

  $html = '';

  if (is_array($cities) && count($cities) > 0) {
    $html .= '<div class="lead">' . __('Main cities', 'epsilon') . '</div>';

    foreach ($cities as $c) {
      $hash = rawurlencode(base64_encode(json_encode(array(
        'fk_i_city_id' => $c['fk_i_city_id'],
        'fk_i_region_id' => $c['fk_i_region_id'],
        'fk_c_country_code' => $c['fk_c_country_code'],
        's_name' => $c['s_name'],
        's_name_native' => @$c['s_name_native'],
        's_name_top' => @$c['s_name_top'],
        's_name_top_native' => @$c['s_name_top_native'],
        'd_coord_lat' => @$c['d_coord_lat'],
        'd_coord_long' => @$c['d_coord_long'],
      ))));

      $cname = function_exists('pngm_city_only')
        ? pngm_city_only($c)
        : osc_location_native_name_selector($c, 's_name');
      if (!empty($c['s_name_top'])) {
        $cname .= ', ' . osc_location_native_name_selector($c, 's_name_top');
      }
      $ccount = isset($c['i_num_items']) ? (int) $c['i_num_items'] : 0;

      $html .= '<a href="' . eps_create_url(array('manualCookieLocation' => 1, 'hash' => $hash)) . '" class="location-elem pngm-loc-item">';
      $html .= '<i class="fas fa-map-marker-alt" aria-hidden="true"></i>';
      $html .= '<span class="pngm-loc-item-name">' . osc_esc_html($cname) . '</span>';
      if ($ccount > 0) {
        $html .= '<em class="pngm-loc-item-count">' . number_format($ccount) . ' '
          . ($ccount === 1 ? __('listing', 'epsilon') : __('listings', 'epsilon'))
          . '</em>';
      }
      $html .= '</a>';
    }
  }

  echo $html;
  exit;
}


// AJAX: set default location cookie (no page redirect / flash banner).
if (@$_GET['ajaxPngmSetLocation'] == 1) {
  header('Content-Type: application/json; charset=utf-8');

  $raw = Params::getParam('hash');
  $data = json_decode(base64_decode(rawurldecode($raw)), true);

  if (!is_array($data)) {
    echo json_encode(array(
      'success' => false,
      'message' => __('Invalid location', 'epsilon'),
    ));
    exit;
  }

  $name = isset($data['s_name']) ? (string) $data['s_name'] : '';
  $name_top = isset($data['s_name_top']) ? (string) $data['s_name_top'] : '';
  $label_parts = array_filter(array(
    osc_location_native_name_selector($data, 's_name'),
    osc_location_native_name_selector($data, 's_name_top'),
  ));
  $loc = implode(', ', $label_parts);
  if ($loc === '') {
    $loc = trim($name . ($name_top !== '' ? ', ' . $name_top : ''));
  }
  if ($loc === '') {
    echo json_encode(array(
      'success' => false,
      'message' => __('Invalid location', 'epsilon'),
    ));
    exit;
  }

  $short = function_exists('pngm_city_only') ? pngm_city_only(array('s_name' => $name)) : $name;
  if ($short === '') {
    $short = osc_location_native_name_selector($data, 's_name');
  }
  if ($short === '' || $short === null) {
    $short = $name !== '' ? $name : $loc;
  }

  // Keep city / province separate so Recent locations can render real picks.
  $city_only = $short;
  $province = $name_top;
  if ($province === '' && strpos($name, ',') !== false) {
    $parts = array_map('trim', explode(',', $name, 2));
    if (!empty($parts[0])) {
      $city_only = $parts[0];
    }
    if (!empty($parts[1])) {
      $province = $parts[1];
    }
  }

  $location_arr = array(
    'success' => true,
    'message' => 'MANUAL',
    's_location' => sprintf(__('Located in %s', 'epsilon'), $loc),
    's_name' => $city_only,
    's_name_top' => $province,
    's_name_native' => @$data['s_name_native'],
    's_name_top_native' => @$data['s_name_top_native'],
    's_region' => @$data['s_region'] !== null && @$data['s_region'] !== '' ? @$data['s_region'] : $province,
    's_city' => @$data['s_city'] !== null && @$data['s_city'] !== '' ? @$data['s_city'] : $city_only,
    'fk_i_city_id' => @$data['fk_i_city_id'],
    'fk_i_region_id' => @$data['fk_i_region_id'],
    'fk_c_country_code' => @$data['fk_c_country_code'],
    's_slug' => @$data['s_slug'],
    'd_coord_lat' => @$data['d_coord_lat'],
    'd_coord_long' => @$data['d_coord_long'],
    'dt_date' => date('Y-m-d H:i:s'),
  );

  $location = json_encode($location_arr);
  eps_location_to_cookies($location);
  if (function_exists('pngm_touch_recent_location')) {
    pngm_touch_recent_location($location_arr);
  }

  echo json_encode(array(
    'success' => true,
    'label' => $city_only,
    's_name' => $city_only,
    's_location' => $location_arr['s_location'],
    'message' => __('Location updated', 'epsilon'),
    'reload' => true,
  ));
  exit;
}


// AJAX: clear default location cookie (no page redirect / flash banner).
if (@$_GET['ajaxPngmClearLocation'] == 1) {
  header('Content-Type: application/json; charset=utf-8');
  eps_location_to_cookies('');
  echo json_encode(array(
    'success' => true,
    'cleared' => true,
    'label' => __('Location', 'epsilon'),
    's_location' => '',
    'message' => __('Location cleared', 'epsilon'),
    'reload' => true,
  ));
  exit;
}


// INCREASE CLICK COUNT ON PHONE NUMBER
if(@$_GET['ajaxPhoneClick'] == '1' && @$_GET['itemId'] > 0) {
  eps_increase_clicks(Params::getParam('itemId'), Params::getParam('itemUserId'));
  exit;
}


// GET ITEM FORMS
if(Params::getParam('ajaxItemForm') == 1) { 
  // Param type: comment, friend, contact
  osc_current_web_theme_path('item-send-friend.php');
  exit;
}

?>