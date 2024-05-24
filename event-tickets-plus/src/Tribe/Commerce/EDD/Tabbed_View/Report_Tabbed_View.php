<?php


/**
 * Class Tribe__Tickets_Plus__Commerce__EDD__Tabbed_View__Report_Tabbed_View
 *
 * Renders a tab navigation on top of a post attendance or sales report.
 *
 * The class is a convenience wrapper around the `Tribe__Tabbed_View` to set up and re-use
 * the code needed to set up and re-use this tabbed view.
 */
class Tribe__Tickets_Plus__Commerce__EDD__Tabbed_View__Report_Tabbed_View {

	/**
	 * Registers the tabbed view actions.
	 *
	 * The tabs are not AJAX powered by UI around existing links.
	 *
	 * @since 4.10
	 */
	public function register() {
		add_filter( 'tribe_tickets_orders_tabbed_view_tab_map', array( $this, 'filter_tribe_tickets_orders_tabbed_view_tab_map' ) );
		add_action( 'tribe_tickets_orders_tabbed_view_register_tab_right', array( $this, 'register_orders_tab' ), 10, 2 );
	}

	/**
	 * Adds the EDD orders tab slug to the tab slug map.
	 *
	 * @since 4.10
	 *
	 * @param array $tab_map
	 *
	 * @return array
	 */
	public function filter_tribe_tickets_orders_tabbed_view_tab_map( array $tab_map = array() ) {
		$tab_map['tickets-orders'] = Tribe__Tickets_Plus__Commerce__EDD__Orders__Report::$tab_slug;

		return $tab_map;
	}

	/**
	 * Registers the EDD orders tab among those the tabbed view should render.
	 *
	 * @since 4.10
	 *
	 * @param Tribe__Tabbed_View $tabbed_view
	 * @param WP_Post            $post
	 */
	public function register_orders_tab( Tribe__Tabbed_View $tabbed_view, WP_Post $post ) {

		// only show Order Tab if EDD is the Default Provider
		$default_ticket = get_post_meta( $post->ID, tribe( 'tickets.handler' )->key_provider_field, true );
		if ( 'Tribe__Tickets_Plus__Commerce__EDD__Main' !== $default_ticket ) {
			return;
		}

		$orders_report     = new Tribe__Tickets_Plus__Commerce__EDD__Tabbed_View__Orders_Report_Tab( $tabbed_view );
		$orders_report_url = Tribe__Tickets_Plus__Commerce__EDD__Orders__Report::get_tickets_report_link( $post );
		$orders_report->set_url( $orders_report_url );
		$tabbed_view->register( $orders_report );
	}

	/**
	 * Renders the tabbed view for the current post.
	 *
	 * @since 4.10
	 *
	 * @param bool $active_tab_slug Whether this tab should be set to active or not.
	 */
	public function render( $active = null ) {
		$view = new Tribe__Tabbed_View();
		$view->set_label( apply_filters( 'the_title', get_post( $this->post_id )->post_title, $this->post_id ) );
		$query_string = empty( $_SERVER['QUERY_STRING'] ) ? '' : '?' . $_SERVER['QUERY_STRING'];
		$request_uri  = 'edit.php' . $query_string;
		$view->set_url( remove_query_arg( 'tab', $request_uri ) );

		if ( ! empty( $active ) ) {
			$view->set_active( $active );
		} else {
			// try to set the active tab from the requested page
			parse_str( $request_uri, $query_args );
			if ( ! empty( $query_args['page'] ) && isset( $this->tab_map[ $query_args['page'] ] ) ) {
				$active = $this->tab_map[ $query_args['page'] ];
				$view->set_active( $active );
			}
		}

		$attendees_report = new Tribe__Tickets__Tabbed_View__Attendee_Report_Tab( $view );
		$post             = get_post( $this->post_id );
		$attendees_report->set_url( tribe( 'tickets.attendees' )->get_report_link( $post ) );
		$view->register( $attendees_report );

		$orders_report     = new Tribe__Tickets_Plus__Commerce__EDD__Tabbed_View__Orders_Report_Tab( $view );
		$orders_report_url = Tribe__Tickets_Plus__Commerce__EDD__Orders__Report::get_tickets_report_link( $post );
		$orders_report->set_url( $orders_report_url );
		$view->register( $orders_report );

		echo $view->render();
	}
}