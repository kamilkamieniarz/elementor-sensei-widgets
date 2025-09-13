<?php
if ( ! defined('ABSPATH') ) exit;

class ESW_Sensei_Continue_Widget extends \Elementor\Widget_Base {

    public function get_name()       { return 'esw_sensei_continue'; }
    public function get_title()      { return 'Sensei – Kontynuuj'; }
    public function get_icon()       { return 'eicon-arrow-right'; }
    public function get_categories() { return [ 'elementor-sensei-widgets' ]; }

    protected function register_controls() {
        $this->start_controls_section('sec_content', [ 'label' => 'Kontynuuj' ]);
        $this->add_control('prefix_text', [
            'label'   => 'Prefiks tekstu',
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'Kontynuuj:',
        ]);
        $this->add_control('fallback_mode', [
            'label'   => 'Gdy brak „ostatniej lekcji”',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'first_incomplete',
            'options' => [
                'first_incomplete' => 'Pierwsza nieukończona lekcja',
                'first'            => 'Pierwsza lekcja w kursie',
                'hide'             => 'Nie pokazuj paska',
            ],
        ]);
        $this->add_control('icon', [
            'label'   => 'Ikona',
            'type'    => \Elementor\Controls_Manager::ICONS,
            'default' => [
                'value'   => 'fas fa-chevron-right',
                'library' => 'fa-solid',
            ],
        ]);
        $this->end_controls_section();

        $this->start_controls_section('sec_style', [ 'label' => 'Styl', 'tab' => \Elementor\Controls_Manager::TAB_STYLE ]);
        $this->add_control('bar_bg', [
            'label'     => 'Tło paska',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .nme-continue' => 'background-color: {{VALUE}};' ],
        ]);
        $this->add_control('bar_bg_hover', [
            'label'     => 'Tło paska (hover)',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .nme-continue:hover' => 'background-color: {{VALUE}};' ],
        ]);
        $this->add_control('text_color', [
            'label'     => 'Kolor tekstu',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .nme-continue' => 'color: {{VALUE}};' ],
        ]);
        $this->add_control('text_color_hover', [
            'label'     => 'Kolor tekstu (hover)',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .nme-continue:hover' => 'color: {{VALUE}};' ],
        ]);
        $this->add_control('icon_color', [
            'label'     => 'Kolor ikony',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .nme-continue .nme-continue-icon svg' => 'fill: {{VALUE}};' ],
        ]);
        $this->add_control('icon_color_hover', [
            'label'     => 'Kolor ikony (hover)',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .nme-continue:hover .nme-continue-icon svg' => 'fill: {{VALUE}};' ],
        ]);
        $this->add_control('border_color', [
            'label'     => 'Kolor obramowania',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .nme-continue' => 'border-color: {{VALUE}};' ],
        ]);
        $this->add_control('radius', [
            'label'     => 'Zaokrąglenie',
            'type'      => \Elementor\Controls_Manager::SLIDER,
            'size_units'=> ['px','%'],
            'default'   => [ 'size' => 8, 'unit' => 'px' ],
            'selectors' => [ '{{WRAPPER}} .nme-continue' => 'border-radius: {{SIZE}}{{UNIT}};' ],
        ]);
        $this->add_control('padding', [
            'label'     => 'Padding',
            'type'      => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units'=> ['px','em','%'],
            'default'   => [ 'top'=>12,'right'=>16,'bottom'=>12,'left'=>16,'unit'=>'px' ],
            'selectors' => [ '{{WRAPPER}} .nme-continue' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ]);
        $this->end_controls_section();
    }

    private function get_course_id_from_context() : int {
        $post = get_post();
        if ( ! $post ) return 0;
        if ( 'course' === $post->post_type ) return (int) $post->ID;
        if ( 'lesson' === $post->post_type ) return (int) get_post_meta($post->ID, '_lesson_course', true);
        return 0;
    }

    private function get_lessons_for_course( int $course_id ) : array {
        if ( function_exists('Sensei') && isset(Sensei()->course) && method_exists(Sensei()->course, 'course_lessons') ) {
            $ls = Sensei()->course->course_lessons($course_id);
            if ( is_array($ls) && ! empty($ls) ) return $ls;
        }
        return get_posts([
            'post_type'        => 'lesson',
            'post_status'      => [ 'publish', 'private' ],
            'posts_per_page'   => -1,
            'orderby'          => 'menu_order title date',
            'order'            => 'ASC',
            'meta_query'       => [
                [ 'key' => '_lesson_course', 'value' => $course_id ],
            ],
        ]);
    }

    private function get_first_incomplete_lesson( array $lessons, int $user_id ) : int {
        if ( empty($lessons) ) return 0;
        if ( ! class_exists('Sensei_Utils') || ! method_exists('Sensei_Utils','user_completed_lesson') ) {
            return (int) ( is_object($lessons[0]) ? $lessons[0]->ID : $lessons[0] );
        }
        foreach ( $lessons as $l ) {
            $lid = (int) ( is_object($l) ? $l->ID : $l );
            if ( ! \Sensei_Utils::user_completed_lesson($lid, $user_id) ) return $lid;
        }
        return (int) ( is_object($lessons[0]) ? $lessons[0]->ID : $lessons[0] );
    }

    private function user_has_course_access( int $course_id, int $user_id ) : bool {
        $enrolled = false;
        if ( class_exists( '\Sensei_Course_Enrolment' ) && method_exists( '\Sensei_Course_Enrolment', 'get_course_instance' ) ) {
            $instance = \Sensei_Course_Enrolment::get_course_instance( $course_id );
            if ( $instance && method_exists( $instance, 'is_enrolled' ) ) {
                $enrolled = (bool) $instance->is_enrolled( $user_id );
                error_log("NME_Continue_Widget: enrolment via instance -> " . ( $enrolled ? 'true' : 'false' ));
            }
        }
        if ( ! $enrolled && function_exists('Sensei') && isset( Sensei()->course ) && method_exists( Sensei()->course, 'is_user_enrolled' ) ) {
            $legacy = (bool) Sensei()->course->is_user_enrolled( $course_id, $user_id );
            error_log("NME_Continue_Widget: enrolment via legacy -> " . ( $legacy ? 'true' : 'false' ));
            if ( $legacy ) $enrolled = true;
        }
        if ( ! $enrolled && class_exists('Sensei_Utils') && method_exists('Sensei_Utils','user_started_course') ) {
            $started = (bool) \Sensei_Utils::user_started_course( $course_id, $user_id );
            error_log("NME_Continue_Widget: enrolment via started_course -> " . ( $started ? 'true' : 'false' ));
            if ( $started ) $enrolled = true;
        }
        return $enrolled;
    }

    private function get_first_free_lesson_id( int $course_id ) : int {
        $free = get_posts([
            'post_type'      => 'lesson',
            'post_status'    => [ 'publish', 'private' ],
            'posts_per_page' => 1,
            'orderby'        => 'menu_order title date',
            'order'          => 'ASC',
            'meta_query'     => [
                [ 'key' => '_lesson_course', 'value' => $course_id ],
            ],
            'tax_query'      => [[ 'taxonomy' => 'lesson-tag', 'field' => 'slug', 'terms' => [ 'darmowe-lekcje' ] ]],
        ]);
        if ( empty( $free ) ) {
            $free = get_posts([
                'post_type'      => 'lesson',
                'post_status'    => [ 'publish', 'private' ],
                'posts_per_page' => 1,
                'orderby'        => 'menu_order title date',
                'order'          => 'ASC',
                'meta_query'     => [
                    [ 'key' => '_lesson_course', 'value' => $course_id ],
                ],
                'tax_query'      => [[ 'taxonomy' => 'post_tag', 'field' => 'slug', 'terms' => [ 'darmowe-lekcje' ] ]],
            ]);
        }
        if ( ! empty( $free ) ) {
            $l = $free[0];
            return (int) ( is_object($l) ? $l->ID : $l );
        }
        return 0;
    }

    private function resolve_certificate_id( int $user_id, int $course_id ) : int {
        $cid = 0;
        if ( function_exists('Sensei_Certificates') ) {
            $obj = Sensei_Certificates();
            if ( is_object($obj) && method_exists($obj, 'get_certificate_id') ) {
                $cid = (int) $obj->get_certificate_id( $user_id, $course_id );
            }
        }
        if ( $cid ) return $cid;

        $variants = [
            [ '_sensei_certificates_user',  '_sensei_certificates_course' ],
            [ '_sensei_certificate_user',   '_sensei_certificate_course'  ],
            [ 'learner_id',                 'course_id'                   ],
        ];

        foreach ( $variants as $pair ) {
            $maybe = get_posts([
                'post_type'      => 'certificate',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'meta_query'     => [
                    [ 'key' => $pair[0], 'value' => $user_id ],
                    [ 'key' => $pair[1], 'value' => $course_id ],
                ],
                'orderby'        => 'date',
                'order'          => 'DESC',
            ]);
            if ( ! empty($maybe) ) {
                $c = $maybe[0];
                return (int) ( is_object($c) ? $c->ID : $c );
            }
        }

        return 0;
    }

    protected function render() {
        if ( ! is_user_logged_in() ) return;

        $user_id   = get_current_user_id();
        $course_id = $this->get_course_id_from_context();
        if ( ! $course_id ) return;

        error_log("NME_Continue_Widget: render user={$user_id} course={$course_id}");

        $enrolled = $this->user_has_course_access( $course_id, $user_id );
        $course_completed = ( class_exists('Sensei_Utils') && method_exists('Sensei_Utils','user_completed_course') )
            ? (bool) \Sensei_Utils::user_completed_course( $course_id, $user_id )
            : false;
        error_log("NME_Continue_Widget: user_completed_course -> " . ( $course_completed ? 'true' : 'false' ));

        if ( $course_completed ) {
            $target_cert_id = $this->resolve_certificate_id( $user_id, $course_id );
            $cert_url = $target_cert_id ? get_permalink( $target_cert_id ) : '';

            if ( $cert_url ) {
                echo '<a class="nme-continue nme-certificate" href="' . esc_url($cert_url) . '" style="display:flex;justify-content:center;align-items:center;gap:12px;border:2px solid #1f8f3a;background:#1db954;color:#fdf8f2;border-radius:10px;padding:12px 16px;text-decoration:none;">';
                    echo '<span class="nme-continue-text" style="font-weight:800;display:flex;align-items:center;gap:10px;"><span style="background:#fdf8f2;display:inline-flex;align-items:center;justify-content:center;width:3em;height:3em;border-radius:50%;background:#fff;color:#1db954;font-size:14px;line-height:1;">🏅</span> Pobierz certyfikat</span>';
                echo '</a>';
                ?>
                <style>
                    .elementor-widget-nme_sensei_continue .nme-certificate { transition: transform .2s ease, box-shadow .2s ease; }
                    .elementor-widget-nme_sensei_continue .nme-certificate:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(0,0,0,.12); }
                    .nme-continue-text img.emoji { height: 2em !important; width: 2em !important;}
     
                </style>
                <?php
            }
            return;
        }

        if ( ! $enrolled ) {
            $free_lesson_id = $this->get_first_free_lesson_id( $course_id );
            if ( ! $free_lesson_id ) {
                return;
            }
            $free_url = get_permalink( $free_lesson_id );
            if ( ! $free_url ) return;
            echo '<a class="nme-continue nme-free-lesson" href="' . esc_url($free_url) . '" style="display:flex;align-items:center;gap:12px;border:2px dashed #999;background:#f7f7f9;color:#222;border-radius:10px;padding:12px 16px;text-decoration:none;">';
                echo '<span class="nme-continue-text" style="font-weight:700;">Zobacz darmową lekcję</span>';
                echo '<span class="nme-continue-icon" aria-hidden="true" style="margin-left:auto;display:inline-flex;align-items:center;">';
                    echo '<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 4l1.41 1.41L8.83 10H20v2H8.83l4.58 4.59L12 18l-8-8z"/></svg>';
                echo '</span>';
            echo '</a>';
            ?>
            <style>
                .elementor-widget-nme_sensei_continue .nme-free-lesson { transition: background-color .25s ease, border-color .25s ease; }
                .elementor-widget-nme_sensei_continue .nme-free-lesson:hover { background:#f0f0f3; border-color:#777; }
            </style>
            <?php
            return;
        }

        $meta_key = 'nme_last_lesson_course_' . $course_id;
        $last     = get_user_meta($user_id, $meta_key, true);
        $lesson_id = 0;

        if ( is_array($last) && ! empty($last['lesson_id']) ) {
            $candidate = (int) $last['lesson_id'];
            if ( get_post_status($candidate) ) $lesson_id = $candidate;
        }

        if ( ! $lesson_id ) {
            $s = $this->get_settings_for_display();
            $mode = $s['fallback_mode'] ?? 'first_incomplete';
            if ( 'hide' === $mode ) return;
            $lessons = $this->get_lessons_for_course($course_id);
            if ( empty($lessons) ) return;
            if ( 'first_incomplete' === $mode ) {
                $lesson_id = $this->get_first_incomplete_lesson($lessons, $user_id);
            } else {
                $lesson_id = (int) ( is_object($lessons[0]) ? $lessons[0]->ID : $lessons[0] );
            }
        }

        $url = get_permalink($lesson_id);
        if ( ! $url ) {
            return;
        }

        $title = get_the_title($lesson_id);
        $s     = $this->get_settings_for_display();
        $pref  = trim( (string) ($s['prefix_text'] ?? 'Kontynuuj:') );

        echo '<a class="nme-continue" href="' . esc_url($url) . '" style="display:flex;align-items:center;gap:12px;border:2px solid;border-radius:10px;padding:12px 16px;text-decoration:none;">';
            echo '<span class="nme-continue-text" style="font-weight:700;">' . esc_html($pref) . ' ' . esc_html($title) . '</span>';
            echo '<span class="nme-continue-icon" aria-hidden="true" style="margin-left:auto;display:inline-flex;align-items:center;">';
                if ( ! empty($s['icon']) ) {
                    \Elementor\Icons_Manager::render_icon( $s['icon'], [ 'aria-hidden' => 'true' ] );
                }
            echo '</span>';
        echo '</a>';

        ?>
        <style>
            .elementor-widget-nme_sensei_continue .nme-continue { transition: background-color .25s ease, color .25s ease, border-color .25s ease; }
            .elementor-widget-nme_sensei_continue .nme-continue .nme-continue-icon svg { width:16px; height:16px; transition: fill .25s ease; }
            .elementor-widget-nme_sensei_continue .nme-continue:hover { box-shadow: 0 6px 18px rgba(0,0,0,.08); }
        </style>
        <?php
    }
}
