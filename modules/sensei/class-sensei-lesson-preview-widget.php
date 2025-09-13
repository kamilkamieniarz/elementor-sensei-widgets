<?php
if ( ! defined('ABSPATH') ) exit;

class ESW_Sensei_Lesson_Preview_Widget extends \Elementor\Widget_Base {
    public function get_name()       { return 'esw_sensei_lesson_preview'; }
    public function get_title()      { return 'Sensei – Zajawka lekcji'; }
    public function get_icon()       { return 'eicon-video-camera'; }
    public function get_categories() { return [ 'elementor-sensei-widgets' ]; }

    protected function render() {
        if ( ! is_singular('lesson') ) return;

        $lesson_id = get_the_ID();
        $course_id = (int) get_post_meta($lesson_id, '_lesson_course', true);
        $user_id   = get_current_user_id();

        $has_access = false;
        if ( $user_id && $course_id && function_exists('Sensei') && isset(Sensei()->course) && method_exists(Sensei()->course, 'is_user_enrolled') ) {
            $has_access = Sensei()->course->is_user_enrolled( $course_id, $user_id );
        }

        if ( ! $has_access ) {
            echo '<div class="nme-sensei-preview">';
            echo '<div class="nme-sensei-preview-video" style="aspect-ratio:16/9; background:#000; display:flex;align-items:center;justify-content:center;color:#fff;">';
            echo '<span>Podgląd wideo (dostęp dla zapisanych)</span>';
            echo '</div>';
            echo '<div class="nme-sensei-preview-text" style="margin-top:10px;">';
            if ( ! is_user_logged_in() ) {
                echo 'Zaloguj się lub zapisz się na Kurs aby otrzymać dostęp';
            } else {
                echo 'Zapisz się na Kurs aby otrzymać dostęp';
            }
            echo '</div>';
            echo '</div>';
        }
    }
}
