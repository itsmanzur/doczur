import { Button, CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

type Props = {
	analyticsOptIn: boolean;
	busy: boolean;
	onAnalyticsChange: ( value: boolean ) => void;
	onSkip: () => void;
	onStart: () => void;
};

const FEATURES = [
	{
		icon: 'dashicons-search',
		label: __( 'Instant search', 'itsmanzur-docs' ),
	},
	{
		icon: 'dashicons-category',
		label: __( 'Clear sections', 'itsmanzur-docs' ),
	},
	{
		icon: 'dashicons-welcome-view-site',
		label: __( 'Public docs portal', 'itsmanzur-docs' ),
	},
];

export function WelcomeStep( {
	analyticsOptIn,
	busy,
	onAnalyticsChange,
	onSkip,
	onStart,
}: Props ) {
	return (
		<div className="itsdz-onboarding-step itsdz-onboarding-welcome">
			<div className="itsdz-onboarding-welcome-copy">
				<p className="itsdz-onboarding-kicker">
					{ __( 'Ready in two minutes', 'itsmanzur-docs' ) }
				</p>
				<h2 className="itsdz-onboarding-title">
					{ __(
						'Beautiful product docs, without the busywork.',
						'itsmanzur-docs'
					) }
				</h2>
				<p className="itsdz-onboarding-pitch">
					{ __(
						'Search, sections, and a public portal — skip any time. Nothing is forced, and existing content is never deleted.',
						'itsmanzur-docs'
					) }
				</p>
				<ul className="itsdz-onboarding-features">
					{ FEATURES.map( ( feature ) => (
						<li key={ feature.icon }>
							<span
								className={ `dashicons ${ feature.icon }` }
								aria-hidden="true"
							/>
							{ feature.label }
						</li>
					) ) }
				</ul>
				<div className="itsdz-onboarding-actions itsdz-onboarding-actions--welcome">
					<Button variant="primary" onClick={ onStart } disabled={ busy }>
						{ __( 'Set up in 2 minutes', 'itsmanzur-docs' ) }
					</Button>
					<Button variant="tertiary" onClick={ onSkip } disabled={ busy }>
						{ __( "Skip, I'll explore myself", 'itsmanzur-docs' ) }
					</Button>
				</div>
				<CheckboxControl
					className="itsdz-onboarding-analytics"
					label={ __(
						'Help improve Nirdeshio by sharing anonymous setup analytics',
						'itsmanzur-docs'
					) }
					help={ __(
						'Optional. Logs only a step number and timestamp on this site. Nothing is sent to an external server, and no personal data, IP address, email, or content is captured.',
						'itsmanzur-docs'
					) }
					checked={ analyticsOptIn }
					onChange={ onAnalyticsChange }
				/>
			</div>
			<div className="itsdz-onboarding-hero-preview" aria-hidden="true">
				<div className="itsdz-onboarding-hero-window">
					<div className="itsdz-onboarding-hero-chrome">
						<span />
						<span />
						<span />
					</div>
					<div className="itsdz-onboarding-hero-body">
						<aside>
							<strong>Docs</strong>
							<em />
							<em className="is-active" />
							<em />
						</aside>
						<main>
							<b />
							<i />
							<i />
							<i className="is-short" />
						</main>
					</div>
				</div>
			</div>
		</div>
	);
}
