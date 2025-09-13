<?php
if ( ! defined('ABSPATH') ) exit;

class ESW_Sensei_Progress_Widget extends \Elementor\Widget_Base {

    public function get_name()       { return 'esw_sensei_progress'; }
    public function get_title()      { return 'Sensei – Progress bar'; }
    public function get_icon()       { return 'eicon-skill-bar'; }
    public function get_categories() { return [ 'elementor-sensei-widgets' ]; }

    protected function register_controls() {
        $this->start_controls_section('sec_progress', [ 'label' => 'Progress' ]);

        $this->add_control('height', [
            'label'   => 'Wysokość paska',
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range' => [ 'px' => [ 'min' => 5, 'max' => 100 ] ],
            'default' => [ 'size' => 18, 'unit' => 'px' ],
            'selectors' => [
                '{{WRAPPER}} .nme-progress-bar' => 'height: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_control('radius', [
            'label'   => 'Zaokrąglenie',
            'type'    => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px','%'],
            'default' => [ 'size' => 9999, 'unit' => 'px' ],
            'selectors' => [
                '{{WRAPPER}} .nme-progress-bar, {{WRAPPER}} .nme-progress-fill' => 'border-radius: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_control('show_text', [
            'label'   => 'Pokaż tekst %',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('bar_bg', [
            'label' => 'Tło paska',
            'type'  => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .nme-progress-bar' => 'background-color: {{VALUE}};',
            ],
        ]);

        $this->add_control('bar_fill', [
            'label' => 'Kolor wypełnienia',
            'type'  => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .nme-progress-fill' => 'background-color: {{VALUE}};',
            ],
        ]);

        $this->add_control('label_color', [
            'label' => 'Kolor tekstu',
            'type'  => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .nme-progress-label' => 'color: {{VALUE}};',
            ],
        ]);

        $this->end_controls_section();
    }

    protected function render() {
        $s = $this->get_settings_for_display();

        $user_id = get_current_user_id();
        if ( ! $user_id ) return;

        $post = get_post();
        if ( ! $post ) return;

        $course_id = 0;
        if ( 'course' === $post->post_type ) {
            $course_id = (int) $post->ID;
        } elseif ( 'lesson' === $post->post_type ) {
            $course_id = (int) get_post_meta($post->ID, '_lesson_course', true);
        }
        if ( ! $course_id ) return;

        $total = 0; $completed = 0;
        if ( class_exists('Sensei_Utils') ) {
            $lessons = get_posts([
                'post_type'        => 'lesson',
                'post_status'      => [ 'publish', 'private' ],
                'posts_per_page'   => -1,
                'suppress_filters' => true,
                'fields'           => 'ids',
                'meta_query'       => [
                    [ 'key' => '_lesson_course', 'value' => (int) $course_id ],
                ],
            ]);
            $total = count($lessons);
            if ( $total > 0 && method_exists('Sensei_Utils','user_completed_lesson') ) {
                foreach ( $lessons as $lid ) {
                    if ( \Sensei_Utils::user_completed_lesson($lid, $user_id) ) $completed++;
                }
            }
        }
        if ( 0 === $total ) return;

        $pct = max(0, min(100, (int) round(($completed / $total) * 100)));

        echo '<div class="nme-progress-wrap elementor-clearfix">';
            echo '<div class="nme-progress-bar" role="meter" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . esc_attr($pct) . '">';
                echo '<div class="nme-progress-fill" style="width:' . esc_attr($pct) . '%;"></div>';
                if ( 'yes' === ( $s['show_text'] ?? 'yes' ) ) {
                    echo '<span class="nme-progress-label">Ukończono: ' . esc_html($pct) . '%</span>';
                }
            echo '</div>';
        echo '</div>';

        // CSS tylko do układu (kolory biorą się z selectors!)
        ?>
        <style>
            .elementor-widget-nme_sensei_progress .nme-progress-bar{
                width:100%;
                position:relative;
                overflow:hidden;
            }
            .elementor-widget-nme_sensei_progress .nme-progress-fill{
                position:absolute;
                left:0; top:0; bottom:0;
                transition:width .35s ease;
                min-width: 5%;
            }
            .elementor-widget-nme_sensei_progress .nme-progress-label{
                position:absolute;
                left:50%; top:50%;
                transform:translate(-50%,-50%);
                font-weight:700;
                font-size:12px;
                line-height:1;
                white-space:nowrap;
                pointer-events:none;
            }
            @media (prefers-reduced-motion: reduce){
                .elementor-widget-nme_sensei_progress .nme-progress-fill{ transition:none; }
            }
        </style>
        <?php
    }
}
