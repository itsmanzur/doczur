import apiFetch from '@wordpress/api-fetch';
import { useCallback, useEffect, useState } from '@wordpress/element';

export interface OverviewCounts {
	total: number;
	published: number;
	draft: number;
	needs_review: number;
}

export type AttentionReason = 'draft' | 'never_reviewed' | 'stale_review';

export interface OverviewAttentionItem {
	id: number;
	title: string;
	reason: AttentionReason;
	last_reviewed: string;
	edit_url: string;
}

export interface OverviewRecentItem {
	id: number;
	title: string;
	status: 'draft' | 'publish';
	modified_gmt: string;
	edit_url: string;
}

export interface KbOverview {
	counts: OverviewCounts;
	attention: OverviewAttentionItem[];
	recent: OverviewRecentItem[];
	/**
	 * Extension point for add-on plugins (via the itsdz_kb_overview_extra
	 * filter server-side) — Free has no reader for it and doesn't need one;
	 * an add-on with its own UI reads this REST response independently.
	 */
	extra: Record< string, unknown >;
}

interface UseKbOverviewResult {
	data: KbOverview | null;
	isLoading: boolean;
	error: Error | null;
	refetch: () => void;
}

/**
 * Fetch the read-only overview for a documentation project.
 *
 * Re-fetches whenever `kbId` changes; a request in flight for a project the
 * caller has since navigated away from is ignored rather than clobbering
 * newer state. `refetch` lets a caller force a reload after an action it
 * knows changed the underlying data (e.g. a bulk "mark reviewed").
 * @param kbId
 */
export function useKbOverview( kbId: number | null ): UseKbOverviewResult {
	const [ data, setData ] = useState< KbOverview | null >( null );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ error, setError ] = useState< Error | null >( null );
	const [ reloadToken, setReloadToken ] = useState( 0 );

	useEffect( () => {
		if ( ! kbId ) {
			setData( null );
			setIsLoading( false );
			setError( null );
			return;
		}

		let cancelled = false;
		setIsLoading( true );
		setError( null );

		apiFetch< KbOverview >( { path: `/itsdz/v1/kb/${ kbId }/overview` } )
			.then( ( result ) => {
				if ( ! cancelled ) {
					setData( result );
					setIsLoading( false );
				}
			} )
			.catch( ( err: unknown ) => {
				if ( ! cancelled ) {
					setError(
						err instanceof Error
							? err
							: new Error( 'Failed to load overview.' )
					);
					setIsLoading( false );
				}
			} );

		return () => {
			cancelled = true;
		};
	}, [ kbId, reloadToken ] );

	const refetch = useCallback( () => setReloadToken( ( n ) => n + 1 ), [] );

	return { data, isLoading, error, refetch };
}
