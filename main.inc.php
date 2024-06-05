<?php
/*
Plugin Name: Quick Fav
Version: auto
Description: Quick action to add photo to favorites from thumbnails page
Plugin URI: http://piwigo.org/ext/extension_view.php?eid=
Author: plg
Author URI: http://le-gall.net/pierrick
*/

if (!defined('PHPWG_ROOT_PATH'))
{
  die('Hacking attempt!');
}

// +-----------------------------------------------------------------------+
// | Define plugin constants                                               |
// +-----------------------------------------------------------------------+

global $prefixeTable;

defined('QFAV_ID') or define('QFAV_ID', basename(dirname(__FILE__)));
define('QFAV_PATH' , PHPWG_PLUGINS_PATH.basename(dirname(__FILE__)).'/');
define('QFAV_FAVORITES_CATEGORY_TABLE',   $prefixeTable . 'favorites_category');
define('QFAV_VERSION', 'auto');

add_event_handler('loc_begin_index_thumbnails', 'qfav_loc_begin_index_thumbnails');
function qfav_loc_begin_index_thumbnails()
{
  global $template;

  $template->set_prefilter('index_thumbnails', 'qfav_loc_begin_index_thumbnails_prefilter');

  if (!is_a_guest())
  {
    $template->func_combine_css(array('path' => 'plugins/quick_fav/quick_fav.css'));
    $template->func_combine_script(array('id'=>'quickFav', 'path'=>'plugins/quick_fav/quick_fav.js', 'load'=>'footer'));
    $template->func_combine_script(array('id'=>'quickFavCat', 'path'=>'plugins/quick_fav/quick_cat_fav.js', 'load'=>'footer'));
    $template->assign('show_quick_fav', true);
  }
  else
  {
    $template->assign('show_quick_fav', false);
  }
}

function qfav_loc_begin_index_thumbnails_prefilter($content)
{
  $search = '<img class';
  
  $replace = '
{if $show_quick_fav}
            <div class="quickFav" data-id="{$thumbnail.id}">
                <i class="far fa-heart fa-fw addFav"{if $thumbnail.is_fav} style="display:none"{/if}></i>
                <i class="fas fa-heart fa-fw remFav"{if !$thumbnail.is_fav} style="display:none"{/if}></i>
                <i class="fas fa-cog fa-spin fa-fw loadFav" style="display:none"></i>
            </div>
{/if}
'.$search;
  
  $content = str_replace($search, $replace, $content);

  return $content;
}

add_event_handler('init', 'qfav_init');
function qfav_init()
{
  global $user;

  // Add check if user uses Bootstrap darkromm or else deactivate
  if ('bootstrap_darkroom' != $user['theme'])
  {
    global $page;
    $plugin_id = 'quick_fav';

    // Deactivate manually plugin from database
    $query = 'UPDATE '.PLUGINS_TABLE.' SET state=\'inactive\' WHERE id=\''.$plugin_id.'\'';
    pwg_query($query);

    $page['warnings'][] = l10n('You need to be using Bootstrap Darkroom by default to use Quick fav');

    return;
  }

  if (!empty($_GET['action']) && ($_GET['action'] == 'remove_all_from_favorites'))
  {
    $query = '
DELETE
  FROM '.QFAV_FAVORITES_CATEGORY_TABLE.'
  WHERE user_id = '.$user['id'].'
;';
    pwg_query($query);
  }
}

add_event_handler('loc_end_index_thumbnails', 'qfav_loc_end_index_thumbnails');
function qfav_loc_end_index_thumbnails($tpl_thumbnails_var, $pictures)
{
  global $page, $user;

  if (count($page['items']) == 0)
  {
    return $tpl_thumbnails_var;
  }

  $query = '
SELECT
    image_id
  FROM '.FAVORITES_TABLE.'
  WHERE user_id = '.$user['id'].'
    AND image_id IN ('.implode(',', $page['items']).')
;';

  $page_favorites = query2array($query, 'image_id');

  foreach ($tpl_thumbnails_var as $idx => $thumbnail)
  {
    $tpl_thumbnails_var[$idx]['is_fav'] = isset($page_favorites[ $thumbnail['id'] ]);
  }

  return $tpl_thumbnails_var;
}

add_event_handler('loc_begin_index_category_thumbnails', 'qfav_loc_begin_index_category_thumbnails');
function qfav_loc_begin_index_category_thumbnails()
{
  global $template;

  $template->set_prefilter('index_category_thumbnails', 'qfav_loc_begin_index_category_thumbnails_prefilter');

  if (!is_a_guest())
  {
    $template->func_combine_css(array('path' => 'plugins/quick_fav/quick_fav.css'));
    $template->func_combine_script(array('id'=>'quickFav', 'path'=>'plugins/quick_fav/quick_cat_fav.js', 'load'=>'footer'));
    $template->assign('show_quick_fav', true);
  }
  else
  {
    $template->assign('show_quick_fav', false);
  }
}

function qfav_loc_begin_index_category_thumbnails_prefilter($content)
{
  $search = '<img class="{if ';
  $search = '<img class';
  
  $replace = '
{if $show_quick_fav}
            <div class="quickFav" data-id="{$cat.ID}">
                <i class="far fa-heart fa-fw fav-status-not addFav"{if $cat.fav_status ne "not"} style="display:none"{/if} title="click to add this album and its photos to favorites"></i>
                <i class="fas fa-heart-broken fa-fw fav-status-partial addFav"{if $cat.fav_status ne "partial"} style="display:none"{/if} title="some photos are not in favorites, click to add them"></i>
                <i class="fas fa-heart fa-fw fav-status-full remFav"{if $cat.fav_status ne "full"} style="display:none"{/if} title="click to remove this album and its photos from favorites"></i>
                <i class="fas fa-cog fa-spin fa-fw loadFav" style="display:none"></i>
            </div>
{/if}
'.$search;
  
  $content = str_replace($search, $replace, $content);

  return $content;
}

add_event_handler('ws_add_methods', 'qfav_ws_add_methods');
function qfav_ws_add_methods($arr)
{
  global $conf;

  $service = &$arr[0];

  $service->addMethod(
    'pwg.users.favorites.addAlbum',
    'ws_users_favorites_addAlbum',
    array(
        'category_id' =>  array('type'=>WS_TYPE_ID),
    ),
    'Add all photos from an album to user favorites'
  );

  $service->addMethod(
    'pwg.users.favorites.removeAlbum',
    'ws_users_favorites_removeAlbum',
    array(
        'category_id' =>  array('type'=>WS_TYPE_ID),
    ),
    'Remove all photos from an album from user favorites'
  );

  $service->addMethod(
    'pwg.users.favorites.checkPartialAlbums',
    'ws_users_favorites_checkPartialAlbums',
    array(
    ),
    'Checks if any favorite album is only partially added'
  );
}

function ws_users_favorites_addAlbum($params, &$service)
{
  global $user;

  // get requested album + its sub-albums and remove forbidden albums
  $cat_ids = array_diff(get_subcat_ids(array($params['category_id'])), explode(',', $user['forbidden_categories']));
  if (count($cat_ids) == 0)
  {
    return new PwgError(403, 'Invalid category_id');;
  }

  qfav_add_to_favorites_from_categories($cat_ids);

  // mark the album as favorites
  $inserts = array();
  foreach ($cat_ids as $cat_id)
  {
    $inserts[] = array(
      'user_id' => $user['id'],
      'category_id' => $cat_id,
    );
  }

  if (count($inserts) > 0)
  {
    mass_inserts(
      QFAV_FAVORITES_CATEGORY_TABLE,
      array_keys($inserts[0]),
      $inserts,
      array('ignore' => true)
    );
  }

  return;
}

function qfav_add_to_favorites_from_categories($cat_ids)
{
  global $user;

  $query = '
SELECT
    DISTINCT(image_id),
    \''.$user['id'].'\' AS user_id
  FROM '.IMAGE_CATEGORY_TABLE.'
  WHERE category_id IN ('.join(',', $cat_ids).')
';
  $query.= get_sql_condition_FandF(
    array(
      'forbidden_categories' => 'category_id',
      'visible_images' => 'image_id',
    ),
    'AND'
  );
  $query.= '
;';
  $inserts = query2array($query);

  if (count($inserts) > 0)
  {
    mass_inserts(
      FAVORITES_TABLE,
      array_keys($inserts[0]),
      $inserts,
      array('ignore' => true)
    );
  }
}

function ws_users_favorites_removeAlbum($params, &$service)
{
  global $user;

  $cat_ids = get_subcat_ids(array($params['category_id']));
  if (count($cat_ids) == 0)
  {
    return new PwgError(403, 'Invalid category_id');;
  }

  $query = '
SELECT
    image_id
  FROM '.IMAGE_CATEGORY_TABLE.'
  WHERE category_id IN ('.join(',', $cat_ids).')
;';
  $image_ids = query2array($query, null, 'image_id');

  if (count($image_ids) > 0)
  {
    $query = '
DELETE
  FROM '.FAVORITES_TABLE.'
  WHERE user_id = '.$user['id'].'
  AND image_id IN ('.implode(',', $image_ids).')
;';
    pwg_query($query);
  }

  if (count($cat_ids) > 0)
  {
    $query = '
DELETE
  FROM '.QFAV_FAVORITES_CATEGORY_TABLE.'
  WHERE user_id = '.$user['id'].'
  AND category_id IN ('.implode(',', $cat_ids).')
;';
    pwg_query($query);
  }

  return;
}

add_event_handler('loc_end_index_category_thumbnails', 'qfav_loc_end_index_category_thumbnails');
function qfav_loc_end_index_category_thumbnails($tpl_thumbnails_var_selection)
{
  global $user;

  $query = '
SELECT
    category_id,
    1 as fake_column
  FROM '.QFAV_FAVORITES_CATEGORY_TABLE.'
  WHERE user_id = '.$user['id'].'
;';
  $favorites_albums = query2array($query, 'category_id', 'fake_column');

  $query = '
SELECT
    image_id
  FROM '.FAVORITES_TABLE.'
  WHERE user_id = '.$user['id'].'
;';
  $favorites_photos = query2array($query, null, 'image_id');

  foreach ($tpl_thumbnails_var_selection as $idx => $cat)
  {
    $fav_status = 'not';

    // is the album "partially in favorites" ?
    if (isset($favorites_albums[ $cat['id'] ]))
    {
      $cat_ids = get_subcat_ids(array($cat['id']));

      $query = '
SELECT
    id
  FROM '.IMAGE_CATEGORY_TABLE.'
    JOIN '.IMAGES_TABLE.' ON id = image_id
  WHERE category_id IN ('.implode(',', $cat_ids).')
  '.get_sql_condition_FandF(
      array(
        'forbidden_categories' => 'category_id',
        'visible_images' => 'image_id'
        ),
      'AND'
      ).'
;';
      $cat_image_ids = query2array($query, null, 'id');
      // echo '<pre>'; print_r($cat_image_ids); echo '</pre>';
      if (count(array_diff($cat_image_ids, $favorites_photos)) > 0)
      {
        $fav_status = 'partial';
      }
      else
      {
        $fav_status = 'full';
      }
    }

    $tpl_thumbnails_var_selection[$idx]['fav_status'] = $fav_status;
  }

  return $tpl_thumbnails_var_selection;
}

add_event_handler('loc_end_section_init', 'qfav_loc_end_section_init');
function qfav_loc_end_section_init()
{
  global $template, $page, $user;

  if ('favorites' != $page['section'])
  {
    return;
  }

  if (isset($_GET['action']) and 'qfav_update_favorites' == $_GET['action'])
  {
    $query = '
  SELECT
      category_id
    FROM '.QFAV_FAVORITES_CATEGORY_TABLE.'
    WHERE user_id = '.$user['id'].'
  ;';
    $favorites_albums = query2array($query, null, 'category_id');

    if (count($favorites_albums) == 0)
    {
      return;
    }

    $cat_ids = get_subcat_ids($favorites_albums);

    qfav_add_to_favorites_from_categories($cat_ids);
    redirect(make_index_url( array('section'=>'favorites') ));
  }

  $show_partial_albums_warning = false;
  if (qfav_are_favorite_albums_partially_added())
  {
    $show_partial_albums_warning = true;
  }

  $update_fav = add_url_params(
    make_index_url( array('section'=>'favorites') ),
    array('action'=>'qfav_update_favorites')
  );

  $message = '<span class="qfav-partial-albums"'.($show_partial_albums_warning ? '' : ' style="display:none"').'>';
  $message.= 'Some photos in your favorite albums are not in your favorite photos.';
  $message.= ' <a href="'.$update_fav.'">Click here to add them!</a>';
  $message.= '</span>';

  $template->assign('CONTENT_DESCRIPTION', $message);
}

function ws_users_favorites_checkPartialAlbums($params, &$service)
{
  if (qfav_are_favorite_albums_partially_added())
  {
    return true;
  }

  return false;
}

function qfav_are_favorite_albums_partially_added()
{
  global $user;

  $query = '
SELECT
    category_id
  FROM '.QFAV_FAVORITES_CATEGORY_TABLE.'
  WHERE user_id = '.$user['id'].'
;';
  $favorites_albums = query2array($query, null, 'category_id');

  if (count($favorites_albums) == 0)
  {
    return false;
  }

  $cat_ids = get_subcat_ids($favorites_albums);

  $query = '
SELECT
    id
  FROM '.IMAGE_CATEGORY_TABLE.'
    JOIN '.IMAGES_TABLE.' ON id = image_id
  WHERE category_id IN ('.implode(',', $cat_ids).')
  '.get_sql_condition_FandF(
      array(
        'forbidden_categories' => 'category_id',
        'visible_images' => 'image_id'
        ),
      'AND'
      ).'
;';
  $cat_image_ids = query2array($query, null, 'id');

  $query = '
SELECT
    image_id
  FROM '.FAVORITES_TABLE.'
  WHERE user_id = '.$user['id'].'
;';
  $favorites_photos = query2array($query, null, 'image_id');

  if (count(array_diff($cat_image_ids, $favorites_photos)) > 0)
  {
    return true;
  }

  return false;
}
?>
