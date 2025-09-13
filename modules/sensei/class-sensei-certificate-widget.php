<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ESW_Sensei_Certificate_Widget extends \Elementor\Widget_Base {

    public function get_name()       { return 'esw_sensei_certificate'; }
    public function get_title()      { return 'Sensei – Certyfikat'; }
    public function get_icon()       { return 'fas fa-award'; } 
    public function get_categories() { return [ 'elementor-sensei-widgets' ]; }

    /* -------------------- KONTROLKI -------------------- */
    protected function register_controls() {
        $this->start_controls_section( 'sec_content', [ 'label' => 'Certyfikat' ] );

        $this->add_control( 'label_text', [
            'label'   => 'Tekst przycisku',
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'Pobierz certyfikat',
        ] );

        $this->add_control( 'subtext', [
            'label'       => 'Pod-tekst (opcjonalnie)',
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'np. PDF z podpisem elektronicznym',
            'default'     => '',
        ] );

        $this->end_controls_section();

        // Prosty styl – kolory
        $this->start_controls_section( 'sec_style', [ 'label' => 'Styl', 'tab' => \Elementor\Controls_Manager::TAB_STYLE ] );

        $this->add_control( 'btn_bg', [
            'label'     => 'Tło przycisku',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#28a745',
            'selectors' => [
                '{{WRAPPER}} .nme-certificate-btn' => 'background-color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'btn_bg_hover', [
            'label'     => 'Tło przycisku (hover)',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#218838',
            'selectors' => [
                '{{WRAPPER}} .nme-certificate-btn:hover' => 'background-color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'btn_color', [
            'label'     => 'Kolor tekstu',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => [
                '{{WRAPPER}} .nme-certificate-btn' => 'color: {{VALUE}};',
            ],
        ] );

        $this->end_controls_section();
    }

    /* -------------------- POMOCNICZE -------------------- */
    private function get_course_id_from_context() : int {
        $post = get_post();
        if ( ! $post ) return 0;
        if ( 'course' === $post->post_type ) {
            return (int) $post->ID;
        }
        if ( 'lesson' === $post->post_type ) {
            return (int) get_post_meta( $post->ID, '_lesson_course', true );
        }
        return 0;
    }

    /* -------------------- RENDER -------------------- */
    protected function render() {
        // 1) Musi być zalogowany
        if ( ! is_user_logged_in() ) return;

        $user_id   = get_current_user_id();
        $course_id = $this->get_course_id_from_context();
        if ( ! $course_id ) return; // brak kontekstu kursu/lekcji → brak widżetu

        // 2) Musi mieć dostęp do kursu (enrolled)
        $has_access = false;
        if ( function_exists( 'Sensei' ) && isset( Sensei()->course ) && method_exists( Sensei()->course, 'is_user_enrolled' ) ) {
            $has_access = (bool) Sensei()->course->is_user_enrolled( $course_id, $user_id );
        }
        if ( ! $has_access ) return;

        // 3) Kurs ukończony
        $completed = false;
        if ( class_exists( 'Sensei_Utils' ) && method_exists( 'Sensei_Utils', 'user_completed_course' ) ) {
            $completed = (bool) \Sensei_Utils::user_completed_course( $course_id, $user_id );
        }
        if ( ! $completed ) return;

        // 4) Certyfikat – przez helper Sensei_Certificates()
        $cert_id = 0;
        if ( function_exists( 'Sensei_Certificates' ) ) {
            $cert = Sensei_Certificates(); // singleton
            if ( $cert && method_exists( $cert, 'get_certificate_id' ) ) {
                $cert_id = (int) $cert->get_certificate_id( $user_id, $course_id );
            }
        }

        // Gdyby wersja bez helpera – pozostawiamy guard na przyszłość (bez błędów):
        if ( ! $cert_id && class_exists( 'Sensei_Certificates' ) ) {
            // Nie wymuszamy, bo nie każda instalacja ma kontener DI. Bezpiecznie pomijamy.
        }

        if ( ! $cert_id ) return; // brak wygenerowanego certyfikatu → nic nie pokazujemy

        $certificate_url = get_permalink( $cert_id );
        if ( ! $certificate_url ) return;

        $s      = $this->get_settings_for_display();
        $label  = trim( (string) ( $s['label_text'] ?? 'Pobierz certyfikat' ) );
        $sub    = trim( (string) ( $s['subtext'] ?? '' ) );

        // 5) UI
        echo '<div class="nme-certificate-box" style="margin:20px 0;text-align:center">';
            echo '<a class="nme-certificate-btn" href="' . esc_url( $certificate_url ) . '" target="_blank" rel="noopener" style="
                display:inline-flex;align-items:center;gap:12px;
                padding:14px 22px;border-radius:12px;text-decoration:none;
                font-weight:800;box-shadow:0 8px 24px rgba(0,0,0,.15);transition:transform .15s ease, box-shadow .15s ease;">
                    <span class=\"nme-cert-ico\" aria-hidden=\"true\" style=\"display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:9999px;background:rgba(255,255,255,.15)\">🏆</span>
                    <span>' . esc_html( $label ) . '</span>
            </a>';

            if ( $sub !== '' ) {
                echo '<div class="nme-certificate-sub" style="margin-top:8px;opacity:.75;font-size:13px;">' . esc_html( $sub ) . '</div>';
            }
        echo '</div>';

        // delikatna animacja hover
        ?>
        <style>
            .elementor-widget-nme_sensei_certificate .nme-certificate-btn:hover{
                transform: translateY(-1px);
                box-shadow:0 10px 28px rgba(0,0,0,.2);
            }
        </style>
        <?php
    }
}
