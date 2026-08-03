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

	return (
		<Card className="itsdz-settings-card">
			<CardBody>
				<h1>{ __( 'Documentation settings', 'doczur' ) }</h1>
				<p>
					{ __(
						'Control the project identity and default visual experience.',
						'doczur'
					) }
				</p>
				<div className="itsdz-form-stack">
					<TextControl
						label={ __( 'Project name', 'doczur' ) }
						value={ name }
						onChange={ setName }
					/>
					<TextControl
						label={ __( 'URL slug', 'doczur' ) }
						value={ slug }
						onChange={ setSlug }
					/>
					<label
						className="itsdz-color-control"
						htmlFor="itsdz-settings-brand-color"
					>
						<span>{ __( 'Brand color', 'doczur' ) }</span>
						<input
							id="itsdz-settings-brand-color"
							type="color"
							value={ color }
							onChange={ ( event ) =>
								setColor( event.target.value )
							}
						/>
					</label>
					<SelectControl
						label={ __( 'Color mode', 'doczur' ) }
						value={ themeMode }
						onChange={ ( value ) =>
							setThemeMode(
								value as Project[ 'meta' ][ '_itsdz_kb_theme_mode' ]
							)
						}
						options={ [
							{
								label: __( 'System', 'doczur' ),
								value: 'system',
							},
							{ label: __( 'Light', 'doczur' ), value: 'light' },
							{ label: __( 'Dark', 'doczur' ), value: 'dark' },
						] }
					/>
					<SelectControl
						label={ __( 'Page shell', 'doczur' ) }
						value={ layoutMode }
						onChange={ ( value ) =>
							setLayoutMode(
								value as Project[ 'meta' ][ '_itsdz_kb_layout_mode' ]
							)
						}
						options={ [
							{
								label: __( 'Doczur canvas', 'doczur' ),
								value: 'canvas',
							},
							{
								label: __(
									'Theme header and footer',
									'doczur'
								),
								value: 'theme',
							},
						] }
					/>
					<SelectControl
						label={ __( 'Template', 'doczur' ) }
						value={ template }
						onChange={ ( value ) =>
							setTemplate(
								value as Project[ 'meta' ][ '_itsdz_kb_template' ]
							)
						}
						options={ [
							{ label: __( 'Clean', 'doczur' ), value: 'clean' },
							{
								label: __( 'Modern', 'doczur' ),
								value: 'modern',
							},
							{
								label: __( 'Compact', 'doczur' ),
								value: 'compact',
							},
						] }
					/>
					<Button
						variant="primary"
						onClick={ () => void save() }
						disabled={ saving || ! name.trim() }
					>
						{ saving && <Spinner /> }{ ' ' }
						{ __( 'Save settings', 'doczur' ) }
					</Button>
				</div>
			</CardBody>
		</Card>
	);
}
