import { __, _n, sprintf } from '@wordpress/i18n';
import type { Article } from './types';

/**
 * Days after which an unreviewed article is considered stale.
 * Documentation that has not been touched for a quarter is the usual
 * trigger point for a re-read in most docs teams.
 */
export const STALE_AFTER_DAYS = 90;

const DAY_IN_MS = 24 * 60 * 60 * 1000;

export interface Staleness {
	days: number;
	isStale: boolean;
	/** True when the age comes from the edit date because no review was logged. */
	inferred: boolean;
	label: string;
}

/**
 * Work out how long ago an article was last verified.
 *
 * Prefers the explicit "last reviewed" date. Articles that have never been
 * reviewed fall back to their last-modified date, so the badge still says
 * something useful on content created before reviews were tracked.
 */
export function getStaleness( article: Pick< Article, 'last_reviewed' | 'modified' > ): Staleness | null {
	const reviewed = article.last_reviewed
		? Date.parse( `${ article.last_reviewed }T00:00:00Z` )
		: NaN;
	const modified = article.modified ? Date.parse( article.modified ) : NaN;

	const inferred = Number.isNaN( reviewed );
	const reference = inferred ? modified : reviewed;

	if ( Number.isNaN( reference ) ) {
		return null;
	}

	const days = Math.max( 0, Math.floor( ( Date.now() - reference ) / DAY_IN_MS ) );
	const isStale = days >= STALE_AFTER_DAYS;

	let label: string;
	if ( inferred ) {
		label = sprintf(
			/* translators: %d: number of days since the article was last edited. */
			_n( 'Not reviewed — edited %d day ago', 'Not reviewed — edited %d days ago', days, 'doczur' ),
			days
		);
	} else if ( days === 0 ) {
		label = __( 'Reviewed today', 'doczur' );
	} else {
		label = sprintf(
			/* translators: %d: number of days since the article was last reviewed. */
			_n( 'Reviewed %d day ago', 'Reviewed %d days ago', days, 'doczur' ),
			days
		);
	}

	return { days, isStale, inferred, label };
}
