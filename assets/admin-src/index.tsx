import { createRoot } from '@wordpress/element';
import { App } from './app/App';
import './store';
import './style.css';

const rootElement = document.getElementById( 'itsdz-admin-app' );

if ( rootElement ) {
	createRoot( rootElement ).render( <App /> );
}
