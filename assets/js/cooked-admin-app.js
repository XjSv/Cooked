/**
 * Cooked admin frame: header, page tabs and section menu, built with the React
 * components that ship with WordPress (wp.element, wp.components).
 *
 * Each screen's content is still rendered by PHP. Every frame screen (Settings,
 * Import, What's New, Upgrade to Pro) is rendered on each of their pages and moved into the frame's
 * content area, so the page tabs switch screens in place and update the URL with
 * the History API. On Settings and Import, the section menu drives the screen's own
 * (hidden) tabs; screens without sections get a single-column body.
 */
( function ( wp, vars ) {
    'use strict';

    var el = wp.element.createElement,
        useState = wp.element.useState,
        useEffect = wp.element.useEffect,
        useLayoutEffect = wp.element.useLayoutEffect,
        useRef = wp.element.useRef,
        Button = wp.components.Button,
        Card = wp.components.Card,
        CardHeader = wp.components.CardHeader,
        CardBody = wp.components.CardBody,
        ExternalLink = wp.components.ExternalLink,
        Fragment = wp.element.Fragment,
        Modal = wp.components.Modal,
        NavigableMenu = wp.components.NavigableMenu,
        Spinner = wp.components.Spinner;

    function icon( name ) {
        return el( 'span', { className: 'cooked-icon cooked-icon-' + name, 'aria-hidden': true } );
    }

    // Text after the page name in the document title, e.g. " ‹ Site — WordPress".
    var titleSuffix = document.title.indexOf( ' ‹ ' ) > -1 ? document.title.slice( document.title.indexOf( ' ‹ ' ) ) : '';

    function reducedMotion() {
        return window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
    }

    // The section named by the URL hash, or the screen's first section.
    function sectionFromHash( screen ) {
        var hash = window.location.hash.replace( '#', '' ),
            sections = vars.screens[ screen ].sections,
            match = sections.filter( function ( section ) {
                return section.slug === hash;
            } );
        return match.length ? hash : ( sections[0] ? sections[0].slug : '' );
    }

    // The frame screen an admin URL points to (admin.php?page=…), or null.
    function screenFromUrl( href ) {
        var url = new window.URL( href || window.location.href, window.location.href ),
            page = url.searchParams.get( 'page' );
        return url.origin === window.location.origin && /\/admin\.php$/.test( url.pathname ) && vars.screens[ page ] ? page : null;
    }

    // Clicks that should keep the browser's default (new tab/window, download, …).
    function isModifiedClick( e ) {
        return e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey;
    }

    // Path-relative, so the History API accepts it even if admin_url() differs in scheme or host.
    function screenUrl( screen, section ) {
        var url = new window.URL( vars.screens[ screen ].url );
        return url.pathname + url.search + ( section ? '#' + section : '' );
    }

    // Keep WordPress's Recipes submenu in step with the current screen.
    function markAdminMenu( screen ) {
        Object.keys( vars.screens ).forEach( function ( slug ) {
            var link = document.querySelector( '#adminmenu a[href="admin.php?page=' + slug + '"]' ),
                current = slug === screen;
            if ( ! link ) {
                return;
            }
            link.classList.toggle( 'current', current );
            link.parentNode.classList.toggle( 'current', current );
            if ( current ) {
                link.setAttribute( 'aria-current', 'page' );
            } else {
                link.removeAttribute( 'aria-current' );
            }
        } );
    }

    // Tom Select for the screens' selects (cooked-admin-functions.js).
    function enhanceSelects() {
        window.cookedEnhanceSelects( '.cooked-admin-screen select' );
    }

    // What's New "View Full Changelog": the whole readme.txt changelog, fetched once.
    function ChangelogModal( props ) {
        var state = useState( { html: null, error: false } ),
            changelog = state[0],
            setChangelog = state[1];

        useEffect( function () {
            var body = new window.FormData();
            body.append( 'action', 'cooked_full_changelog' );
            body.append( 'nonce', vars.nonces.fullChangelog );
            window.fetch( vars.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' } )
                .then( function ( response ) {
                    return response.json();
                } )
                .then( function ( json ) {
                    if ( ! json || ! json.success ) {
                        throw new Error( 'cooked_full_changelog' );
                    }
                    setChangelog( { html: json.data.html, error: false } );
                } )
                .catch( function () {
                    setChangelog( { html: null, error: true } );
                } );
        }, [] );

        return el( Modal, {
            title: vars.i18n.changelog,
            onRequestClose: props.onClose,
            size: 'large',
            className: 'cooked-modal cooked-changelog-modal',
            overlayClassName: 'cooked-modal-overlay'
        },
            changelog.html ?
                // Built from readme.txt and passed through wp_kses_post() by the AJAX handler.
                el( 'div', { className: 'cooked-welcome-changelog cooked-changelog-full', dangerouslySetInnerHTML: { __html: changelog.html } } ) :
                changelog.error ?
                    el( 'p', { className: 'cooked-modal-error' }, vars.i18n.changelogError ) :
                    el( 'div', { className: 'cooked-modal-loading' }, el( Spinner ) )
        );
    }

    // Splits CSV text into rows of fields (RFC 4180: quoted fields may hold commas,
    // newlines and "" for a quote).
    function parseCsv( text ) {
        var rows = [],
            row = [],
            field = '',
            quoted = false,
            i,
            c;
        text = text.replace( /^\uFEFF/, '' );
        for ( i = 0; i < text.length; i++ ) {
            c = text[ i ];
            if ( quoted ) {
                if ( '"' === c && '"' === text[ i + 1 ] ) {
                    field += '"';
                    i++;
                } else if ( '"' === c ) {
                    quoted = false;
                } else {
                    field += c;
                }
            } else if ( '"' === c ) {
                quoted = true;
            } else if ( ',' === c ) {
                row.push( field );
                field = '';
            } else if ( '\n' === c || '\r' === c ) {
                if ( '\r' === c && '\n' === text[ i + 1 ] ) {
                    i++;
                }
                row.push( field );
                rows.push( row );
                row = [];
                field = '';
            } else {
                field += c;
            }
        }
        if ( field || row.length ) {
            row.push( field );
            rows.push( row );
        }
        return rows.filter( function ( r ) {
            return r.length > 1 || r[0];
        } );
    }

    // A CSV cell for the preview table: Cooked's pipe-separated lists (ingredients,
    // directions) get visible, wrappable separators; the text itself is unchanged.
    function csvCell( cell, index ) {
        var parts = cell.split( '|' );
        return el( 'td', { key: index, className: cell.length > 60 ? 'is-long' : undefined },
            parts.length < 2 ? cell : parts.map( function ( part, partIndex ) {
                return el( Fragment, { key: partIndex },
                    // Spaces around the separator give lines a place to wrap.
                    partIndex > 0 && ' ',
                    partIndex > 0 && el( 'span', { className: 'cooked-csv-separator', 'aria-hidden': true }, '|' ),
                    partIndex > 0 && ' ',
                    part
                );
            } )
        );
    }

    // Import > CSV Import sample files: the parsed file as a table, with Download and
    // Import Now (which hands the file to the screen's own upload form).
    function CsvPreviewModal( props ) {
        var state = useState( { text: null, error: false } ),
            csv = state[0],
            setCsv = state[1],
            importErrorState = useState( false ),
            rows = csv.text ? parseCsv( csv.text ) : [];

        useEffect( function () {
            window.fetch( props.sample.url, { credentials: 'same-origin' } )
                .then( function ( response ) {
                    if ( ! response.ok ) {
                        throw new Error( 'cooked_csv_sample' );
                    }
                    return response.text();
                } )
                .then( function ( text ) {
                    setCsv( { text: text, error: false } );
                } )
                .catch( function () {
                    setCsv( { text: null, error: true } );
                } );
        }, [] );

        function importNow() {
            var input = document.getElementById( 'cooked-csv-file' ),
                button = document.getElementById( 'cooked-csv-import-button' ),
                transfer;
            try {
                transfer = new window.DataTransfer();
                transfer.items.add( new window.File( [ csv.text ], props.sample.name, { type: 'text/csv' } ) );
                input.files = transfer.files;
                input.dispatchEvent( new window.Event( 'change', { bubbles: true } ) );
            } catch ( e ) {
                importErrorState[1]( true );
                return;
            }
            props.onClose();
            button.scrollIntoView( { block: 'center', behavior: reducedMotion() ? 'auto' : 'smooth' } );
            // After the modal has closed, so the upload's confirm() isn't drawn over it.
            window.setTimeout( function () {
                button.click();
            }, 300 );
        }

        return el( Modal, {
            title: vars.i18n.csvPreview.replace( '%s', props.sample.label ),
            onRequestClose: props.onClose,
            size: 'large',
            className: 'cooked-modal cooked-csv-preview-modal',
            overlayClassName: 'cooked-modal-overlay'
        },
            csv.error ?
                el( 'p', { className: 'cooked-modal-error' }, vars.i18n.csvError ) :
                ! csv.text ?
                    el( 'div', { className: 'cooked-modal-loading' }, el( Spinner ) ) :
                    // Focusable, so keyboard users can scroll the table too.
                    el( 'div', { className: 'cooked-modal-scroll-x cooked-csv-preview', tabIndex: 0, role: 'region', 'aria-label': vars.i18n.csvPreview.replace( '%s', props.sample.label ) },
                        el( 'table', null,
                            el( 'thead', null,
                                el( 'tr', null, rows[0].map( function ( heading, index ) {
                                    return el( 'th', { key: index, scope: 'col' }, heading );
                                } ) )
                            ),
                            el( 'tbody', null, rows.slice( 1 ).map( function ( row, rowIndex ) {
                                return el( 'tr', { key: rowIndex }, row.map( csvCell ) );
                            } ) )
                        )
                    ),
            importErrorState[0] && el( 'p', { className: 'cooked-modal-error' }, vars.i18n.csvImportError ),
            el( 'div', { className: 'cooked-modal-actions' },
                el( Button, { variant: 'secondary', href: props.sample.url, download: props.sample.name }, vars.i18n.download ),
                el( Button, { variant: 'primary', onClick: importNow, disabled: ! csv.text }, vars.i18n.importNow )
            )
        );
    }

    function Header( props ) {
        return el( 'div', { className: 'cooked-admin-header' },
            el( 'div', { className: 'cooked-admin-brand' },
                el( 'span', { className: 'cooked-admin-logo' }, icon( 'cooked-icon' ) ),
                el( 'div', null,
                    el( 'h1', { className: 'cooked-admin-title' }, vars.i18n.title ),
                    el( 'p', { className: 'cooked-admin-subtitle' }, props.subtitle )
                )
            ),
            el( 'div', { className: 'cooked-admin-header-actions' },
                el( 'span', { className: 'cooked-admin-version' },
                    el( 'span', { className: 'cooked-admin-version-label' }, vars.i18n.version ),
                    el( 'strong', null, vars.version )
                ),
                el( ExternalLink, { href: vars.docsUrl, className: 'cooked-admin-docs-link' }, vars.i18n.documentation ),
                props.canSave && el( Button, { variant: 'primary', className: 'cooked-admin-save-button', onClick: props.onSave }, vars.i18n.save )
            )
        );
    }

    // Lowercase, without accents, for matching ("Crème" finds "creme").
    function normalize( text ) {
        return String( text || '' ).toLowerCase().normalize( 'NFD' ).replace( /[\u0300-\u036f]/g, '' );
    }

    // Settings search index, built from the Settings form already on the page: each setting's
    // title, description and option labels (so "servings" finds Global Recipe Toggles).
    function buildSettingsIndex() {
        var screen = vars.screens.cooked_settings,
            names = {},
            items = [];
        if ( ! screen ) {
            return items;
        }
        screen.sections.forEach( function ( s ) {
            names[ s.slug ] = s.name;
        } );
        Array.prototype.forEach.call( document.querySelectorAll( '.cooked-admin-screen[data-screen="cooked_settings"] .cooked-settings-tab-content' ), function ( content ) {
            var section = content.id.replace( 'cooked-settings-tab-content-', '' );
            Array.prototype.forEach.call( content.querySelectorAll( '.recipe-setting-block' ), function ( block ) {
                var title = block.querySelector( 'h3.cooked-settings-title' ),
                    desc = block.querySelector( '.cooked-settings-desc' ),
                    labels = Array.prototype.map.call( block.querySelectorAll( 'label' ), function ( label ) {
                        return label.textContent.trim();
                    } ).filter( Boolean );
                if ( ! title || ! names[ section ] ) {
                    return;
                }
                items.push( {
                    id: items.length,
                    title: title.textContent.trim(),
                    desc: desc ? desc.textContent.trim() : '',
                    labels: labels,
                    section: section,
                    sectionName: names[ section ],
                    block: block
                } );
            } );
        } );
        return items;
    }

    // Best matches first: every word must appear somewhere; titles outrank option labels,
    // which outrank descriptions.
    function searchSettings( items, query ) {
        var words = normalize( query ).split( /\s+/ ).filter( Boolean );
        if ( ! words.length ) {
            return [];
        }
        return items.map( function ( item ) {
            var title = normalize( item.title ),
                labels = normalize( item.labels.join( ' ' ) ),
                desc = normalize( item.desc ),
                score = 0,
                label = '';
            for ( var i = 0; i < words.length; i++ ) {
                var word = words[ i ];
                if ( 0 === title.indexOf( word ) ) {
                    score += 4;
                } else if ( title.indexOf( word ) > -1 ) {
                    score += 3;
                } else if ( labels.indexOf( word ) > -1 ) {
                    score += 2;
                    label = label || item.labels.filter( function ( l ) {
                        return normalize( l ).indexOf( word ) > -1;
                    } )[0];
                } else if ( desc.indexOf( word ) > -1 ) {
                    score += 1;
                } else {
                    return null;
                }
            }
            return { item: item, score: score, label: label };
        } ).filter( Boolean ).sort( function ( a, b ) {
            return b.score - a.score || a.item.id - b.item.id;
        } ).slice( 0, 8 );
    }

    // Text with the query's words wrapped in <mark>.
    function highlight( text, query ) {
        var words = normalize( query ).split( /\s+/ ).filter( Boolean ),
            plain = normalize( text ),
            marks = [],
            parts = [],
            last = 0;
        words.forEach( function ( word ) {
            var at = plain.indexOf( word );
            if ( at > -1 ) {
                marks.push( [ at, at + word.length ] );
            }
        } );
        marks.sort( function ( a, b ) {
            return a[0] - b[0];
        } ).forEach( function ( mark, index ) {
            if ( mark[0] < last ) {
                return;
            }
            parts.push( text.slice( last, mark[0] ), el( 'mark', { key: index }, text.slice( mark[0], mark[1] ) ) );
            last = mark[1];
        } );
        parts.push( text.slice( last ) );
        return parts;
    }

    // Header search: type to find a setting, pick one to jump to it (an ARIA combobox with
    // a listbox of suggestions, like Awesomplete).
    function SettingsSearch( props ) {
        var queryState = useState( '' ),
            query = queryState[0],
            setQuery = queryState[1],
            activeState = useState( -1 ),
            active = activeState[0],
            setActive = activeState[1],
            openState = useState( false ),
            open = openState[0],
            setOpen = openState[1],
            index = useRef( null ),
            results;

        if ( ! index.current && query ) {
            index.current = buildSettingsIndex();
        }
        results = query ? searchSettings( index.current || [], query ) : [];

        function choose( result ) {
            setQuery( '' );
            setOpen( false );
            setActive( -1 );
            props.onPick( result.item );
        }

        function onKeyDown( e ) {
            if ( 'ArrowDown' === e.key && results.length ) {
                e.preventDefault();
                setOpen( true );
                setActive( ( active + 1 ) % results.length );
            } else if ( 'ArrowUp' === e.key && results.length ) {
                e.preventDefault();
                setActive( active <= 0 ? results.length - 1 : active - 1 );
            } else if ( 'Enter' === e.key && open && results.length ) {
                e.preventDefault();
                choose( results[ active > -1 ? active : 0 ] );
            } else if ( 'Escape' === e.key ) {
                if ( open ) {
                    e.preventDefault();
                    setOpen( false );
                    setActive( -1 );
                } else {
                    setQuery( '' );
                }
            }
        }

        var expanded = open && '' !== query;

        return el( 'div', { className: 'cooked-admin-search', role: 'search' },
            icon( 'search' ),
            el( 'input', {
                type: 'search',
                className: 'cooked-admin-search-input',
                placeholder: vars.i18n.searchPlaceholder,
                'aria-label': vars.i18n.searchLabel,
                role: 'combobox',
                'aria-autocomplete': 'list',
                'aria-expanded': expanded ? 'true' : 'false',
                'aria-controls': 'cooked-admin-search-results',
                'aria-activedescendant': expanded && active > -1 ? 'cooked-admin-search-result-' + active : undefined,
                autoComplete: 'off',
                value: query,
                onChange: function ( e ) {
                    setQuery( e.target.value );
                    setOpen( true );
                    setActive( -1 );
                },
                onFocus: function () {
                    index.current = null; // rebuild: sections Pro adds, or labels that changed
                    setOpen( true );
                },
                onBlur: function () {
                    setOpen( false );
                    setActive( -1 );
                },
                onKeyDown: onKeyDown
            } ),
            el( 'ul', { id: 'cooked-admin-search-results', className: 'cooked-admin-search-results', role: 'listbox', 'aria-label': vars.i18n.searchLabel, hidden: ! expanded },
                results.map( function ( result, i ) {
                    return el( 'li', {
                        key: result.item.id,
                        id: 'cooked-admin-search-result-' + i,
                        role: 'option',
                        'aria-selected': i === active ? 'true' : 'false',
                        className: 'cooked-admin-search-result' + ( i === active ? ' is-active' : '' ),
                        // mousedown, before the input's blur closes the list.
                        onMouseDown: function ( e ) {
                            e.preventDefault();
                            choose( result );
                        },
                        onMouseEnter: function () {
                            setActive( i );
                        }
                    },
                        el( 'span', { className: 'cooked-admin-search-result-title' }, highlight( result.item.title, query ) ),
                        result.label && el( 'span', { className: 'cooked-admin-search-result-label' }, highlight( result.label, query ) ),
                        el( 'span', { className: 'cooked-admin-search-result-section' }, result.item.sectionName )
                    );
                } ),
                expanded && ! results.length && el( 'li', { className: 'cooked-admin-search-empty', role: 'presentation' }, vars.i18n.searchEmpty )
            ),
            el( 'span', { className: 'screen-reader-text', 'aria-live': 'polite' },
                expanded ? ( results.length ? vars.i18n.searchCount.replace( '%d', results.length ) : vars.i18n.searchEmpty ) : ''
            )
        );
    }

    function PageTabs( props ) {
        var navRef = useRef( null ),
            indicatorState = useState( null ),
            indicator = indicatorState[0],
            setIndicator = indicatorState[1],
            // Off for the first placement, so the line doesn't slide in on page load.
            animatedState = useState( false );

        // Position the underline under the active tab.
        function measure() {
            var active = navRef.current && navRef.current.querySelector( '.cooked-admin-page-tab.is-active' );
            setIndicator( active ? { left: active.offsetLeft, width: active.offsetWidth } : null );
        }

        useLayoutEffect( measure, [ props.screen ] );

        useEffect( function () {
            var resizeObserver = null,
                frame = window.requestAnimationFrame( function () {
                    animatedState[1]( true );
                } );
            window.addEventListener( 'resize', measure );
            if ( window.ResizeObserver ) {
                resizeObserver = new window.ResizeObserver( measure );
                resizeObserver.observe( navRef.current );
            }
            if ( document.fonts && document.fonts.ready ) {
                document.fonts.ready.then( measure );
            }
            return function () {
                window.cancelAnimationFrame( frame );
                window.removeEventListener( 'resize', measure );
                if ( resizeObserver ) {
                    resizeObserver.disconnect();
                }
            };
        }, [] );

        return el( 'nav', { className: 'cooked-admin-page-tabs', 'aria-label': vars.i18n.pages, ref: navRef },
            indicator && el( 'span', {
                className: 'cooked-admin-page-tabs-indicator' + ( animatedState[0] ? ' is-animated' : '' ),
                style: { width: indicator.width + 'px', transform: 'translateX(' + indicator.left + 'px)' },
                'aria-hidden': true
            } ),
            vars.pages.map( function ( page ) {
                var current = page.slug === props.screen;
                return el( 'a', {
                    key: page.slug,
                    href: page.url,
                    className: 'cooked-admin-page-tab' + ( current ? ' is-active' : '' ) + ( page.highlight ? ' is-highlight' : '' ),
                    'aria-current': current ? 'page' : undefined,
                    onClick: function ( e ) {
                        // Pages outside the frame, and modified clicks (new tab/window), navigate normally.
                        if ( ! vars.screens[ page.slug ] || isModifiedClick( e ) ) {
                            return;
                        }
                        e.preventDefault();
                        props.onOpen( page.slug );
                    }
                }, page.highlight && icon( 'star' ), page.label );
            } )
        );
    }

    function SectionMenu( props ) {
        var scrollState = useState( { left: false, right: false } ),
            canScroll = scrollState[0],
            setCanScroll = scrollState[1],
            orientationState = useState( window.matchMedia( '(max-width: 540px)' ).matches ? 'horizontal' : 'vertical' ),
            orientation = orientationState[0],
            setOrientation = orientationState[1],
            // Phones: the first section shown, and the last one that fits whole after it.
            page = useRef( { first: 0, last: 0 } );

        function menu() {
            var root = document.getElementById( 'cooked-admin-app' );
            return root ? root.querySelector( '.cooked-admin-sections' ) : null;
        }

        function buttons() {
            var scroller = menu();
            return scroller ? Array.prototype.slice.call( scroller.querySelectorAll( '.cooked-admin-section' ) ) : [];
        }

        // Width of buttons[from..to], gaps included.
        function span( list, from, to ) {
            return list[ to ].offsetLeft + list[ to ].offsetWidth - list[ from ].offsetLeft;
        }

        // On a phone the menu is one row cut to the sections that fit whole (no label is cut
        // off), centred between the arrows, which page through the rest, as the recipe editor's
        // tabs do. Wider screens show them all.
        function layout( animate ) {
            var scroller = menu(),
                list = buttons(),
                room,
                first,
                last;
            if ( ! scroller || ! list.length ) {
                return;
            }
            scroller.style.width = '';
            if ( ! window.matchMedia( '(max-width: 540px)' ).matches || scroller.scrollWidth <= scroller.clientWidth + 1 ) {
                scroller.scrollLeft = 0;
                page.current = { first: 0, last: list.length - 1 };
                setCanScroll( function ( previous ) {
                    return previous.left || previous.right ? { left: false, right: false } : previous;
                } );
                return;
            }
            room = scroller.parentNode.clientWidth - 96; // the arrows and their gaps
            first = Math.min( page.current.first, list.length - 1 );
            // Fill the last page rather than leave it short.
            while ( first > 0 && span( list, first - 1, list.length - 1 ) <= room ) {
                first--;
            }
            last = first;
            while ( last + 1 < list.length && span( list, first, last + 1 ) <= room ) {
                last++;
            }
            page.current = { first: first, last: last };
            scroller.style.width = Math.ceil( span( list, first, last ) ) + 'px';
            scroller.scrollTo( {
                left: list[ first ].offsetLeft - list[0].offsetLeft,
                behavior: animate && ! reducedMotion() ? 'smooth' : 'auto'
            } );
            setCanScroll( function ( previous ) {
                var next = { left: first > 0, right: last < list.length - 1 };
                return previous.left === next.left && previous.right === next.right ? previous : next;
            } );
        }

        function scrollSections( direction ) {
            var list = buttons(),
                room = menu() ? menu().parentNode.clientWidth - 96 : 0,
                first = page.current.first;
            if ( direction > 0 ) {
                page.current.first = Math.min( page.current.last + 1, list.length - 1 );
            } else {
                first = Math.max( first - 1, 0 );
                while ( first > 0 && span( list, first - 1, page.current.first - 1 ) <= room ) {
                    first--;
                }
                page.current.first = first;
            }
            layout( true );
        }

        // A section chosen or focused (arrow keys) outside the page brings its page in.
        function reveal( index, animate ) {
            if ( index > -1 && ( index < page.current.first || index > page.current.last ) ) {
                page.current.first = index;
                layout( animate );
            }
        }

        useEffect( function () {
            var scroller = menu(),
                resizeObserver = null,
                narrow = window.matchMedia( '(max-width: 540px)' ),
                onResize = function () {
                    layout( false );
                },
                onFocus = function ( e ) {
                    reveal( buttons().indexOf( e.target.closest( '.cooked-admin-section' ) ), true );
                },
                onNarrowChange = function () {
                    setOrientation( narrow.matches ? 'horizontal' : 'vertical' );
                    layout( false );
                };
            if ( ! scroller ) {
                return;
            }
            scroller.addEventListener( 'focusin', onFocus );
            window.addEventListener( 'resize', onResize );
            if ( window.ResizeObserver ) {
                resizeObserver = new window.ResizeObserver( onResize );
                resizeObserver.observe( scroller.parentNode );
            }
            if ( narrow.addEventListener ) {
                narrow.addEventListener( 'change', onNarrowChange );
            } else {
                narrow.addListener( onNarrowChange );
            }
            layout( false );
            return function () {
                scroller.removeEventListener( 'focusin', onFocus );
                window.removeEventListener( 'resize', onResize );
                if ( resizeObserver ) {
                    resizeObserver.disconnect();
                }
                if ( narrow.removeEventListener ) {
                    narrow.removeEventListener( 'change', onNarrowChange );
                } else {
                    narrow.removeListener( onNarrowChange );
                }
            };
        }, [] );

        useEffect( function () {
            var list = buttons(),
                active = menu() && menu().querySelector( '.cooked-admin-section.is-active' );
            layout( false );
            if ( 'horizontal' === orientation && active ) {
                reveal( list.indexOf( active ), false );
            }
        }, [ props.active, props.sections, orientation ] );

        function arrow( direction, disabled, label ) {
            return el( 'button', {
                type: 'button',
                className: 'cooked-admin-sections-arrow ' + ( direction < 0 ? 'is-left' : 'is-right' ),
                onClick: function () {
                    scrollSections( direction );
                },
                disabled: disabled,
                'aria-label': label
            }, icon( direction < 0 ? 'angle-left' : 'angle-right' ) );
        }

        return el( Fragment, null,
            arrow( -1, ! canScroll.left, vars.i18n.sectionsScrollLeft ),
            // role="toolbar" (buttons moved between with the arrow keys): NavigableMenu defaults
            // to "menu", which requires menuitem children, and sets aria-orientation, which
            // "group" doesn't allow.
            el( NavigableMenu, { className: 'cooked-admin-sections', role: 'toolbar', orientation: orientation, 'aria-label': vars.i18n.sections },
                el( 'p', { className: 'cooked-admin-sections-title' }, vars.i18n.sections ),
                props.sections.map( function ( section ) {
                    var active = section.slug === props.active;
                    return el( Button, {
                        key: section.slug,
                        className: 'cooked-admin-section' + ( active ? ' is-active' : '' ),
                        'aria-current': active ? 'true' : undefined,
                        onClick: function () {
                            props.onSelect( section.slug );
                        }
                    }, icon( section.icon ), el( 'span', null, section.name ) );
                } )
            ),
            arrow( 1, ! canScroll.right, vars.i18n.sectionsScrollRight )
        );
    }

    function App() {
        var screenState = useState( vars.current ),
            screen = screenState[0],
            setScreen = screenState[1],
            state = useState( function () {
                return sectionFromHash( vars.current );
            } ),
            section = state[0],
            setSection = state[1],
            contentRef = useRef( null ),
            sentinelRef = useRef( null ),
            openScreenRef = useRef( null ),
            screenRef = useRef( vars.current ),
            changelogState = useState( false ),
            csvSampleState = useState( null ),
            switched = useRef( false ),
            // The last section shown on each screen, restored when switching back.
            lastSections = useRef( {} ),
            stuckState = useState( false ),
            config = vars.screens[ screen ],
            hasSections = config.sections.length > 0,
            current = config.sections.filter( function ( s ) {
                return s.slug === section;
            } )[0];

        useEffect( function () {
            Array.prototype.forEach.call( document.querySelectorAll( '.cooked-admin-screen' ), function ( content ) {
                contentRef.current.appendChild( content );
            } );
            document.getElementById( 'cooked-admin-app' ).classList.add( 'cooked-admin-ready' );
            // After cooked-admin-functions.js has wrapped the selects (on DOM ready).
            window.jQuery( enhanceSelects );

            function onPopState() {
                var next = screenFromUrl();
                if ( next ) {
                    setScreen( next );
                    setSection( sectionFromHash( next ) );
                }
            }

            // Links inside a screen to another frame screen (e.g. What's New -> Settings)
            // switch in place too.
            function onContentClick( e ) {
                var sample = e.target.closest( '.cooked-csv-sample' );
                if ( e.target.closest( '.cooked-full-changelog-button' ) ) {
                    changelogState[1]( true );
                    return;
                }
                // Sample CSVs open a preview; modified clicks still download.
                if ( sample && ! isModifiedClick( e ) ) {
                    e.preventDefault();
                    csvSampleState[1]( {
                        url: sample.href,
                        name: sample.getAttribute( 'download' ),
                        label: sample.textContent.trim()
                    } );
                    return;
                }
                var link = e.target.closest( 'a[href]' ),
                    next = link && ! link.target && ! isModifiedClick( e ) && screenFromUrl( link.href );
                // A link to the current screen (e.g. Import's "reload the import screen")
                // is a real reload.
                if ( next && next !== screenRef.current ) {
                    e.preventDefault();
                    openScreenRef.current( next );
                }
            }

            var content = contentRef.current;
            window.addEventListener( 'popstate', onPopState );
            content.addEventListener( 'click', onContentClick );
            return function () {
                window.removeEventListener( 'popstate', onPopState );
                content.removeEventListener( 'click', onContentClick );
            };
        }, [] );

        function openScreen( next ) {
            if ( next === screen ) {
                return;
            }
            var nextSection;
            lastSections.current[ screen ] = section;
            nextSection = lastSections.current[ next ] || ( vars.screens[ next ].sections[0] ? vars.screens[ next ].sections[0].slug : '' );
            if ( window.history.pushState ) {
                window.history.pushState( null, '', screenUrl( next, nextSection ) );
            }
            setScreen( next );
            setSection( nextSection );
        }
        openScreenRef.current = openScreen;
        screenRef.current = screen;

        useEffect( function () {
            Array.prototype.forEach.call( document.querySelectorAll( '.cooked-admin-screen' ), function ( content ) {
                content.hidden = content.getAttribute( 'data-screen' ) !== screen;
            } );
            document.title = config.title + titleSuffix;
            markAdminMenu( screen );
        }, [ screen ] );

        useEffect( function () {
            if ( ! window.IntersectionObserver || ! sentinelRef.current ) {
                return;
            }
            var sticky = document.querySelector( '.cooked-admin-sticky' ),
                stickyTop = sticky ? parseInt( window.getComputedStyle( sticky ).top, 10 ) || 0 : 0,
                observer = new window.IntersectionObserver( function ( entries ) {
                    stuckState[1]( ! entries[0].isIntersecting );
                }, { rootMargin: '-' + stickyTop + 'px 0px 0px 0px' } );
            observer.observe( sentinelRef.current );
            return function () {
                observer.disconnect();
            };
        }, [] );

        useEffect( function () {
            // Every one: fields added to the form (e.g. Cooked Pro's License nonce) bring their own,
            // and PHP keeps the last one posted.
            var referers = document.querySelectorAll( '.cooked-admin-screen[data-screen="' + screen + '"] input[name="_wp_http_referer"]' );
            Array.prototype.forEach.call( document.querySelectorAll( '.cooked-admin-screen[data-screen="' + screen + '"] .cooked-settings-tab-content' ), function ( content ) {
                content.style.display = content.id === 'cooked-settings-tab-content-' + section ? 'block' : 'none';
            } );
            if ( switched.current ) {
                var content = document.querySelector( '.cooked-admin-content' );
                if ( content ) {
                    content.classList.remove( 'is-entering' );
                    void content.offsetWidth;
                    content.classList.add( 'is-entering' );
                }
                scrollToContent();
                // Focus stays on the tab or section button that was used (arrow keys keep moving
                // through them); screen readers hear what is now shown.
                if ( wp.a11y && wp.a11y.speak ) {
                    wp.a11y.speak( current ? vars.i18n.shownSection.replace( '%1$s', config.title ).replace( '%2$s', current.name ) : config.title );
                }
            }
            switched.current = true;
            if ( window.history.replaceState ) {
                window.history.replaceState( null, '', screenUrl( screen, section ) );
            }
            // The form was rendered on whichever frame page loaded first; send it back
            // to its own screen (and section) after saving.
            Array.prototype.forEach.call( referers, function ( referer ) {
                referer.value = screenUrl( screen, section );
            } );
        }, [ screen, section ] );

        function scrollToContent() {
            var body = document.querySelector( '.cooked-admin-body' ),
                sticky = document.querySelector( '.cooked-admin-sticky' ),
                stickyTop,
                top;
            if ( ! body || ! sticky ) {
                return;
            }
            stickyTop = parseInt( window.getComputedStyle( sticky ).top, 10 ) || 0;
            top = body.getBoundingClientRect().top + window.scrollY - stickyTop - sticky.offsetHeight;
            if ( window.scrollY > top ) {
                window.scrollTo( {
                    top: top,
                    behavior: reducedMotion() ? 'auto' : 'smooth'
                } );
            }
        }

        // Settings search: open Settings and the setting's section, then bring the setting
        // into view below the sticky header, flash it, and focus its control.
        function goToSetting( item ) {
            if ( 'cooked_settings' !== screen ) {
                openScreen( 'cooked_settings' );
            }
            setSection( item.section );
            window.setTimeout( function () {
                var sticky = document.querySelector( '.cooked-admin-sticky' ),
                    stickyTop = sticky ? parseInt( window.getComputedStyle( sticky ).top, 10 ) || 0 : 0,
                    offset = stickyTop + ( sticky ? sticky.offsetHeight : 0 ) + 16,
                    control = item.block.querySelector( '.ts-control, input:not([type="hidden"]), select, textarea, button' );
                window.scrollTo( {
                    top: item.block.getBoundingClientRect().top + window.scrollY - offset,
                    behavior: reducedMotion() ? 'auto' : 'smooth'
                } );
                item.block.classList.remove( 'is-search-target' );
                void item.block.offsetWidth;
                item.block.classList.add( 'is-search-target' );
                if ( control ) {
                    control.focus( { preventScroll: true } );
                }
            }, 80 );
        }

        function save() {
            var submit = document.getElementById( 'submit' );
            if ( submit ) {
                submit.click();
            }
        }

        return el( 'div', { className: 'cooked-admin' },
            changelogState[0] && el( ChangelogModal, {
                onClose: function () {
                    changelogState[1]( false );
                }
            } ),
            csvSampleState[0] && el( CsvPreviewModal, {
                sample: csvSampleState[0],
                onClose: function () {
                    csvSampleState[1]( null );
                }
            } ),
            // Past the header, tabs and section menu to the section's fields.
            el( 'a', {
                className: 'cooked-admin-skip-link',
                href: '#cooked-admin-content',
                onClick: function ( e ) {
                    var target = document.getElementById( 'cooked-admin-content' );
                    e.preventDefault();
                    if ( target ) {
                        scrollToContent();
                        target.focus( { preventScroll: true } );
                    }
                }
            }, vars.i18n.skipToContent ),
            el( 'div', { className: 'cooked-admin-sticky-sentinel', ref: sentinelRef } ),
            el( Card, { className: 'cooked-admin-frame' },
                el( 'div', { className: 'cooked-admin-sticky' + ( stuckState[0] ? ' is-stuck' : '' ) },
                    el( 'div', { className: 'cooked-admin-sticky-inner' },
                        el( CardHeader, { className: 'cooked-admin-frame-header' },
                            el( Header, { subtitle: config.title, canSave: config.canSave && section !== 'migration' && section !== 'developer', onSave: save } )
                        ),
                        el( 'div', { className: 'cooked-admin-tabs-row' },
                            el( PageTabs, { screen: screen, onOpen: openScreen } ),
                            vars.screens.cooked_settings && el( SettingsSearch, { onPick: goToSetting } )
                        )
                    )
                ),
                el( 'div', { className: 'cooked-admin-body' + ( hasSections ? '' : ' is-single' ) },
                    hasSections && el( 'div', { className: 'cooked-admin-sidebar' },
                        el( SectionMenu, { sections: config.sections, active: section, onSelect: setSection } )
                    ),
                    el( 'div', { className: 'cooked-admin-content', id: 'cooked-admin-content', tabIndex: -1 },
                        current && el( 'div', { className: 'cooked-admin-section-header' },
                            el( 'span', { className: 'cooked-admin-section-icon' }, icon( current.icon ) ),
                            el( 'h2', null, current.name )
                        ),
                        el( CardBody, { className: 'cooked-admin-section-body' },
                            el( 'div', { ref: contentRef } )
                        )
                    )
                )
            )
        );
    }

    var root = document.getElementById( 'cooked-admin-app' );
    if ( root ) {
        wp.element.createRoot( root ).render( el( App ) );
    }
}( window.wp, cooked_admin_app_js_vars ) );
