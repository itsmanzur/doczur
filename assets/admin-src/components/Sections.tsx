import {
	Button,
	Card,
	CardBody,
	SelectControl,
	Spinner,
	TextControl,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { api } from '../api';
import { store } from '../store';
import type { Section } from '../types';

const ICON_PRESETS = [
	'dashicons-book',
	'dashicons-category',
	'dashicons-admin-generic',
	'dashicons-editor-help',
	'dashicons-lightbulb',
	'dashicons-admin-plugins',
];

function parseIcon( section: Section ) {
	return section.meta?._itsdz_section_icon || '';
}

export function Sections() {
	const sections = useSelect(
		( select ) => select( store ).getSections(),
		[]
	);
	const { setSections, setNotice } = useDispatch( store );
	const [ name, setName ] = useState( '' );
	const [ parent, setParent ] = useState( 0 );
	const [ icon, setIcon ] = useState( '' );
	const [ busy, setBusy ] = useState( false );
	const [ drafts, setDrafts ] = useState<
		Record< number, { name: string; parent: number; icon: string } >
	>( {} );

	const parentOptions = [
		{ label: __( 'Top level', 'itsmanzur-docs' ), value: '0' },
		...sections.map( ( section ) => ( {
			label: section.name,
			value: String( section.id ),
		} ) ),
	];

	const refresh = async () => {
		setSections( await api.listSections() );
	};

	const create = async () => {
		const title = name.trim();

		if ( ! title ) {
			return;
		}

		setBusy( true );
		try {
			await api.createSection( title, parent, {
				meta: { _itsdz_section_icon: icon },
			} );
			setName( '' );
			setParent( 0 );
			setIcon( '' );
			await refresh();
			setNotice( {
				status: 'success',
				message: __( 'Section created.', 'itsmanzur-docs' ),
			} );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error instanceof Error
						? error.message
						: __( 'Section could not be created.', 'itsmanzur-docs' ),
			} );
		} finally {
			setBusy( false );
		}
	};

	const save = async ( section: Section ) => {
		const draft = drafts[ section.id ] ?? {
			name: section.name,
			parent: section.parent,
			icon: parseIcon( section ),
		};

		setBusy( true );
		try {
			await api.updateSection( section.id, {
				name: draft.name.trim() || section.name,
				parent: draft.parent,
				meta: { _itsdz_section_icon: draft.icon },
			} );
			await refresh();
			setNotice( {
				status: 'success',
				message: __( 'Section saved.', 'itsmanzur-docs' ),
			} );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error instanceof Error
						? error.message
						: __( 'Section could not be saved.', 'itsmanzur-docs' ),
			} );
		} finally {
			setBusy( false );
		}
	};

	const remove = async ( section: Section ) => {
		// eslint-disable-next-line no-alert
		if (
			! window.confirm(
				__(
					'Delete this section? Articles stay published; they just become unsectioned.',
					'itsmanzur-docs'
				)
			)
		) {
			return;
		}

		setBusy( true );
		try {
			await api.deleteSection( section.id );
			await refresh();
			setNotice( {
				status: 'success',
				message: __( 'Section deleted.', 'itsmanzur-docs' ),
			} );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error instanceof Error
						? error.message
						: __( 'Section could not be deleted.', 'itsmanzur-docs' ),
			} );
		} finally {
			setBusy( false );
		}
	};

	const sorted = [ ...sections ].sort( ( left, right ) => {
		if ( left.parent !== right.parent ) {
			return left.parent - right.parent;
		}

		return left.name.localeCompare( right.name );
	} );

	return (
		<div className="itsdz-settings-container">
			<div className="itsdz-settings-header">
				<div>
					<h1>{ __( 'Sections', 'itsmanzur-docs' ) }</h1>
					<p>
						{ __(
							'Create, nest, and icon the folders that appear in the public sidebar.',
							'itsmanzur-docs'
						) }
					</p>
				</div>
			</div>

			<Card className="itsdz-settings-card">
				<CardBody>
					<div className="itsdz-settings-card-header">
						<span
							className="dashicons dashicons-plus-alt2"
							aria-hidden="true"
						/>
						<h2>{ __( 'Add section', 'itsmanzur-docs' ) }</h2>
					</div>
					<div className="itsdz-section-create">
						<TextControl
							label={ __( 'Name', 'itsmanzur-docs' ) }
							value={ name }
							onChange={ setName }
						/>
						<SelectControl
							label={ __( 'Parent', 'itsmanzur-docs' ) }
							value={ String( parent ) }
							options={ parentOptions }
							onChange={ ( value ) =>
								setParent( Number.parseInt( value, 10 ) || 0 )
							}
						/>
						<TextControl
							label={ __( 'Dashicon class', 'itsmanzur-docs' ) }
							value={ icon }
							onChange={ setIcon }
							placeholder="dashicons-book"
						/>
						<Button
							variant="primary"
							onClick={ () => void create() }
							disabled={ busy || ! name.trim() }
						>
							{ busy && <Spinner /> }{ ' ' }
							{ __( 'Add section', 'itsmanzur-docs' ) }
						</Button>
					</div>
					<div className="itsdz-icon-presets" aria-label={ __( 'Icon presets', 'itsmanzur-docs' ) }>
						{ ICON_PRESETS.map( ( preset ) => (
							<button
								key={ preset }
								type="button"
								className={ `itsdz-icon-preset ${
									icon === preset ? 'is-active' : ''
								}` }
								onClick={ () => setIcon( preset ) }
								title={ preset }
							>
								<span className={ `dashicons ${ preset }` } />
							</button>
						) ) }
					</div>
				</CardBody>
			</Card>

			<div className="itsdz-section-manager-list">
				{ sorted.map( ( section ) => {
					const draft = drafts[ section.id ] ?? {
						name: section.name,
						parent: section.parent,
						icon: parseIcon( section ),
					};

					return (
						<Card key={ section.id } className="itsdz-settings-card">
							<CardBody>
								<div
									className="itsdz-section-edit-row"
									style={ {
										paddingInlineStart: section.parent
											? 18
											: 0,
									} }
								>
									<TextControl
										label={ __( 'Name', 'itsmanzur-docs' ) }
										value={ draft.name }
										onChange={ ( value ) =>
											setDrafts( ( current ) => ( {
												...current,
												[ section.id ]: {
													...draft,
													name: value,
												},
											} ) )
										}
									/>
									<SelectControl
										label={ __( 'Parent', 'itsmanzur-docs' ) }
										value={ String( draft.parent ) }
										options={ parentOptions.filter(
											( option ) =>
												option.value !==
												String( section.id )
										) }
										onChange={ ( value ) =>
											setDrafts( ( current ) => ( {
												...current,
												[ section.id ]: {
													...draft,
													parent:
														Number.parseInt(
															value,
															10
														) || 0,
												},
											} ) )
										}
									/>
									<TextControl
										label={ __( 'Icon', 'itsmanzur-docs' ) }
										value={ draft.icon }
										onChange={ ( value ) =>
											setDrafts( ( current ) => ( {
												...current,
												[ section.id ]: {
													...draft,
													icon: value,
												},
											} ) )
										}
									/>
									<div className="itsdz-section-edit-actions">
										<Button
											variant="secondary"
											onClick={ () =>
												void save( section )
											}
											disabled={ busy }
										>
											{ __( 'Save', 'itsmanzur-docs' ) }
										</Button>
										<Button
											variant="tertiary"
											isDestructive
											onClick={ () =>
												void remove( section )
											}
											disabled={ busy }
										>
											{ __( 'Delete', 'itsmanzur-docs' ) }
										</Button>
									</div>
								</div>
							</CardBody>
						</Card>
					);
				} ) }
				{ ! sorted.length && (
					<p className="itsdz-settings-card-intro">
						{ __(
							'No sections yet. Add Getting Started or FAQ to start grouping articles.',
							'itsmanzur-docs'
						) }
					</p>
				) }
			</div>
		</div>
	);
}
