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

function showtime($atts = array()) {
    global $gmoshowtime;
    if (get_option('gmoshowtime-maintenance', 1)) {
        if (get_header_image()) {
            return sprintf(
                '<img src="%s" height="%d" width="%d" alt="" />',
                get_header_image(),
                get_custom_header()->height,
                get_custom_header()->width
            );
        } else {
            return;
        }
    }

    $page_types = $gmoshowtime->get_page_types();
    foreach (get_option('gmoshowtime-page-types', array_keys($page_types)) as $page_type) {
        if (isset($page_types[$page_type]) && is_array($page_types[$page_type]['callback'])) {
            foreach ($page_types[$page_type]['callback'] as $callback) {
                if (call_user_func($callback)) {
                    echo $gmoshowtime->get_slider_contents($atts);
                    break;
                }
            }
        }
    }
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
private $default_image_size = 'gmoshowtime-image';
private $default_transition = 'fade';

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
	add_image_size( 'gmoshowtime-image', 1200, 600, true);

    add_action('wp_enqueue_scripts', array($this, 'wp_enqueue_scripts'));
    add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    add_action('admin_menu', array($this, 'admin_menu'));
    add_action('admin_init', array($this, 'admin_init'));
    add_action('admin_print_footer_scripts', array($this, 'admin_print_footer_scripts'), 9999);
    add_filter('post_gallery', array($this, 'post_gallery'), 10, 2);

    add_shortcode('showtime', array($this, 'showtime'));
}

public function post_gallery($null, $atts)
{
    if (get_option('gmoshowtime-apply-gallery', 0)) {
        return $this->gallery($atts);
    } else {
        return;
    }
}

private function gallery($atts)
{
    $post = get_post();

    extract(shortcode_atts(array(
        'transition' => get_option('gmoshowtime-transition', $this->get_default_transition()),
        'show_title' => $this->get_default_show_title(),
        'size'       => get_option('gmoshowtime-image-size', $this->get_default_image_size()),
        'order'      => 'ASC',
        'orderby'    => 'menu_order ID',
        'id'         => $post ? $post->ID : 0,
        'columns'    => $this->get_default_columns(),
        'include'    => '',
        'exclude'    => '',
        'link'       => ''
    ), $atts, 'gallery'));

    $id = intval($id);
    if ('RAND' == $order) {
        $orderby = 'none';
    }

    if (!empty($include)) {
        $_attachments = get_posts(array(
            'include' => $include,
            'post_status' => 'inherit',
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'order' => $order,
            'orderby' => $orderby,
        ));

        $attachments = array();
        foreach ($_attachments as $key => $val) {
            $attachments[$val->ID] = $_attachments[$key];
        }
    } elseif (!empty($exclude)) {
        $attachments = get_children(array(
            'post_parent' => $id,
            'exclude' => $exclude,
            'post_status' => 'inherit',
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'order' => $order,
            'orderby' => $orderby
        ));
    } else {
        $attachments = get_children(array(
            'post_parent' => $id,
            'post_status' => 'inherit',
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'order' => $order,
            'orderby' => $orderby
        ));
    }

    if ( empty($attachments) )
        return '';

    $images = array();
    foreach ($attachments as $id => $attachment) {
        $image_url = wp_get_attachment_image_src($id, $size, false);
        if (!empty($link) && 'file' === $link) {
            $image_link = get_attachment_link($id, $size, false, false);
        } elseif (!empty($link) && 'none' === $link) {
            $image_link = wp_get_attachment_image_src($id, $size, false);
        } else {
            $image_link = get_attachment_link($id, $size, true, false);
        }
        $images[] = array(
            'link'    => $image_link,
            'image'   => $image_url[0],
            'title'   => wptexturize($attachment->post_excerpt),
            'content' => '',
        );
    }

    return $this->get_slider_contents(array(
        'columns'     => $columns,
        'images'     => $images,
        'transition' => $transition,
        'show_title' => $show_title,
        'image_size' => $size,
    ));
}

public function showtime($atts)
{
    if (get_option('gmoshowtime-maintenance', 1)) {
        if (get_header_image()) {
            return sprintf(
                '<img src="%s" height="%s" width="%s" alt="" />',
                get_header_image(),
                get_custom_header()->height,
                get_custom_header()->width
            );
        } else {
            return;
        }
    }

    return $this->get_slider_contents($atts);
}

public function get_slider_contents($atts = array())
{
    global $post;

    extract( shortcode_atts( array(
        'columns'     => $this->get_default_columns(),
        'transition' => get_option('gmoshowtime-transition', $this->get_default_transition()),
        'show_title' => $this->get_default_show_title(),
        'image_size' => get_option('gmoshowtime-image-size', $this->get_default_image_size()),
        'images'      => array(),
    ), $atts ) );

    if (!count($images)) {
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

        foreach ($posts as $post) {
            setup_postdata( $post );
            $thumb = get_the_post_thumbnail($post->ID, $image_size);
            $image = preg_replace("/.*src=[\"\'](.+?)[\"\'].*/", "$1", $thumb);;
            $images[] = array(
                'link'  => get_permalink(),
                'image' => $image,
                'title' => get_the_title(),
                'content' => get_the_excerpt()
            );
        }
        wp_reset_postdata();
    }

    $html = '';
    $html .= "\n<!-- Start GMO Showtime-->\n";
    $html .= sprintf(
        '<div class="showtime" data-columns="%d" data-transition="%s" data-show_title="%d">',
        $columns,
        $transition,
        $show_title
    );

    $template = $this->get_slide_template();
    foreach ($images as $img) {
        if (!$img['image']) {
            continue;
        }

        $slide = $template;
        $slide = str_replace(
            "%css_class%",
            esc_attr(get_option('gmoshowtime-css-class', $this->get_default_css_class())),
            $slide
        );
        $slide = str_replace("%title%", $img['title'], $slide);
        $slide = str_replace("%content%", esc_html($img['content']), $slide);
        $slide = str_replace("%link%", esc_url($img['link']), $slide);
        $slide = str_replace("%image%", esc_url($img['image']), $slide);
        $html .= $slide;
    }

    $html .= '</div>';
    $html .= "\n<!-- End GMO Showtime-->\n";

    return $html;
}

public function admin_init()
{
    if (isset($_POST['gmoshowtime']) && $_POST['gmoshowtime']){
        if (check_admin_referer('gmoshowtime', 'gmoshowtime')){
            if (isset($_POST['transition']) && in_array($_POST['transition'], $this->get_transitions())) {
                update_option('gmoshowtime-transition', $_POST['transition']);
            } else {
                update_option('gmoshowtime-transition', $this->get_default_transition());
            }
            if (isset($_POST['page-types']) && is_array($_POST['page-types'])) {
                update_option('gmoshowtime-page-types', $_POST['page-types']);
            } else {
                update_option('gmoshowtime-page-types', array());
            }
            if (isset($_POST['css-class']) && in_array($_POST['css-class'], array_keys($this->get_css_classes()))) {
                update_option('gmoshowtime-css-class', $_POST['css-class']);
            } else {
                update_option('gmoshowtime-css-class', $this->get_default_css_class());
            }
            if (isset($_POST['image-size'])
                    && in_array($_POST['image-size'], array_keys($this->list_image_sizes()))) {
                update_option('gmoshowtime-image-size', $_POST['image-size']);
            } else {
                update_option('gmoshowtime-image-size', $this->get_default_image_size());
            }
            if (isset($_POST['apply-gallery']) && $_POST['apply-gallery']) {
                update_option('gmoshowtime-apply-gallery', 1);
            } else {
                update_option('gmoshowtime-apply-gallery', 0);
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
    require_once(dirname(__FILE__).'/includes/admin.php');
}

public function admin_enqueue_scripts()
{
    if (isset($_GET['page']) && $_GET['page'] === 'gmoshowtime') {
	    wp_enqueue_style(
	        'genericons',
	        plugins_url('genericons/genericons.min.css', __FILE__),
	        array(),
	        $this->version,
	        'all'
	    );
        wp_enqueue_style(
            'admin-gmoshowtime-style',
            plugins_url('css/admin-gmo-showtime.min.css', __FILE__),
            array( 'genericons' ),
            $this->version,
            'all'
        );

        wp_enqueue_style(
            'gmoshowtime-style',
            plugins_url('css/gmo-showtime.min.css', __FILE__),
            array( 'genericons' ),
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

    transition_enabled();

$('input[name="columns"]').click(function(){
    if (parseInt($('input[name="columns"]:checked').val()) == 1) {
        transition_enabled();
    } else {
        transition_disabled();
    }
});

var base_url = '<?php echo plugins_url("", __FILE__); ?>';
$('.transitions .showtime-transition-preview').each(function(){
    for (var i=0; i<10; i++) {
        var img = 'orange.png';
        if (i % 2) {
            img = 'blue.png';
        }
        var n = i + 1;
        var html = '<div class="slide"><div class="slide-wrap"><img src="'+base_url+'/img/'+img+'" alt=""></div></div>';
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

public function wp_enqueue_scripts()
{
    wp_enqueue_style(
        'genericons',
        plugins_url('genericons/genericons.min.css', __FILE__),
        array(),
        $this->version,
        'all'
    );
    wp_enqueue_style(
        'gmo-showtime-style',
        plugins_url('css/gmo-showtime.min.css', __FILE__),
        array('genericons'),
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

public function get_page_types()
{
    return apply_filters("gmoshowtime_page_types", array(
        'home' => array(
            'caption'  => 'Home',
            'callback' => array('is_home', 'is_front_page'),
        ),
        'singular' => array(
            'caption'  => 'Posts and Pages',
            'callback' => array('is_singular'),
        ),
        'archive' => array(
            'caption'  => 'Archive',
            'callback' => array('is_archive'),
        ),
    ));
}

private function get_default_transition()
{
    return apply_filters('gmoshowtime_default_transition', $this->default_transition);
}

private function get_default_image_size()
{
    return apply_filters('gmoshowtime_default_image_size', $this->default_image_size);
}

private function get_slide_template()
{
    $template = <<<EOL
<!-- slide loop -->
<div class="slide">
    <div class="%css_class%">
        <div class="slide-image">
            <a href="%link%"><img src="%image%" alt="%title%"></a>
        </div>
        <div class="slide-text">
            <h2 class="slide-title">%title%</h2>
            <div class="slide-content">%content%</div>
        </div>
    </div>
</div>
EOL;

    return apply_filters("gmoshowtime_slide_template", $template);
}

private function get_css_classes()
{
    $css_classes = array(
        'top-left'         => __('Top-Left', 'gmoshowtime'),
        'top-right'        => __('Top-Right', 'gmoshowtime'),
        'bottom-left'      => __('Bottom-Left', 'gmoshowtime'),
        'bottom-right'     => __('Bottom-Right', 'gmoshowtime'),
        'left-photo-right' => __('Left', 'gmoshowtime'),
        'right-photo-left' => __('Right', 'gmoshowtime'),
    );

    return apply_filters('gmoshowtime-css-classes', $css_classes);
}

private function get_default_css_class()
{
    // see get_css_classes()
    return apply_filters("gmoshowtime_default_css_class", "top-left");
}

private function get_default_show_title()
{
    return apply_filters('gmoshowtime_default_show_title', 1);
}

private function get_default_columns()
{
    return apply_filters('get_default_columns', 1);
}

private function get_preview_contents()
{

    printf(
        '<div class="showtime" data-columns="%d" data-transition="%s" data-show_title="%d">',
        $this->get_default_columns(),
        get_option('gmoshowtime-transition', $this->get_default_transition()),
        $this->get_default_show_title()
    );

    $template = $this->get_slide_template();
    for ($i=0; $i<20; $i++) {
        if ($i % 2) {
            $img = plugins_url('img/blue.png', __FILE__);
        } else {
            $img = plugins_url('img/orange.png', __FILE__);
        }
        $n = $i + 1;
        $html = $template;
        $html = str_replace(
            "%css_class%",
            esc_attr(get_option('gmoshowtime-css-class', $this->get_default_css_class())),
            $html
        );
        $html = str_replace("%title%", 'Page '.$n, $html);
        $html = str_replace("%content%", 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', $html);
        $html = str_replace("%link%", '#', $html);
        $html = str_replace("%image%", $img, $html);
        echo $html;
    }

    echo '</div>';
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


} // end TestPlugin

// EOF
