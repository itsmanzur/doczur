import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const STYLES: Array< {
	value: 'accordion' | 'rail' | 'tree';
	icon: string;
	label: string;
	help: string;
} > = [
	{
		value: 'accordion',
		icon: 'dashicons-list-view',
		label: __( 'Accordion', 'itsmanzur-docs' ),
		help: __( 'Collapsible sections, current page filled in.', 'itsmanzur-docs' ),
	},
	{
		value: 'rail',
		icon: 'dashicons-align-left',
		label: __( 'Rail', 'itsmanzur-docs' ),
		help: __( 'Narrower, denser sidebar for large docs.', 'itsmanzur-docs' ),
	},
	{
		value: 'tree',
		icon: 'dashicons-networking',
		label: __( 'Tree', 'itsmanzur-docs' ),
		help: __( 'Nested sections in the public sidebar.', 'itsmanzur-docs' ),
	},
];

type Props = {
	busy: boolean;
	navStyle: 'accordion' | 'rail' | 'tree';
	onBack: () => void;
	onNext: () => void;
	onSkip: () => void;
	onStyleChange: ( value: 'accordion' | 'rail' | 'tree' ) => void;
};

function MiniNav( { style }: { style: 'accordion' | 'rail' | 'tree' } ) {
	return (
		<div className={ `itsdz-onboarding-mini-nav is-${ style }` } aria-hidden="true">
			<span className="is-group" />
			<span className="is-current" />
			<span />
			<span className="is-group" />
			<span className="is-nested" />
		</div>
	);
}

export function NavStyleStep( {
	busy,
	navStyle,
	onBack,
	onNext,
	onSkip,
	onStyleChange,
}: Props ) {
	return (
		<div className="itsdz-onboarding-step">
			<p className="itsdz-onboarding-kicker">
				{ __( 'Step 3 of 5', 'itsmanzur-docs' ) }
			</p>
			<h2 className="itsdz-onboarding-title">
				{ __( 'Pick a navigation style', 'itsmanzur-docs' ) }
			</h2>
			<p className="itsdz-onboarding-lede">
				{ __(
					'This is how readers move through your docs. You can change it later in Settings.',
					'itsmanzur-docs'
				) }
			</p>
			<div
				className="itsdz-onboarding-nav-grid"
				role="radiogroup"
				aria-label={ __( 'Navigation style', 'itsmanzur-docs' ) }
			>
				{ STYLES.map( ( style ) => (
					<button
						key={ style.value }
						type="button"
						role="radio"
						aria-checked={ navStyle === style.value }
						className={ `itsdz-choice-card ${
							navStyle === style.value ? 'is-selected' : ''
						}` }
						onClick={ () => onStyleChange( style.value ) }
					>
						<MiniNav style={ style.value } />
						<span
							className={ `dashicons ${ style.icon }` }
							aria-hidden="true"
						/>
						<strong>{ style.label }</strong>
						<span>{ style.help }</span>
					</button>
				) ) }
			</div>
			<div
				className={ `itsdz-onboarding-nav-preview is-${ navStyle }` }
				aria-hidden="true"
			>
				<div className="itsdz-onboarding-nav-preview-sidebar">
					<small>{ __( 'Docs', 'itsmanzur-docs' ) }</small>
					<span className="itsdz-onboarding-nav-preview-group" />
					<span className="itsdz-onboarding-nav-preview-item is-current" />
					<span className="itsdz-onboarding-nav-preview-item" />
					<span className="itsdz-onboarding-nav-preview-group" />
					<span className="itsdz-onboarding-nav-preview-item is-nested" />
				</div>
				<div className="itsdz-onboarding-nav-preview-page">
					<b />
					<i />
					<i />
					<i className="is-short" />
				</div>
			</div>
			<div className="itsdz-onboarding-actions">
				<Button variant="tertiary" onClick={ onBack } disabled={ busy }>
					{ __( 'Back', 'itsmanzur-docs' ) }
				</Button>
				<div className="itsdz-onboarding-actions-end">
					<Button variant="tertiary" onClick={ onSkip } disabled={ busy }>
						{ __( 'Skip remaining steps', 'itsmanzur-docs' ) }
					</Button>
					<Button variant="primary" onClick={ onNext } disabled={ busy }>
						{ __( 'Continue', 'itsmanzur-docs' ) }
					</Button>
				</div>
			</div>
		</div>
	);
}
