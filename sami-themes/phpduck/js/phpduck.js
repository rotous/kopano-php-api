(function(){
    window.addEventListener('load', initializePhpDuck);

    function initializePhpDuck(){
        window.addEventListener('resize', resizeClassNavSubmenus);
        resizeClassNavSubmenus();
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
})();
