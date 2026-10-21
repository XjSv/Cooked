/**
 * Cooked recipe gallery: a scroll-snap slider with dots or thumbnails, and PhotoSwipe
 * to view images fullscreen. Markup comes from Cooked_Shortcodes::cooked_gallery_html().
 */
( function () {
    'use strict';

    var VIDEO_HOSTS = [ 'https://www.youtube-nocookie.com/embed/', 'https://player.vimeo.com/video/' ];

    function initGallery( root ) {
        var track = root.querySelector( '.cooked-gallery-track' ),
            slides = Array.prototype.slice.call( track.children ),
            navItems = Array.prototype.slice.call( root.querySelectorAll( '.cooked-gallery-nav-item' ) ),
            prev = root.querySelector( '.cooked-gallery-prev' ),
            next = root.querySelector( '.cooked-gallery-next' ),
            loop = root.getAttribute( 'data-loop' ) !== 'false',
            autoplay = parseInt( root.getAttribute( 'data-autoplay' ), 10 ) || 0,
            current = 0,
            autoplayTimer = null,
            scrollFrame = null;

        if ( root.getAttribute( 'data-swipe' ) === 'false' ) {
            root.classList.add( 'cooked-gallery-noswipe' );
        }

        function direction() {
            return window.getComputedStyle( track ).direction === 'rtl' ? -1 : 1;
        }

        function stopVideos( except ) {
            slides.forEach( function ( slide, index ) {
                var iframe = slide.querySelector( 'iframe' );
                if ( iframe && index !== except ) {
                    iframe.remove();
                    slide.classList.remove( 'cooked-gallery-playing' );
                }
            } );
        }

        function update( index ) {
            current = index;
            navItems.forEach( function ( item, i ) {
                item.classList.toggle( 'cooked-gallery-active', i === index );
                if ( i === index ) {
                    item.setAttribute( 'aria-current', 'true' );
                } else {
                    item.removeAttribute( 'aria-current' );
                }
            } );
            if ( prev && next && ! loop ) {
                prev.disabled = index === 0;
                next.disabled = index === slides.length - 1;
            }
            stopVideos( index );
        }

        function goTo( index ) {
            if ( loop ) {
                index = ( index + slides.length ) % slides.length;
            } else {
                index = Math.max( 0, Math.min( slides.length - 1, index ) );
            }
            track.scrollTo( { left: index * track.clientWidth * direction(), behavior: 'smooth' } );
            update( index );
        }

        function stopAutoplay() {
            if ( autoplayTimer ) {
                window.clearInterval( autoplayTimer );
                autoplayTimer = null;
            }
        }

        track.addEventListener( 'scroll', function () {
            if ( scrollFrame ) {
                return;
            }
            scrollFrame = window.requestAnimationFrame( function () {
                scrollFrame = null;
                var index = Math.round( Math.abs( track.scrollLeft ) / Math.max( 1, track.clientWidth ) );
                if ( index !== current ) {
                    update( index );
                }
            } );
        }, { passive: true } );

        if ( prev && next ) {
            prev.addEventListener( 'click', function () {
                stopAutoplay();
                goTo( current - 1 );
            } );
            next.addEventListener( 'click', function () {
                stopAutoplay();
                goTo( current + 1 );
            } );
        }

        navItems.forEach( function ( item ) {
            item.addEventListener( 'click', function () {
                stopAutoplay();
                goTo( parseInt( item.getAttribute( 'data-slide' ), 10 ) );
            } );
        } );

        root.addEventListener( 'keydown', function ( e ) {
            if ( e.key === 'ArrowLeft' || e.key === 'ArrowRight' ) {
                e.preventDefault();
                stopAutoplay();
                goTo( current + ( e.key === 'ArrowRight' ? 1 : -1 ) * direction() );
            }
        } );

        slides.forEach( function ( slide ) {
            var play = slide.querySelector( '.cooked-gallery-play' ),
                embed = slide.getAttribute( 'data-video-embed' ) || '';

            if ( ! play ) {
                return;
            }

            play.addEventListener( 'click', function () {
                var valid = VIDEO_HOSTS.some( function ( host ) {
                    return embed.indexOf( host ) === 0;
                } );
                if ( ! valid ) {
                    return;
                }
                stopAutoplay();
                var iframe = document.createElement( 'iframe' );
                iframe.src = embed + '?autoplay=1';
                iframe.title = play.getAttribute( 'aria-label' );
                iframe.allow = 'autoplay; fullscreen; picture-in-picture';
                iframe.allowFullscreen = true;
                slide.appendChild( iframe );
                slide.classList.add( 'cooked-gallery-playing' );
            } );
        } );

        if ( autoplay && slides.length > 1 ) {
            autoplayTimer = window.setInterval( function () {
                goTo( current + 1 );
            }, autoplay );
            [ 'pointerdown', 'focusin', 'wheel', 'touchstart' ].forEach( function ( type ) {
                root.addEventListener( type, stopAutoplay, { passive: true } );
            } );
        }

        initLightbox( root, slides, loop, function ( slideIndex ) {
            var link = slides[ slideIndex ].querySelector( '.cooked-gallery-image' );
            stopAutoplay();
            track.scrollTo( { left: slideIndex * track.clientWidth * direction() } );
            update( slideIndex );
            if ( link ) {
                link.focus( { preventScroll: true } );
            }
        } );

        update( 0 );
        root.classList.add( 'cooked-gallery-loaded' );
    }

    function initLightbox( root, slides, loop, onClose ) {
        var images = slides.filter( function ( slide ) {
            return slide.querySelector( '.cooked-gallery-image' );
        } );

        if ( ! images.length ) {
            return;
        }

        if ( root.getAttribute( 'data-allowfullscreen' ) === 'false' || typeof window.PhotoSwipeLightbox === 'undefined' ) {
            root.addEventListener( 'click', function ( e ) {
                if ( e.target.closest( '.cooked-gallery-image' ) ) {
                    e.preventDefault();
                }
            } );
            return;
        }

        var lightbox = new window.PhotoSwipeLightbox( {
            gallery: root,
            children: '.cooked-gallery-image',
            pswpModule: window.PhotoSwipe,
            loop: loop,
            returnFocus: false
        } );

        lightbox.on( 'uiRegister', function () {
            lightbox.pswp.ui.registerElement( {
                name: 'cooked-caption',
                order: 9,
                isButton: false,
                appendTo: 'root',
                onInit: function ( el, pswp ) {
                    pswp.on( 'change', function () {
                        var link = pswp.currSlide.data.element;
                        el.textContent = link ? link.getAttribute( 'data-caption' ) || '' : '';
                        el.hidden = ! el.textContent;
                    } );
                }
            } );
        } );

        var closingIndex = 0;

        lightbox.on( 'close', function () {
            closingIndex = slides.indexOf( images[ lightbox.pswp.currIndex ] );
        } );

        lightbox.on( 'destroy', function () {
            onClose( closingIndex );
        } );

        lightbox.init();
    }

    function init() {
        Array.prototype.forEach.call( document.querySelectorAll( '.cooked-gallery:not(.cooked-gallery-loaded)' ), initGallery );
    }

    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', init );
    } else {
        init();
    }
}() );
