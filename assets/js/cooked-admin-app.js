/**
 * Cooked admin frame: header, page tabs and section menu, built with the React
 * components that ship with WordPress (wp.element, wp.components).
 *
 * The settings form is still rendered by PHP; it is moved into the frame's content
 * area, and the section menu drives the form's own (hidden) tabs.
 */
( function ( wp, vars ) {
    'use strict';

    var el = wp.element.createElement,
        useState = wp.element.useState,
        useEffect = wp.element.useEffect,
        useRef = wp.element.useRef,
        Button = wp.components.Button,
        Card = wp.components.Card,
        CardHeader = wp.components.CardHeader,
        CardBody = wp.components.CardBody,
        ExternalLink = wp.components.ExternalLink,
        NavigableMenu = wp.components.NavigableMenu;

    function icon( name ) {
        return el( 'span', { className: 'cooked-icon cooked-icon-' + name, 'aria-hidden': true } );
    }

    function initialSection() {
        var hash = window.location.hash.replace( '#', '' ),
            match = vars.sections.filter( function ( section ) {
                return section.slug === hash;
            } );
        return match.length ? hash : ( vars.sections[0] ? vars.sections[0].slug : '' );
    }

    function Header( props ) {
        return el( 'div', { className: 'cooked-admin-header' },
            el( 'div', { className: 'cooked-admin-brand' },
                el( 'span', { className: 'cooked-admin-logo' }, icon( 'cooked-icon' ) ),
                el( 'div', null,
                    el( 'h1', { className: 'cooked-admin-title' }, vars.i18n.title ),
                    el( 'p', { className: 'cooked-admin-subtitle' }, vars.i18n.subtitle )
                )
            ),
            el( 'div', { className: 'cooked-admin-header-actions' },
                el( 'span', { className: 'cooked-admin-version' }, vars.version ),
                el( ExternalLink, { href: vars.docsUrl }, vars.i18n.documentation ),
                props.canSave && el( Button, { variant: 'primary', onClick: props.onSave }, vars.i18n.save )
            )
        );
    }

    function PageTabs() {
        return el( 'nav', { className: 'cooked-admin-page-tabs', 'aria-label': vars.i18n.pages },
            vars.pages.map( function ( page ) {
                return el( 'a', {
                    key: page.slug,
                    href: page.url,
                    className: 'cooked-admin-page-tab' + ( page.current ? ' is-active' : '' ) + ( page.highlight ? ' is-highlight' : '' ),
                    'aria-current': page.current ? 'page' : undefined
                }, page.label );
            } )
        );
    }

    function SectionMenu( props ) {
        return el( NavigableMenu, { className: 'cooked-admin-sections', orientation: 'vertical', 'aria-label': vars.i18n.sections },
            el( 'p', { className: 'cooked-admin-sections-title' }, vars.i18n.sections ),
            vars.sections.map( function ( section ) {
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
        );
    }

    function App() {
        var state = useState( initialSection ),
            section = state[0],
            setSection = state[1],
            contentRef = useRef( null ),
            sentinelRef = useRef( null ),
            switched = useRef( false ),
            stuckState = useState( false ),
            current = vars.sections.filter( function ( s ) {
                return s.slug === section;
            } )[0];

        useEffect( function () {
            var legacy = document.getElementById( vars.legacyId );
            if ( legacy && contentRef.current ) {
                contentRef.current.appendChild( legacy );
            }
            document.getElementById( 'cooked-admin-app' ).classList.add( 'cooked-admin-ready' );
        }, [] );

        useEffect( function () {
            if ( ! window.IntersectionObserver || ! sentinelRef.current ) {
                return;
            }
            var adminBar = parseInt( window.getComputedStyle( document.documentElement ).getPropertyValue( '--wp-admin--admin-bar--height' ), 10 ) || 32,
                observer = new window.IntersectionObserver( function ( entries ) {
                    stuckState[1]( ! entries[0].isIntersecting );
                }, { rootMargin: '-' + ( adminBar + 10 ) + 'px 0px 0px 0px' } );
            observer.observe( sentinelRef.current );
            return function () {
                observer.disconnect();
            };
        }, [] );

        useEffect( function () {
            Array.prototype.forEach.call( document.querySelectorAll( '.cooked-settings-tab-content' ), function ( content ) {
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
            }
            switched.current = true;
            if ( section && window.history.replaceState ) {
                window.history.replaceState( null, '', '#' + section );
            }
            var referer = document.querySelector( '#' + vars.legacyId + ' input[name="_wp_http_referer"]' );
            if ( referer && section ) {
                referer.value = referer.value.split( '#' )[0] + '#' + section;
            }
        }, [ section ] );

        function scrollToContent() {
            var body = document.querySelector( '.cooked-admin-body' ),
                sticky = document.querySelector( '.cooked-admin-sticky' ),
                adminBar = parseInt( window.getComputedStyle( document.documentElement ).getPropertyValue( '--wp-admin--admin-bar--height' ), 10 ) || 32,
                top;
            if ( ! body || ! sticky ) {
                return;
            }
            top = body.getBoundingClientRect().top + window.scrollY - adminBar - sticky.offsetHeight;
            if ( window.scrollY > top ) {
                window.scrollTo( {
                    top: top,
                    behavior: window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 'auto' : 'smooth'
                } );
            }
        }

        function save() {
            var submit = document.getElementById( 'submit' );
            if ( submit ) {
                submit.click();
            }
        }

        return el( 'div', { className: 'cooked-admin' },
            el( 'div', { className: 'cooked-admin-sticky-sentinel', ref: sentinelRef } ),
            el( Card, { className: 'cooked-admin-frame' },
                el( 'div', { className: 'cooked-admin-sticky' + ( stuckState[0] ? ' is-stuck' : '' ) },
                    el( CardHeader, { className: 'cooked-admin-frame-header' },
                        el( Header, { canSave: section !== 'migration' && section !== 'developer', onSave: save } )
                    ),
                    el( PageTabs )
                ),
                el( 'div', { className: 'cooked-admin-body' },
                    el( 'div', { className: 'cooked-admin-sidebar' },
                        el( SectionMenu, { active: section, onSelect: setSection } )
                    ),
                    el( 'div', { className: 'cooked-admin-content' },
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

    document.addEventListener( 'click', function ( e ) {
        var button = e.target.closest( '.cooked-copy-code' ),
            code = button && document.getElementById( button.getAttribute( 'data-target' ) );
        if ( ! code || ! window.navigator.clipboard ) {
            return;
        }
        window.navigator.clipboard.writeText( code.textContent ).then( function () {
            var label = button.textContent;
            button.textContent = vars.i18n.copied;
            window.setTimeout( function () {
                button.textContent = label;
            }, 2000 );
        } );
    } );

    var root = document.getElementById( 'cooked-admin-app' );
    if ( root ) {
        wp.element.createRoot( root ).render( el( App ) );
    }
}( window.wp, cooked_admin_app_js_vars ) );
