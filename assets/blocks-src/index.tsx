/**
 * Editor UI for the Doczur blocks.
 *
 * The blocks themselves render server-side (`render.php`), so each edit
 * component only has to expose the attributes — the frontend markup is never
 * duplicated here.
 */
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	PanelBody,
	SelectControl,
	TextControl,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';
import { RichText, useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { registerBlockType, type BlockConfiguration } from '@wordpress/blocks';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import searchMetadata from '../../includes/Frontend/Blocks/search/block.json';
import docsListMetadata from '../../includes/Frontend/Blocks/article-list/block.json';
import popularMetadata from '../../includes/Frontend/Blocks/popular-docs/block.json';
import faqMetadata from '../../includes/Frontend/Blocks/faq/block.json';
import glossaryMetadata from '../../includes/Frontend/Blocks/glossary/block.json';
import calloutMetadata from '../../includes/Frontend/Blocks/callout/block.json';

/**
 * block.json is the single source of truth for names, attributes and defaults.
 * TypeScript widens the imported JSON to plain strings, so the metadata is cast
 * back to the shape registerBlockType expects.
 */
type BlockMetadata = BlockConfiguration< Record< string, unknown > >;

interface KnowledgeBase {
	id: number;
	title: string;
}

interface FaqItem {
	question: string;
	answer: string;
}

/**
 * Editor-only placeholder styling. Kept inline so the blocks do not need a
 * second stylesheet just for the edit view.
 */
const previewStyle: React.CSSProperties = {
	padding: '16px',
	color: '#50575e',
	fontSize: '14px',
	background: '#f7f8fb',
	border: '1px dashed #c8cdd6',
	borderRadius: '8px',
};

const faqItemStyle: React.CSSProperties = {
	padding: '12px',
	marginBottom: '12px',
	background: '#fff',
	border: '1px solid #dcdcde',
	borderRadius: '6px',
};

/**
 * Only labels the dropdown — the block's own stylesheet
 * (`.itsdz-callout-*` in frontend-src/style.css, loaded into the editor via
 * block.json's `style` field) draws the actual colour and icon, so there is
 * nothing here for those to drift out of sync with.
 */
const CALLOUT_VARIANTS = [
	{ value: 'info', label: __( 'Info', 'doczur' ), icon: 'ℹ️' },
	{ value: 'tip', label: __( 'Tip', 'doczur' ), icon: '💡' },
	{ value: 'warning', label: __( 'Warning', 'doczur' ), icon: '⚠️' },
	{ value: 'danger', label: __( 'Danger', 'doczur' ), icon: '🚫' },
] as const;

/**
 * Load the available knowledge bases once per editor session.
 */
function useKnowledgeBases() {
	const [ options, setOptions ] = useState< { label: string; value: string }[] >( [
		{ label: __( 'Select a knowledge base…', 'doczur' ), value: '0' },
	] );

	useEffect( () => {
		let cancelled = false;

		apiFetch< KnowledgeBase[] >( { path: '/itsdz/v1/kb' } )
			.then( ( items ) => {
				if ( cancelled ) {
					return;
				}
				setOptions( [
					{ label: __( 'Select a knowledge base…', 'doczur' ), value: '0' },
					...items.map( ( kb ) => ( {
						label: kb.title || __( '(untitled)', 'doczur' ),
						value: String( kb.id ),
					} ) ),
				] );
			} )
			.catch( () => {
				// Leave the placeholder option in place; the block renders a
				// notice on the frontend when no KB is selected.
			} );

		return () => {
			cancelled = true;
		};
	}, [] );

	return options;
}

registerBlockType( searchMetadata as unknown as BlockMetadata, {
	edit: ( { attributes, setAttributes }: any ) => {
		const kbOptions = useKnowledgeBases();

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Search settings', 'doczur' ) }>
						<SelectControl
							label={ __( 'Knowledge base', 'doczur' ) }
							value={ String( attributes.kb_id ) }
							options={ kbOptions }
							onChange={ ( value: string ) => setAttributes( { kb_id: Number( value ) } ) }
						/>
						<TextControl
							label={ __( 'Placeholder', 'doczur' ) }
							value={ attributes.placeholder }
							onChange={ ( value: string ) => setAttributes( { placeholder: value } ) }
						/>
						<TextControl
							label={ __( 'Button text', 'doczur' ) }
							value={ attributes.button_text }
							onChange={ ( value: string ) => setAttributes( { button_text: value } ) }
						/>
					</PanelBody>
				</InspectorControls>
				<div { ...useBlockProps() }>
					<div className="itsdz-editor-preview" style={ previewStyle }>
						<strong>{ __( 'Doczur Search', 'doczur' ) }</strong>
						<p>
							{ attributes.kb_id
								? __( 'A documentation search box renders here.', 'doczur' )
								: __( 'Choose a knowledge base in the block sidebar.', 'doczur' ) }
						</p>
					</div>
				</div>
			</>
		);
	},
} );

registerBlockType( docsListMetadata as unknown as BlockMetadata, {
	edit: ( { attributes, setAttributes }: any ) => {
		const kbOptions = useKnowledgeBases();

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Article list settings', 'doczur' ) }>
						<SelectControl
							label={ __( 'Knowledge base', 'doczur' ) }
							value={ String( attributes.kb_id ) }
							options={ kbOptions }
							onChange={ ( value: string ) => setAttributes( { kb_id: Number( value ) } ) }
						/>
						<TextControl
							type="number"
							label={ __( 'Number of articles', 'doczur' ) }
							value={ String( attributes.limit ) }
							onChange={ ( value: string ) =>
								setAttributes( { limit: Math.max( 1, Math.min( 50, Number( value ) || 5 ) ) } )
							}
						/>
						<ToggleControl
							label={ __( 'Show section name', 'doczur' ) }
							checked={ !! attributes.show_section }
							onChange={ ( value: boolean ) => setAttributes( { show_section: value } ) }
						/>
					</PanelBody>
				</InspectorControls>
				<div { ...useBlockProps() }>
					<div className="itsdz-editor-preview" style={ previewStyle }>
						<strong>{ __( 'Doczur Article List', 'doczur' ) }</strong>
						<p>
							{ attributes.kb_id
								? __( 'The newest articles render here.', 'doczur' )
								: __( 'Choose a knowledge base in the block sidebar.', 'doczur' ) }
						</p>
					</div>
				</div>
			</>
		);
	},
} );

registerBlockType( popularMetadata as unknown as BlockMetadata, {
	edit: ( { attributes, setAttributes }: any ) => {
		const kbOptions = useKnowledgeBases();

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Ranking settings', 'doczur' ) }>
						<SelectControl
							label={ __( 'Knowledge base', 'doczur' ) }
							value={ String( attributes.kb_id ) }
							options={ kbOptions }
							onChange={ ( value: string ) => setAttributes( { kb_id: Number( value ) } ) }
						/>
						<SelectControl
							label={ __( 'Order by', 'doczur' ) }
							value={ attributes.order }
							options={ [
								{ label: __( 'Most viewed', 'doczur' ), value: 'popular' },
								{ label: __( 'Most recent', 'doczur' ), value: 'recent' },
							] }
							onChange={ ( value: string ) => setAttributes( { order: value } ) }
						/>
						<TextControl
							type="number"
							label={ __( 'Number of articles', 'doczur' ) }
							value={ String( attributes.limit ) }
							onChange={ ( value: string ) =>
								setAttributes( { limit: Math.max( 1, Math.min( 50, Number( value ) || 5 ) ) } )
							}
						/>
						{ attributes.order === 'popular' && (
							<ToggleControl
								label={ __( 'Show view counts', 'doczur' ) }
								checked={ !! attributes.show_views }
								onChange={ ( value: boolean ) => setAttributes( { show_views: value } ) }
							/>
						) }
					</PanelBody>
				</InspectorControls>
				<div { ...useBlockProps() }>
					<div className="itsdz-editor-preview" style={ previewStyle }>
						<strong>
							{ attributes.order === 'recent'
								? __( 'Doczur Recent Articles', 'doczur' )
								: __( 'Doczur Popular Articles', 'doczur' ) }
						</strong>
						<p>
							{ attributes.kb_id
								? __( 'The ranked article list renders here.', 'doczur' )
								: __( 'Choose a knowledge base in the block sidebar.', 'doczur' ) }
						</p>
					</div>
				</div>
			</>
		);
	},
} );

registerBlockType( faqMetadata as unknown as BlockMetadata, {
	edit: ( { attributes, setAttributes }: any ) => {
		const items: FaqItem[] = Array.isArray( attributes.items ) ? attributes.items : [];

		const updateItem = ( index: number, patch: Partial< FaqItem > ) => {
			const next = items.map( ( item, i ) => ( i === index ? { ...item, ...patch } : item ) );
			setAttributes( { items: next } );
		};

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'FAQ settings', 'doczur' ) }>
						<TextControl
							label={ __( 'Heading', 'doczur' ) }
							value={ attributes.heading }
							onChange={ ( value: string ) => setAttributes( { heading: value } ) }
						/>
						<ToggleControl
							label={ __( 'Add FAQ structured data', 'doczur' ) }
							help={ __(
								'Outputs FAQPage schema so questions can appear directly in search results.',
								'doczur'
							) }
							checked={ !! attributes.schema }
							onChange={ ( value: boolean ) => setAttributes( { schema: value } ) }
						/>
					</PanelBody>
				</InspectorControls>
				<div { ...useBlockProps() }>
					<div className="itsdz-faq-editor">
						{ items.length === 0 && (
							<p className="itsdz-faq-editor-empty">
								{ __( 'No questions yet. Add your first one below.', 'doczur' ) }
							</p>
						) }
						{ items.map( ( item, index ) => (
							// eslint-disable-next-line react/no-array-index-key
							<div className="itsdz-faq-editor-item" style={ faqItemStyle } key={ index }>
								<TextControl
									label={ __( 'Question', 'doczur' ) }
									value={ item.question }
									onChange={ ( value: string ) => updateItem( index, { question: value } ) }
								/>
								<TextareaControl
									label={ __( 'Answer', 'doczur' ) }
									value={ item.answer }
									rows={ 3 }
									onChange={ ( value: string ) => updateItem( index, { answer: value } ) }
								/>
								<Button
									variant="tertiary"
									isDestructive
									onClick={ () =>
										setAttributes( { items: items.filter( ( _, i ) => i !== index ) } )
									}
								>
									{ __( 'Remove question', 'doczur' ) }
								</Button>
							</div>
						) ) }
						<Button
							variant="secondary"
							onClick={ () =>
								setAttributes( { items: [ ...items, { question: '', answer: '' } ] } )
							}
						>
							{ __( 'Add question', 'doczur' ) }
						</Button>
					</div>
				</div>
			</>
		);
	},
} );

registerBlockType( glossaryMetadata as unknown as BlockMetadata, {
	edit: ( { attributes, setAttributes }: any ) => {
		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Glossary settings', 'doczur' ) }>
						<TextControl
							label={ __( 'Heading', 'doczur' ) }
							value={ attributes.heading }
							onChange={ ( value: string ) => setAttributes( { heading: value } ) }
						/>
						<ToggleControl
							label={ __( 'Show alternative spellings', 'doczur' ) }
							checked={ !! attributes.show_aliases }
							onChange={ ( value: boolean ) => setAttributes( { show_aliases: value } ) }
						/>
					</PanelBody>
				</InspectorControls>
				<div { ...useBlockProps() }>
					<div className="itsdz-editor-preview" style={ previewStyle }>
						<strong>{ __( 'Doczur Glossary', 'doczur' ) }</strong>
						<p>
							{ __(
								'Every glossary term and its definition renders here, sorted alphabetically.',
								'doczur'
							) }
						</p>
					</div>
				</div>
			</>
		);
	},
} );

registerBlockType( calloutMetadata as unknown as BlockMetadata, {
	edit: ( { attributes, setAttributes }: any ) => {
		const variant =
			CALLOUT_VARIANTS.find( ( item ) => item.value === attributes.variant ) ?? CALLOUT_VARIANTS[ 0 ];

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Callout settings', 'doczur' ) }>
						<SelectControl
							label={ __( 'Type', 'doczur' ) }
							value={ variant.value }
							options={ CALLOUT_VARIANTS.map( ( item ) => ( {
								label: `${ item.icon } ${ item.label }`,
								value: item.value,
							} ) ) }
							onChange={ ( value: string ) => setAttributes( { variant: value } ) }
						/>
					</PanelBody>
				</InspectorControls>
				{ /*
				 * Reuses the real `.itsdz-callout*` rules from the frontend
				 * stylesheet (block.json declares it as this block's `style`,
				 * so WordPress loads it into the editor canvas too) — the
				 * preview can never drift from what visitors actually see,
				 * because it is the same CSS.
				 */ }
				<div { ...useBlockProps( { className: `itsdz-callout itsdz-callout-${ variant.value }` } ) }>
					<RichText
						tagName="p"
						value={ attributes.content }
						onChange={ ( value: string ) => setAttributes( { content: value } ) }
						placeholder={ __( 'Write your note here…', 'doczur' ) }
						allowedFormats={ [ 'core/bold', 'core/italic', 'core/link', 'core/code' ] }
					/>
				</div>
			</>
		);
	},
} );
