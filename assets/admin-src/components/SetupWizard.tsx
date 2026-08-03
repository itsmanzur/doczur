import {
	Button,
	Card,
	CardBody,
	Notice,
	SelectControl,
	Spinner,
	TextControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { api } from '../api';
import type { Project } from '../types';

const STARTERS: Record< string, string[] > = {
	software: [
		'Getting Started',
		'Installation',
		'Configuration',
		'Features',
		'FAQ',
		'Changelog',
	],
	'wp-plugin': [
		'Getting Started',
		'Installation',
		'Settings',
		'How-to Guides',
		'FAQ',
		'Changelog',
	],
	'wp-theme': [
		'Getting Started',
		'Installation',
		'Customization',
		'Templates',
		'FAQ',
		'Changelog',
	],
	'physical-product': [
		'Overview',
		'Setup',
		'Using the Product',
		'Care & Maintenance',
		'Troubleshooting',
		'FAQ',
	],
	internal: [
		'Welcome',
		'Policies',
		'Processes',
		'Tools',
		'Team Resources',
		'FAQ',
	],
	blank: [ 'Getting Started' ],
};

interface Props {
	onComplete: () => Promise< void >;
	onCancel?: () => void;
}

export function SetupWizard( { onComplete, onCancel }: Props ) {
	const [ step, setStep ] = useState( 1 );
	const [ docType, setDocType ] = useState( 'software' );
	const [ name, setName ] = useState( '' );
	const [ slug, setSlug ] = useState( '' );
	const [ color, setColor ] = useState( '#3858e9' );
	const [ themeMode, setThemeMode ] =
		useState< Project[ 'meta' ][ '_itsdz_kb_theme_mode' ] >( 'system' );
	const [ template, setTemplate ] = useState( 'clean' );
	const [ sections, setSections ] = useState( STARTERS.software );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( '' );
	const stepClassName = ( number: number ) => {
		if ( number === step ) {
			return 'is-current';
		}
		return number < step ? 'is-done' : '';
	};

	const chooseType = ( value: string ) => {
		setDocType( value );
		setSections( [ ...( STARTERS[ value ] ?? STARTERS.blank ) ] );
	};

	const finish = async () => {
		setSaving( true );
		setError( '' );
		try {
			await api.createProject( {
				title: name,
				status: 'publish',
				meta: {
					_itsdz_kb_brand_color: color,
					_itsdz_kb_theme_mode: themeMode,
					_itsdz_kb_template: template,
					_itsdz_kb_doc_type: docType,
					_itsdz_kb_slug_base: slug,
					_itsdz_kb_layout_mode: 'canvas',
				},
			} );
			await Promise.all(
				sections
					.map( ( section ) => section.trim() )
					.filter( Boolean )
					.map( ( section ) => api.createSection( section ) )
			);
			await onComplete();
		} catch ( caught ) {
			setError(
				caught instanceof Error
					? caught.message
					: __(
							'The documentation project could not be created.',
							'doczur'
					  )
			);
		} finally {
			setSaving( false );
		}
	};

	return (
		<div className="itsdz-wizard">
			<div className="itsdz-wizard-heading">
				<span className="itsdz-brand-mark">D</span>
				<div>
					<h1>
						{ __( 'Create your documentation portal', 'doczur' ) }
					</h1>
					<p>
						{ __(
							'Five focused steps. Your first portal will be live in minutes.',
							'doczur'
						) }
					</p>
				</div>
			</div>

			<ol
				className="itsdz-stepper"
				aria-label={ __( 'Setup progress', 'doczur' ) }
			>
				{ [ 1, 2, 3, 4, 5 ].map( ( number ) => (
					<li key={ number } className={ stepClassName( number ) }>
						<span>{ number }</span>
						{
							/* translators: %d: setup step number. */
							sprintf( __( 'Step %d', 'doczur' ), number )
						}
					</li>
				) ) }
			</ol>

			<Card className="itsdz-wizard-card">
				<CardBody>
					{ error && (
						<Notice status="error" isDismissible={ false }>
							{ error }
						</Notice>
					) }
					{ step === 1 && (
						<div>
							<h2>
								{ __( 'What are you documenting?', 'doczur' ) }
							</h2>
							<p>
								{ __(
									'We will prepare a useful starter structure for your product.',
									'doczur'
								) }
							</p>
							<div className="itsdz-choice-grid">
								{ [
									[
										'software',
										__( 'Software / SaaS', 'doczur' ),
									],
									[
										'wp-plugin',
										__( 'WordPress Plugin', 'doczur' ),
									],
									[
										'wp-theme',
										__( 'WordPress Theme', 'doczur' ),
									],
									[
										'physical-product',
										__( 'Physical Product', 'doczur' ),
									],
									[
										'internal',
										__( 'Internal Docs', 'doczur' ),
									],
									[ 'blank', __( 'Start Blank', 'doczur' ) ],
								].map( ( [ value, label ] ) => (
									<button
										type="button"
										key={ value }
										className={
											docType === value
												? 'is-selected'
												: ''
										}
										onClick={ () => chooseType( value ) }
										aria-pressed={ docType === value }
									>
										{ label }
									</button>
								) ) }
							</div>
						</div>
					) }

					{ step === 2 && (
						<div className="itsdz-form-stack">
							<h2>{ __( 'Make it yours', 'doczur' ) }</h2>
							<TextControl
								label={ __( 'Documentation name', 'doczur' ) }
								value={ name }
								onChange={ setName }
								required
							/>
							<TextControl
								label={ __( 'URL slug', 'doczur' ) }
								value={ slug }
								onChange={ setSlug }
								help={ __( 'Example: product-name', 'doczur' ) }
							/>
							<label
								className="itsdz-color-control"
								htmlFor="itsdz-wizard-brand-color"
							>
								<span>{ __( 'Brand color', 'doczur' ) }</span>
								<input
									id="itsdz-wizard-brand-color"
									type="color"
									value={ color }
									onChange={ ( event ) =>
										setColor( event.target.value )
									}
								/>
							</label>
							<SelectControl
								label={ __( 'Default color mode', 'doczur' ) }
								value={ themeMode }
								onChange={ ( value ) =>
									setThemeMode(
										value as Project[ 'meta' ][ '_itsdz_kb_theme_mode' ]
									)
								}
								options={ [
									{
										label: __(
											'Follow visitor system',
											'doczur'
										),
										value: 'system',
									},
									{
										label: __( 'Light', 'doczur' ),
										value: 'light',
									},
									{
										label: __( 'Dark', 'doczur' ),
										value: 'dark',
									},
								] }
							/>
						</div>
					) }

					{ step === 3 && (
						<div>
							<h2>
								{ __(
									'Shape the starter structure',
									'doczur'
								) }
							</h2>
							<p>
								{ __(
									'Rename, remove, or add sections before publishing.',
									'doczur'
								) }
							</p>
							<div className="itsdz-section-editor">
								{ sections.map( ( section, index ) => (
									<div
										key={ `${ index }-${ section }` }
										className="itsdz-section-row"
									>
										<TextControl
											label={ sprintf(
												/* translators: %d: section position. */
												__( 'Section %d', 'doczur' ),
												index + 1
											) }
											value={ section }
											onChange={ ( value ) =>
												setSections(
													sections.map(
														( item, itemIndex ) =>
															itemIndex === index
																? value
																: item
													)
												)
											}
										/>
										<Button
											variant="tertiary"
											isDestructive
											onClick={ () =>
												setSections(
													sections.filter(
														( _, itemIndex ) =>
															itemIndex !== index
													)
												)
											}
										>
											{ __( 'Remove', 'doczur' ) }
										</Button>
									</div>
								) ) }
								<Button
									variant="secondary"
									onClick={ () =>
										setSections( [
											...sections,
											__( 'New Section', 'doczur' ),
										] )
									}
								>
									{ __( 'Add section', 'doczur' ) }
								</Button>
							</div>
						</div>
					) }

					{ step === 4 && (
						<div>
							<h2>
								{ __( 'Choose a starting look', 'doczur' ) }
							</h2>
							<div className="itsdz-template-grid">
								{ [ 'clean', 'modern', 'compact' ].map(
									( value ) => (
										<button
											type="button"
											key={ value }
											onClick={ () =>
												setTemplate( value )
											}
											className={
												template === value
													? 'is-selected'
													: ''
											}
											aria-pressed={ template === value }
										>
											<span
												className={ `itsdz-template-preview is-${ value }` }
											/>
											<strong>
												{ value
													.charAt( 0 )
													.toUpperCase() +
													value.slice( 1 ) }
											</strong>
										</button>
									)
								) }
							</div>
						</div>
					) }

					{ step === 5 && (
						<div className="itsdz-publish-summary">
							<h2>{ __( 'Ready to publish', 'doczur' ) }</h2>
							<p>
								{ __(
									'Doczur will create your project and starter sections now.',
									'doczur'
								) }
							</p>
							<dl>
								<dt>{ __( 'Name', 'doczur' ) }</dt>
								<dd>{ name }</dd>
								<dt>{ __( 'Type', 'doczur' ) }</dt>
								<dd>{ docType }</dd>
								<dt>{ __( 'Template', 'doczur' ) }</dt>
								<dd>{ template }</dd>
								<dt>{ __( 'Sections', 'doczur' ) }</dt>
								<dd>{ sections.filter( Boolean ).length }</dd>
							</dl>
						</div>
					) }

					<div className="itsdz-wizard-actions">
						<div>
							{ step > 1 && (
								<Button
									variant="tertiary"
									onClick={ () => setStep( step - 1 ) }
								>
									{ __( 'Back', 'doczur' ) }
								</Button>
							) }
							{ step === 1 && onCancel && (
								<Button variant="tertiary" onClick={ onCancel }>
									{ __( 'Cancel', 'doczur' ) }
								</Button>
							) }
						</div>
						{ step < 5 ? (
							<Button
								variant="primary"
								onClick={ () => setStep( step + 1 ) }
								disabled={ step === 2 && ! name.trim() }
							>
								{ __( 'Continue', 'doczur' ) }
							</Button>
						) : (
							<Button
								variant="primary"
								onClick={ finish }
								disabled={ saving || ! name.trim() }
							>
								{ saving && <Spinner /> }
								{ __( 'Publish documentation', 'doczur' ) }
							</Button>
						) }
					</div>
				</CardBody>
			</Card>
		</div>
	);
}
