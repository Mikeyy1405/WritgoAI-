/**
 * WritgoAI Theme JavaScript
 *
 * Handles mobile navigation, smooth scrolling, animations, and other interactive elements.
 *
 * @package WritgoAI
 */

( function() {
    'use strict';

    /**
     * Mobile Navigation Toggle with Smooth Animation
     */
    function initMobileNav() {
        const menuToggle = document.querySelector( '.menu-toggle' );
        const navigation = document.querySelector( '.main-navigation' );
        const menu = document.querySelector( '#primary-menu' );
        const backdrop = document.querySelector( '.menu-backdrop' );
        const body = document.body;

        if ( ! menuToggle || ! navigation ) {
            return;
        }

        function openMenu() {
            menuToggle.setAttribute( 'aria-expanded', 'true' );
            navigation.classList.add( 'toggled' );
            if ( backdrop ) {
                backdrop.classList.add( 'is-visible' );
            }
            body.style.overflow = 'hidden';
        }

        function closeMenu() {
            menuToggle.setAttribute( 'aria-expanded', 'false' );
            navigation.classList.remove( 'toggled' );
            if ( backdrop ) {
                backdrop.classList.remove( 'is-visible' );
            }
            body.style.overflow = '';
        }

        menuToggle.addEventListener( 'click', function( e ) {
            e.preventDefault();
            const isExpanded = menuToggle.getAttribute( 'aria-expanded' ) === 'true';
            
            if ( isExpanded ) {
                closeMenu();
            } else {
                openMenu();
            }
        } );

        // Close menu when clicking on backdrop
        if ( backdrop ) {
            backdrop.addEventListener( 'click', closeMenu );
        }

        // Close menu when clicking a menu link
        if ( menu ) {
            const menuLinks = menu.querySelectorAll( 'a' );
            menuLinks.forEach( function( link ) {
                link.addEventListener( 'click', function() {
                    if ( navigation.classList.contains( 'toggled' ) ) {
                        closeMenu();
                    }
                } );
            } );
        }

        // Close menu on escape key
        document.addEventListener( 'keydown', function( e ) {
            if ( e.key === 'Escape' && navigation.classList.contains( 'toggled' ) ) {
                closeMenu();
                menuToggle.focus();
            }
        } );

        // Close menu on resize to desktop
        let resizeTimer;
        window.addEventListener( 'resize', function() {
            clearTimeout( resizeTimer );
            resizeTimer = setTimeout( function() {
                if ( window.innerWidth >= 768 && navigation.classList.contains( 'toggled' ) ) {
                    closeMenu();
                }
            }, 100 );
        }, { passive: true } );

        // Handle dropdown menus on touch devices
        const menuItemsWithChildren = navigation.querySelectorAll( '.menu-item-has-children > a' );
        menuItemsWithChildren.forEach( function( item ) {
            item.addEventListener( 'click', function( e ) {
                if ( window.innerWidth < 768 ) {
                    const parent = this.parentElement;
                    const submenu = parent.querySelector( '.sub-menu' );
                    
                    if ( submenu && ! parent.classList.contains( 'is-open' ) ) {
                        e.preventDefault();
                        parent.classList.add( 'is-open' );
                        submenu.style.display = 'block';
                    }
                }
            } );
        } );
    }

    /**
     * Smooth Scrolling for Anchor Links
     */
    function initSmoothScroll() {
        const links = document.querySelectorAll( 'a[href^="#"]' );
        
        links.forEach( function( link ) {
            link.addEventListener( 'click', function( e ) {
                const href = this.getAttribute( 'href' );
                
                // Skip if it's just "#" or if it's a skip link
                if ( href === '#' || this.classList.contains( 'skip-link' ) ) {
                    return;
                }
                
                const target = document.querySelector( href );
                
                if ( target ) {
                    e.preventDefault();
                    
                    const header = document.querySelector( '.site-header' );
                    const headerHeight = header ? header.offsetHeight : 0;
                    const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - headerHeight - 20;
                    
                    window.scrollTo( {
                        top: targetPosition,
                        behavior: 'smooth'
                    } );
                    
                    // Update URL without triggering scroll
                    history.pushState( null, null, href );
                    
                    // Set focus to target for accessibility
                    target.setAttribute( 'tabindex', '-1' );
                    target.focus( { preventScroll: true } );
                }
            } );
        } );
    }

    /**
     * Lazy Loading for Images
     */
    function initLazyLoading() {
        // Check if native lazy loading is supported
        if ( 'loading' in HTMLImageElement.prototype ) {
            // Add loading="lazy" to images without it
            const images = document.querySelectorAll( 'img:not([loading])' );
            images.forEach( function( img ) {
                // Don't lazy load images in hero section or above the fold
                if ( ! img.closest( '.hero-section' ) && ! img.closest( '.site-header' ) ) {
                    img.setAttribute( 'loading', 'lazy' );
                }
            } );
        } else {
            // Fallback for browsers that don't support native lazy loading
            const lazyImages = document.querySelectorAll( 'img[data-src]' );
            
            if ( lazyImages.length === 0 ) {
                return;
            }
            
            const imageObserver = new IntersectionObserver( function( entries, observer ) {
                entries.forEach( function( entry ) {
                    if ( entry.isIntersecting ) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        if ( img.dataset.srcset ) {
                            img.srcset = img.dataset.srcset;
                        }
                        img.classList.add( 'loaded' );
                        observer.unobserve( img );
                    }
                } );
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            } );
            
            lazyImages.forEach( function( img ) {
                imageObserver.observe( img );
            } );
        }
    }

    /**
     * Sticky Header Enhancement
     */
    function initStickyHeader() {
        const header = document.querySelector( '.site-header' );
        
        if ( ! header ) {
            return;
        }
        
        let lastScroll = 0;
        let ticking = false;
        
        function updateHeader() {
            const currentScroll = window.pageYOffset;
            
            // Add scrolled class when page is scrolled
            if ( currentScroll > 50 ) {
                header.classList.add( 'is-scrolled' );
            } else {
                header.classList.remove( 'is-scrolled' );
            }
            
            lastScroll = currentScroll;
            ticking = false;
        }
        
        window.addEventListener( 'scroll', function() {
            if ( ! ticking ) {
                window.requestAnimationFrame( updateHeader );
                ticking = true;
            }
        }, { passive: true } );
        
        // Initial check
        updateHeader();
    }

    /**
     * Back to Top Button
     */
    function initBackToTop() {
        // Create back to top button if it doesn't exist
        let backToTop = document.querySelector( '.back-to-top' );
        
        if ( ! backToTop ) {
            backToTop = document.createElement( 'button' );
            backToTop.className = 'back-to-top';
            backToTop.setAttribute( 'aria-label', 'Back to top' );
            backToTop.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>';
            document.body.appendChild( backToTop );
            
            // Add styles dynamically
            const style = document.createElement( 'style' );
            style.textContent = `
                .back-to-top {
                    position: fixed;
                    bottom: 2rem;
                    right: 2rem;
                    width: 48px;
                    height: 48px;
                    background: linear-gradient(135deg, #1877F2 0%, #f97316 100%);
                    color: #fff;
                    border: none;
                    border-radius: 12px;
                    cursor: pointer;
                    opacity: 0;
                    visibility: hidden;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    z-index: 100;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 4px 15px rgba(24, 119, 242, 0.35);
                }
                .back-to-top:hover {
                    transform: translateY(-4px);
                    box-shadow: 0 8px 25px rgba(24, 119, 242, 0.45);
                }
                .back-to-top.is-visible {
                    opacity: 1;
                    visibility: visible;
                }
                @media (max-width: 767px) {
                    .back-to-top {
                        bottom: 1rem;
                        right: 1rem;
                        width: 44px;
                        height: 44px;
                    }
                }
            `;
            document.head.appendChild( style );
        }
        
        // Show/hide based on scroll position
        let ticking = false;
        
        window.addEventListener( 'scroll', function() {
            if ( ! ticking ) {
                window.requestAnimationFrame( function() {
                    if ( window.pageYOffset > 500 ) {
                        backToTop.classList.add( 'is-visible' );
                    } else {
                        backToTop.classList.remove( 'is-visible' );
                    }
                    ticking = false;
                } );
                ticking = true;
            }
        }, { passive: true } );
        
        // Scroll to top on click
        backToTop.addEventListener( 'click', function() {
            window.scrollTo( {
                top: 0,
                behavior: 'smooth'
            } );
        } );
    }

    /**
     * Scroll Animations with Intersection Observer
     */
    function initScrollAnimations() {
        const animatedElements = document.querySelectorAll( '.fade-in-up, .fade-in-left, .fade-in-right' );
        
        if ( animatedElements.length === 0 ) {
            return;
        }
        
        // Check if user prefers reduced motion
        const prefersReducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
        
        if ( prefersReducedMotion ) {
            // Show all elements immediately if reduced motion is preferred
            animatedElements.forEach( function( el ) {
                el.classList.add( 'is-visible' );
            } );
            return;
        }
        
        const observerOptions = {
            root: null,
            rootMargin: '0px 0px -50px 0px',
            threshold: 0.1
        };
        
        const observer = new IntersectionObserver( function( entries ) {
            entries.forEach( function( entry ) {
                if ( entry.isIntersecting ) {
                    entry.target.classList.add( 'is-visible' );
                    observer.unobserve( entry.target );
                }
            } );
        }, observerOptions );
        
        animatedElements.forEach( function( el ) {
            observer.observe( el );
        } );
    }

    /**
     * Stats Counter Animation
     */
    function initStatsCounter() {
        const statNumbers = document.querySelectorAll( '.stat-number[data-count], .hero-stat-number[data-count]' );
        
        if ( statNumbers.length === 0 ) {
            return;
        }
        
        // Check if user prefers reduced motion
        const prefersReducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
        
        const animateCounter = function( element ) {
            const target = parseInt( element.getAttribute( 'data-count' ), 10 );
            const duration = 2000; // 2 seconds
            const startTime = performance.now();
            
            if ( prefersReducedMotion ) {
                element.textContent = target;
                return;
            }
            
            function updateCounter( currentTime ) {
                const elapsed = currentTime - startTime;
                const progress = Math.min( elapsed / duration, 1 );
                
                // Easing function (ease-out)
                const easeOut = 1 - Math.pow( 1 - progress, 3 );
                const current = Math.floor( easeOut * target );
                
                element.textContent = current;
                
                if ( progress < 1 ) {
                    requestAnimationFrame( updateCounter );
                } else {
                    element.textContent = target;
                }
            }
            
            requestAnimationFrame( updateCounter );
        };
        
        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.5
        };
        
        const observer = new IntersectionObserver( function( entries ) {
            entries.forEach( function( entry ) {
                if ( entry.isIntersecting ) {
                    animateCounter( entry.target );
                    observer.unobserve( entry.target );
                }
            } );
        }, observerOptions );
        
        statNumbers.forEach( function( el ) {
            observer.observe( el );
        } );
    }

    /**
     * Form Validation Enhancement
     */
    function initFormValidation() {
        const forms = document.querySelectorAll( 'form:not(.search-form)' );
        
        forms.forEach( function( form ) {
            const inputs = form.querySelectorAll( 'input:not([type="hidden"]):not([type="submit"]), textarea, select' );
            
            inputs.forEach( function( input ) {
                // Add validation on blur
                input.addEventListener( 'blur', function() {
                    validateInput( this );
                } );
                
                // Remove error on input
                input.addEventListener( 'input', function() {
                    if ( this.classList.contains( 'is-invalid' ) ) {
                        this.classList.remove( 'is-invalid' );
                        const error = this.parentElement.querySelector( '.error-message' );
                        if ( error ) {
                            error.remove();
                        }
                    }
                } );
            } );
        } );
        
        function validateInput( input ) {
            const value = input.value.trim();
            let isValid = true;
            let message = '';
            
            // Check required
            if ( input.hasAttribute( 'required' ) && value === '' ) {
                isValid = false;
                message = 'This field is required.';
            }
            
            // Check email
            if ( isValid && input.type === 'email' && value !== '' ) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if ( ! emailRegex.test( value ) ) {
                    isValid = false;
                    message = 'Please enter a valid email address.';
                }
            }
            
            // Check URL
            if ( isValid && input.type === 'url' && value !== '' ) {
                try {
                    new URL( value );
                } catch ( e ) {
                    isValid = false;
                    message = 'Please enter a valid URL.';
                }
            }
            
            // Update UI
            if ( ! isValid ) {
                input.classList.add( 'is-invalid' );
                
                // Remove existing error message
                const existingError = input.parentElement.querySelector( '.error-message' );
                if ( existingError ) {
                    existingError.remove();
                }
                
                // Add new error message
                const errorElement = document.createElement( 'span' );
                errorElement.className = 'error-message';
                errorElement.textContent = message;
                errorElement.style.cssText = 'color: var(--color-error, #f56565); font-size: 0.875rem; display: block; margin-top: 0.25rem;';
                input.parentElement.appendChild( errorElement );
            }
            
            return isValid;
        }
    }

    /**
     * Keyboard Navigation Enhancement
     */
    function initKeyboardNav() {
        // Add focus visible polyfill-like behavior
        document.addEventListener( 'keydown', function( e ) {
            if ( e.key === 'Tab' ) {
                document.body.classList.add( 'user-is-tabbing' );
            }
        } );
        
        document.addEventListener( 'mousedown', function() {
            document.body.classList.remove( 'user-is-tabbing' );
        } );
        
        // Add focus styles
        const style = document.createElement( 'style' );
        style.textContent = `
            body:not(.user-is-tabbing) *:focus {
                outline: none;
            }
            body.user-is-tabbing *:focus {
                outline: 2px solid var(--color-primary, #1877F2);
                outline-offset: 2px;
            }
        `;
        document.head.appendChild( style );
    }

    /**
     * Initialize all functions when DOM is ready
     */
    function init() {
        initMobileNav();
        initSmoothScroll();
        initLazyLoading();
        initStickyHeader();
        initBackToTop();
        initScrollAnimations();
        initStatsCounter();
        initFormValidation();
        initKeyboardNav();
    }

    // Run on DOM ready
    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', init );
    } else {
        init();
    }

} )();
