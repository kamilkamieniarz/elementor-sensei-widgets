<?php
if ( ! defined('ABSPATH') ) exit;

class ESW_Sensei_Course_Access_Widget extends \Elementor\Widget_Base {
    public function get_name()       { return 'esw_sensei_course_access'; }
    public function get_title()      { return 'Sensei – Dostęp do kursu/lekcji'; }
    public function get_icon()       { return 'eicon-lock-user'; }
    public function get_categories() { return [ 'elementor-sensei-widgets' ]; }

    protected function render() {
        if ( ! ( is_singular('course') || is_singular('lesson') ) ) {
            return;
        }

	    if ( is_singular('lesson') ) {
	        if ( shortcode_exists('etrapez_protect_dacast') ) {
	            echo do_shortcode('[etrapez_protect_dacast]');
	        } elseif ( function_exists('etrapez__strip_dacast_for_non_enrolled') && ! has_filter('the_content', 'etrapez__strip_dacast_for_non_enrolled') ) {
	            add_filter('the_content', 'etrapez__strip_dacast_for_non_enrolled', 12);
	        }
	        return;
	    }
	    
        $post_id  = get_the_ID();
        $user_id  = get_current_user_id();
        $course_id = 0;

        if ( is_singular('course') ) {
            $course_id = $post_id;
        } elseif ( is_singular('lesson') ) {
            if ( function_exists('Sensei') && isset( Sensei()->lesson ) && method_exists( Sensei()->lesson, 'get_course_id' ) ) {
                $course_id = (int) Sensei()->lesson->get_course_id( $post_id );
            }
            if ( ! $course_id ) {
                $maybe = get_post_meta( $post_id, '_lesson_course', true );
                if ( $maybe ) $course_id = (int) $maybe;
            }
        }

        if ( ! $course_id ) {
            return;
        }

        $has_access = false;
        if ( $user_id && function_exists('Sensei') && isset( Sensei()->course ) && method_exists( Sensei()->course, 'is_user_enrolled' ) ) {
            $has_access = (bool) Sensei()->course->is_user_enrolled( $course_id, $user_id );
        }

        if ( is_user_logged_in() && $has_access ) {
            return;
        }

        echo '<div class="nme-sensei-access-text">';
        if ( ! is_user_logged_in() ) {
            echo '<a href="https://etrapez.pl/ustawienia-konta"><strong>Zaloguj się</strong></a> lub ';
            echo '<a href="https://etrapez.pl/kursy-etrapez/"><strong>zapisz się na Kurs</strong></a> aby otrzymać dostęp';
        } else {
            echo '<a href="https://etrapez.pl/kursy-etrapez/"><strong>Zapisz się na Kurs</strong></a> aby otrzymać dostęp';
        }
        echo '</div>';
    }
}


