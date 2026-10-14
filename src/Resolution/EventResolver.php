<?php
/**
 * Event resolution extension contract.
 *
 * @package SaveTheDate
 */

namespace SaveTheDate\Resolution;

use SaveTheDate\Calendar\Event;

defined( 'ABSPATH' ) || exit;

/** Resolves one source without keeping request or entry state. */
interface EventResolver {
	/**
	 * Resolve sanitized settings for an explicit output context.
	 *
	 * @param array<string, mixed>      $settings Settings.
	 * @param array<string, mixed>      $form Form.
	 * @param array<string, mixed>|null $entry Entry, or null for public output.
	 * @param string                    $context Output context.
	 * @return Event|null
	 */
	public function resolve( array $settings, array $form, ?array $entry, string $context ): ?Event;
}
