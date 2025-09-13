<?php
if ( ! defined('ABSPATH') ) exit;

class ESW_Sensei_Lessons_Widget extends \Elementor\Widget_Base {

    public function get_name()       { return 'esw_sensei_lessons'; }
    public function get_title()      { return 'Sensei – Lista lekcji'; }
    public function get_icon()       { return 'eicon-post-list'; }
    public function get_categories() { return [ 'elementor-sensei-widgets' ]; }

    protected function register_controls() {
        $this->start_controls_section( 'sec_lessons', [ 'label' => 'Lista lekcji' ] );

        $this->add_control( 'title', [
            'label'   => 'Nagłówek',
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'Lekcje',
        ] );

        $this->add_control( 'show_duration', [
            'label'   => 'Pokaż czas trwania',
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ] );

        $this->add_control( 'duration_meta', [
            'label'   => 'Meta z czasem trwania',
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'lesson_duration',
        ] );

        // --- IKONY (SVG) ---
        $this->add_control( 'play_icon', [
            'label'   => 'Ikona odtwarzania',
            'type'    => \Elementor\Controls_Manager::ICONS,
            'default' => [
                'value'   => 'fas fa-play',
                'library' => 'fa-solid',
            ],
        ] );

        $this->add_control( 'icon_color', [
            'label'     => 'Kolor ikony',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .nme-lesson-play svg' => 'fill: {{VALUE}};',
            ],
        ] );
        
		$this->add_control( 'icon_hover_color', [
		    'label'     => 'Kolor ikony (hover)',
		    'type'      => \Elementor\Controls_Manager::COLOR,
		    'selectors' => [
		        '{{WRAPPER}} a.nme-lesson-item:hover .nme-lesson-play svg' => 'fill: {{VALUE}};',
		    ],
		] );

        $this->add_control( 'locked_icon', [
            'label'   => 'Ikona „zablokowane”',
            'type'    => \Elementor\Controls_Manager::ICONS,
            'default' => [
                'value'   => 'fas fa-lock',
                'library' => 'fa-solid',
            ],
        ] );

        // --- KOLORY ---
        $this->add_control( 'heading_color', [
            'label'     => 'Kolor nagłówka',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .nme-lesson-heading' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'link_color', [
            'label'     => 'Kolor linków',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .nme-lesson-item a' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'link_hover_color', [
            'label'     => 'Kolor linków (hover)',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .nme-lesson-item:hover a' => 'color: {{VALUE}};',
            ],
        ] );
        
		$this->add_control( 'list_border_color', [
		    'label'     => 'Kolor obramowania listy',
		    'type'      => \Elementor\Controls_Manager::COLOR,
		    'selectors' => [
		        '{{WRAPPER}} .nme-lesson-list' => 'border-color: {{VALUE}};',
		    ],
		] );
		
		$this->add_control( 'item_border_color', [
		    'label'     => 'Kolor odstępu między lekcjami',
		    'type'      => \Elementor\Controls_Manager::COLOR,
		    'selectors' => [
		        '{{WRAPPER}} .nme-lesson-item' => 'border-bottom-color: {{VALUE}};',
		    ],
		] );

        $this->add_control( 'bg_color', [
            'label'     => 'Kolor tła (domyślny)',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .nme-lesson-item' => 'background-color: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'hover_bg', [
            'label'     => 'Tło w hoverze',
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .nme-lesson-item:hover' => 'background: {{VALUE}};',
            ],
        ] );

        $this->add_control( 'sort_by', [
            'label'   => 'Sortuj wg',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'menu_order',
            'options' => [
                'menu_order' => 'Kolejność kursu (menu_order)',
                'title'      => 'Tytuł',
                'date'       => 'Data',
            ],
        ] );

        $this->add_control( 'sort_dir', [
            'label'   => 'Kierunek',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'ASC',
            'options' => [
                'ASC'  => 'Rosnąco',
                'DESC' => 'Malejąco',
            ],
        ] );

        $this->end_controls_section();
    }

    protected function get_course_lessons( $course_id, $sort_by = 'menu_order', $sort_dir = 'ASC' ) : array {
        $lessons = [];

        if ( function_exists( 'Sensei' ) && isset( Sensei()->course ) && method_exists( Sensei()->course, 'course_lessons' ) ) {
            $api_lessons = Sensei()->course->course_lessons( $course_id );
            if ( is_array( $api_lessons ) ) $lessons = $api_lessons;
        }

        if ( empty( $lessons ) ) {
            $orderby_chain = 'menu_order title date';
            if ( 'title' === $sort_by ) $orderby_chain = 'title menu_order date';
            if ( 'date'  === $sort_by ) $orderby_chain = 'date menu_order title';

            $lessons = get_posts( [
                'post_type'        => 'lesson',
                'post_status'      => [ 'publish', 'private' ],
                'posts_per_page'   => -1,
                'ignore_sticky_posts' => true,
                'suppress_filters' => true,
                'orderby'          => $orderby_chain,
                'order'            => $sort_dir,
                'meta_query'       => [
                    [ 'key' => '_lesson_course', 'value' => (int) $course_id ],
                ],
                'fields'           => 'all',
            ] );
        }

        if ( empty( $lessons ) ) return [];

        $dir = ( strtoupper( $sort_dir ) === 'DESC' ) ? -1 : 1;
        usort( $lessons, function( $a, $b ) use ( $sort_by, $dir ) {
            if ( 'title' === $sort_by ) {
                $cmp = strnatcasecmp( $a->post_title, $b->post_title ) * $dir;
            } elseif ( 'date' === $sort_by ) {
                $cmp = ( strtotime( $a->post_date ) <=> strtotime( $b->post_date ) ) * $dir;
            } else {
                $cmp = ( (int) $a->menu_order <=> (int) $b->menu_order ) * $dir;
            }
            if ( 0 === $cmp ) $cmp = ( $a->ID <=> $b->ID );
            return $cmp;
        } );

        return $lessons;
    }

	/**
	 * Czy lekcja ma tag 'darmowe' (szuka po wszystkich taksonomiach typu 'lesson').
	 */
	protected function lesson_has_free_tag( int $lesson_id ): bool {
	    if ( get_post_type( $lesson_id ) !== 'lesson' ) return false;

	    $taxes = get_object_taxonomies( 'lesson' );
	    if ( empty( $taxes ) ) {
	        // awaryjnie sprawdź też globalne tagi wpisów
	        $taxes = [ 'post_tag' ];
	    }

	    foreach ( $taxes as $tax ) {
	        if ( taxonomy_exists( $tax ) && has_term( 'darmowe', $tax, $lesson_id ) ) {
	            return true;
	        }
	    }
	    return false;
	}

	/**
	 * Zasada: jeśli user zalogowany i to PIERWSZA napotkana lekcja z tagiem 'darmowe'.
	 */
	protected function can_open_free_preview( int $lesson_id, bool $already_gave_free ): bool {
	    if ( $already_gave_free ) return false;                // tylko jedna lekcja na liście
	    if ( ! is_user_logged_in() ) return false;             // wymagane logowanie
	    return $this->lesson_has_free_tag( $lesson_id );
	}


    protected function render() {
        $s = $this->get_settings_for_display();
        $post = get_post();
        if ( ! $post ) return;

        $course_id = 0;
        if ( 'course' === $post->post_type ) {
            $course_id = (int) $post->ID;
        } elseif ( 'lesson' === $post->post_type ) {
            $course_id = (int) get_post_meta( $post->ID, '_lesson_course', true );
        }
        if ( ! $course_id ) return;

	    $user_id    = get_current_user_id();
	    $has_access = false;
	    if ( $user_id && function_exists('Sensei') && isset(Sensei()->course) && method_exists(Sensei()->course, 'is_user_enrolled') ) {
	        $has_access = (bool) Sensei()->course->is_user_enrolled( $course_id, $user_id );
	    }

        $sort_by  = $s['sort_by']  ?? 'menu_order';
        $sort_dir = strtoupper( $s['sort_dir'] ?? 'ASC' );
        $lessons = $this->get_course_lessons( $course_id, $sort_by, $sort_dir );
        if ( empty( $lessons ) ) return;

        $title = $s['title'] ?? 'Lekcje';
		$wrap_id = 'nme-lesson-wrap-' . uniqid();

        echo '<section id="'.esc_attr($wrap_id).'" class="nme-lesson"><div class="nme-lesson-inner" style="max-width:520px">';
        echo '<h2 class="nme-lesson-heading" style="margin:0 0 10px;font-weight:800;">' . esc_html( $title ) . '</h2>';
        echo '<div class="nme-lesson-list" style="border:none;overflow:hidden;border-radius: 8px;box-shadow: 0px 0px 24px -15px rgba(66, 68, 90, 1);">';

		$free_preview_given = false;

        foreach ( $lessons as $l ) {
            $lid     = is_object( $l ) ? $l->ID : (int) $l;
            $l_title = get_the_title( $lid );
            $url     = get_permalink( $lid );
            $dur     = ( ( $s['show_duration'] ?? 'yes' ) === 'yes' )
                ? get_post_meta( $lid, ( $s['duration_meta'] ?? 'lesson_duration' ), true )
                : '';
			$is_clickable = $has_access;

		    if ( ! $has_access ) {
		        if ( $this->can_open_free_preview( $lid, $free_preview_given ) ) {
		            $is_clickable      = true;
		            $free_preview_given = true;
		        }
		    }
    
			$tag          = $is_clickable ? 'a' : 'div';
			$href_attr    = $is_clickable ? ' href="' . esc_url( $url ) . '"' : '';
			$locked_class = $is_clickable ? '' : ' is-locked';
			$aria         = $is_clickable ? '' : ' aria-disabled="true"';

			$has_free_tag = $this->lesson_has_free_tag( $lid );

			$free_class = '';
			if ( ! $has_access && is_user_logged_in() && $has_free_tag && $is_clickable ) {
			    $free_class = ' free-highlight';
			}

			$data_attrs = sprintf(
			    ' data-lesson-id="%d" data-clickable="%d" data-has-free-tag="%d" data-user-logged="%d" data-user-enrolled="%d"',
			    $lid,
			    $is_clickable ? 1 : 0,
			    $has_free_tag ? 1 : 0,
			    is_user_logged_in() ? 1 : 0,
			    $has_access ? 1 : 0
			);

	        echo '<' . $tag . $href_attr . ' class="nme-lesson-item' . $locked_class. $free_class . '"'.$aria.$data_attrs.' style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border-bottom-style:solid;border-bottom-width:1px;text-decoration:none;">';

	            echo '<span class="nme-lesson-link" style="font-weight:600;">' . esc_html( $l_title ) . '</span>';

	            echo '<div class="nme-lesson-meta" style="display:flex;gap:10px;align-items:center;font-weight:700;font-size:12px;line-height:1;padding:0 10px;white-space:nowrap;">';

	                if ( ( $s['show_duration'] ?? 'yes' ) === 'yes' && ! empty( $dur ) ) {
	                    echo '<span class="nme-lesson-duration">' . esc_html( $dur ) . '</span>';
	                }

	                echo '<span class="nme-lesson-play" aria-hidden="true" style="display:inline-flex;align-items:center">';
	                // Ikona: play dla dostępnych, kłódka dla zablokowanych
	                $icon_to_render = $is_clickable ? ( $s['play_icon'] ?? null ) : ( $s['locked_icon'] ?? null );
	                if ( ! empty( $icon_to_render ) ) {
	                    \Elementor\Icons_Manager::render_icon( $icon_to_render, [ 'aria-hidden' => 'true' ] );
	                }
	                echo '</span>';

	            echo '</div>';

	        echo '</' . $tag . '>';
        }

        echo '</div></div></section>';
        

        ?>
        <style>
            .elementor-widget-nme_sensei_lessons .nme-lesson-item:hover .nme-lesson-link{ text-decoration:none }
            .elementor-widget-nme_sensei_lessons .nme-lesson-play svg{ width:14px; height:14px;transition:fill .35s ease }
        	.elementor-widget-nme_sensei_lessons .nme-lesson-item.is-locked{ cursor:default }
        	.elementor-widget-nme_sensei_lessons .nme-lesson-item.free-highlight { border: solid red 1px !important; border-radius: 15px 15px 0px 0px;	}
        </style>
        <?php
    }
}
