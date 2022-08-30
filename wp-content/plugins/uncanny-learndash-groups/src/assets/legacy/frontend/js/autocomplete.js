(function ($) {
  $(function () {
    var url = ajax_url.url + '?action=uo_groups_search_user&group-id=' + current_group_id.id
    var a_url = redirect_url.url
    $('#ulg-manage-progress-user-search-field').autocomplete({
      source: url,
      delay: 500,
      minLength: 2,
      focus: function (event, ui) {
        $('#uncanny-ajax-search').val(ui.item.label)
        return false
      },
      select: function (event, ui) {
          $('#ulg-manage-progress-user-search-field').val(ui.item.label).attr('disabled', true);
          location.href = a_url + '&user-id=' + ui.item.user_id;

        return false
      }
    })
  })
})(jQuery);
