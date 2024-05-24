<?php
/**
 * Renders the WooCommerce tickets table/form
 *
 * Override this template in your own theme by creating a file at:
 *
 *     [your-theme]/tribe-events/wootickets/tickets.php
 *
 * @deprecated 4.11.0
 *
 * @since 4.10.7 Restrict quantity selectors to allowed purchase limit and removed unused variables.
 *
 * @version 4.11.0
 *
 * @var bool $global_stock_enabled
 * @var bool $must_login
 */
$post_id = get_the_id();
Tribe__Tickets__Tickets_View::instance()->get_tickets_block( $post_id );
