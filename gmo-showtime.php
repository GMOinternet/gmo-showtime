<?php
/**
 * Plugin Name: GMO Showtime
 * Plugin URI:  https://digitalcube.jp/
 * Description: This is a awesome cool plugin.
 * Version:     0.1.0
 * Author:      Digitalcube Co,.Ltd
 * Author URI:  https://digitalcube.jp/
 * License:     GPLv2
 * Text Domain: gmoshowtime
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2013 Digitalcube Co,.Ltd (https://digitalcube.jp/)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */



define('GMOSHOWTIME_URL',  plugins_url('', __FILE__));
define('GMOSHOWTIME_PATH', dirname(__FILE__));

$gmoshowtime = new GMOShowtime();
$gmoshowtime->init();

class GMOShowtime {

private $version = '';
private $langs   = '';

function __construct()
{
    $data = get_file_data(
        __FILE__,
        array('ver' => 'Version', 'langs' => 'Domain Path')
    );
    $this->version = $data['ver'];
    $this->langs   = $data['langs'];
}

public function init()
{
    add_action('plugins_loaded', array($this, 'plugins_loaded'));
}

public function plugins_loaded()
{
    load_plugin_textdomain(
        'gmoshowtime',
        false,
        dirname(plugin_basename(__FILE__)).$this->langs
    );

    add_action('wp_enqueue_scripts', array($this, 'wp_enqueue_scripts'));
    add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    add_action('admin_menu', array($this, 'admin_menu'));
    add_action('admin_init', array($this, 'admin_init'));
}

public function admin_init()
{
    if (isset($_POST['gmoshowtime']) && $_POST['gmoshowtime']){
        if (check_admin_referer('gmoshowtime', 'gmoshowtime')){
        }
    }
}

public function admin_menu()
{
    add_options_page(
        __('GMO Showtime', 'gmoshowtime'),
        __('GMO Showtime', 'gmoshowtime'),
        'publish_posts',
        'gmoshowtime',
        array($this, 'options_page')
    );
}

public function options_page()
{
?>
<div id="gmoshowtime" class="wrap">
<form id="save-social" method="post" action="<?php echo esc_attr($_SERVER['REQUEST_URI']); ?>">
<?php wp_nonce_field('gmo_share_connection', 'gmo_share_connection'); ?>

</form>
</div><!-- #gmoshowtime -->
<?php
}

public function admin_enqueue_scripts()
{
    if (isset($_GET['page']) && $_GET['page'] === 'gmoshowtime') {
        wp_enqueue_style(
            'admin-gmoshowtime-style',
            plugins_url('css/admin-gmoshowtime.min.css', __FILE__),
            array(),
            $this->version,
            'all'
        );

        wp_enqueue_script(
            'admin-gmoshowtime-script',
            plugins_url('js/admin-gmoshowtime.min.js', __FILE__),
            array('jquery-ui-droppable', 'jquery-ui-sortable'),
            $this->version,
            true
        );
    }
}

public function wp_enqueue_scripts()
{
    wp_enqueue_style(
        'gmo-showtime-style',
        plugins_url('css/gmo-showtime.min.css', __FILE__),
        array(),
        $this->version,
        'all'
    );

    wp_enqueue_script(
        'gmo-showtime-script',
        plugins_url('js/gmo-showtime.min.js', __FILE__),
        array('jquery'),
        $this->version,
        true
    );
}

} // end TestPlugin

// EOF
