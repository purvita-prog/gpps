/**
 * @file
 * Global utilities.
 *
 */
(function (Drupal) {

  'use strict';

  Drupal.behaviors.bootstrap_barrio_subtheme = {
    attach: function (context, settings) {
	document.querySelectorAll('.dropdown-toggle').forEach(function(el) {
  el.addEventListener('click', function(e) {
    window.location = this.href;
  });
});
    }
  };

})(Drupal);
