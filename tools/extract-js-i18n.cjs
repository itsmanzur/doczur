'use strict';

/**
 * Extract @wordpress/i18n strings from JS/TS/TSX. Prints JSON to stdout.
 *
 * Usage: node tools/extract-js-i18n.cjs
 */

const fs = require( 'fs' );
const path = require( 'path' );
const parser = require( '@babel/parser' );
const traverse = require( '@babel/traverse' ).default;

const root = path.resolve( __dirname, '..' );
const domain = 'itsmanzur-docs';
const skip = new Set( [
	'.git',
	'node_modules',
	'vendor',
	'tests',
	'build',
	'tools',
] );
const exts = new Set( [ '.js', '.jsx', '.ts', '.tsx' ] );
const schemas = new Map( [
	[ '__', [ 'text', 'domain' ] ],
	[ '_x', [ 'text', 'context', 'domain' ] ],
	[ '_n', [ 'single', 'plural', 'number', 'domain' ] ],
	[ '_nx', [ 'single', 'plural', 'number', 'context', 'domain' ] ],
	[ '_n_noop', [ 'single', 'plural', 'domain' ] ],
	[ '_nx_noop', [ 'single', 'plural', 'context', 'domain' ] ],
] );

function walk( dir, out = [] ) {
	for ( const entry of fs.readdirSync( dir, { withFileTypes: true } ) ) {
		const abs = path.join( dir, entry.name );
		if ( entry.isDirectory() ) {
			if ( ! skip.has( entry.name ) ) {
				walk( abs, out );
			}
		} else if ( exts.has( path.extname( entry.name ) ) ) {
			out.push( abs );
		}
	}
	return out;
}

function literal( node ) {
	if ( ! node ) {
		return null;
	}
	if ( node.type === 'StringLiteral' ) {
		return node.value;
	}
	if ( node.type === 'TemplateLiteral' && node.expressions.length === 0 ) {
		return node.quasis.map( ( quasi ) => quasi.value.cooked ).join( '' );
	}
	if ( node.type === 'BinaryExpression' && node.operator === '+' ) {
		const left = literal( node.left );
		const right = literal( node.right );
		return null !== left && null !== right ? left + right : null;
	}
	return null;
}

function calleeName( node ) {
	if ( 'Identifier' === node.type ) {
		return node.name;
	}
	if (
		'MemberExpression' === node.type &&
		! node.computed &&
		node.property &&
		'Identifier' === node.property.type
	) {
		return node.property.name;
	}
	return '';
}

const entries = [];
const files = walk( path.join( root, 'assets' ) );

for ( const file of files ) {
	const code = fs.readFileSync( file, 'utf8' );
	let ast;
	try {
		ast = parser.parse( code, {
			sourceType: 'unambiguous',
			plugins: [ 'typescript', 'jsx' ],
			errorRecovery: true,
		} );
	} catch ( error ) {
		continue;
	}

	const rel = path.relative( root, file ).replace( /\\/g, '/' );

	traverse( ast, {
		CallExpression( visit ) {
			const name = calleeName( visit.node.callee );
			const schema = schemas.get( name );
			if ( ! schema ) {
				return;
			}

			const args = visit.node.arguments.map( literal );
			const named = {};
			schema.forEach( ( role, index ) => {
				named[ role ] = args[ index ] ?? null;
			} );

			if ( named.domain !== domain ) {
				return;
			}

			const line = visit.node.loc ? visit.node.loc.start.line : 1;

			if ( named.single && named.plural ) {
				entries.push( {
					msgid: named.single,
					plural: named.plural,
					context: named.context || '',
					refs: [ `${ rel }:${ line }` ],
					extracted: [],
				} );
				return;
			}

			if ( 'string' === typeof named.text && '' !== named.text ) {
				entries.push( {
					msgid: named.text,
					plural: null,
					context: named.context || '',
					refs: [ `${ rel }:${ line }` ],
					extracted: [],
				} );
			}
		},
	} );
}

process.stdout.write( JSON.stringify( entries ) );
