import { registerPlugin } from '@wordpress/plugins';
import { DoczurPanel } from './DoczurPanel';

registerPlugin( 'doczur-editor-panel', {
	render: DoczurPanel,
} );
