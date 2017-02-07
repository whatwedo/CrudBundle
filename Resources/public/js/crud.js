$(document).ready(function() {
  $('[data-toggle="default-crud-save"]').click(function(e) {
    e.preventDefault();
    $('.default-crud-form').submit();
  });
});
