import {
	Button,
	Card,
	CardBody,
	CheckboxControl,
	Spinner,
	TextControl,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { api } from '../api';
import { store } from '../store';
import type { Project } from '../types';

export function Settings( { project }: { project: Project } ) {
	const projects = useSelect(
		( select ) => select( store ).getProjects(),
		[]
	);
	const { setProjects, setNotice } = useDispatch( store );
	const [ name, setName ] = useState( project.title );
	const [ slug, setSlug ] = useState(
		project.meta._itsdz_kb_slug_base || project.slug
	);
	const [ color, setColor ] = useState(
		project.meta._itsdz_kb_brand_color || '#3858e9'
	);
	const [ themeMode, setThemeMode ] = useState(
		project.meta._itsdz_kb_theme_mode || 'system'
	);
	const [ template, setTemplate ] = useState(
		project.meta._itsdz_kb_template || 'clean'
	);
	const [ layoutMode, setLayoutMode ] = useState(
		project.meta._itsdz_kb_layout_mode || 'canvas'
	);
	const [ saving, setSaving ] = useState( false );
	const [ sampleExists, setSampleExists ] = useState( false );
	const [ sampleBusy, setSampleBusy ] = useState( false );
	const [ deleteDataOnUninstall, setDeleteDataOnUninstall ] =
		useState( false );
	const [ deleteDataSaving, setDeleteDataSaving ] = useState( false );

	useEffect( () => {
		let cancelled = false;

		api.getSampleDataStatus( project.id )
			.then( ( result ) => {
				if ( ! cancelled ) {
					setSampleExists( result.exists );
				}
			} )
			.catch( () => {
				// Non-critical: the card still offers to generate content.
			} );

		return () => {
			cancelled = true;
		};
	}, [ project.id ] );

	useEffect( () => {
		let cancelled = false;

		api.getSettings()
			.then( ( result ) => {
				if ( ! cancelled ) {
					setDeleteDataOnUninstall( result.delete_data_on_uninstall );
				}
			} )
			.catch( () => {
				// Non-critical: the checkbox just stays at its default (off).
			} );

		return () => {
			cancelled = true;
		};
	}, [] );

	const toggleDeleteDataOnUninstall = async ( checked: boolean ) => {
		setDeleteDataOnUninstall( checked );
		setDeleteDataSaving( true );
		try {
			await api.updateSettings( { delete_data_on_uninstall: checked } );
		} catch ( error ) {
			setDeleteDataOnUninstall( ! checked );
			setNotice( {
				status: 'error',
				message:
					error instanceof Error
						? error.message
						: __( 'This setting could not be saved.', 'doczur' ),
			} );
		} finally {
			setDeleteDataSaving( false );
		}
	};

	const generateSample = async () => {
		setSampleBusy( true );
		try {
			const result = await api.createSampleData( project.id );
			setSampleExists( true );
			setNotice( {
				status: 'success',
				message: sprintf(
					/* translators: 1: number of articles, 2: number of sections. */
					__( 'Added %1$d sample articles across %2$d sections.', 'doczur' ),
					result.articles,
					result.sections
				),
			} );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error instanceof Error
						? error.message
						: __( 'Sample content could not be created.', 'doczur' ),
			} );
		} finally {
			setSampleBusy( false );
		}
	};

	const removeSample = async () => {
		// eslint-disable-next-line no-alert
		const confirmed = window.confirm(
			__(
				'This permanently deletes the sample articles, including any edits you made to them. Continue?',
				'doczur'
			)
		);

		if ( ! confirmed ) {
			return;
		}

		setSampleBusy( true );
		try {
			const result = await api.removeSampleData( project.id );
			setSampleExists( false );
			setNotice( {
				status: 'success',
				message: sprintf(
					/* translators: %d: number of removed articles. */
					__( 'Removed %d sample articles.', 'doczur' ),
					result.articles
				),
			} );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error instanceof Error
						? error.message
						: __( 'Sample content could not be removed.', 'doczur' ),
			} );
		} finally {
			setSampleBusy( false );
		}
	};

	const save = async () => {
		setSaving( true );
		try {
			const saved = await api.updateProject( project.id, {
				title: name,
				meta: {
					_itsdz_kb_slug_base: slug,
					_itsdz_kb_brand_color: color,
					_itsdz_kb_theme_mode: themeMode,
					_itsdz_kb_template: template,
					_itsdz_kb_layout_mode: layoutMode,
				},
			} );
			setProjects(
				projects.map( ( item ) =>
					item.id === saved.id ? saved : item
				)
			);
			setNotice( {
				status: 'success',
				message: __( 'Documentation settings saved.', 'doczur' ),
			} );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error instanceof Error
						? error.message
						: __( 'Settings could not be saved.', 'doczur' ),
			} );
		} finally {
			setSaving( false );
		}
	};

	const presetColors = [ '#3858e9', '#0d9488', '#2563eb', '#7c3aed', '#db2777', '#ea580c', '#16a34a' ];

	return (
		<div className="itsdz-settings-container">
			<div className="itsdz-settings-header">
				<div>
					<h1>{ __( 'Documentation Settings', 'doczur' ) }</h1>
					<p>{ __( 'Customize identity, layout, colors, and global display preferences.', 'doczur' ) }</p>
				</div>
				<Button
					variant="primary"
					onClick={ () => void save() }
					disabled={ saving || ! name.trim() }
				>
					{ saving && <Spinner /> }{ ' ' }
					{ __( 'Save Settings', 'doczur' ) }
				</Button>
			</div>

			<div className="itsdz-settings-sections">
				{ /* Section 1: Project Identity & Permalinks */ }
				<Card className="itsdz-settings-card">
					<CardBody>
						<div className="itsdz-settings-card-header">
							<span className="dashicons dashicons-admin-generic" aria-hidden="true" />
							<h2>{ __( 'Identity & Permalinks', 'doczur' ) }</h2>
						</div>
						<div className="itsdz-form-grid-2">
							<TextControl
								label={ __( 'PROJECT NAME', 'doczur' ) }
								value={ name }
								onChange={ setName }
								placeholder={ __( 'Documentation project title', 'doczur' ) }
							/>
							<div>
								<TextControl
									label={ __( 'URL SLUG BASE', 'doczur' ) }
									value={ slug }
									onChange={ setSlug }
									placeholder={ __( 'docs', 'doczur' ) }
								/>
								<div className="itsdz-setting-permalink-hint">
									<span>{ __( 'Base URL:', 'doczur' ) } <code>{ window.location.origin }/{ slug || 'docs' }/</code></span>
								</div>
							</div>
						</div>
					</CardBody>
				</Card>

				{ /* Section 2: Branding & Color Accent */ }
				<Card className="itsdz-settings-card">
					<CardBody>
						<div className="itsdz-settings-card-header">
							<span className="dashicons dashicons-art" aria-hidden="true" />
							<h2>{ __( 'Branding & Color Accent', 'doczur' ) }</h2>
						</div>
						<div className="itsdz-color-picker-box">
							<label className="itsdz-field-label" htmlFor="itsdz-settings-color-input">
								{ __( 'PRIMARY BRAND COLOR', 'doczur' ) }
							</label>
							<div className="itsdz-color-picker-controls">
								<input
									id="itsdz-settings-color-input"
									type="color"
									className="itsdz-color-swatch-input"
									value={ color }
									onChange={ ( event ) => setColor( event.target.value ) }
								/>
								<input
									type="text"
									className="itsdz-color-hex-input"
									value={ color }
									onChange={ ( event ) => setColor( event.target.value ) }
									placeholder="#3858e9"
								/>
								<div className="itsdz-color-presets">
									{ presetColors.map( ( hex ) => (
										<button
											key={ hex }
											type="button"
											className={ `itsdz-preset-btn ${ color.toLowerCase() === hex ? 'is-active' : '' }` }
											style={ { background: hex } }
											onClick={ () => setColor( hex ) }
											aria-label={ `Select color ${ hex }` }
										/>
									) ) }
								</div>
							</div>
						</div>
					</CardBody>
				</Card>

				{ /* Section 3: Visual Theme & Appearance */ }
				<Card className="itsdz-settings-card">
					<CardBody>
						<div className="itsdz-settings-card-header">
							<span className="dashicons dashicons-desktop" aria-hidden="true" />
							<h2>{ __( 'Appearance & Color Mode', 'doczur' ) }</h2>
						</div>
						<div className="itsdz-visual-choice-group">
							<label className="itsdz-field-label">{ __( 'COLOR MODE PREFERENCE', 'doczur' ) }</label>
							<div className="itsdz-visual-grid-3">
								<button
									type="button"
									className={ `itsdz-choice-card ${ themeMode === 'system' ? 'is-selected' : '' }` }
									onClick={ () => setThemeMode( 'system' ) }
								>
									<span className="dashicons dashicons-admin-settings" aria-hidden="true" />
									<strong>{ __( 'System Auto', 'doczur' ) }</strong>
									<span>{ __( 'Matches visitor’s OS setting', 'doczur' ) }</span>
								</button>
								<button
									type="button"
									className={ `itsdz-choice-card ${ themeMode === 'light' ? 'is-selected' : '' }` }
									onClick={ () => setThemeMode( 'light' ) }
								>
									<span className="dashicons dashicons-day" aria-hidden="true" />
									<strong>{ __( 'Light Mode', 'doczur' ) }</strong>
									<span>{ __( 'Always clean light theme', 'doczur' ) }</span>
								</button>
								<button
									type="button"
									className={ `itsdz-choice-card ${ themeMode === 'dark' ? 'is-selected' : '' }` }
									onClick={ () => setThemeMode( 'dark' ) }
								>
									<span className="dashicons dashicons-night" aria-hidden="true" />
									<strong>{ __( 'Dark Mode', 'doczur' ) }</strong>
									<span>{ __( 'Always sleek dark theme', 'doczur' ) }</span>
								</button>
							</div>
						</div>
					</CardBody>
				</Card>

				{ /* Section 4: Page Shell & Template Style */ }
				<Card className="itsdz-settings-card">
					<CardBody>
						<div className="itsdz-settings-card-header">
							<span className="dashicons dashicons-layout" aria-hidden="true" />
							<h2>{ __( 'Layout & Template Style', 'doczur' ) }</h2>
						</div>

						<div className="itsdz-visual-choice-group">
							<label className="itsdz-field-label">{ __( 'PAGE SHELL LAYOUT', 'doczur' ) }</label>
							<div className="itsdz-visual-grid-2">
								<button
									type="button"
									className={ `itsdz-choice-card ${ layoutMode === 'canvas' ? 'is-selected' : '' }` }
									onClick={ () => setLayoutMode( 'canvas' ) }
								>
									<span className="dashicons dashicons-welcome-view-site" aria-hidden="true" />
									<strong>{ __( 'Doczur Canvas', 'doczur' ) }</strong>
									<span>{ __( 'Standalone full-screen portal, zero theme conflicts', 'doczur' ) }</span>
								</button>
								<button
									type="button"
									className={ `itsdz-choice-card ${ layoutMode === 'theme' ? 'is-selected' : '' }` }
									onClick={ () => setLayoutMode( 'theme' ) }
								>
									<span className="dashicons dashicons-align-center" aria-hidden="true" />
									<strong>{ __( 'Active Theme Integration', 'doczur' ) }</strong>
									<span>{ __( 'Integrates inside your active WordPress theme header & footer', 'doczur' ) }</span>
								</button>
							</div>
						</div>

						<div className="itsdz-visual-choice-group" style={ { marginTop: '22px' } }>
							<label className="itsdz-field-label">{ __( 'TEMPLATE STYLE', 'doczur' ) }</label>
							<div className="itsdz-visual-grid-3">
								<button
									type="button"
									className={ `itsdz-choice-card ${ template === 'clean' ? 'is-selected' : '' }` }
									onClick={ () => setTemplate( 'clean' ) }
								>
									<span className="dashicons dashicons-category" aria-hidden="true" />
									<strong>{ __( 'Clean', 'doczur' ) }</strong>
									<span>{ __( 'Minimalist, content-focused layout', 'doczur' ) }</span>
								</button>
								<button
									type="button"
									className={ `itsdz-choice-card ${ template === 'modern' ? 'is-selected' : '' }` }
									onClick={ () => setTemplate( 'modern' ) }
								>
									<span className="dashicons dashicons-superhero" aria-hidden="true" />
									<strong>{ __( 'Modern', 'doczur' ) }</strong>
									<span>{ __( 'Vibrant hero header & rich cards', 'doczur' ) }</span>
								</button>
								<button
									type="button"
									className={ `itsdz-choice-card ${ template === 'compact' ? 'is-selected' : '' }` }
									onClick={ () => setTemplate( 'compact' ) }
								>
									<span className="dashicons dashicons-excerpt-view" aria-hidden="true" />
									<strong>{ __( 'Compact', 'doczur' ) }</strong>
									<span>{ __( 'Dense sidebar layout for large docs', 'doczur' ) }</span>
								</button>
							</div>
						</div>
					</CardBody>
				</Card>

				{ /* Section 5: Data & Privacy */ }
				<Card className="itsdz-settings-card">
					<CardBody>
						<div className="itsdz-settings-card-header">
							<span className="dashicons dashicons-privacy" aria-hidden="true" />
							<h2>{ __( 'Data & Privacy', 'doczur' ) }</h2>
						</div>

						<CheckboxControl
							label={ __(
								'Delete all Doczur data when the plugin is uninstalled',
								'doczur'
							) }
							help={ __(
								'Off by default. Removes your documentation projects, articles, and settings only when you actually delete the plugin — not on deactivation.',
								'doczur'
							) }
							checked={ deleteDataOnUninstall }
							disabled={ deleteDataSaving }
							onChange={ ( checked: boolean ) =>
								void toggleDeleteDataOnUninstall( checked )
							}
						/>
					</CardBody>
				</Card>

				{ /* Section 6: Sample Content */ }
				<Card className="itsdz-settings-card">
					<CardBody>
						<div className="itsdz-settings-card-header">
							<span className="dashicons dashicons-welcome-add-page" aria-hidden="true" />
							<h2>{ __( 'Sample Content', 'doczur' ) }</h2>
						</div>

						<p className="itsdz-settings-card-intro">
							{ sampleExists
								? __(
										'This project contains generated sample articles. Remove them once you have finished exploring — your own articles are never touched.',
										'doczur'
								  )
								: __(
										'Publishes eight ready-made articles across three sections so you can see a finished documentation site immediately. Every formatting feature is demonstrated, and you can delete it all in one click.',
										'doczur'
								  ) }
						</p>

						<div className="itsdz-sample-actions">
							<Button
								variant={ sampleExists ? 'secondary' : 'primary' }
								onClick={ () => void generateSample() }
								disabled={ sampleBusy }
							>
								{ sampleBusy && <Spinner /> }{ ' ' }
								{ sampleExists
									? __( 'Regenerate sample content', 'doczur' )
									: __( 'Generate sample content', 'doczur' ) }
							</Button>
							{ sampleExists && (
								<Button
									variant="tertiary"
									isDestructive
									onClick={ () => void removeSample() }
									disabled={ sampleBusy }
								>
									{ __( 'Remove sample content', 'doczur' ) }
								</Button>
							) }
						</div>
					</CardBody>
				</Card>

				<div className="itsdz-settings-footer-actions">
					<Button
						variant="primary"
						onClick={ () => void save() }
						disabled={ saving || ! name.trim() }
					>
						{ saving && <Spinner /> }{ ' ' }
						{ __( 'Save Settings', 'doczur' ) }
					</Button>
				</div>
			</div>
		</div>
	);
}

