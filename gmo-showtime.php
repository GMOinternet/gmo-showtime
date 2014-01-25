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

require_once(dirname(__FILE__).'/includes/add_meta_box.php');

define('GMOSHOWTIME_URL',  plugins_url('', __FILE__));
define('GMOSHOWTIME_PATH', dirname(__FILE__));

$gmoshowtime = new GMOShowtime();
$gmoshowtime->init();

function showtime() {
    global $gmoshowtime;
    echo $gmoshowtime->get_slider_contents();
}

class GMOShowtime {

private $version = '';
private $langs   = '';
private $transitions = array(
    'fade',
    'backSlide',
    'goDown',
    'fadeUp'
);

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
    add_action('admin_print_footer_scripts', array($this, 'admin_print_footer_scripts'), 9999);
    add_action('wp_footer', array($this, 'wp_footer'), 9999);

    add_shortcode('showtime', array($this, 'get_slider_contents'));
}

public function get_slider_contents()
{
    if (get_option('gmoshowtime-maintenance', 1)) {
        return sprintf(
            '<img src="%s" height="%s" width="%s" alt="" />',
            get_header_image(),
            get_custom_header()->height,
            get_custom_header()->width
        );
    }

    $args = array(
        "post_type"             => "any",
        "nopaging"              => 0,
        "posts_per_page"        => 10,
        "post_status"           => 'publish',
        "meta_key"              => '_featured',
        "orderby"               => 'meta_value_num',
        "order"                 => 'DESC',
        "ignore_sticky_posts"    => 1,
    );
    $posts = get_posts($args);

    $html = '';
    $html .= "\n<!-- Start GMO Showtime-->\n";
    $html .= '<div class="gmo-showtime">'."\n";

    foreach ($posts as $p) {
        $html .= '<div class="slide slide-'.esc_attr($p->ID).'">';
        $html .= '<div class="slide-wrap">';
        $url    = get_permalink($p->ID);
        $thumb = get_the_post_thumbnail($p->ID, get_option('gmoshowtime-image-size', 'full'));
        $image = preg_replace("/.*src=[\"\'](.+?)[\"\'].*/", "$1", $thumb);;
        $title  = get_the_title($p->ID);
        $html .= '<h2>'.$title.'</h2>';
        $html .= sprintf(
            '<a href="%s"><img src="%s" alt="%s"></a>',
            $url,
            $image,
            $title
        );
        $html .= '</div>'."\n";
        $html .= '</div>'."\n";
    }

    $html .= '</div>';
    $html .= "\n<!-- End GMO Showtime-->\n";

    return $html;
}

public function wp_footer()
{
    if (!get_option('gmoshowtime-maintenance', 1)) {
        $this->load_scripts();
    }
}

public function admin_init()
{
    if (isset($_POST['gmoshowtime']) && $_POST['gmoshowtime']){
        if (check_admin_referer('gmoshowtime', 'gmoshowtime')){
            if (isset($_POST['slide-type']) && intval($_POST['slide-type'])) {
                update_option('gmoshowtime-slide-type', $_POST['slide-type']);
            } else {
                update_option('gmoshowtime-slide-type', 1);
            }
            if (isset($_POST['transition']) && in_array($_POST['transition'], $this->get_transitions())) {
                update_option('gmoshowtime-transition', $_POST['transition']);
            } else {
                update_option('gmoshowtime-transition', 'fade');
            }
            if (isset($_POST['show-title']) && $_POST['show-title']) {
                update_option('gmoshowtime-show-title', 1);
            } else {
                update_option('gmoshowtime-show-title', 0);
            }
            if (isset($_POST['image-size'])
                    && in_array($_POST['image-size'], array_keys($this->list_image_sizes()))) {
                update_option('gmoshowtime-image-size', $_POST['image-size']);
            } else {
                update_option('gmoshowtime-image-size', 'full');
            }
            if (isset($_POST['maintenance']) && intval($_POST['maintenance'])) {
                update_option('gmoshowtime-maintenance', 1);
            } else {
                update_option('gmoshowtime-maintenance', 0);
            }
            wp_redirect('options-general.php?page=gmoshowtime');
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

<h2>GMO Showtime</h2>

<p class="helplink"><a href="#setup-help"><?php _e('How to Setup', 'gmoshowtime'); ?></a></p>

<div id="alpha">

<form id="save-social" method="post" action="<?php echo esc_attr($_SERVER['REQUEST_URI']); ?>" style="margin-bottom:3em;">
<?php wp_nonce_field('gmoshowtime', 'gmoshowtime'); ?>

<h3 style="margin-top: 0;"><?php _e('Preview', 'gmoshowtime'); ?></h3>

<div id="mainpreview">
<?php $this->get_preview_contents(); ?>
</div>

<br clear="all">

<h3 style="margin-top: 2em;"><?php _e('Slider Settings', 'gmoshowtime'); ?></h3>

<div class="slide-types">
    <div class="type">
        <div class="boxes">
            <img src="<?php echo plugins_url('', __FILE__); ?>/img/single.png" alt="" />
        </div>
        <h4><label>
            <?php if (intval(get_option('gmoshowtime-slide-type', 1)) === 1): ?>
            <input type="radio" name="slide-type" value="1" checked />
            <?php else: ?>
            <input type="radio" name="slide-type" value="1" />
            <?php endif; ?>
            One Slide
        </label></h4>
    </div>
    <div class="type">
        <div class="boxes">
            <img src="<?php echo plugins_url('', __FILE__); ?>/img/images.png" alt="" />
        </div>
        <h4><label>
            <?php if (intval(get_option('gmoshowtime-slide-type', 1)) === 2): ?>
            <input type="radio" name="slide-type" value="2" checked />
            <?php else: ?>
            <input type="radio" name="slide-type" value="2" />
            <?php endif; ?>
            Three Slides
        </label></h4>
    </div>
    <div class="type">
        <div class="boxes">
            <img src="<?php echo plugins_url('', __FILE__); ?>/img/many.png" alt="" />
        </div>
        <h4><label>
            <?php if (intval(get_option('gmoshowtime-slide-type', 1)) === 3): ?>
            <input type="radio" name="slide-type" value="3" checked />
            <?php else: ?>
            <input type="radio" name="slide-type" value="3" />
            <?php endif; ?>
            Five Slides
        </label></h4>
    </div>
</div>



<div id="transitions-settings">

<div id="transitions">
<?php

foreach ($this->get_transitions() as $tran) {
?>
<div class="transitions">
    <div class="gmo-showtime" data-transition="<?php echo $tran; ?>">
    </div>
    <h4><label>
        <?php if (get_option('gmoshowtime-transition', 'fade') === $tran): ?>
        <input type="radio" name="transition" value="<?php echo $tran; ?>" checked />
        <?php else: ?>
        <input type="radio" name="transition" value="<?php echo $tran; ?>" />
        <?php endif; ?>
        <?php echo $tran; ?>
    </label></h4>
</div>
<?php
}
?>
</div>

</div><!-- #transitions-settings -->

<h3><?php _e('Gneral Settings', 'gmoshowtime'); ?></h3>

<table class="form-table">
    <tr>
        <th scope="row">Title</th>
        <td>
            <label>
                <?php if (intval(get_option('gmoshowtime-show-title', 1)) === 1): ?>
                    <input type="checkbox" name="show-title" value="1" checked /> <?php _e('Show Title with image.', 'gmoshowtime'); ?>
                <?php else: ?>
                    <input type="checkbox" name="show-title" value="1" /> <?php _e('Show Title with image.', 'gmoshowtime'); ?>
                <?php endif; ?>
            </label>
        </td>
    </tr>
    <tr>
        <th scope="row">Image Size</th>
        <td>
            <select name="image-size">
                <option value="full">Full-Size</option>
<?php
    $sizes = $this->list_image_sizes();
    $options = array();
    foreach ($sizes as $size => $atts) {
        if (get_option('gmoshowtime-image-size', 'full') === $size) {
            $selected = 'selected';
        } else {
            $selected = '';
        }
        $options[] = sprintf(
            '<option value="%1$s" %4$s>%1$s (%2$dpx &times; %3$dpx)</option>',
            $size,
            $atts[0],
            $atts[1],
            $selected
        );
    }

    echo join("\n", $options);
?>
            </select>
        </td>
    </tr>
    <tr>
        <th scope="row">Maintenance Mode</th>
        <td>
            <label>
                <?php if (intval(get_option('gmoshowtime-maintenance', 1)) === 1): ?>
                    <input type="checkbox" name="maintenance" value="1" checked />
                <?php else: ?>
                    <input type="checkbox" name="maintenance" value="1" />
                <?php endif; ?>
                <a href="<?php echo admin_url('themes.php?page=custom-header'); ?>"><?php _e('Show custom header image instead.', 'gmoshowtime'); ?></a>
            </label>
        </td>
    </tr>
</table>


<p style="margin-top: 3em;"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e("Save Changes", "gmoshowtime"); ?>"></p>
</form>

</div><!-- #alpha -->
<div id="beta">

<h3 id="setup-help" style="margin-top: 0;"><?php _e('How to Setup', 'gmoshowtime'); ?></h3>

<h4>1. <?php _e('Place the code in your theme like below.', 'gmoshowtime'); ?></h4>

<pre id="sample-code" readonly>&lt;?php if ( function_exists( &#039;showtime&#039; ) ): ?&gt;
&lt;?php showtime(); ?&gt;
&lt;?php endif; ?&gt;</pre>

<h4>2. <?php _e('Select `Featured Image` and check `Showtime` in your posts or pages admin.', 'gmoshowtime'); ?></h4>

<p><img src="<?php echo plugins_url('', __FILE__); ?>/img/help1.png" alt=""></p>

</div><!-- #beta -->

<br clear="all" />
<p style="text-align: right;">Carousel Powered by <a href="http://owlgraphic.com/owlcarousel/">Owl Carousel</a></p>

</div><!-- #gmoshowtime -->
<?php
}

public function admin_enqueue_scripts()
{
    if (isset($_GET['page']) && $_GET['page'] === 'gmoshowtime') {
        wp_enqueue_style(
            'admin-gmoshowtime-style',
            plugins_url('css/admin-gmo-showtime.min.css', __FILE__),
            array(),
            $this->version,
            'all'
        );

        wp_enqueue_style(
            'gmoshowtime-style',
            plugins_url('css/gmo-showtime.min.css', __FILE__),
            array(),
            $this->version,
            'all'
        );

        wp_enqueue_script(
            'admin-gmoshowtime-script',
            plugins_url('js/admin-gmo-showtime.min.js', __FILE__),
            array('jquery'),
            $this->version,
            true
        );
    }
}

public function admin_print_footer_scripts()
{
    $this->load_scripts('#mainpreview .gmo-showtime');

    if (is_admin()):
?>
<script type="text/javascript">
(function($){

function transition_enabled() {
    $('#transitions-settings').animate({'opacity': 1});
    $('#transitions-settings input').prop('disabled', false);
}

function transition_disabled() {
    $('#transitions-settings').animate({'opacity': 0.2});
    $('#transitions-settings input').prop('disabled', true);
}

if (parseInt($('input[name="slide-type"]:checked').val()) == 1) {
    transition_enabled();
} else {
    transition_disabled();
}

$('input[name="slide-type"]').click(function(){
    if (parseInt($('input[name="slide-type"]:checked').val()) == 1) {
        transition_enabled();
    } else {
        transition_disabled();
    }
});

var base_url = '<?php echo plugins_url("", __FILE__); ?>';
$('.transitions .gmo-showtime').each(function(){
    for (var i=0; i<10; i++) {
        var img = 'orange.png';
        if (i % 2) {
            img = 'blue.png';
        }
        var n = i + 1;
        var html = '<div class="slide"><div class="slide-wrap"><h2>Page '+n+'</h2><img src="'+base_url+'/img/'+img+'" alt=""></div></div>';
        $(this).append(html);
    }

    var owl2 = $(this);
    owl2.owlCarousel({
        pagination: false,
        itemsScaleUp: true,
        autoPlay: 2000,
        navigation : false,
        singleItem : true,
        transitionStyle : $(this).attr('data-transition')
    });
});
})(jQuery);
</script>
<?php
    endif;
}

private function get_preview_contents()
{

    echo '<div class="gmo-showtime">';

    for ($i=0; $i<20; $i++) {
        if ($i % 2) {
            $img = plugins_url('img/blue.png', __FILE__);
        } else {
            $img = plugins_url('img/orange.png', __FILE__);
        }
        $n = $i + 1;
        printf(
            '<div class="slide"><div class="slide-wrap"><h2>%s</h2><img src="%s" alt=""></div></div>',
            'Page '.$n,
            $img
        );
    }

    echo '</div>';
}

private function load_scripts($class = '.gmo-showtime')
{
    if (!$class) {
        $class = '.gmo-showtime';
    }
?>
<script type="text/javascript">
(function($){
var showtime = $("<?php echo $class; ?>");
showtime.owlCarousel({
<?php if (intval(get_option('gmoshowtime-slide-type', 1)) === 1): ?>
    transitionStyle : '<?php echo get_option('gmoshowtime-transition', 'fade'); ?>',
    singleItem : true,
<?php elseif (intval(get_option('gmoshowtime-slide-type', 1)) === 2): ?>
    items : 3,
    itemsTablet: [600, 2],
    itemsMobile : false,
<?php elseif (intval(get_option('gmoshowtime-slide-type', 1)) === 3): ?>
    items : 5,
    itemsDesktopSmall : [900, 5],
    itemsTablet: [600, 2],
    itemsMobile : false,
<?php endif; ?>
    itemsScaleUp: true,
    autoPlay: 3000,
    navigation : false
});

<?php if (intval(get_option('gmoshowtime-show-title', 1)) === 0): ?>
$('.gmo-showtime h2').hide();
<?php endif; ?>
})(jQuery);
</script>
<?php
}

private function get_transitions()
{
    return apply_filters(
        'gmoshowtime_transtions',
        $this->transitions
    );
}

private function list_image_sizes()
{
    global $_wp_additional_image_sizes;
    $sizes = array();
    foreach (get_intermediate_image_sizes() as $s) {
        $sizes[$s] = array(0, 0);
        if (in_array($s, array('thumbnail', 'medium', 'large'))) {
            $sizes[$s][0] = get_option($s . '_size_w');
            $sizes[$s][1] = get_option($s . '_size_h');
        } else {
            if (isset($_wp_additional_image_sizes) && isset($_wp_additional_image_sizes[$s])) {
                $sizes[ $s ] = array(
                    $_wp_additional_image_sizes[$s]['width'],
                    $_wp_additional_image_sizes[$s]['height'],
                );
            }
        }
    }

    return $sizes;
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
