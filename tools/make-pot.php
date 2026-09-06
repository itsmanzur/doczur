<?php
/**
 * Generate languages/itsmanzur-docs.pot from PHP, JS/TS, and block.json.
 *
 * Usage: php tools/make-pot.php
 *
 * @package ItsDZ\Doczur
 */

$root   = dirname( __DIR__ );
$domain = 'itsmanzur-docs';
$out    = $root . '/languages/itsmanzur-docs.pot';

$skip_dirs = array( '.git', 'node_modules', 'vendor', 'tests', 'build', 'tools' );

$php_funcs = array(
	'__'           => array( 'text', 'domain' ),
	'_e'           => array( 'text', 'domain' ),
	'esc_html__'   => array( 'text', 'domain' ),
	'esc_html_e'   => array( 'text', 'domain' ),
	'esc_attr__'   => array( 'text', 'domain' ),
	'esc_attr_e'   => array( 'text', 'domain' ),
	'_x'           => array( 'text', 'context', 'domain' ),
	'_ex'          => array( 'text', 'context', 'domain' ),
	'esc_html_x'   => array( 'text', 'context', 'domain' ),
	'esc_attr_x'   => array( 'text', 'context', 'domain' ),
	'_n'           => array( 'single', 'plural', 'number', 'domain' ),
	'_nx'          => array( 'single', 'plural', 'number', 'context', 'domain' ),
	'_n_noop'      => array( 'single', 'plural', 'domain' ),
	'_nx_noop'     => array( 'single', 'plural', 'context', 'domain' ),
);

$entries = array();

foreach ( itsdz_pot_files( $root, $skip_dirs ) as $abs ) {
	$rel  = str_replace( '\\', '/', substr( $abs, strlen( $root ) + 1 ) );
	$ext  = strtolower( pathinfo( $abs, PATHINFO_EXTENSION ) );
	if ( 'json' === $ext && 'block.json' !== basename( $abs ) ) {
		continue;
	}

	$code = file_get_contents( $abs );

	if ( false === $code ) {
		continue;
	}

	if ( 'php' === $ext ) {
		itsdz_pot_merge( $entries, itsdz_pot_extract_php( $code, $rel, $php_funcs, $domain ) );
	} elseif ( 'json' === $ext ) {
		itsdz_pot_merge( $entries, itsdz_pot_extract_block_json( $code, $rel, $domain ) );
	}
}

itsdz_pot_merge( $entries, itsdz_pot_extract_js_via_node( $root ) );

ksort( $entries, SORT_STRING );

$header = itsdz_pot_header( $root );
$body   = '';

foreach ( $entries as $entry ) {
	$body .= "\n";
	if ( $entry['extracted'] ) {
		foreach ( $entry['extracted'] as $comment ) {
			$body .= '#. ' . $comment . "\n";
		}
	}
	foreach ( $entry['refs'] as $ref ) {
		$body .= '#: ' . $ref . "\n";
	}
	if ( '' !== $entry['context'] ) {
		$body .= 'msgctxt ' . itsdz_pot_quote( $entry['context'] ) . "\n";
	}
	$body .= 'msgid ' . itsdz_pot_quote( $entry['msgid'] ) . "\n";
	if ( null !== $entry['plural'] ) {
		$body .= 'msgid_plural ' . itsdz_pot_quote( $entry['plural'] ) . "\n";
		$body .= "msgstr[0] \"\"\n";
		$body .= "msgstr[1] \"\"\n";
	} else {
		$body .= "msgstr \"\"\n";
	}
}

if ( ! is_dir( dirname( $out ) ) ) {
	mkdir( dirname( $out ), 0755, true );
}

file_put_contents( $out, $header . $body );

$count = count( $entries );
echo "Wrote {$count} entries to {$out}\n";

/**
 * @param string   $root      Plugin root.
 * @param string[] $skip_dirs Directory names to skip.
 * @return Generator<string>
 */
function itsdz_pot_files( $root, $skip_dirs ) {
	$iterator = new RecursiveIteratorIterator(
		new RecursiveCallbackFilterIterator(
			new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ),
			static function ( SplFileInfo $current ) use ( $skip_dirs ) {
				if ( $current->isDir() && in_array( $current->getFilename(), $skip_dirs, true ) ) {
					return false;
				}
				return true;
			}
		)
	);

	foreach ( $iterator as $file ) {
		if ( ! $file->isFile() ) {
			continue;
		}
		$ext = strtolower( $file->getExtension() );
		if ( in_array( $ext, array( 'php', 'json' ), true ) ) {
			yield $file->getPathname();
		}
	}
}

/**
 * Run the Babel-based JS extractor.
 *
 * @param string $root Plugin root.
 * @return array<int, array<string, mixed>>
 */
function itsdz_pot_extract_js_via_node( $root ) {
	$script = $root . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'extract-js-i18n.cjs';
	if ( ! is_readable( $script ) ) {
		fwrite( STDERR, "JS extractor missing: {$script}\n" );
		return array();
	}

	$candidates = array(
		'node',
		'C:\\Program Files\\nodejs\\node.exe',
	);

	$json = null;
	foreach ( $candidates as $binary ) {
		$command = escapeshellarg( $binary ) . ' ' . escapeshellarg( $script );
		$output  = shell_exec( $command );
		if ( is_string( $output ) && '' !== trim( $output ) ) {
			$json = $output;
			break;
		}
	}

	if ( ! is_string( $json ) || '' === trim( $json ) ) {
		fwrite( STDERR, "JS extractor returned no output. Is node on PATH?\n" );
		return array();
	}

	$decoded = json_decode( $json, true );
	if ( ! is_array( $decoded ) ) {
		fwrite( STDERR, "JS extractor returned invalid JSON.\n" );
		return array();
	}

	return $decoded;
}

/**
 * @param array<string, array<string, mixed>> $entries Entries keyed by context+msgid.
 * @param array<int, array<string, mixed>>    $found   Newly found entries.
 */
function itsdz_pot_merge( array &$entries, array $found ) {
	foreach ( $found as $item ) {
		$item['extracted'] = isset( $item['extracted'] ) && is_array( $item['extracted'] ) ? $item['extracted'] : array();
		$item['refs']      = isset( $item['refs'] ) && is_array( $item['refs'] ) ? $item['refs'] : array();
		$item['context']   = isset( $item['context'] ) ? (string) $item['context'] : '';
		$item['plural']    = $item['plural'] ?? null;

		$key = $item['context'] . "\x04" . $item['msgid'] . "\x04" . (string) $item['plural'];
		if ( ! isset( $entries[ $key ] ) ) {
			$entries[ $key ] = $item;
			continue;
		}
		$entries[ $key ]['refs'] = array_values( array_unique( array_merge( $entries[ $key ]['refs'], $item['refs'] ) ) );
		$entries[ $key ]['extracted'] = array_values( array_unique( array_merge( $entries[ $key ]['extracted'], $item['extracted'] ) ) );
	}
}

/**
 * @param string               $code  File contents.
 * @param string               $rel   Relative path.
 * @param array<string, array<int, string>> $funcs Function map.
 * @param string               $domain Text domain.
 * @return array<int, array<string, mixed>>
 */
function itsdz_pot_extract_php( $code, $rel, $funcs, $domain ) {
	$tokens = token_get_all( $code );
	$found  = array();
	$count  = count( $tokens );

	for ( $i = 0; $i < $count; $i++ ) {
		$token = $tokens[ $i ];
		if ( ! is_array( $token ) || T_STRING !== $token[0] || ! isset( $funcs[ $token[1] ] ) ) {
			continue;
		}

		$prev = itsdz_pot_prev_significant( $tokens, $i );
		if ( is_array( $prev ) && in_array( $prev[0], array( T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NS_SEPARATOR, T_FUNCTION, T_NEW ), true ) ) {
			continue;
		}

		$comment = itsdz_pot_translators_comment( $tokens, $i );
		$line    = (int) $token[2];
		$schema  = $funcs[ $token[1] ];
		$j       = $i + 1;

		while ( $j < $count && is_array( $tokens[ $j ] ) && in_array( $tokens[ $j ][0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
			++$j;
		}

		if ( $j >= $count || '(' !== $tokens[ $j ] ) {
			continue;
		}

		$args = itsdz_pot_php_args( $tokens, $j, $count );
		if ( null === $args ) {
			continue;
		}

		$entry = itsdz_pot_from_args( $args, $schema, $domain, $rel, $line, $comment );
		if ( $entry ) {
			$found[] = $entry;
		}
	}

	return $found;
}

/**
 * @param array<int, mixed> $tokens Token list.
 * @param int               $index  Current index.
 * @return mixed
 */
function itsdz_pot_prev_significant( array $tokens, $index ) {
	for ( $i = $index - 1; $i >= 0; $i-- ) {
		$token = $tokens[ $i ];
		if ( is_array( $token ) && in_array( $token[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
			continue;
		}
		return $token;
	}
	return null;
}

/**
 * @param array<int, mixed> $tokens Token list.
 * @param int               $index  Current index.
 * @return string
 */
function itsdz_pot_translators_comment( array $tokens, $index ) {
	for ( $i = $index - 1; $i >= 0; $i-- ) {
		$token = $tokens[ $i ];
		if ( is_array( $token ) && T_WHITESPACE === $token[0] ) {
			continue;
		}
		if ( is_array( $token ) && in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
			$text = $token[1];
			if ( preg_match( '/translators:\s*(.+)/is', $text, $match ) ) {
				return trim( preg_replace( '/\*\/$/', '', $match[1] ) );
			}
		}
		return '';
	}
	return '';
}

/**
 * @param array<int, mixed> $tokens Token list.
 * @param int               $index  Index of '('.
 * @param int               $count  Token count.
 * @return array<int, string|null>|null
 */
function itsdz_pot_php_args( array $tokens, $index, $count ) {
	$args    = array();
	$parts   = array();
	$depth   = 0;
	$ok      = true;
	$started = false;

	for ( $i = $index; $i < $count; $i++ ) {
		$token = $tokens[ $i ];

		if ( '(' === $token ) {
			++$depth;
			if ( 1 === $depth ) {
				$started = true;
				continue;
			}
			$ok = false;
			continue;
		}

		if ( ')' === $token ) {
			--$depth;
			if ( 0 === $depth ) {
				if ( $parts || $args ) {
					$args[] = ( $ok && $parts ) ? implode( '', $parts ) : null;
				}
				return $args;
			}
			$ok = false;
			continue;
		}

		if ( ! $started ) {
			continue;
		}

		if ( 1 === $depth && ',' === $token ) {
			$args[] = ( $ok && $parts ) ? implode( '', $parts ) : null;
			$parts  = array();
			$ok     = true;
			continue;
		}

		if ( is_array( $token ) && in_array( $token[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
			continue;
		}

		if ( 1 === $depth && is_array( $token ) && T_CONSTANT_ENCAPSED_STRING === $token[0] ) {
			$parts[] = itsdz_pot_unquote_php( $token[1] );
			continue;
		}

		if ( 1 === $depth && '.' === $token ) {
			continue;
		}

		$ok = false;
	}

	return null;
}

/**
 * @param string $token Quoted PHP string token.
 * @return string
 */
function itsdz_pot_unquote_php( $token ) {
	$quote = $token[0];
	$inner = substr( $token, 1, -1 );
	if ( '"' === $quote ) {
		return stripcslashes( $inner );
	}
	return str_replace( array( '\\\\', '\\\'' ), array( '\\', "'" ), $inner );
}

/**
 * @param string $code JSON.
 * @param string $rel  Relative path.
 * @param string $domain Text domain.
 * @return array<int, array<string, mixed>>
 */
function itsdz_pot_extract_block_json( $code, $rel, $domain ) {
	$data = json_decode( $code, true );
	if ( ! is_array( $data ) || ( $data['textdomain'] ?? '' ) !== $domain ) {
		return array();
	}

	$found = array();
	$map   = array(
		'title'       => 'block title',
		'description' => 'block description',
	);

	foreach ( $map as $key => $context ) {
		if ( ! empty( $data[ $key ] ) && is_string( $data[ $key ] ) ) {
			$found[] = itsdz_pot_entry( $data[ $key ], $context, null, $rel, 1, '' );
		}
	}

	if ( ! empty( $data['keywords'] ) && is_array( $data['keywords'] ) ) {
		$line = 1;
		foreach ( $data['keywords'] as $keyword ) {
			if ( is_string( $keyword ) && '' !== $keyword && $keyword !== $domain ) {
				$found[] = itsdz_pot_entry( $keyword, 'block keyword', null, $rel, $line, '' );
			}
		}
	}

	return $found;
}

/**
 * @param array<int, string|null> $args    Parsed arguments.
 * @param array<int, string>      $schema  Argument roles.
 * @param string                  $domain  Text domain.
 * @param string                  $rel     Relative path.
 * @param int                     $line    Line number.
 * @param string                  $comment Translators comment.
 * @return array<string, mixed>|null
 */
function itsdz_pot_from_args( array $args, array $schema, $domain, $rel, $line, $comment ) {
	$named = array();
	foreach ( $schema as $i => $role ) {
		$named[ $role ] = $args[ $i ] ?? null;
	}

	if ( ( $named['domain'] ?? null ) !== $domain ) {
		return null;
	}

	if ( isset( $named['single'], $named['plural'] ) ) {
		if ( ! is_string( $named['single'] ) || ! is_string( $named['plural'] ) ) {
			return null;
		}
		return itsdz_pot_entry(
			$named['single'],
			isset( $named['context'] ) && is_string( $named['context'] ) ? $named['context'] : '',
			$named['plural'],
			$rel,
			$line,
			$comment
		);
	}

	if ( ! is_string( $named['text'] ?? null ) || '' === $named['text'] ) {
		return null;
	}

	return itsdz_pot_entry(
		$named['text'],
		isset( $named['context'] ) && is_string( $named['context'] ) ? $named['context'] : '',
		null,
		$rel,
		$line,
		$comment
	);
}

/**
 * @param string      $msgid   Source string.
 * @param string      $context Context.
 * @param string|null $plural  Plural form.
 * @param string      $rel     Relative path.
 * @param int         $line    Line number.
 * @param string      $comment Translators comment.
 * @return array<string, mixed>
 */
function itsdz_pot_entry( $msgid, $context, $plural, $rel, $line, $comment ) {
	return array(
		'msgid'     => $msgid,
		'context'   => $context,
		'plural'    => $plural,
		'refs'      => array( $rel . ':' . $line ),
		'extracted' => $comment ? array( $comment ) : array(),
	);
}

/**
 * @param string $text Raw string.
 * @return string Quoted POT string (possibly multiline).
 */
function itsdz_pot_quote( $text ) {
	$escaped = str_replace(
		array( '\\', '"', "\t", "\r" ),
		array( '\\\\', '\\"', '\\t', '\\r' ),
		$text
	);
	$parts = explode( "\n", $escaped );

	if ( 1 === count( $parts ) ) {
		return '"' . $parts[0] . '"';
	}

	$out = "\"\"\n";
	$last = count( $parts ) - 1;
	foreach ( $parts as $i => $part ) {
		$suffix = ( $i === $last ) ? '' : '\\n';
		$out   .= '"' . $part . $suffix . '"' . ( $i === $last ? '' : "\n" );
	}

	return $out;
}

/**
 * @param string $root Plugin root.
 * @return string
 */
function itsdz_pot_header( $root ) {
	$plugin = file_get_contents( $root . '/itsmanzur-docs.php' );
	$version = '1.1.1';
	if ( is_string( $plugin ) && preg_match( '/^\s*\*\s*Version:\s*(.+)$/m', $plugin, $match ) ) {
		$version = trim( $match[1] );
	}

	$date = gmdate( 'Y-m-d\\TH:i:s+00:00' );

	return <<<POT
# Copyright (C) 2026 ItsDZ
# This file is distributed under the GPLv2 or later.
msgid ""
msgstr ""
"Project-Id-Version: Nirdeshio {$version}\\n"
"Report-Msgid-Bugs-To: https://wordpress.org/support/plugin/itsmanzur-docs\\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\\n"
"Language-Team: LANGUAGE <LL@li.org>\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"POT-Creation-Date: {$date}\\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\\n"
"X-Generator: tools/make-pot.php\\n"
"X-Domain: itsmanzur-docs\\n"

POT;
}
