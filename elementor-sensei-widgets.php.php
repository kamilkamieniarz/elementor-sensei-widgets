<?php
/**
 * Plugin Name: Elementor Sensei Widgets
 * Description: Custom Elementor widgets for Sensei LMS (lessons, progress, continue, access, preview, certificate, video popup).
 * Version: 1.0.7
 * Author: Kamil Kamieniarz
 * Text Domain: elementor-sensei-widgets
 */
if ( ! defined('ABSPATH') ) exit;

/** Info dla admina, jeśli Elementor nie jest aktywny */
add_action('admin_init', function () {
    if ( ! did_action('elementor/loaded') ) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>Elementor Sensei Widgets:</strong> wymaga aktywnego <em>Elementora</em>.</p></div>';
        });
        return;
    }
});

/** Rejestracja kategorii Elementora */
add_action('elementor/elements/categories_registered', function($elements_manager){
    $elements_manager->add_category('elementor-sensei-widgets', [
        'title' => __('Elementor Sensei Widgets', 'elementor-sensei-widgets'),
        'icon'  => 'fa fa-plug',
    ]);
});

/** Rejestracja widgetów */
add_action('elementor/widgets/register', function ($widgets_manager) {

    require_once __DIR__ . '/modules/sensei/class-sensei-lessons-widget.php';
    require_once __DIR__ . '/modules/sensei/class-sensei-progress-widget.php';
    require_once __DIR__ . '/modules/sensei/class-sensei-continue-widget.php';
    require_once __DIR__ . '/modules/sensei/class-sensei-course-access-widget.php';
    require_once __DIR__ . '/modules/sensei/class-sensei-lesson-preview-widget.php';
    require_once __DIR__ . '/modules/sensei/class-video-popup.php';
    require_once __DIR__ . '/modules/sensei/class-sensei-certificate-widget.php'; 

    if ( class_exists('\ESW_Sensei_Lessons_Widget') ) {
        $widgets_manager->register( new \ESW_Sensei_Lessons_Widget() );
    }
    if ( class_exists('\ESW_Sensei_Progress_Widget') ) {
        $widgets_manager->register( new \ESW_Sensei_Progress_Widget() );
    }
    if ( class_exists('\ESW_Sensei_Continue_Widget') ) {
        $widgets_manager->register( new \ESW_Sensei_Continue_Widget() );
    }
    if ( class_exists('\ESW_Sensei_Course_Access_Widget') ) {
        $widgets_manager->register( new \ESW_Sensei_Course_Access_Widget() );
    }
    if ( class_exists('\ESW_Sensei_Lesson_Preview_Widget') ) {
        $widgets_manager->register( new \ESW_Sensei_Lesson_Preview_Widget() );
    }
    if ( class_exists('\ESW_Video_Popup') ) {
        $widgets_manager->register( new \ESW_Video_Popup() );
    }
    if ( class_exists('\ESW_Sensei_Certificate_Widget') ) {
        $widgets_manager->register( new \ESW_Sensei_Certificate_Widget() );
    }

}, 10);


// ========== ŚLEDZENIE OSTATNIO ODWIEDZONEJ LEKCJI ==========
add_action('template_redirect', function () {
    if ( ! is_user_logged_in() ) return;
    if ( ! is_singular('lesson') ) return;

    $user_id  = get_current_user_id();
    $lesson   = get_post();
    if ( ! $lesson ) return;

    // kurs tej lekcji
    $course_id = (int) get_post_meta($lesson->ID, '_lesson_course', true);
    if ( ! $course_id ) return;

    // Zapisz w user_meta: klucz per kurs -> ID lekcji + znacznik czasu
    $key = 'nme_last_lesson_course_' . $course_id;
    update_user_meta($user_id, $key, [
        'lesson_id' => (int) $lesson->ID,
        'time'      => time(),
    ]);
});
