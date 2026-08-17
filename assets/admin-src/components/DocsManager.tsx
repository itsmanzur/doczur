import {
	DndContext,
	KeyboardSensor,
	PointerSensor,
	closestCenter,
	useSensor,
	useSensors,
	type DragEndEvent,
} from '@dnd-kit/core';
import {
	SortableContext,
	arrayMove,
	sortableKeyboardCoordinates,
	useSortable,
	verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import {
	Button,
	Card,
	CardBody,
	SelectControl,
	Spinner,
	TextControl,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { api } from '../api';
import { getStaleness } from '../staleness';
import { store } from '../store';
import type { Article, Project } from '../types';
import { KbOverviewPanel } from './KbOverviewPanel';

interface ManagerProps {
	project: Project;
}

/**
 * Relative to wp-admin/, same as the current admin.php?page=doczur screen —
 * this is the only editing surface for an article's content and metadata
 * from this point on, so every row navigates straight into it.
 * @param articleId
 */
function editUrl( articleId: number ): string {
	return `post.php?post=${ articleId }&action=edit`;
}

interface SortableArticleProps {
	article: Article;
	checked: boolean;
	sectionName: string;
	onCheck: ( checked: boolean ) => void;
	onDuplicate: () => void;
	onToggleStatus: () => void;
	onDelete: () => void;
	viewCount?: number;
}

function SortableArticle( {
	article,
	checked,
	sectionName,
	onCheck,
	onDuplicate,
	onToggleStatus,
	onDelete,
	viewCount,
}: SortableArticleProps ) {
	const {
		attributes,
		listeners,
		setNodeRef,
		transform,
		transition,
		isDragging,
	} = useSortable( { id: article.id } );

	const isPublished = article.status === 'publish';
	const staleness = getStaleness( article );

	return (
		<div
			ref={ setNodeRef }
			style={ {
				transform: CSS.Transform.toString( transform ),
				transition,
			} }
			className={ `itsdz-tree-item${ isDragging ? ' is-dragging' : '' }` }
		>
			<input
				type="checkbox"
				className="itsdz-tree-check"
				checked={ checked }
				onChange={ ( event ) => onCheck( event.target.checked ) }
				aria-label={ __( 'Select article for bulk action', 'doczur' ) }
			/>
			<button
				type="button"
				className="itsdz-drag-handle"
				aria-label={ __( 'Reorder article', 'doczur' ) }
				{ ...attributes }
				{ ...listeners }
			>
				<span className="dashicons dashicons-menu" aria-hidden="true" />
			</button>
			<a className="itsdz-tree-select" href={ editUrl( article.id ) }>
				<strong>
					{ article.title || __( 'Untitled article', 'doczur' ) }
					{ !! viewCount && (
						<span
							className="itsdz-view-badge"
							title={ sprintf(
								/* translators: %d: number of page views. */
								__( '%d views', 'doczur' ),
								viewCount
							) }
						>
							<span
								className="dashicons dashicons-visibility"
								aria-hidden="true"
							/>
							{ viewCount }
						</span>
					) }
				</strong>
				<span className="itsdz-tree-meta">
					<span className="itsdz-tree-section-tag">
						{ sectionName || __( 'Unsectioned', 'doczur' ) }
					</span>
					<span
						className={ `itsdz-status-pill ${
							isPublished ? 'is-published' : 'is-draft'
						}` }
					>
						{ isPublished
							? __( 'Published', 'doczur' )
							: __( 'Draft', 'doczur' ) }
					</span>
					{ staleness?.isStale && (
						<span
							className="itsdz-stale-pill"
							title={ staleness.label }
						>
							{ __( 'Needs review', 'doczur' ) }
						</span>
					) }
				</span>
			</a>
			<div className="itsdz-tree-actions">
				<a
					href={ editUrl( article.id ) }
					title={ __( 'Edit Article', 'doczur' ) }
					aria-label={ __( 'Edit article', 'doczur' ) }
				>
					<span
						className="dashicons dashicons-edit"
						aria-hidden="true"
					/>
				</a>
				<a
					href={ article.url }
					target="_blank"
					rel="noopener noreferrer"
					title={ __( 'Preview Article', 'doczur' ) }
					aria-label={ __( 'Preview article', 'doczur' ) }
				>
					<span
						className="dashicons dashicons-external"
						aria-hidden="true"
					/>
				</a>
				<button
					type="button"
					onClick={ onToggleStatus }
					title={
						isPublished
							? __( 'Switch to Draft', 'doczur' )
							: __( 'Publish Article', 'doczur' )
					}
					aria-label={
						isPublished
							? __( 'Move article to drafts', 'doczur' )
							: __( 'Publish article', 'doczur' )
					}
				>
					<span
						className={ `dashicons ${
							isPublished
								? 'dashicons-hidden'
								: 'dashicons-visibility'
						}` }
						aria-hidden="true"
					/>
				</button>
				<button
					type="button"
					onClick={ onDuplicate }
					title={ __( 'Duplicate Article', 'doczur' ) }
					aria-label={ __( 'Duplicate article', 'doczur' ) }
				>
					<span
						className="dashicons dashicons-admin-page"
						aria-hidden="true"
					/>
				</button>
				<button
					type="button"
					onClick={ onDelete }
					title={ __( 'Delete Article', 'doczur' ) }
					aria-label={ __( 'Delete article', 'doczur' ) }
					className="itsdz-delete-btn"
				>
					<span
						className="dashicons dashicons-trash"
						aria-hidden="true"
					/>
				</button>
			</div>
		</div>
	);
}

export function DocsManager( { project }: ManagerProps ) {
	const { articles, sections } = useSelect(
		( select ) => ( {
			articles: select( store ).getArticles(),
			sections: select( store ).getSections(),
		} ),
		[]
	);
	const { setArticles, setNotice } = useDispatch( store );
	const [ creating, setCreating ] = useState( false );
	const [ selectedForBulk, setSelectedForBulk ] = useState< number[] >( [] );
	const [ bulkSectionId, setBulkSectionId ] = useState( 0 );
	const [ bulkBusy, setBulkBusy ] = useState( false );
	const [ viewTotals, setViewTotals ] = useState< Record< number, number > >(
		{}
	);

	useEffect( () => {
		apiFetch< { articles: Record< number, number > } >( {
			path: `/itsdz/v1/analytics/views?kb_id=${ project.id }`,
		} )
			.then( ( res ) => setViewTotals( res.articles ?? {} ) )
			.catch( () => {} );
	}, [ project.id ] );

	const sensors = useSensors(
		useSensor( PointerSensor, { activationConstraint: { distance: 6 } } ),
		useSensor( KeyboardSensor, {
			coordinateGetter: sortableKeyboardCoordinates,
		} )
	);
	const sectionNames = useMemo(
		() =>
			new Map(
				sections.map( ( section ) => [ section.id, section.name ] )
			),
		[ sections ]
	);

	const createArticle = useCallback( async () => {
		setCreating( true );
		try {
			const article = await api.createArticle( {
				kb_id: project.id,
				title: __( 'Untitled article', 'doczur' ),
				status: 'draft',
				menu_order: articles.length,
			} );
			setArticles( [ ...articles, article ] );
			window.location.href = editUrl( article.id );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error instanceof Error
						? error.message
						: __( 'Article creation failed.', 'doczur' ),
			} );
			setCreating( false );
		}
	}, [ articles, project.id, setArticles, setNotice ] );

	const toggleStatus = async ( article: Article ) => {
		try {
			const saved = await api.updateArticle( article.id, {
				status: article.status === 'publish' ? 'draft' : 'publish',
			} );
			setArticles(
				articles.map( ( item ) =>
					item.id === saved.id ? saved : item
				)
			);
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error instanceof Error
						? error.message
						: __( 'Status update failed.', 'doczur' ),
			} );
		}
	};

	const duplicateArticle = async ( article: Article ) => {
		try {
			const duplicate = await api.createArticle( {
				kb_id: project.id,
				title: `${ article.title } ${ __( '(Copy)', 'doczur' ) }`,
				content: article.content,
				status: 'draft',
				menu_order: articles.length,
				section_ids: article.section_ids,
				tag_ids: article.tag_ids,
			} );
			setArticles( [ ...articles, duplicate ] );
			window.location.href = editUrl( duplicate.id );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error instanceof Error
						? error.message
						: __( 'Article duplication failed.', 'doczur' ),
			} );
		}
	};

	const [ treeSearch, setTreeSearch ] = useState( '' );
	const [ sectionFilter, setSectionFilter ] = useState< number | null >(
		null
	);

	const filteredArticles = useMemo( () => {
		let list = articles;

		if ( null !== sectionFilter ) {
			list = list.filter( ( article ) =>
				article.section_ids.includes( sectionFilter )
			);
		}

		const term = treeSearch.trim().toLowerCase();
		if ( term ) {
			list = list.filter( ( article ) =>
				article.title.toLowerCase().includes( term )
			);
		}

		return list;
	}, [ articles, treeSearch, sectionFilter ] );

	const bulkMove = async () => {
		if ( ! selectedForBulk.length ) {
			return;
		}

		setBulkBusy( true );
		try {
			const moved = await Promise.all(
				articles
					.filter( ( article ) =>
						selectedForBulk.includes( article.id )
					)
					.map( ( article ) =>
						api.updateArticle( article.id, {
							section_ids: bulkSectionId ? [ bulkSectionId ] : [],
						} )
					)
			);
			const movedById = new Map(
				moved.map( ( article ) => [ article.id, article ] )
			);
			setArticles(
				articles.map(
					( article ) => movedById.get( article.id ) ?? article
				)
			);
			setSelectedForBulk( [] );
			setNotice( {
				status: 'success',
				message: __( 'Selected articles moved.', 'doczur' ),
			} );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error instanceof Error
						? error.message
						: __( 'Bulk move failed.', 'doczur' ),
			} );
		} finally {
			setBulkBusy( false );
		}
	};

	const bulkSetStatus = async ( status: 'publish' | 'draft' ) => {
		if ( ! selectedForBulk.length ) {
			return;
		}

		setBulkBusy( true );
		try {
			const updated = await Promise.all(
				articles
					.filter( ( article ) =>
						selectedForBulk.includes( article.id )
					)
					.map( ( article ) =>
						api.updateArticle( article.id, { status } )
					)
			);
			const updatedById = new Map(
				updated.map( ( article ) => [ article.id, article ] )
			);
			setArticles(
				articles.map(
					( article ) => updatedById.get( article.id ) ?? article
				)
			);
			setSelectedForBulk( [] );
			setNotice( {
				status: 'success',
				message:
					status === 'publish'
						? __( 'Selected articles published.', 'doczur' )
						: __( 'Selected articles set to draft.', 'doczur' ),
			} );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error instanceof Error
						? error.message
						: __( 'Bulk status update failed.', 'doczur' ),
			} );
		} finally {
			setBulkBusy( false );
		}
	};

	useEffect( () => {
		const shortcut = ( event: KeyboardEvent ) => {
			if (
				( event.ctrlKey || event.metaKey ) &&
				event.key.toLowerCase() === 'n'
			) {
				event.preventDefault();
				void createArticle();
			}
		};
		window.addEventListener( 'keydown', shortcut );
		return () => window.removeEventListener( 'keydown', shortcut );
	}, [ createArticle ] );

	const dragEnd = async ( event: DragEndEvent ) => {
		if ( ! event.over || event.active.id === event.over.id ) {
			return;
		}

		const oldIndex = articles.findIndex(
			( article ) => article.id === event.active.id
		);
		const newIndex = articles.findIndex(
			( article ) => article.id === event.over?.id
		);
		const reordered = arrayMove( articles, oldIndex, newIndex ).map(
			( article, index ) => ( {
				...article,
				menu_order: index,
			} )
		);
		setArticles( reordered );

		try {
			await api.reorderArticles( project.id, reordered );
		} catch ( error ) {
			setArticles( articles );
			setNotice( {
				status: 'error',
				message:
					error instanceof Error
						? error.message
						: __( 'Reordering failed.', 'doczur' ),
			} );
		}
	};

	const deleteArticle = async ( article: Article ) => {
		if (
			// eslint-disable-next-line no-alert
			! window.confirm(
				__( 'Are you sure you want to delete this article?', 'doczur' )
			)
		) {
			return;
		}
		try {
			await api.deleteArticle( article.id );
			setArticles(
				articles.filter( ( item ) => item.id !== article.id )
			);
			setNotice( {
				status: 'success',
				message: __( 'Article deleted.', 'doczur' ),
			} );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error instanceof Error
						? error.message
						: __( 'Delete failed.', 'doczur' ),
			} );
		}
	};

	const bulkDelete = async () => {
		if ( ! selectedForBulk.length ) {
			return;
		}
		if (
			// eslint-disable-next-line no-alert
			! window.confirm(
				__(
					'Are you sure you want to delete selected articles?',
					'doczur'
				)
			)
		) {
			return;
		}

		setBulkBusy( true );
		try {
			await Promise.all(
				selectedForBulk.map( ( id ) => api.deleteArticle( id ) )
			);
			setArticles(
				articles.filter(
					( article ) => ! selectedForBulk.includes( article.id )
				)
			);
			setSelectedForBulk( [] );
			setNotice( {
				status: 'success',
				message: __( 'Selected articles deleted.', 'doczur' ),
			} );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error instanceof Error
						? error.message
						: __( 'Bulk delete failed.', 'doczur' ),
			} );
		} finally {
			setBulkBusy( false );
		}
	};

	return (
		<div className="itsdz-doc-manager">
			<Card className="itsdz-tree-panel">
				<CardBody>
					<div className="itsdz-panel-heading">
						<div>
							<h1>{ project.title }</h1>
							<p>
								{ articles.length }{ ' ' }
								{ __( 'articles', 'doczur' ) }
							</p>
						</div>
						<Button
							variant="primary"
							onClick={ () => void createArticle() }
							disabled={ creating }
						>
							{ creating ? <Spinner /> : '+' }{ ' ' }
							{ __( 'New', 'doczur' ) }
						</Button>
					</div>
					<div className="itsdz-tree-search-bar">
						<TextControl
							placeholder={ __( 'Search tree…', 'doczur' ) }
							value={ treeSearch }
							onChange={ setTreeSearch }
						/>
					</div>
					<div
						className="itsdz-section-summary"
						role="group"
						aria-label={ __( 'Filter by section', 'doczur' ) }
					>
						<button
							type="button"
							className={ `itsdz-section-chip${
								null === sectionFilter ? ' is-active' : ''
							}` }
							onClick={ () => setSectionFilter( null ) }
						>
							{ __( 'All', 'doczur' ) }
						</button>
						{ sections.map( ( section ) => (
							<button
								type="button"
								key={ section.id }
								className={ `itsdz-section-chip${
									sectionFilter === section.id
										? ' is-active'
										: ''
								}` }
								style={ {
									marginInlineStart: `${
										section.parent ? 12 : 0
									}px`,
								} }
								onClick={ () => setSectionFilter( section.id ) }
							>
								{ section.parent ? '↳ ' : '' }
								{ section.name }
							</button>
						) ) }
					</div>
					{ selectedForBulk.length > 0 && (
						<div className="itsdz-bulk-actions" role="group">
							<SelectControl
								label={ __( 'Move selected to', 'doczur' ) }
								value={ String( bulkSectionId ) }
								onChange={ ( value ) =>
									setBulkSectionId( Number( value ) )
								}
								options={ [
									{
										label: __( 'Unsectioned', 'doczur' ),
										value: '0',
									},
									...sections.map( ( section ) => ( {
										label: section.name,
										value: String( section.id ),
									} ) ),
								] }
							/>
							<div
								className="itsdz-bulk-btn-group"
								style={ {
									display: 'flex',
									gap: '4px',
									marginTop: '6px',
									flexWrap: 'wrap',
								} }
							>
								<Button
									variant="secondary"
									onClick={ () => void bulkMove() }
									disabled={ bulkBusy }
								>
									{ bulkBusy && <Spinner /> }
									{ __( 'Move', 'doczur' ) }
								</Button>
								<Button
									variant="secondary"
									onClick={ () =>
										void bulkSetStatus( 'publish' )
									}
									disabled={ bulkBusy }
								>
									{ __( 'Publish', 'doczur' ) }
								</Button>
								<Button
									variant="secondary"
									onClick={ () =>
										void bulkSetStatus( 'draft' )
									}
									disabled={ bulkBusy }
								>
									{ __( 'Draft', 'doczur' ) }
								</Button>
								<Button
									variant="secondary"
									isDestructive
									onClick={ () => void bulkDelete() }
									disabled={ bulkBusy }
								>
									{ __( 'Delete', 'doczur' ) }
								</Button>
								<Button
									variant="tertiary"
									onClick={ () => setSelectedForBulk( [] ) }
								>
									{ __( 'Clear', 'doczur' ) }
								</Button>
							</div>
						</div>
					) }
					<DndContext
						sensors={ sensors }
						collisionDetection={ closestCenter }
						onDragEnd={ dragEnd }
					>
						<SortableContext
							items={ filteredArticles.map(
								( article ) => article.id
							) }
							strategy={ verticalListSortingStrategy }
						>
							<div className="itsdz-tree-list">
								{ articles.length === 0 && (
									<div className="itsdz-tree-empty">
										<span
											className="dashicons dashicons-media-document"
											aria-hidden="true"
										/>
										<p>
											{ __(
												'No articles yet.',
												'doczur'
											) }
										</p>
										<Button
											variant="secondary"
											onClick={ () =>
												void createArticle()
											}
											disabled={ creating }
										>
											{ __(
												'Write your first article',
												'doczur'
											) }
										</Button>
									</div>
								) }
								{ articles.length > 0 &&
									filteredArticles.length === 0 && (
										<div className="itsdz-tree-empty">
											<p>
												{ null !== sectionFilter
													? __(
															'No articles in this section.',
															'doczur'
													  )
													: __(
															'No articles match your search.',
															'doczur'
													  ) }
											</p>
											<Button
												variant="tertiary"
												onClick={ () => {
													setTreeSearch( '' );
													setSectionFilter( null );
												} }
											>
												{ __(
													'Clear filters',
													'doczur'
												) }
											</Button>
										</div>
									) }
								{ filteredArticles.map( ( article ) => (
									<SortableArticle
										key={ article.id }
										article={ article }
										checked={ selectedForBulk.includes(
											article.id
										) }
										sectionName={
											sectionNames.get(
												article.section_ids[ 0 ]
											) ?? ''
										}
										onCheck={ ( checked ) =>
											setSelectedForBulk( ( current ) =>
												checked
													? [ ...current, article.id ]
													: current.filter(
															( id ) =>
																id !==
																article.id
													  )
											)
										}
										onDuplicate={ () =>
											void duplicateArticle( article )
										}
										onToggleStatus={ () =>
											void toggleStatus( article )
										}
										onDelete={ () =>
											void deleteArticle( article )
										}
										viewCount={ viewTotals[ article.id ] }
									/>
								) ) }
							</div>
						</SortableContext>
					</DndContext>
				</CardBody>
			</Card>
			<Card className="itsdz-overview-panel">
				<CardBody>
					<KbOverviewPanel kbId={ project.id } />
				</CardBody>
			</Card>
		</div>
	);
}
