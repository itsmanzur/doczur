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
	TextareaControl,
	TextControl,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { api } from '../api';
import { store } from '../store';
import type { Article, Project, Section } from '../types';

interface ManagerProps {
	project: Project;
}

interface SortableArticleProps {
	article: Article;
	active: boolean;
	checked: boolean;
	sectionName: string;
	onSelect: () => void;
	onCheck: ( checked: boolean ) => void;
	onDuplicate: () => void;
	onToggleStatus: () => void;
}

function SortableArticle( {
	article,
	active,
	checked,
	sectionName,
	onSelect,
	onCheck,
	onDuplicate,
	onToggleStatus,
}: SortableArticleProps ) {
	const {
		attributes,
		listeners,
		setNodeRef,
		transform,
		transition,
		isDragging,
	} = useSortable( { id: article.id } );

	return (
		<div
			ref={ setNodeRef }
			style={ {
				transform: CSS.Transform.toString( transform ),
				transition,
			} }
			className={ `itsdz-tree-item${ active ? ' is-active' : '' }${
				isDragging ? ' is-dragging' : ''
			}` }
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
				<span aria-hidden="true">⋮⋮</span>
			</button>
			<button
				type="button"
				className="itsdz-tree-select"
				onClick={ onSelect }
			>
				<strong>
					{ article.title || __( 'Untitled article', 'doczur' ) }
				</strong>
				<span>
					{ sectionName || __( 'Unsectioned', 'doczur' ) } ·{ ' ' }
					{ article.status }
				</span>
			</button>
			<div className="itsdz-tree-actions">
				<button
					type="button"
					onClick={ onToggleStatus }
					aria-label={
						article.status === 'publish'
							? __( 'Move article to drafts', 'doczur' )
							: __( 'Publish article', 'doczur' )
					}
				>
					{ article.status === 'publish' ? '●' : '○' }
				</button>
				<button
					type="button"
					onClick={ onDuplicate }
					aria-label={ __( 'Duplicate article', 'doczur' ) }
				>
					⧉
				</button>
			</div>
		</div>
	);
}

interface EditorProps {
	article: Article | null;
	sections: Section[];
	onSaved: ( article: Article ) => void;
}

function ArticleEditor( { article, sections, onSaved }: EditorProps ) {
	const [ title, setTitle ] = useState( '' );
	const [ content, setContent ] = useState( '' );
	const [ status, setStatus ] = useState< 'draft' | 'publish' >( 'draft' );
	const [ sectionId, setSectionId ] = useState( 0 );
	const [ dirty, setDirty ] = useState( false );
	const [ saving, setSaving ] = useState( false );
	let saveState: string = __( 'Saved', 'doczur' );
	if ( saving ) {
		saveState = __( 'Saving…', 'doczur' );
	} else if ( dirty ) {
		saveState = __( 'Unsaved changes', 'doczur' );
	}

	useEffect( () => {
		setTitle( article?.title ?? '' );
		setContent( article?.content ?? '' );
		setStatus( article?.status ?? 'draft' );
		setSectionId( article?.section_ids[ 0 ] ?? 0 );
		setDirty( false );
	}, [ article ] );

	const save = useCallback( async () => {
		if ( ! article || ! dirty || saving ) {
			return;
		}

		setSaving( true );
		try {
			const saved = await api.updateArticle( article.id, {
				title,
				content,
				status,
				section_ids: sectionId ? [ sectionId ] : [],
			} );
			onSaved( saved );
			setDirty( false );
		} finally {
			setSaving( false );
		}
	}, [ article, content, dirty, onSaved, saving, sectionId, status, title ] );

	useEffect( () => {
		if ( ! dirty ) {
			return undefined;
		}

		const timeout = window.setTimeout( () => void save(), 1500 );
		return () => window.clearTimeout( timeout );
	}, [ dirty, save ] );

	useEffect( () => {
		const warn = ( event: BeforeUnloadEvent ) => {
			if ( dirty ) {
				event.preventDefault();
			}
		};
		window.addEventListener( 'beforeunload', warn );
		return () => window.removeEventListener( 'beforeunload', warn );
	}, [ dirty ] );

	useEffect( () => {
		const shortcut = ( event: KeyboardEvent ) => {
			if (
				( event.ctrlKey || event.metaKey ) &&
				event.key.toLowerCase() === 's'
			) {
				event.preventDefault();
				void save();
			}
		};
		window.addEventListener( 'keydown', shortcut );
		return () => window.removeEventListener( 'keydown', shortcut );
	}, [ save ] );

	if ( ! article ) {
		return (
			<div className="itsdz-empty-editor">
				<h2>{ __( 'Choose an article', 'doczur' ) }</h2>
				<p>
					{ __(
						'Select an article from the documentation tree or create a new one.',
						'doczur'
					) }
				</p>
			</div>
		);
	}

	const change =
		( setter: ( value: string ) => void ) => ( value: string ) => {
			setter( value );
			setDirty( true );
		};

	return (
		<div className="itsdz-editor">
			<div className="itsdz-editor-toolbar">
				<div>
					<span
						className={ `itsdz-save-state ${
							dirty ? 'is-dirty' : ''
						}` }
					>
						{ saveState }
					</span>
				</div>
				<div>
					<Button
						variant="secondary"
						href={ article.url }
						target="_blank"
					>
						{ __( 'Preview', 'doczur' ) }
					</Button>
					<Button
						variant="primary"
						onClick={ () => void save() }
						disabled={ ! dirty || saving }
					>
						{ saving && <Spinner /> } { __( 'Save', 'doczur' ) }
					</Button>
				</div>
			</div>
			<TextControl
				className="itsdz-title-input"
				label={ __( 'Article title', 'doczur' ) }
				value={ title }
				onChange={ change( setTitle ) }
			/>
			<div className="itsdz-editor-meta">
				<SelectControl
					label={ __( 'Status', 'doczur' ) }
					value={ status }
					onChange={ ( value: 'draft' | 'publish' ) => {
						setStatus( value );
						setDirty( true );
					} }
					options={ [
						{ label: __( 'Draft', 'doczur' ), value: 'draft' },
						{
							label: __( 'Published', 'doczur' ),
							value: 'publish',
						},
					] }
				/>
				<SelectControl
					label={ __( 'Section', 'doczur' ) }
					value={ String( sectionId ) }
					onChange={ ( value ) => {
						setSectionId( Number( value ) );
						setDirty( true );
					} }
					options={ [
						{ label: __( 'Unsectioned', 'doczur' ), value: '0' },
						...sections.map( ( section ) => ( {
							label: section.name,
							value: String( section.id ),
						} ) ),
					] }
				/>
			</div>
			<TextareaControl
				className="itsdz-content-editor"
				label={ __( 'Article content', 'doczur' ) }
				help={ __(
					'HTML is supported. Gutenberg editing will remain available from the native post editor.',
					'doczur'
				) }
				value={ content }
				onChange={ change( setContent ) }
				rows={ 22 }
			/>
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
	const [ selectedId, setSelectedId ] = useState< number | null >(
		articles[ 0 ]?.id ?? null
	);
	const [ creating, setCreating ] = useState( false );
	const [ selectedForBulk, setSelectedForBulk ] = useState< number[] >( [] );
	const [ bulkSectionId, setBulkSectionId ] = useState( 0 );
	const [ bulkBusy, setBulkBusy ] = useState( false );
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
	const selected =
		articles.find( ( article ) => article.id === selectedId ) ?? null;

	useEffect( () => {
		if (
			selectedId &&
			! articles.some( ( article ) => article.id === selectedId )
		) {
			setSelectedId( articles[ 0 ]?.id ?? null );
		}
	}, [ articles, selectedId ] );

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
			setSelectedId( article.id );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error instanceof Error
						? error.message
						: __( 'Article creation failed.', 'doczur' ),
			} );
		} finally {
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
			setSelectedId( duplicate.id );
			setNotice( {
				status: 'success',
				message: __( 'Article duplicated as a draft.', 'doczur' ),
			} );
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

	const updateSaved = ( saved: Article ) => {
		setArticles(
			articles.map( ( article ) =>
				article.id === saved.id ? saved : article
			)
		);
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
					<div
						className="itsdz-section-summary"
						aria-label={ __( 'Documentation sections', 'doczur' ) }
					>
						{ sections.map( ( section ) => (
							<span
								key={ section.id }
								style={ {
									marginInlineStart: `${
										section.parent ? 12 : 0
									}px`,
								} }
							>
								{ section.parent ? '↳ ' : '' }
								{ section.name }
							</span>
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
							<Button
								variant="secondary"
								onClick={ () => void bulkMove() }
								disabled={ bulkBusy }
							>
								{ bulkBusy && <Spinner /> }
								{ __( 'Move', 'doczur' ) }
							</Button>
							<Button
								variant="tertiary"
								onClick={ () => setSelectedForBulk( [] ) }
							>
								{ __( 'Clear', 'doczur' ) }
							</Button>
						</div>
					) }
					<DndContext
						sensors={ sensors }
						collisionDetection={ closestCenter }
						onDragEnd={ dragEnd }
					>
						<SortableContext
							items={ articles.map( ( article ) => article.id ) }
							strategy={ verticalListSortingStrategy }
						>
							<div className="itsdz-tree-list">
								{ articles.map( ( article ) => (
									<SortableArticle
										key={ article.id }
										article={ article }
										active={ article.id === selectedId }
										checked={ selectedForBulk.includes(
											article.id
										) }
										sectionName={
											sectionNames.get(
												article.section_ids[ 0 ]
											) ?? ''
										}
										onSelect={ () =>
											setSelectedId( article.id )
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
									/>
								) ) }
							</div>
						</SortableContext>
					</DndContext>
				</CardBody>
			</Card>
			<Card className="itsdz-editor-panel">
				<CardBody>
					<ArticleEditor
						article={ selected }
						sections={ sections }
						onSaved={ updateSaved }
					/>
				</CardBody>
			</Card>
		</div>
	);
}
