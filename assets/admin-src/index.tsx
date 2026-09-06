import { createRoot } from '@wordpress/element';
import { App } from './app/App';
import './store';
import './style.css';

declare let __webpack_public_path__: string;

if ( window.itsdzAdmin?.pluginUrl ) {
	__webpack_public_path__ = `${ window.itsdzAdmin.pluginUrl }build/`;
}

const rootElement = document.getElementById( 'itsdz-admin-app' );

if ( rootElement ) {
	createRoot( rootElement ).render( <App /> );
}
