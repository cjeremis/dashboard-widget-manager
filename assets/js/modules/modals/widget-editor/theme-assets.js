/**
 * Dashboard Widget Manager - Theme Asset Generator
 *
 * Builds default Template/CSS/JS content per display mode + theme.
 *
 * @package Dashboard_Widget_Manager
 * @since 1.0.0
 */

const TABLE_THEMES = [ 'default', 'minimal', 'dark', 'striped', 'bordered', 'ocean' ];
const LIST_THEMES = [ 'clean', 'bordered', 'striped', 'compact', 'dark', 'accent' ];
const BUTTON_THEMES = [ 'solid', 'outline', 'pill', 'flat', 'gradient', 'dark' ];
const CARD_LIST_THEMES = [ 'elevated', 'flat', 'bordered', 'minimal', 'dark', 'colorful' ];
const CHART_THEMES = [ 'classic', 'sunset', 'forest', 'oceanic', 'monochrome', 'candy' ];

const CHART_THEME_SWATCH = {
	classic:    { accent: '#667eea', soft: '#e8ebff', text: '#312e81' },
	sunset:     { accent: '#f97316', soft: '#fff1e5', text: '#9a3412' },
	forest:     { accent: '#15803d', soft: '#e8f8ee', text: '#14532d' },
	oceanic:    { accent: '#0284c7', soft: '#e8f7ff', text: '#0c4a6e' },
	monochrome: { accent: '#4b5563', soft: '#f2f4f7', text: '#1f2937' },
	candy:      { accent: '#8b5cf6', soft: '#f3efff', text: '#581c87' },
};

export function isChartDisplayMode( mode ) {
	return [ 'bar', 'line', 'pie', 'doughnut' ].indexOf( mode ) !== -1;
}

export function isDataDisplayMode( mode ) {
	return [ 'table', 'list', 'button', 'card-list' ].indexOf( mode ) !== -1;
}

export function getDefaultThemeForMode( mode ) {
	if ( isChartDisplayMode( mode ) ) return 'classic';
	if ( mode === 'list' ) return 'clean';
	if ( mode === 'button' ) return 'solid';
	if ( mode === 'card-list' ) return 'elevated';
	return 'default';
}

export function normalizeThemeForMode( mode, theme ) {
	let themeList;
	if ( isChartDisplayMode( mode ) ) {
		themeList = CHART_THEMES;
	} else if ( mode === 'list' ) {
		themeList = LIST_THEMES;
	} else if ( mode === 'button' ) {
		themeList = BUTTON_THEMES;
	} else if ( mode === 'card-list' ) {
		themeList = CARD_LIST_THEMES;
	} else {
		themeList = TABLE_THEMES;
	}
	return themeList.indexOf( theme ) !== -1 ? theme : themeList[ 0 ];
}

export function buildThemeAssets( mode, theme, columnAliases, outputConfig ) {
	if ( isChartDisplayMode( mode ) ) {
		const normalizedTheme = normalizeThemeForMode( mode, theme );
		return {
			template: getDefaultChartTemplate( mode ),
			styles: getChartThemeCss( mode, normalizedTheme ),
			scripts: getChartThemeJs( mode, normalizedTheme ),
		};
	}

	if ( mode === 'list' ) {
		const normalizedTheme = normalizeThemeForMode( 'list', theme );
		return {
			template: getDefaultListTemplate( columnAliases, outputConfig ),
			styles: getListThemeCss( normalizedTheme ),
			scripts: getGenericThemeJs( 'list', normalizedTheme ),
		};
	}

	if ( mode === 'button' ) {
		const normalizedTheme = normalizeThemeForMode( 'button', theme );
		return {
			template: getDefaultButtonTemplate( columnAliases, outputConfig ),
			styles: getButtonThemeCss( normalizedTheme ),
			scripts: getGenericThemeJs( 'button', normalizedTheme ),
		};
	}

	if ( mode === 'card-list' ) {
		const normalizedTheme = normalizeThemeForMode( 'card-list', theme );
		return {
			template: getDefaultCardListTemplate( columnAliases, outputConfig ),
			styles: getCardListThemeCss( normalizedTheme ),
			scripts: getGenericThemeJs( 'card-list', normalizedTheme ),
		};
	}

	// Default: table
	const normalizedTheme = normalizeThemeForMode( 'table', theme );
	return {
		template: getDefaultTableTemplate( columnAliases, outputConfig ),
		styles: getTableThemeCss( normalizedTheme ),
		scripts: getTableThemeJs( normalizedTheme ),
	};
}

/**
 * Look up link config for a column from outputConfig.columns[].
 */
function getColumnLink( outputConfig, col ) {
	if ( ! outputConfig || ! outputConfig.columns ) return null;
	for ( let i = 0; i < outputConfig.columns.length; i++ ) {
		if ( outputConfig.columns[ i ].key === col && outputConfig.columns[ i ].link && outputConfig.columns[ i ].link.enabled ) {
			return outputConfig.columns[ i ].link;
		}
	}
	return null;
}

/**
 * Look up formatter config for a column from outputConfig.columns[].
 */
function getColumnFormatter( outputConfig, col ) {
	if ( ! outputConfig || ! outputConfig.columns ) return null;
	for ( let i = 0; i < outputConfig.columns.length; i++ ) {
		if ( outputConfig.columns[ i ].key === col && outputConfig.columns[ i ].formatter && outputConfig.columns[ i ].formatter.type !== 'text' ) {
			return outputConfig.columns[ i ].formatter;
		}
	}
	return null;
}

/**
 * Build a PHP expression for a column value, applying formatter if present.
 */
function buildValueExpression( col, formatter ) {
	const escapedCol = col.replace( /'/g, "\\'" );
	const rawExpr = '$row[\'' + escapedCol + '\']';

	if ( ! formatter || formatter.type === 'text' ) {
		return '<?php echo esc_html( ' + rawExpr + ' ); ?>';
	}

	const opts = formatter.options || {};

	switch ( formatter.type ) {
		case 'date': {
			const fmt = opts.format || 'Y-m-d';
			if ( fmt === 'relative' ) {
				return '<?php echo esc_html( human_time_diff( strtotime( ' + rawExpr + ' ), current_time( \'timestamp\' ) ) . \' ago\' ); ?>';
			}
			return '<?php echo esc_html( date_i18n( \'' + fmt.replace( /'/g, "\\'" ) + '\', strtotime( ' + rawExpr + ' ) ) ); ?>';
		}
		case 'number': {
			const decimals = opts.decimals !== undefined ? parseInt( opts.decimals, 10 ) : 0;
			const tSep = opts.thousands_sep !== undefined ? opts.thousands_sep : ',';
			const dSep = tSep === '.' ? ',' : '.';
			const prefix = opts.prefix || '';
			const suffix = opts.suffix || '';
			let expr = 'number_format( (float) ' + rawExpr + ', ' + decimals + ', \'' + dSep.replace( /'/g, "\\'" ) + '\', \'' + tSep.replace( /'/g, "\\'" ) + '\' )';
			if ( prefix || suffix ) {
				expr = '\'' + prefix.replace( /'/g, "\\'" ) + '\' . ' + expr + ' . \'' + suffix.replace( /'/g, "\\'" ) + '\'';
			}
			return '<?php echo esc_html( ' + expr + ' ); ?>';
		}
		case 'excerpt': {
			const len = opts.length || 100;
			const sfx = opts.suffix !== undefined ? opts.suffix : '...';
			return '<?php echo esc_html( mb_strlen( ' + rawExpr + ' ) > ' + len + ' ? mb_substr( ' + rawExpr + ', 0, ' + len + ' ) . \'' + sfx.replace( /'/g, "\\'" ) + '\' : ' + rawExpr + ' ); ?>';
		}
		case 'case': {
			const transform = opts.transform || 'uppercase';
			const fnMap = {
				uppercase:  'strtoupper',
				lowercase:  'strtolower',
				capitalize: 'ucwords',
				sentence:   'ucfirst'
			};
			const fn = fnMap[ transform ] || 'strtoupper';
			if ( transform === 'sentence' ) {
				return '<?php echo esc_html( ucfirst( strtolower( ' + rawExpr + ' ) ) ); ?>';
			}
			return '<?php echo esc_html( ' + fn + '( ' + rawExpr + ' ) ); ?>';
		}
		default:
			return '<?php echo esc_html( ' + rawExpr + ' ); ?>';
	}
}

/**
 * Build a link-wrapped or plain cell for a column.
 */
function buildCellContent( col, link, formatter ) {
	const valueExpr = buildValueExpression( col, formatter );

	if ( ! link || ! link.enabled || ! link.url ) {
		return valueExpr;
	}

	// Build URL as a PHP expression and escape the final assembled href.
	const hrefExpr = buildLinkHrefExpression( link.url );

	const target = link.open_in_new_tab !== false ? ' target="_blank" rel="noopener noreferrer"' : '';
	return '<a href="<?php echo esc_url( ' + hrefExpr + ' ); ?>"' + target + '>' + valueExpr + '</a>';
}

function buildLinkHrefExpression( urlTemplate ) {
	const template = String( urlTemplate || '' );
	const tokenRegex = /\{([^}]+)\}/g;
	let cursor = 0;
	let match = null;
	const parts = [];

	while ( ( match = tokenRegex.exec( template ) ) !== null ) {
		const staticChunk = template.slice( cursor, match.index );
		if ( staticChunk ) {
			parts.push( '\'' + staticChunk.replace( /\\/g, '\\\\' ).replace( /'/g, "\\'" ) + '\'' );
		}

		const varName = match[ 1 ].replace( /'/g, "\\'" );
		parts.push( '( isset( $row[\'' + varName + '\'] ) ? rawurlencode( (string) $row[\'' + varName + '\'] ) : \'\' )' );
		cursor = match.index + match[ 0 ].length;
	}

	const trailingChunk = template.slice( cursor );
	if ( trailingChunk ) {
		parts.push( '\'' + trailingChunk.replace( /\\/g, '\\\\' ).replace( /'/g, "\\'" ) + '\'' );
	}

	return parts.length > 0 ? parts.join( ' . ' ) : '\'\'';
}

function getDefaultTableTemplate( columnAliases, outputConfig ) {
	// When column aliases exist, generate explicit <th> and <td> tags
	// so the user controls header labels independently of theme changes.
	if ( columnAliases && typeof columnAliases === 'object' ) {
		const columns = Object.keys( columnAliases );
		if ( columns.length > 0 ) {
			let headerRows = '';
			let bodyRows   = '';

			columns.forEach( function( col ) {
				const label     = columnAliases[ col ] || col;
				const link      = getColumnLink( outputConfig, col );
				const formatter = getColumnFormatter( outputConfig, col );
				headerRows += '\t\t\t\t<th>' + label + '</th>\n';
				bodyRows   += '\t\t\t\t\t<td>' + buildCellContent( col, link, formatter ) + '</td>\n';
			} );

			return (
				'<?php if ( ! empty( $query_results ) ) : ?>\n' +
				'<table class="dwm-table">\n' +
				'\t<thead>\n' +
				'\t\t<tr>\n' +
				headerRows +
				'\t\t</tr>\n' +
				'\t</thead>\n' +
				'\t<tbody>\n' +
				'\t\t<?php foreach ( $query_results as $row ) : ?>\n' +
				'\t\t\t<tr>\n' +
				bodyRows +
				'\t\t\t</tr>\n' +
				'\t\t<?php endforeach; ?>\n' +
				'\t</tbody>\n' +
				'</table>\n' +
				'<?php else : ?>\n' +
				'<p>No results found.</p>\n' +
				'<?php endif; ?>'
			);
		}
	}

	// Default: dynamic headers from array_keys
	return (
		'<?php if ( ! empty( $query_results ) ) : ?>\n' +
		'<table class="dwm-table">\n' +
		'\t<thead>\n' +
		'\t\t<tr>\n' +
		'\t\t\t<?php foreach ( array_keys( $query_results[0] ) as $header ) : ?>\n' +
		'\t\t\t\t<th><?php echo esc_html( $header ); ?></th>\n' +
		'\t\t\t<?php endforeach; ?>\n' +
		'\t\t</tr>\n' +
		'\t</thead>\n' +
		'\t<tbody>\n' +
		'\t\t<?php foreach ( $query_results as $row ) : ?>\n' +
		'\t\t\t<tr>\n' +
		'\t\t\t\t<?php foreach ( $row as $value ) : ?>\n' +
		'\t\t\t\t\t<td><?php echo esc_html( $value ); ?></td>\n' +
		'\t\t\t\t<?php endforeach; ?>\n' +
		'\t\t\t</tr>\n' +
		'\t\t<?php endforeach; ?>\n' +
		'\t</tbody>\n' +
		'</table>\n' +
		'<?php else : ?>\n' +
		'<p>No results found.</p>\n' +
		'<?php endif; ?>'
	);
}

function getDefaultChartTemplate( mode ) {
	const titleByMode = {
		bar: 'Bar',
		line: 'Line',
		pie: 'Pie',
		doughnut: 'Doughnut',
	};

	return (
		'<?php if ( ! empty( $query_results ) ) : ?>\n' +
		'<div class="dwm-chart-summary" data-chart-mode="' + titleByMode[ mode ] + '">\n' +
		'\t<strong><?php echo esc_html( count( $query_results ) ); ?></strong> data points returned.\n' +
		'</div>\n' +
		'<?php endif; ?>'
	);
}

function getTableThemeCss( theme ) {
	const cssMap = {
		default:
			'.dwm-table { width: 100%; border-collapse: collapse; }\n' +
			'.dwm-table th { background: #2271b1; color: #fff; padding: 8px 12px; text-align: left; font-size: 13px; }\n' +
			'.dwm-table td { padding: 8px 12px; border-bottom: 1px solid #e0e0e0; font-size: 13px; color: #1d2327; }\n' +
			'.dwm-table tr:last-child td { border-bottom: none; }',

		minimal:
			'.dwm-table { width: 100%; border-collapse: collapse; }\n' +
			'.dwm-table th { color: #666; padding: 6px 10px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e0e0e0; background: transparent; }\n' +
			'.dwm-table td { padding: 8px 10px; border-bottom: 1px solid #f0f0f0; font-size: 13px; color: #333; }\n' +
			'.dwm-table tr:last-child td { border-bottom: none; }',

		dark:
			'.dwm-table { width: 100%; border-collapse: collapse; background: #1e1e2d; border-radius: 4px; overflow: hidden; }\n' +
			'.dwm-table th { background: #111827; color: #e5e7eb; padding: 10px 12px; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }\n' +
			'.dwm-table td { padding: 8px 12px; border-bottom: 1px solid #374151; font-size: 13px; color: #d1d5db; }\n' +
			'.dwm-table tr:last-child td { border-bottom: none; }',

		striped:
			'.dwm-table { width: 100%; border-collapse: collapse; }\n' +
			'.dwm-table th { background: #f8f9fa; color: #1d2327; padding: 8px 12px; text-align: left; font-size: 12px; border-bottom: 2px solid #dee2e6; }\n' +
			'.dwm-table tbody tr:nth-child(even) td { background: #f8f9fa; }\n' +
			'.dwm-table tbody tr:nth-child(odd) td { background: #fff; }\n' +
			'.dwm-table td { padding: 7px 12px; font-size: 13px; color: #1d2327; }',

		bordered:
			'.dwm-table { width: 100%; border-collapse: collapse; border: 1px solid #dee2e6; }\n' +
			'.dwm-table th { background: #fff; color: #1d2327; padding: 8px 12px; text-align: left; font-size: 12px; border: 1px solid #dee2e6; }\n' +
			'.dwm-table td { padding: 7px 12px; font-size: 13px; color: #1d2327; border: 1px solid #dee2e6; }',

		ocean:
			'.dwm-table { width: 100%; border-collapse: collapse; }\n' +
			'.dwm-table th { background: #0077b6; color: #fff; padding: 8px 12px; text-align: left; font-size: 13px; }\n' +
			'.dwm-table tbody tr:nth-child(even) td { background: #e0f7fa; }\n' +
			'.dwm-table td { padding: 8px 12px; border-bottom: 1px solid #caf0f8; font-size: 13px; color: #1d2327; }\n' +
			'.dwm-table tr:last-child td { border-bottom: none; }',
	};

	return cssMap[ theme ] || cssMap.default;
}

function getTableThemeJs( theme ) {
	return (
		'(function($){\n' +
		'\t$(function(){\n' +
		'\t\t// Auto-generated table script (' + theme + ' theme).\n' +
		'\t\t// Add optional table interactions here.\n' +
		'\t});\n' +
		'})(jQuery);'
	);
}

function getChartThemeCss( mode, theme ) {
	const swatch = CHART_THEME_SWATCH[ theme ] || CHART_THEME_SWATCH.classic;
	return (
		'.dwm-chart-summary {\n' +
		'\tdisplay: inline-flex;\n' +
		'\talign-items: center;\n' +
		'\tgap: 8px;\n' +
		'\tmargin-bottom: 10px;\n' +
		'\tpadding: 8px 12px;\n' +
		'\tborder-radius: 999px;\n' +
		'\tbackground: ' + swatch.soft + ';\n' +
		'\tcolor: ' + swatch.text + ';\n' +
		'\tfont-size: 12px;\n' +
		'\tfont-weight: 600;\n' +
		'\tborder: 1px solid ' + swatch.accent + '33;\n' +
		'}\n' +
		'.dwm-chart-summary strong {\n' +
		'\tcolor: ' + swatch.accent + ';\n' +
		'}\n' +
		'.dwm-chart-wrapper canvas {\n' +
		'\tmax-height: 360px;\n' +
		'}\n' +
		'/* Auto-generated ' + mode + ' chart styles (' + theme + ' theme). */'
	);
}

function getChartThemeJs( mode, theme ) {
	return (
		'(function($){\n' +
		'\t$(function(){\n' +
		'\t\t// Auto-generated ' + mode + ' chart script (' + theme + ' theme).\n' +
		'\t\t// Add optional chart behavior here.\n' +
		'\t});\n' +
		'})(jQuery);'
	);
}

function getGenericThemeJs( mode, theme ) {
	return (
		'(function($){\n' +
		'\t$(function(){\n' +
		'\t\t// Auto-generated ' + mode + ' script (' + theme + ' theme).\n' +
		'\t\t// Add optional interactions here.\n' +
		'\t});\n' +
		'})(jQuery);'
	);
}

// ── List Mode Templates & CSS ────────────────────────────────────────────────

function getDefaultListTemplate( columnAliases, outputConfig ) {
	if ( columnAliases && typeof columnAliases === 'object' ) {
		const columns = Object.keys( columnAliases );
		if ( columns.length > 0 ) {
			let itemContent = '';
			columns.forEach( function( col ) {
				const label     = columnAliases[ col ] || col;
				const link      = getColumnLink( outputConfig, col );
				const formatter = getColumnFormatter( outputConfig, col );
				itemContent += '\t\t\t<span class="dwm-list-field"><strong>' + label + ':</strong> ' + buildCellContent( col, link, formatter ) + '</span>\n';
			} );

			return (
				'<?php if ( ! empty( $query_results ) ) : ?>\n' +
				'<ul class="dwm-list">\n' +
				'\t<?php foreach ( $query_results as $row ) : ?>\n' +
				'\t\t<li class="dwm-list-item">\n' +
				itemContent +
				'\t\t</li>\n' +
				'\t<?php endforeach; ?>\n' +
				'</ul>\n' +
				'<?php else : ?>\n' +
				'<p>No results found.</p>\n' +
				'<?php endif; ?>'
			);
		}
	}

	return (
		'<?php if ( ! empty( $query_results ) ) : ?>\n' +
		'<ul class="dwm-list">\n' +
		'\t<?php foreach ( $query_results as $row ) : ?>\n' +
		'\t\t<li class="dwm-list-item">\n' +
		'\t\t\t<?php foreach ( $row as $key => $value ) : ?>\n' +
		'\t\t\t\t<span class="dwm-list-field"><strong><?php echo esc_html( $key ); ?>:</strong> <?php echo esc_html( $value ); ?></span>\n' +
		'\t\t\t<?php endforeach; ?>\n' +
		'\t\t</li>\n' +
		'\t<?php endforeach; ?>\n' +
		'</ul>\n' +
		'<?php else : ?>\n' +
		'<p>No results found.</p>\n' +
		'<?php endif; ?>'
	);
}

function getListThemeCss( theme ) {
	const cssMap = {
		clean:
			'.dwm-list { list-style: none; margin: 0; padding: 0; }\n' +
			'.dwm-list-item { padding: 10px 12px; border-bottom: 1px solid #e5e7eb; display: flex; flex-wrap: wrap; gap: 8px 16px; }\n' +
			'.dwm-list-item:last-child { border-bottom: none; }\n' +
			'.dwm-list-field { font-size: 13px; color: #1d2327; }\n' +
			'.dwm-list-field strong { color: #646970; font-weight: 600; }',

		bordered:
			'.dwm-list { list-style: none; margin: 0; padding: 0; border: 1px solid #dee2e6; border-radius: 6px; overflow: hidden; }\n' +
			'.dwm-list-item { padding: 10px 14px; border-bottom: 1px solid #dee2e6; display: flex; flex-wrap: wrap; gap: 8px 16px; }\n' +
			'.dwm-list-item:last-child { border-bottom: none; }\n' +
			'.dwm-list-field { font-size: 13px; color: #1d2327; }\n' +
			'.dwm-list-field strong { color: #495057; font-weight: 600; }',

		striped:
			'.dwm-list { list-style: none; margin: 0; padding: 0; }\n' +
			'.dwm-list-item { padding: 10px 12px; display: flex; flex-wrap: wrap; gap: 8px 16px; }\n' +
			'.dwm-list-item:nth-child(even) { background: #f8f9fa; }\n' +
			'.dwm-list-item:nth-child(odd) { background: #fff; }\n' +
			'.dwm-list-field { font-size: 13px; color: #1d2327; }\n' +
			'.dwm-list-field strong { color: #646970; font-weight: 600; }',

		compact:
			'.dwm-list { list-style: none; margin: 0; padding: 0; }\n' +
			'.dwm-list-item { padding: 6px 10px; border-bottom: 1px solid #f0f0f0; display: flex; flex-wrap: wrap; gap: 4px 12px; font-size: 12px; }\n' +
			'.dwm-list-item:last-child { border-bottom: none; }\n' +
			'.dwm-list-field { color: #333; }\n' +
			'.dwm-list-field strong { color: #666; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; }',

		dark:
			'.dwm-list { list-style: none; margin: 0; padding: 0; background: #1e1e2d; border-radius: 6px; overflow: hidden; }\n' +
			'.dwm-list-item { padding: 10px 14px; border-bottom: 1px solid #374151; display: flex; flex-wrap: wrap; gap: 8px 16px; }\n' +
			'.dwm-list-item:last-child { border-bottom: none; }\n' +
			'.dwm-list-field { font-size: 13px; color: #d1d5db; }\n' +
			'.dwm-list-field strong { color: #9ca3af; font-weight: 600; }',

		accent:
			'.dwm-list { list-style: none; margin: 0; padding: 0; }\n' +
			'.dwm-list-item { padding: 10px 14px; border-bottom: 1px solid #e0e7ff; border-left: 3px solid #667eea; display: flex; flex-wrap: wrap; gap: 8px 16px; }\n' +
			'.dwm-list-item:last-child { border-bottom: none; }\n' +
			'.dwm-list-field { font-size: 13px; color: #1d2327; }\n' +
			'.dwm-list-field strong { color: #4338ca; font-weight: 600; }\n' +
			'.dwm-list-field a { color: #667eea; text-decoration: none; }\n' +
			'.dwm-list-field a:hover { text-decoration: underline; }',
	};

	return cssMap[ theme ] || cssMap.clean;
}

// ── Button Mode Templates & CSS ──────────────────────────────────────────────

function getDefaultButtonTemplate( columnAliases, outputConfig ) {
	if ( columnAliases && typeof columnAliases === 'object' ) {
		const columns = Object.keys( columnAliases );
		if ( columns.length > 0 ) {
			// Use the first column as the button label by default
			const labelCol       = columns[ 0 ];
			const labelFormatter = getColumnFormatter( outputConfig, labelCol );
			const labelValue     = buildValueExpression( labelCol, labelFormatter );

			// Check if first column has link config
			const linkConfig = getColumnLink( outputConfig, labelCol );
			let btnHref = '#';
			let btnTarget = '';
			if ( linkConfig && linkConfig.url ) {
				btnHref = buildLinkUrlPhp( linkConfig.url );
				btnTarget = linkConfig.open_in_new_tab !== false ? ' target="_blank" rel="noopener noreferrer"' : '';
			}

			let metaContent = '';
			columns.slice( 1 ).forEach( function( col ) {
				const alias     = columnAliases[ col ] || col;
				const formatter = getColumnFormatter( outputConfig, col );
				metaContent += '\t\t\t<span class="dwm-btn-meta"><strong>' + alias + ':</strong> ' + buildValueExpression( col, formatter ) + '</span>\n';
			} );

			return (
				'<?php if ( ! empty( $query_results ) ) : ?>\n' +
				'<div class="dwm-button-grid">\n' +
				'\t<?php foreach ( $query_results as $row ) : ?>\n' +
				'\t\t<a href="' + btnHref + '" class="dwm-btn-item"' + btnTarget + '>\n' +
				'\t\t\t<span class="dwm-btn-label">' + labelValue + '</span>\n' +
				metaContent +
				'\t\t</a>\n' +
				'\t<?php endforeach; ?>\n' +
				'</div>\n' +
				'<?php else : ?>\n' +
				'<p>No results found.</p>\n' +
				'<?php endif; ?>'
			);
		}
	}

	return (
		'<?php if ( ! empty( $query_results ) ) : ?>\n' +
		'<div class="dwm-button-grid">\n' +
		'\t<?php foreach ( $query_results as $row ) : ?>\n' +
		'\t\t<a href="#" class="dwm-btn-item">\n' +
		'\t\t\t<span class="dwm-btn-label"><?php echo esc_html( reset( $row ) ); ?></span>\n' +
		'\t\t</a>\n' +
		'\t<?php endforeach; ?>\n' +
		'</div>\n' +
		'<?php else : ?>\n' +
		'<p>No results found.</p>\n' +
		'<?php endif; ?>'
	);
}

function getButtonThemeCss( theme ) {
	const cssMap = {
		solid:
			'.dwm-button-grid { display: flex; flex-wrap: wrap; gap: 8px; }\n' +
			'.dwm-btn-item { display: inline-flex; flex-direction: column; align-items: center; gap: 4px; padding: 10px 18px; background: #2271b1; color: #fff; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; transition: background 0.15s; }\n' +
			'.dwm-btn-item:hover { background: #135e96; color: #fff; }\n' +
			'.dwm-btn-meta { font-size: 11px; font-weight: 400; opacity: 0.85; }\n' +
			'.dwm-btn-meta strong { font-weight: 600; }',

		outline:
			'.dwm-button-grid { display: flex; flex-wrap: wrap; gap: 8px; }\n' +
			'.dwm-btn-item { display: inline-flex; flex-direction: column; align-items: center; gap: 4px; padding: 10px 18px; background: transparent; color: #2271b1; border: 2px solid #2271b1; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; transition: all 0.15s; }\n' +
			'.dwm-btn-item:hover { background: #2271b1; color: #fff; }\n' +
			'.dwm-btn-meta { font-size: 11px; font-weight: 400; opacity: 0.8; }\n' +
			'.dwm-btn-meta strong { font-weight: 600; }',

		pill:
			'.dwm-button-grid { display: flex; flex-wrap: wrap; gap: 8px; }\n' +
			'.dwm-btn-item { display: inline-flex; flex-direction: column; align-items: center; gap: 4px; padding: 10px 24px; background: #667eea; color: #fff; border-radius: 999px; text-decoration: none; font-size: 13px; font-weight: 600; transition: background 0.15s; }\n' +
			'.dwm-btn-item:hover { background: #5a67d8; color: #fff; }\n' +
			'.dwm-btn-meta { font-size: 11px; font-weight: 400; opacity: 0.85; }\n' +
			'.dwm-btn-meta strong { font-weight: 600; }',

		flat:
			'.dwm-button-grid { display: flex; flex-wrap: wrap; gap: 6px; }\n' +
			'.dwm-btn-item { display: inline-flex; flex-direction: column; align-items: center; gap: 4px; padding: 8px 16px; background: #f0f0f1; color: #1d2327; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: 500; transition: background 0.15s; }\n' +
			'.dwm-btn-item:hover { background: #dcdcde; color: #1d2327; }\n' +
			'.dwm-btn-meta { font-size: 11px; font-weight: 400; color: #646970; }\n' +
			'.dwm-btn-meta strong { font-weight: 600; }',

		gradient:
			'.dwm-button-grid { display: flex; flex-wrap: wrap; gap: 8px; }\n' +
			'.dwm-btn-item { display: inline-flex; flex-direction: column; align-items: center; gap: 4px; padding: 10px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; transition: opacity 0.15s; box-shadow: 0 2px 8px rgba(102,126,234,0.3); }\n' +
			'.dwm-btn-item:hover { opacity: 0.9; color: #fff; }\n' +
			'.dwm-btn-meta { font-size: 11px; font-weight: 400; opacity: 0.9; }\n' +
			'.dwm-btn-meta strong { font-weight: 600; }',

		dark:
			'.dwm-button-grid { display: flex; flex-wrap: wrap; gap: 8px; }\n' +
			'.dwm-btn-item { display: inline-flex; flex-direction: column; align-items: center; gap: 4px; padding: 10px 18px; background: #1f2937; color: #e5e7eb; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; transition: background 0.15s; }\n' +
			'.dwm-btn-item:hover { background: #374151; color: #f9fafb; }\n' +
			'.dwm-btn-meta { font-size: 11px; font-weight: 400; color: #9ca3af; }\n' +
			'.dwm-btn-meta strong { font-weight: 600; color: #d1d5db; }',
	};

	return cssMap[ theme ] || cssMap.solid;
}

// ── Card List Mode Templates & CSS ───────────────────────────────────────────

function getDefaultCardListTemplate( columnAliases, outputConfig ) {
	if ( columnAliases && typeof columnAliases === 'object' ) {
		const columns = Object.keys( columnAliases );
		if ( columns.length > 0 ) {
			let fieldContent = '';
			columns.forEach( function( col ) {
				const label     = columnAliases[ col ] || col;
				const link      = getColumnLink( outputConfig, col );
				const formatter = getColumnFormatter( outputConfig, col );
				fieldContent += '\t\t\t<div class="dwm-card-field"><span class="dwm-card-label">' + label + '</span><span class="dwm-card-value">' + buildCellContent( col, link, formatter ) + '</span></div>\n';
			} );

			return (
				'<?php if ( ! empty( $query_results ) ) : ?>\n' +
				'<div class="dwm-card-list">\n' +
				'\t<?php foreach ( $query_results as $row ) : ?>\n' +
				'\t\t<div class="dwm-card-item">\n' +
				fieldContent +
				'\t\t</div>\n' +
				'\t<?php endforeach; ?>\n' +
				'</div>\n' +
				'<?php else : ?>\n' +
				'<p>No results found.</p>\n' +
				'<?php endif; ?>'
			);
		}
	}

	return (
		'<?php if ( ! empty( $query_results ) ) : ?>\n' +
		'<div class="dwm-card-list">\n' +
		'\t<?php foreach ( $query_results as $row ) : ?>\n' +
		'\t\t<div class="dwm-card-item">\n' +
		'\t\t\t<?php foreach ( $row as $key => $value ) : ?>\n' +
		'\t\t\t\t<div class="dwm-card-field"><span class="dwm-card-label"><?php echo esc_html( $key ); ?></span><span class="dwm-card-value"><?php echo esc_html( $value ); ?></span></div>\n' +
		'\t\t\t<?php endforeach; ?>\n' +
		'\t\t</div>\n' +
		'\t<?php endforeach; ?>\n' +
		'</div>\n' +
		'<?php else : ?>\n' +
		'<p>No results found.</p>\n' +
		'<?php endif; ?>'
	);
}

function getCardListThemeCss( theme ) {
	const cssMap = {
		elevated:
			'.dwm-card-list { display: flex; flex-direction: column; gap: 10px; }\n' +
			'.dwm-card-item { background: #fff; border-radius: 8px; padding: 14px 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06); }\n' +
			'.dwm-card-field { display: flex; justify-content: space-between; align-items: center; padding: 4px 0; border-bottom: 1px solid #f3f4f6; }\n' +
			'.dwm-card-field:last-child { border-bottom: none; }\n' +
			'.dwm-card-label { font-size: 12px; font-weight: 600; color: #646970; text-transform: uppercase; letter-spacing: 0.3px; }\n' +
			'.dwm-card-value { font-size: 13px; color: #1d2327; font-weight: 500; }\n' +
			'.dwm-card-value a { color: #2271b1; text-decoration: none; }\n' +
			'.dwm-card-value a:hover { text-decoration: underline; }',

		flat:
			'.dwm-card-list { display: flex; flex-direction: column; gap: 8px; }\n' +
			'.dwm-card-item { background: #f8f9fa; border-radius: 6px; padding: 12px 14px; }\n' +
			'.dwm-card-field { display: flex; justify-content: space-between; align-items: center; padding: 3px 0; }\n' +
			'.dwm-card-label { font-size: 12px; font-weight: 600; color: #646970; }\n' +
			'.dwm-card-value { font-size: 13px; color: #1d2327; }\n' +
			'.dwm-card-value a { color: #2271b1; text-decoration: none; }\n' +
			'.dwm-card-value a:hover { text-decoration: underline; }',

		bordered:
			'.dwm-card-list { display: flex; flex-direction: column; gap: 10px; }\n' +
			'.dwm-card-item { background: #fff; border: 1px solid #dee2e6; border-radius: 6px; padding: 14px 16px; }\n' +
			'.dwm-card-field { display: flex; justify-content: space-between; align-items: center; padding: 4px 0; border-bottom: 1px solid #f0f0f1; }\n' +
			'.dwm-card-field:last-child { border-bottom: none; }\n' +
			'.dwm-card-label { font-size: 12px; font-weight: 600; color: #495057; }\n' +
			'.dwm-card-value { font-size: 13px; color: #1d2327; }\n' +
			'.dwm-card-value a { color: #2271b1; text-decoration: none; }\n' +
			'.dwm-card-value a:hover { text-decoration: underline; }',

		minimal:
			'.dwm-card-list { display: flex; flex-direction: column; gap: 6px; }\n' +
			'.dwm-card-item { padding: 10px 0; border-bottom: 1px solid #e5e7eb; }\n' +
			'.dwm-card-item:last-child { border-bottom: none; }\n' +
			'.dwm-card-field { display: flex; justify-content: space-between; align-items: center; padding: 2px 0; }\n' +
			'.dwm-card-label { font-size: 11px; font-weight: 600; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }\n' +
			'.dwm-card-value { font-size: 13px; color: #333; }\n' +
			'.dwm-card-value a { color: #2271b1; text-decoration: none; }\n' +
			'.dwm-card-value a:hover { text-decoration: underline; }',

		dark:
			'.dwm-card-list { display: flex; flex-direction: column; gap: 10px; }\n' +
			'.dwm-card-item { background: #1f2937; border-radius: 8px; padding: 14px 16px; }\n' +
			'.dwm-card-field { display: flex; justify-content: space-between; align-items: center; padding: 4px 0; border-bottom: 1px solid #374151; }\n' +
			'.dwm-card-field:last-child { border-bottom: none; }\n' +
			'.dwm-card-label { font-size: 12px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.3px; }\n' +
			'.dwm-card-value { font-size: 13px; color: #e5e7eb; font-weight: 500; }\n' +
			'.dwm-card-value a { color: #60a5fa; text-decoration: none; }\n' +
			'.dwm-card-value a:hover { text-decoration: underline; }',

		colorful:
			'.dwm-card-list { display: flex; flex-direction: column; gap: 10px; }\n' +
			'.dwm-card-item { background: linear-gradient(135deg, #f5f3ff 0%, #eff6ff 100%); border-radius: 8px; padding: 14px 16px; border-left: 3px solid #667eea; }\n' +
			'.dwm-card-field { display: flex; justify-content: space-between; align-items: center; padding: 4px 0; border-bottom: 1px solid rgba(102,126,234,0.12); }\n' +
			'.dwm-card-field:last-child { border-bottom: none; }\n' +
			'.dwm-card-label { font-size: 12px; font-weight: 600; color: #4338ca; text-transform: uppercase; letter-spacing: 0.3px; }\n' +
			'.dwm-card-value { font-size: 13px; color: #1e1b4b; font-weight: 500; }\n' +
			'.dwm-card-value a { color: #667eea; text-decoration: none; }\n' +
			'.dwm-card-value a:hover { text-decoration: underline; }',
	};

	return cssMap[ theme ] || cssMap.elevated;
}

// ── Link Helper Utilities ────────────────────────────────────────────────────

function buildLinkUrlPhp( urlTemplate ) {
	// Replace {column_name} template variables with PHP echo statements
	return urlTemplate.replace( /\{([^}]+)\}/g, function( match, colName ) {
		return '<?php echo urlencode( $row[\'' + colName.replace( /'/g, "\\'" ) + '\'] ); ?>';
	} );
}
