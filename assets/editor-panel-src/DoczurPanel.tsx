import {
	PluginDocumentSettingPanel,
	store as editorStore,
} from '@wordpress/editor';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import {
	Button,
	FormTokenField,
	Modal,
	SelectControl,
	TextControl,
} from '@wordpress/components';
import { useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useKnowledgeBases, useSections, useTags, useVersions } from './hooks';

const ARTICLE_POST_TYPE = 'itsdz_doc';

/**
 * Inline rather than a separate stylesheet: this bundle loads on every
 * itsdz_doc edit screen, and the panel is small enough that a second
 * enqueued CSS file would cost more than it saves.
 */
const reviewedRowStyle: React.CSSProperties = {
	marginTop: '20px',
	paddingTop: '16px',
	borderTop: '1px solid #e2e8f0',
};

const reviewedLabelStyle: React.CSSProperties = {
	display: 'block',
	fontSize: '11px',
	fontWeight: 600,
	textTransform: 'uppercase',
	letterSpacing: '0.5px',
	color: '#64748b',
};

const reviewedValueStyle: React.CSSProperties = {
	display: 'block',
	margin: '4px 0 10px',
	fontSize: '13px',
	color: '#1e293b',
};

const versionRowStyle: React.CSSProperties = {
	display: 'flex',
	alignItems: 'flex-end',
	gap: '8px',
};

const modalActionsStyle: React.CSSProperties = {
	display: 'flex',
	justifyContent: 'flex-end',
	gap: '8px',
	marginTop: '16px',
};

interface MetaShape {
	_itsdz_kb_id?: number;
	_itsdz_last_reviewed?: string;
}

/**
 * The actual panel content.
 *
 * Split out from `DoczurPanel` so its `useEntityProp` calls only ever run
 * while an itsdz_doc article is open. `registerPlugin` mounts its `render`
 * component on every post type's edit screen; calling `useEntityProp(
 * 'postType', 'itsdz_doc', … )` there unconditionally would, while editing
 * an ordinary post, resolve against a post ID that belongs to a different
 * post type entirely — conditionally rendering this component (rather than
 * conditionally calling hooks inside one component) is what keeps that from
 * happening while still following the rules of hooks.
 */
function DoczurPanelFields() {
	const [ meta, setMeta ] = useEntityProp(
		'postType',
		ARTICLE_POST_TYPE,
		'meta'
	) as [ MetaShape, ( value: MetaShape ) => void, MetaShape ];
	const [ sectionIds, setSectionIds ] = useEntityProp(
		'postType',
		ARTICLE_POST_TYPE,
		'itsdz_section'
	) as [ number[], ( value: number[] ) => void, number[] ];
	const [ versionIds, setVersionIds ] = useEntityProp(
		'postType',
		ARTICLE_POST_TYPE,
		'itsdz_version'
	) as [ number[], ( value: number[] ) => void, number[] ];
	const [ tagIds, setTagIds ] = useEntityProp(
		'postType',
		ARTICLE_POST_TYPE,
		'itsdz_tag'
	) as [ number[], ( value: number[] ) => void, number[] ];

	const kbOptions = useKnowledgeBases();
	const sectionOptions = useSections();
	const { options: versionOptions, createVersion } = useVersions();
	const { terms: tagTerms, ensureTerms } = useTags();

	const [ isVersionModalOpen, setVersionModalOpen ] = useState( false );
	const [ newVersionName, setNewVersionName ] = useState( '' );
	const [ creatingVersion, setCreatingVersion ] = useState( false );

	const closeVersionModal = () => {
		setVersionModalOpen( false );
		setNewVersionName( '' );
	};

	const submitNewVersion = async () => {
		if ( ! newVersionName.trim() ) {
			return;
		}
		setCreatingVersion( true );
		try {
			const term = await createVersion( newVersionName.trim() );
			setVersionIds( [ term.id ] );
			closeVersionModal();
		} finally {
			setCreatingVersion( false );
		}
	};

	const tagNames = useMemo(
		() =>
			( tagIds ?? [] )
				.map(
					( id ) => tagTerms.find( ( term ) => term.id === id )?.name
				)
				.filter( ( name ): name is string => Boolean( name ) ),
		[ tagIds, tagTerms ]
	);

	return (
		<PluginDocumentSettingPanel
			name="doczur-metadata"
			title={ __( 'Doczur', 'doczur' ) }
			className="itsdz-editor-panel"
		>
			<SelectControl
				label={ __( 'Knowledge base', 'doczur' ) }
				value={ String( meta?._itsdz_kb_id ?? 0 ) }
				options={ kbOptions }
				onChange={ ( value: string ) =>
					setMeta( { ...meta, _itsdz_kb_id: Number( value ) } )
				}
			/>

			<SelectControl
				label={ __( 'Section', 'doczur' ) }
				value={ String( sectionIds?.[ 0 ] ?? 0 ) }
				options={ sectionOptions }
				onChange={ ( value: string ) =>
					setSectionIds( Number( value ) ? [ Number( value ) ] : [] )
				}
			/>

			<div style={ versionRowStyle }>
				<SelectControl
					label={ __( 'Version', 'doczur' ) }
					value={ String( versionIds?.[ 0 ] ?? 0 ) }
					options={ versionOptions }
					onChange={ ( value: string ) =>
						setVersionIds(
							Number( value ) ? [ Number( value ) ] : []
						)
					}
				/>
				<Button
					variant="tertiary"
					onClick={ () => setVersionModalOpen( true ) }
				>
					+ { __( 'New version', 'doczur' ) }
				</Button>
			</div>

			{ isVersionModalOpen && (
				<Modal
					title={ __( 'New version', 'doczur' ) }
					onRequestClose={ closeVersionModal }
					size="small"
				>
					<TextControl
						label={ __( 'Version name', 'doczur' ) }
						placeholder={ __( 'e.g. v1.0', 'doczur' ) }
						value={ newVersionName }
						onChange={ setNewVersionName }
						onKeyDown={ ( event: React.KeyboardEvent ) => {
							if ( event.key === 'Enter' ) {
								event.preventDefault();
								void submitNewVersion();
							}
						} }
					/>
					<div style={ modalActionsStyle }>
						<Button
							variant="tertiary"
							onClick={ closeVersionModal }
							disabled={ creatingVersion }
						>
							{ __( 'Cancel', 'doczur' ) }
						</Button>
						<Button
							variant="primary"
							onClick={ () => void submitNewVersion() }
							disabled={
								creatingVersion || ! newVersionName.trim()
							}
						>
							{ __( 'Create version', 'doczur' ) }
						</Button>
					</div>
				</Modal>
			) }

			<FormTokenField
				label={ __( 'Tags', 'doczur' ) }
				value={ tagNames }
				suggestions={ tagTerms.map( ( term ) => term.name ) }
				onChange={ ( tokens ) => {
					void ensureTerms( tokens as string[] ).then( ( ids ) =>
						setTagIds( ids )
					);
				} }
			/>

			<div style={ reviewedRowStyle }>
				<span style={ reviewedLabelStyle }>
					{ __( 'Last reviewed', 'doczur' ) }
				</span>
				<span style={ reviewedValueStyle }>
					{ meta?._itsdz_last_reviewed ||
						__( 'Never reviewed', 'doczur' ) }
				</span>
				<Button
					variant="secondary"
					onClick={ () =>
						setMeta( {
							...meta,
							_itsdz_last_reviewed: new Date()
								.toISOString()
								.slice( 0, 10 ),
						} )
					}
				>
					{ __( 'Mark reviewed today', 'doczur' ) }
				</Button>
			</div>
		</PluginDocumentSettingPanel>
	);
}

export function DoczurPanel() {
	const postType = useSelect(
		( select ) => select( editorStore ).getCurrentPostType(),
		[]
	);

	if ( postType !== ARTICLE_POST_TYPE ) {
		return null;
	}

	return <DoczurPanelFields />;
}
