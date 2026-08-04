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

	const isPublished = article.status === 'publish';

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
				<span className="dashicons dashicons-menu" aria-hidden="true" />
			</button>
			<button
				type="button"
				className="itsdz-tree-select"
				onClick={ onSelect }
			>
				<strong>
					{ article.title || __( 'Untitled article', 'doczur' ) }
				</strong>
				<span className="itsdz-tree-meta">
					<span className="itsdz-tree-section-tag">
						{ sectionName || __( 'Unsectioned', 'doczur' ) }
					</span>
					<span className={ `itsdz-status-pill ${ isPublished ? 'is-published' : 'is-draft' }` }>
						{ isPublished ? __( 'Published', 'doczur' ) : __( 'Draft', 'doczur' ) }
					</span>
				</span>
			</button>
			<div className="itsdz-tree-actions">
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
					<span className={ `dashicons ${ isPublished ? 'dashicons-hidden' : 'dashicons-visibility' }` } aria-hidden="true" />
				</button>
				<button
					type="button"
					onClick={ onDuplicate }
					title={ __( 'Duplicate Article', 'doczur' ) }
					aria-label={ __( 'Duplicate article', 'doczur' ) }
				>
					<span className="dashicons dashicons-admin-page" aria-hidden="true" />
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
	const [ editorMode, setEditorMode ] = useState< 'edit' | 'preview' >( 'edit' );
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
		setEditorMode( 'edit' );
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

	// Calculate word count & estimated reading time.
	const wordCount = useMemo( () => {
		const plainText = content.replace( /<[^>]*>/g, ' ' ).trim();
		return plainText ? plainText.split( /\s+/ ).length : 0;
	}, [ content ] );

	const readingTime = useMemo( () => {
		return Math.max( 1, Math.ceil( wordCount / 200 ) );
	}, [ wordCount ] );

	// Quick Formatting Helper for Textarea insertion.
	const insertFormat = ( openTag: string, closeTag: string = '' ) => {
		const textarea = document.querySelector< HTMLTextAreaElement >(
			'.itsdz-content-editor textarea'
		);
		if ( ! textarea ) {
			setContent( ( prev ) => prev + openTag + closeTag );
			setDirty( true );
			return;
		}

		const start = textarea.selectionStart;
		const end = textarea.selectionEnd;
		const selectedText = content.substring( start, end );
		const replacement = openTag + ( selectedText || 'text' ) + closeTag;

		const newContent =
			content.substring( 0, start ) + replacement + content.substring( end );
		setContent( newContent );
		setDirty( true );

		window.setTimeout( () => {
			textarea.focus();
			textarea.setSelectionRange(
				start + openTag.length,
				start + openTag.length + ( selectedText || 'text' ).length
			);
		}, 10 );
	};

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

	const nativeGutenbergUrl = `post.php?post=${ article.id }&action=edit`;

	return (
		<div className="itsdz-editor">
			<div className="itsdz-editor-toolbar">
				<div>
					<span
						className={ `itsdz-save-badge ${
							saving ? 'is-saving' : dirty ? 'is-dirty' : 'is-saved'
						}` }
					>
						{ saving ? (
							<Spinner />
						) : (
							<span className={ `dashicons ${ dirty ? 'dashicons-edit' : 'dashicons-saved' }` } aria-hidden="true" />
						) }
						<span>{ saveState }</span>
					</span>
				</div>
				<div>
					<Button
						variant="tertiary"
						href={ nativeGutenbergUrl }
						target="_blank"
						title={ __( 'Edit with WordPress Block Editor (Gutenberg)', 'doczur' ) }
					>
						<span className="dashicons dashicons-wordpress" aria-hidden="true" style={ { marginInlineEnd: '4px', fontSize: '15px', width: '15px', height: '15px' } } />
						{ __( 'Gutenberg Editor', 'doczur' ) }
					</Button>
					<Button
						variant="secondary"
						href={ article.url }
						target="_blank"
					>
						<span className="dashicons dashicons-external" aria-hidden="true" style={ { marginInlineEnd: '4px', fontSize: '15px', width: '15px', height: '15px' } } />
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

			<div className="itsdz-editor-form-group">
				<TextControl
					className="itsdz-title-input"
					label={ __( 'ARTICLE TITLE', 'doczur' ) }
					value={ title }
					onChange={ change( setTitle ) }
					placeholder={ __( 'Enter article title…', 'doczur' ) }
				/>
				{ article.url && (
					<div className="itsdz-permalink-preview">
						<span className="dashicons dashicons-admin-links" aria-hidden="true" />
						<span>{ article.url }</span>
					</div>
				) }

				<div className="itsdz-editor-meta">
					<SelectControl
						label={ __( 'STATUS', 'doczur' ) }
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
						label={ __( 'SECTION', 'doczur' ) }
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

				<div className="itsdz-content-editor-wrapper">
					<div className="itsdz-content-header">
						<label className="itsdz-content-label">{ __( 'ARTICLE CONTENT', 'doczur' ) }</label>
						<div className="itsdz-editor-mode-toggle">
							<button
								type="button"
								className={ editorMode === 'edit' ? 'is-active' : '' }
								onClick={ () => setEditorMode( 'edit' ) }
							>
								<span className="dashicons dashicons-editor-code" aria-hidden="true" />
								{ __( 'Write / HTML', 'doczur' ) }
							</button>
							<button
								type="button"
								className={ editorMode === 'preview' ? 'is-active' : '' }
								onClick={ () => setEditorMode( 'preview' ) }
							>
								<span className="dashicons dashicons-visibility" aria-hidden="true" />
								{ __( 'Live Preview', 'doczur' ) }
							</button>
						</div>
					</div>

					{ editorMode === 'edit' && (
						<>
							<div className="itsdz-formatting-toolbar" role="toolbar" aria-label={ __( 'Formatting options', 'doczur' ) }>
								<button type="button" onClick={ () => insertFormat( '<strong>', '</strong>' ) } title={ __( 'Bold', 'doczur' ) }>
									<strong>B</strong>
								</button>
								<button type="button" onClick={ () => insertFormat( '<em>', '</em>' ) } title={ __( 'Italic', 'doczur' ) }>
									<em>I</em>
								</button>
								<button type="button" onClick={ () => insertFormat( '<h2>', '</h2>' ) } title={ __( 'Heading 2', 'doczur' ) }>
									H2
								</button>
								<button type="button" onClick={ () => insertFormat( '<h3>', '</h3>' ) } title={ __( 'Heading 3', 'doczur' ) }>
									H3
								</button>
								<button type="button" onClick={ () => insertFormat( '<ul>\n  <li>', '</li>\n</ul>' ) } title={ __( 'Bullet List', 'doczur' ) }>
									<span className="dashicons dashicons-editor-ul" aria-hidden="true" />
								</button>
								<button type="button" onClick={ () => insertFormat( '<ol>\n  <li>', '</li>\n</ol>' ) } title={ __( 'Numbered List', 'doczur' ) }>
									<span className="dashicons dashicons-editor-ol" aria-hidden="true" />
								</button>
								<button type="button" onClick={ () => insertFormat( '<pre><code>', '</code></pre>' ) } title={ __( 'Code Block', 'doczur' ) }>
									&lt;/&gt;
								</button>
								<button type="button" onClick={ () => insertFormat( '<blockquote>', '</blockquote>' ) } title={ __( 'Quote', 'doczur' ) }>
									<span className="dashicons dashicons-editor-quote" aria-hidden="true" />
								</button>
								<button type="button" onClick={ () => insertFormat( '<div class="itsdz-callout">\n  ', '\n</div>' ) } title={ __( 'Callout / Alert Box', 'doczur' ) }>
									<span className="dashicons dashicons-info" aria-hidden="true" />
								</button>
								<button type="button" onClick={ () => insertFormat( '<a href="https://">', '</a>' ) } title={ __( 'Insert Link', 'doczur' ) }>
									<span className="dashicons dashicons-admin-links" aria-hidden="true" />
								</button>
							</div>

							<TextareaControl
								className="itsdz-content-editor"
								value={ content }
								onChange={ change( setContent ) }
								rows={ 18 }
								placeholder={ __( 'Write your article content here (HTML and formatting tags supported)…', 'doczur' ) }
							/>
						</>
					) }

					{ editorMode === 'preview' && (
						<div className="itsdz-live-preview-box">
							{ content ? (
								<div
									className="itsdz-article-content-rendered"
									dangerouslySetInnerHTML={ { __html: content } }
								/>
							) : (
								<p className="itsdz-preview-placeholder">
									{ __( 'No content to preview yet. Switch to Write mode to add text.', 'doczur' ) }
								</p>
							) }
						</div>
					) }

					<div className="itsdz-content-footer">
						<span className="itsdz-content-help">
							{ __( 'HTML & formatting supported. Gutenberg editing is also available natively.', 'doczur' ) }
						</span>
						<div className="itsdz-content-stats">
							<span>📝 { wordCount } { __( 'words', 'doczur' ) }</span>
							<span>⏱ { readingTime } { __( 'min read', 'doczur' ) }</span>
						</div>
					</div>
				</div>
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
