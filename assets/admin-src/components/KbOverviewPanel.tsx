import { Button } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { useCallback, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { api } from '../api';
import { store } from '../store';
import { useKbOverview } from '../hooks/useKbOverview';
import type {
	AttentionReason,
	KbOverview,
	OverviewAttentionItem,
	OverviewCounts,
	OverviewRecentItem,
} from '../hooks/useKbOverview';

interface Props {
	kbId: number;
}

const REASON_LABELS: Record< AttentionReason, string > = {
	draft: __( 'Draft', 'itsmanzur-docs' ),
	never_reviewed: __( 'Never reviewed', 'itsmanzur-docs' ),
	stale_review: __( 'Review overdue', 'itsmanzur-docs' ),
};

// Keeps the sidebar card from turning into a long, undifferentiated list on
// a fresh install where nothing has been reviewed yet.
const ATTENTION_VISIBLE_CAP = 5;

/**
 * "2 hours ago" / "3 days ago" — short and relative, since this list is
 * about recency, not exact timestamps.
 * @param isoDate
 */
function timeAgo( isoDate: string ): string {
	const ms = Date.now() - new Date( isoDate ).getTime();
	const minutes = Math.max( 0, Math.round( ms / 60000 ) );

	if ( minutes < 1 ) {
		return __( 'just now', 'itsmanzur-docs' );
	}
	if ( minutes < 60 ) {
		return sprintf(
			/* translators: %d: number of minutes. */
			__( '%dm ago', 'itsmanzur-docs' ),
			minutes
		);
	}
	const hours = Math.round( minutes / 60 );
	if ( hours < 24 ) {
		return sprintf(
			/* translators: %d: number of hours. */
			__( '%dh ago', 'itsmanzur-docs' ),
			hours
		);
	}
	const days = Math.round( hours / 24 );
	return sprintf(
		/* translators: %d: number of days. */
		__( '%dd ago', 'itsmanzur-docs' ),
		days
	);
}

function StatsCards( { counts }: { counts: OverviewCounts } ) {
	const cards: [ string, number ][] = [
		[ __( 'Total', 'itsmanzur-docs' ), counts.total ],
		[ __( 'Published', 'itsmanzur-docs' ), counts.published ],
		[ __( 'Draft', 'itsmanzur-docs' ), counts.draft ],
		[ __( 'Needs review', 'itsmanzur-docs' ), counts.needs_review ],
	];

	return (
		<div className="itsdz-overview-stats">
			{ cards.map( ( [ label, value ] ) => (
				<div className="itsdz-overview-stat" key={ label }>
					<strong>{ value }</strong>
					<span>{ label }</span>
				</div>
			) ) }
		</div>
	);
}

interface AttentionListProps {
	items: OverviewAttentionItem[];
	onMarkReviewed: () => void;
	isMarking: boolean;
}

function AttentionList( {
	items,
	onMarkReviewed,
	isMarking,
}: AttentionListProps ) {
	const visible = items.slice( 0, ATTENTION_VISIBLE_CAP );
	const hiddenCount = items.length - visible.length;
	// Marking "reviewed" only makes sense for articles that have actually
	// been reviewed and found current — a draft needs publishing, not a
	// review date, so it's excluded from the bulk action.
	const reviewableCount = items.filter(
		( item ) => item.reason !== 'draft'
	).length;

	return (
		<div className="itsdz-overview-card">
			<div className="itsdz-overview-card-heading">
				<h3>{ __( 'Needs attention', 'itsmanzur-docs' ) }</h3>
				{ reviewableCount > 0 && (
					<Button
						variant="link"
						onClick={ onMarkReviewed }
						disabled={ isMarking }
					>
						{ isMarking
							? __( 'Marking…', 'itsmanzur-docs' )
							: __( 'Mark all reviewed', 'itsmanzur-docs' ) }
					</Button>
				) }
			</div>
			<ul className="itsdz-overview-list">
				{ visible.map( ( item ) => (
					<li key={ item.id }>
						<a href={ item.edit_url }>
							{ item.title || __( 'Untitled article', 'itsmanzur-docs' ) }
						</a>
						<span
							className={ `itsdz-overview-reason itsdz-reason-${ item.reason }` }
						>
							{ REASON_LABELS[ item.reason ] }
						</span>
					</li>
				) ) }
			</ul>
			{ hiddenCount > 0 && (
				<p className="itsdz-overview-more">
					{ sprintf(
						/* translators: %d: number of additional articles not shown. */
						__( '+ %d more', 'itsmanzur-docs' ),
						hiddenCount
					) }
				</p>
			) }
		</div>
	);
}

function RecentList( { items }: { items: OverviewRecentItem[] } ) {
	if ( items.length === 0 ) {
		return null;
	}

	return (
		<div className="itsdz-overview-card">
			<h3>{ __( 'Recently edited', 'itsmanzur-docs' ) }</h3>
			<ul className="itsdz-overview-list">
				{ items.map( ( item ) => (
					<li key={ item.id }>
						<a href={ item.edit_url }>
							{ item.title || __( 'Untitled article', 'itsmanzur-docs' ) }
						</a>
						<span className="itsdz-overview-time">
							{ timeAgo( item.modified_gmt ) }
						</span>
					</li>
				) ) }
			</ul>
		</div>
	);
}

function OverviewSkeleton() {
	return (
		<div className="itsdz-overview-skeleton" aria-hidden="true">
			<div className="itsdz-overview-stats">
				{ [ 0, 1, 2, 3 ].map( ( i ) => (
					<div
						className="itsdz-overview-stat itsdz-skeleton-block"
						key={ i }
					/>
				) ) }
			</div>
			<div
				className="itsdz-overview-card itsdz-skeleton-block"
				style={ { height: '90px' } }
			/>
			<div
				className="itsdz-overview-card itsdz-skeleton-block"
				style={ { height: '90px' } }
			/>
		</div>
	);
}

function OverviewEmptyState() {
	return (
		<div className="itsdz-overview-empty">
			<span
				className="dashicons dashicons-chart-bar"
				aria-hidden="true"
			/>
			<p>
				{ __(
					'Write your first article to see stats here.',
					'itsmanzur-docs'
				) }
			</p>
		</div>
	);
}

function OverviewErrorState() {
	return (
		<div className="itsdz-overview-empty">
			<p>{ __( 'The overview could not be loaded.', 'itsmanzur-docs' ) }</p>
		</div>
	);
}

export function KbOverviewPanel( { kbId }: Props ) {
	const { data, isLoading, error, refetch } = useKbOverview( kbId );
	const { setNotice } = useDispatch( store );
	const [ isMarking, setIsMarking ] = useState( false );

	const markAllReviewed = useCallback( async () => {
		const targets = ( data?.attention ?? [] ).filter(
			( item ) => item.reason !== 'draft'
		);

		if ( targets.length === 0 ) {
			return;
		}

		setIsMarking( true );
		const today = new Date().toISOString().slice( 0, 10 );

		try {
			await Promise.all(
				targets.map( ( item ) =>
					api.updateArticle( item.id, { last_reviewed: today } )
				)
			);
			refetch();
			setNotice( {
				status: 'success',
				message: __( 'Selected articles marked reviewed.', 'itsmanzur-docs' ),
			} );
		} catch ( err ) {
			setNotice( {
				status: 'error',
				message:
					err instanceof Error
						? err.message
						: __( 'Marking articles reviewed failed.', 'itsmanzur-docs' ),
			} );
		} finally {
			setIsMarking( false );
		}
	}, [ data, refetch, setNotice ] );

	if ( isLoading ) {
		return <OverviewSkeleton />;
	}

	if ( error ) {
		return <OverviewErrorState />;
	}

	if ( ! data || data.counts.total === 0 ) {
		return <OverviewEmptyState />;
	}

	return (
		<div className="itsdz-kb-overview">
			<StatsCards counts={ data.counts } />
			{ data.attention.length > 0 && (
				<AttentionList
					items={ data.attention }
					onMarkReviewed={ () => void markAllReviewed() }
					isMarking={ isMarking }
				/>
			) }
			<RecentList items={ data.recent } />
		</div>
	);
}

// Re-exported so DocsManager (and anywhere else this panel is used) can type
// its own KB-selection state against the same shape the hook returns.
export type { KbOverview };
