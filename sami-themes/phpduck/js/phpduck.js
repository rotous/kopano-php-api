(function(){
    // Read the column width from localStorage
    let w = parseInt(localStorage.getItem('column-left-width'), 10);
    if ( w ){
        setLeftColumnWidth(w);
    }

    window.addEventListener('load', initializePhpDuck);

    function initializePhpDuck(){
        window.addEventListener('resize', resizeClassNavSubmenus);
        resizeClassNavSubmenus();

        initializeDragHandle();

        initializeMenuBar();
    }

    function resizeClassNavSubmenus(){
        let el = document.querySelectorAll('.class-nav-submenu');
        if ( el.length ){
            el.forEach(function(ul){
                let w = 0;
                for ( let i=0; i<ul.children.length; i++ ){
                    let li = ul.children[i];
                    if ( li.offsetLeft+li.offsetWidth > w ){
                        w = li.offsetLeft+li.offsetWidth;
                    }
                }
                ul.style.width = w + 'px';
            });
        }
    }

    // TODO store the state in the localStorage
    function initializeDragHandle(){
        let handle = document.querySelector('#drag-handle');
        let left = document.querySelector('#left-column');
        let right = document.querySelector('#right-column');

        handle.addEventListener("dragstart", function( event ) {
            // make it half transparent
            handle.style.opacity = .5;
            handle.style.background = '#ccc';
        }, false);
        handle.addEventListener("dragend", function( event ) {
            let w = Math.max(event.clientX, 50);
            setLeftColumnWidth(w);
            // Store in localstorage
            localStorage.setItem('column-left-width', w);
            // remove transparency
            handle.style.opacity = 1;
            handle.style.background = 'transparent';
        }, false);
    }

    function setLeftColumnWidth(w){
        let left = document.querySelector('#left-column');
        let right = document.querySelector('#right-column');
        w = Math.max( w, 50);
        w = Math.min(w, window.innerWidth - 50);
        left.style.width = w + 'px';
        right.style.width = 'calc(100% - ' + w + 'px)';
        right.style.marginLeft = w + 'px';
    }

    function initializeMenuBar(){
        let menuItems = document.querySelectorAll('.class-navigation-menu-header');
        for ( let i=0; i<menuItems.length; i++ ){
            menuItems[i].addEventListener('mouseover', function(e){
                menuItems[i].classList.add('pd-mouseover');
            });
            menuItems[i].addEventListener('mouseout', function(e){
                menuItems[i].classList.remove('pd-mouseover');
            });
        }

        let items = document.querySelectorAll('.class-nav-submenu a');
        for ( let i=0; i<items.length; i++ ){
            items[i].addEventListener('click', function(e){
                let menu = e.target.parentElement.parentElement.parentElement;
                menu.classList.remove('pd-mouseover');
            });
        }
    }
})();
