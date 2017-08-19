(function(){
    window.addEventListener('load', initializePhpDuck);

    function initializePhpDuck(){
        window.addEventListener('resize', resizeClassNavSubmenus);
        resizeClassNavSubmenus();

        initializeDragHandle();
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
            let x = event.clientX;
            left.style.width = x + 'px';
            right.style.width = 'calc(100% - ' + x + 'px)';
            right.style.marginLeft = x + 'px';
            // remove transparency
            handle.style.opacity = 1;
            handle.style.background = 'transparent';
        }, false);
    }
})();
