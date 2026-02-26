(($, Drupal, once) => {
  Drupal.behaviors.NpContextMenu = {
    attach(context, settings) {

      const clickedOutsideContextMenu = (e) => {
        Drupal.behaviors.NpContextMenu.closeContextMenus();
        document.removeEventListener('click', clickedOutsideContextMenu);
      };

      once('NpContextMenu', '.np-context-menu', context).forEach(contextMenu => {
        const hasWrapper = contextMenu.parentElement.classList.contains('item-list');
        const toggleTarget = hasWrapper ? contextMenu.parentElement : contextMenu;

        toggleTarget.addEventListener('click', (e) => {
          const isActive = toggleTarget.classList.contains('active');
          if (isActive) {
            Drupal.behaviors.NpContextMenu.closeContextMenus();
            document.removeEventListener('click', clickedOutsideContextMenu);
          } else {
            Drupal.behaviors.NpContextMenu.closeContextMenus();
            toggleTarget.classList.add('active');
            document.addEventListener('click', clickedOutsideContextMenu);
          }
        });

        toggleTarget.addEventListener('click', (e) => {
          e.stopPropagation();
        });
      });
    },
    closeContextMenus() {
      document.querySelectorAll('.np-context-menu').forEach(contextMenu => {
        const hasWrapper = contextMenu.parentElement.classList.contains('item-list');
        const toggleTarget = hasWrapper ? contextMenu.parentElement : contextMenu;
        toggleTarget.classList.remove('active');
      });
    },
  };
})(jQuery, Drupal, once);
