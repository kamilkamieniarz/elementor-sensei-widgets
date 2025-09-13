<?php
if ( ! defined('ABSPATH') ) exit;

class NME_Video_Popup extends \Elementor\Widget_Base {

    public function get_name()       { return 'nme_video_popup'; }
    public function get_title()      { return 'NME – Video Popup'; }
    public function get_icon()       { return 'eicon-play'; }
    public function get_categories() { return [ 'elementor-sensei-widgets' ]; }

    protected function register_controls() {
        $this->start_controls_section( 'sec_video', [ 'label' => 'Video Popup' ] );

        $this->add_control( 'style', [
            'label'   => 'Styl',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => '6',
            'options' => [
                '1' => 'Styl 1',
                '2' => 'Styl 2',
                '3' => 'Styl 3',
                '4' => 'Styl 4',
                '5' => 'Styl 5',
                '6' => 'Styl 6 (zajawka kursu z fallbackiem)',
            ],
        ] );

        $this->add_control( 'thumb', [
            'label'   => 'Miniatura (opcjonalnie)',
            'type'    => \Elementor\Controls_Manager::MEDIA,
            'default' => [],
        ] );

        $this->add_control( 'link', [
            'label'       => 'Link do wideo (URL)',
            'type'        => \Elementor\Controls_Manager::URL,
            'label_block' => true,
            'description' => 'YouTube/Vimeo/Dacast URL. Jeśli zostawisz puste i styl=6, użyje pola „Course Video”.',
        ] );

        $this->add_control( 'embed', [
            'label'       => 'Embed (iframe)',
            'type'        => \Elementor\Controls_Manager::TEXTAREA,
            'rows'        => 5,
            'placeholder' => '<iframe src="..."></iframe>',
            'description' => 'Jeśli podasz embed, to on będzie użyty w popupie zamiast URL.',
        ] );

        $this->add_control( 'title', [
            'label'       => 'Tytuł (overlay)',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
        ] );

        $this->add_control( 'play_icon', [
            'label'   => 'Ikona Play',
            'type'    => \Elementor\Controls_Manager::ICONS,
            'default' => [
                'value'   => 'fas fa-play',
                'library' => 'fa-solid',
            ],
        ] );

        $this->add_control( 'aspect', [
            'label'       => 'Proporcje',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '16/9',
            'description' => 'Np. 16/9 lub 1.78',
        ] );

        $this->add_control( 'radius', [
            'label'      => 'Zaokrąglenie',
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => [ 'px', '%' ],
            'range'      => [
                'px' => [ 'min' => 0, 'max' => 64 ],
                '%'  => [ 'min' => 0, 'max' => 50 ],
            ],
            'default'    => [ 'size' => 12, 'unit' => 'px' ],
            'selectors'  => [
                '{{WRAPPER}} .nme-vp-thumb' => 'border-radius: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .nme-vp-modal .nme-vp-iframe-wrap' => 'border-radius: {{SIZE}}{{UNIT}};',
            ],
        ] );

        $this->add_control( 'overlay_bg', [
            'label'     => 'Overlay (na miniaturze)',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => 'rgba(0,0,0,.35)',
            'selectors' => [
                '{{WRAPPER}} .nme-vp-thumb .nme-vp-overlay' => 'background: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'icon_color', [
            'label'     => 'Kolor ikony',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .nme-vp-play svg' => 'fill: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'icon_hover_color', [
            'label'     => 'Kolor ikony (hover)',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .nme-vp-thumb:hover .nme-vp-play svg' => 'fill: {{VALUE}};',
            ],
        ] );

        $this->end_controls_section();

        $this->start_controls_section( 'sec_modal', [ 'label' => 'Popup', 'tab' => \Elementor\Controls_Manager::TAB_STYLE ] );

        $this->add_control( 'modal_bg', [
            'label'     => 'Tło popupu',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => 'rgba(0,0,0,.85)',
            'selectors' => [
                '{{WRAPPER}} .nme-vp-modal' => 'background: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'close_color', [
            'label'     => 'Kolor przycisku zamknięcia',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#fff',
            'selectors' => [
                '{{WRAPPER}} .nme-vp-close' => 'color: {{VALUE}};',
            ],
        ] );

        $this->end_controls_section();
    }

    protected function render() {
    
	    $is_edit_mode = class_exists('\Elementor\Plugin') && \Elementor\Plugin::$instance->editor->is_edit_mode();
	    if ( is_user_logged_in() && ! $is_edit_mode ) {
	        return;
	    }
    
        $s       = $this->get_settings_for_display();
        $style   = (string) ( $s['style'] ?? '6' );
        $title   = trim( (string) ( $s['title'] ?? '' ) );
        $aspectS = trim( (string) ( $s['aspect'] ?? '16/9' ) );

        $ratio = 1.7777778;
        if ( strpos( $aspectS, '/' ) !== false ) {
            [$w,$h] = array_map( 'floatval', explode( '/', $aspectS, 2 ) );
            if ( $w > 0 && $h > 0 ) $ratio = $w / $h;
        } else {
            $r = floatval( $aspectS );
            if ( $r > 0.4 ) $ratio = $r;
        }
        $ratio_css = number_format( $ratio, 3, '.', '' );

        $thumb_url = '';
        if ( ! empty( $s['thumb']['url'] ) ) {
            $thumb_url = $s['thumb']['url'];
        }

        $link_url = '';
        if ( ! empty( $s['link']['url'] ) ) {
            $link_url = $s['link']['url'];
        }

        $embed_raw = trim( (string) ( $s['embed'] ?? '' ) );
        $uid = 'nmevp-' . wp_generate_uuid4();

        if ( $style === '6' && empty($link_url) && empty($embed_raw) ) {
            $course_id = 0;
            $post = get_post();
            if ( $post ) {
                if ( $post->post_type === 'course' ) {
                    $course_id = (int) $post->ID;
                } elseif ( $post->post_type === 'lesson' ) {
                    $course_id = (int) get_post_meta( $post->ID, '_lesson_course', true );
                }
            }
            if ( $course_id ) {
                if ( empty( $thumb_url ) ) {
                    $thumb_url = get_the_post_thumbnail_url( $course_id, 'large' ) ?: '';
                }
                $course_embed = get_post_meta( $course_id, '_course_video_embed', true );
                if ( $course_embed ) {
                    $embed_raw = $course_embed;
                }
            }
        }


        $modal_content = '';
if ( ! empty( $embed_raw ) ) {

    if ( filter_var( $embed_raw, FILTER_VALIDATE_URL ) ) {
        $o = wp_oembed_get( $embed_raw );
        if ( $o ) {

            $o = preg_replace( '~<iframe ~i', '<iframe style="position:absolute;inset:0;width:100%;height:100%;" ', $o );
            $modal_content = $o;
        } else {

            $src = $embed_raw;


            if ( preg_match('~^https?://(www\.)?youtube\.com/watch\?~i', $src) ) {
                parse_str( (string) parse_url($src, PHP_URL_QUERY), $q );
                if ( ! empty($q['v']) ) {
                    $src = 'https://www.youtube.com/embed/' . rawurlencode($q['v']);
                }
            }

            $modal_content = '<iframe src="'.esc_url($src).'" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen style="position:absolute;inset:0;width:100%;height:100%;border:0"></iframe>';
        }
    } else {

        $allowed = [
            'iframe' => [
                'src' => true, 'width' => true, 'height' => true, 'frameborder' => true,
                'allow' => true, 'allowfullscreen' => true, 'referrerpolicy' => true,
                'title' => true, 'loading' => true, 'style' => true,
            ],
            'div'    => [ 'id' => true, 'class' => true, 'style' => true ],
            'script' => [ 'src' => true, 'id' => true, 'class' => true, 'style' => true ],
        ];
        $safe = wp_kses( $embed_raw, $allowed );
        if ( stripos( $safe, '<iframe' ) !== false && stripos( $safe, 'style=' ) === false ) {
            $safe = preg_replace( '~<iframe ~i', '<iframe style="position:absolute;inset:0;width:100%;height:100%;" ', $safe );
        }
        $modal_content = $safe;
    }
} elseif ( ! empty( $link_url ) ) {

    $src = $link_url;


    if ( preg_match('~^https?://(www\.)?youtube\.com/watch\?~i', $src) ) {
        parse_str( (string) parse_url($src, PHP_URL_QUERY), $q );
        if ( ! empty($q['v']) ) {
            $src = 'https://www.youtube.com/embed/' . rawurlencode($q['v']);
        }
    }

    $modal_content = '<iframe src="'.esc_url($src).'" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen style="position:absolute;inset:0;width:100%;height:100%;border:0"></iframe>';
}


        $thumb_html = '';
        if ( $thumb_url ) {
            $thumb_html = '<img src="'.esc_url($thumb_url).'" alt="" loading="lazy" />';
        } else {

            $thumb_html = '<div style="width:100%;height:100%;background:#000"></div>';
        }

		echo '<h2>Fragment Kursu</h2>';
        echo '<div class="nme-vp">';

        echo '<div class="nme-vp-thumb" id="'.esc_attr($uid).'-thumb" style="position:relative;aspect-ratio:'.$ratio_css.';overflow:hidden;">';
            echo $thumb_html;
            echo '<span class="nme-vp-overlay" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center">';
                echo '<span class="nme-vp-play" aria-hidden="true" style="display:inline-flex;align-items:center;justify-content:center;width:64px;height:64px;border-radius:9999px;background:rgba(0,0,0,.5)">';
                    if ( ! empty( $s['play_icon'] ) ) {
                        \Elementor\Icons_Manager::render_icon( $s['play_icon'], [ 'aria-hidden' => 'true' ] );
                    }
                echo '</span>';
            echo '</span>';
            if ( $title !== '' ) {
                echo '<span class="nme-vp-title" style="position:absolute;left:12px;bottom:12px;color:#fff;font-weight:700;background:rgba(0,0,0,.5);padding:6px 10px;border-radius:8px;">'.esc_html($title).'</span>';
            }
        echo '</div>';


        echo '<div class="nme-vp-modal" id="'.esc_attr($uid).'-modal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;padding:5vw;">';
            echo '<button class="nme-vp-close" type="button" aria-label="Zamknij" style="position:absolute;top:20px;right:24px;font-size:28px;background:none;border:0;cursor:pointer">&times;</button>';
            echo '<div class="nme-vp-iframe-wrap" style="position:relative;width:min(1200px,100%);aspect-ratio:'.$ratio_css.';overflow:hidden;background:#000;">';
                if ( $modal_content ) {
                    echo $modal_content;
                } else {
                    echo '<div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#fff">Brak wideo</div>';
                }
            echo '</div>';
        echo '</div>';


        ?>
        <style>
            .elementor-widget-nme_video_popup .nme-vp-thumb img{ width:100%; height:100%; object-fit:cover; display:block; }
            .elementor-widget-nme_video_popup .nme-vp-play svg{ width:20px; height:20px; transition:fill .25s ease }
            .elementor-widget-nme_video_popup .nme-vp-thumb:hover .nme-vp-play{ transform:scale(1.05) }
            .elementor-widget-nme_video_popup .nme-vp-thumb {  cursor: default; }
            .elementor-widget-nme_video_popup .nme-vp-thumb > img,
            .elementor-widget-nme_video_popup .nme-vp-thumb > div { cursor: pointer; }
            .elementor-widget-nme_video_popup .nme-vp-thumb .nme-vp-overlay,
            .elementor-widget-nme_video_popup .nme-vp-thumb .nme-vp-title { pointer-events: none; }
        </style>
        <?php


        ?>
        <script>
        (function(){
            var thumb = document.getElementById('<?php echo esc_js($uid); ?>-thumb');
            var modal = document.getElementById('<?php echo esc_js($uid); ?>-modal');
            if(!thumb || !modal) return;

            function openM(){ modal.style.display = 'flex'; document.documentElement.style.overflow = 'hidden'; }
            function closeM(){
                modal.style.display = 'none';
                document.documentElement.style.overflow = '';
                var ifr = modal.querySelector('iframe');
                if(ifr){ try{ var s=ifr.src; ifr.src = s; }catch(e){} }
            }

            thumb.addEventListener('click', openM);
            modal.addEventListener('click', function(e){
                if(e.target === modal) closeM();
            });
            var cls = modal.querySelector('.nme-vp-close');
            if(cls){ cls.addEventListener('click', closeM); }
        })();
        </script>
        <?php

        echo '</div>';
    }
}
