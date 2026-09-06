import apiFetch from '@wordpress/api-fetch';

export type OnboardingStatusName =
	| 'pending'
	| 'in_progress'
	| 'skipped'
	| 'completed';

export type OnboardingStatus = {
	status: OnboardingStatusName;
	step: number;
	analytics_opt_in: boolean;
	should_show: boolean;
	page_id: number;
	page_url: string;
	dropoff: Record< string, number >;
};

export type CompleteStepPayload = {
	step?: number;
	restart?: boolean;
	analytics_opt_in?: boolean;
	create_page?: boolean;
};

export const SHORTCODE = '[nirdeshio_docs]';

export function restErrorMessage( caught: unknown, fallback: string ): string {
	if ( caught instanceof Error && caught.message.trim() ) {
		return caught.message;
	}

	if ( caught && typeof caught === 'object' && 'message' in caught ) {
		const message = ( caught as { message: unknown } ).message;

		if ( typeof message === 'string' && message.trim() ) {
			return message;
		}
	}

	if ( typeof caught === 'string' && caught.trim() ) {
		return caught;
	}

	return fallback;
}

export async function getOnboardingStatus() {
	return apiFetch< OnboardingStatus >( {
		path: '/itsdz/v1/onboarding/status',
	} );
}

export async function completeOnboardingStep( payload: CompleteStepPayload ) {
	return apiFetch< OnboardingStatus >( {
		path: '/itsdz/v1/onboarding/complete-step',
		method: 'POST',
		data: payload,
	} );
}

export async function skipOnboarding( analyticsOptIn?: boolean ) {
	return apiFetch< OnboardingStatus >( {
		path: '/itsdz/v1/onboarding/skip',
		method: 'POST',
		data:
			undefined === analyticsOptIn
				? {}
				: { analytics_opt_in: analyticsOptIn },
	} );
}
