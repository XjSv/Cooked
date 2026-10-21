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
        Fragment = wp.element.Fragment,
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
                el( 'span', { className: 'cooked-admin-version' },
                    el( 'span', { className: 'cooked-admin-version-label' }, vars.i18n.version ),
                    el( 'strong', null, vars.version )
                ),
                el( ExternalLink, { href: vars.docsUrl, className: 'cooked-admin-docs-link' }, vars.i18n.documentation ),
                props.canSave && el( Button, { variant: 'primary', className: 'cooked-admin-save-button', onClick: props.onSave }, vars.i18n.save )
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
            setOrientation = orientationState[1];

        function menu() {
            var root = document.getElementById( 'cooked-admin-app' );
            return root ? root.querySelector( '.cooked-admin-sections' ) : null;
        }

        function updateScrollState() {
            var scroller = menu();
            if ( ! scroller ) {
                return;
            }
            var next = {
                left: scroller.scrollLeft > 1,
                right: scroller.scrollLeft + scroller.clientWidth < scroller.scrollWidth - 1
            };
            setCanScroll( function ( previous ) {
                return previous.left === next.left && previous.right === next.right ? previous : next;
            } );
        }

        function scrollSections( direction ) {
            var scroller = menu();
            if ( ! scroller ) {
                return;
            }
            scroller.scrollBy( {
                left: direction * Math.max( 120, scroller.clientWidth * 0.7 ),
                behavior: window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 'auto' : 'smooth'
            } );
        }

        useEffect( function () {
            var scroller = menu(),
                resizeObserver = null,
                narrow = window.matchMedia( '(max-width: 540px)' ),
                onNarrowChange = function () {
                    setOrientation( narrow.matches ? 'horizontal' : 'vertical' );
                    updateScrollState();
                };
            if ( ! scroller ) {
                return;
            }
            scroller.addEventListener( 'scroll', updateScrollState, { passive: true } );
            window.addEventListener( 'resize', updateScrollState );
            if ( window.ResizeObserver ) {
                resizeObserver = new window.ResizeObserver( updateScrollState );
                resizeObserver.observe( scroller );
            }
            if ( narrow.addEventListener ) {
                narrow.addEventListener( 'change', onNarrowChange );
            } else {
                narrow.addListener( onNarrowChange );
            }
            updateScrollState();
            return function () {
                scroller.removeEventListener( 'scroll', updateScrollState );
                window.removeEventListener( 'resize', updateScrollState );
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
            updateScrollState();
        }, [ props.active, orientation ] );

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
            el( NavigableMenu, { className: 'cooked-admin-sections', orientation: orientation, 'aria-label': vars.i18n.sections },
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
            ),
            arrow( 1, ! canScroll.right, vars.i18n.sectionsScrollRight )
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
                    el( 'div', { className: 'cooked-admin-sticky-inner' },
                        el( CardHeader, { className: 'cooked-admin-frame-header' },
                            el( Header, { canSave: section !== 'migration' && section !== 'developer', onSave: save } )
                        ),
                        el( PageTabs )
                    )
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
