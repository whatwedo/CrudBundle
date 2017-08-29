$(document).ready(function() {
    $('select').not('[data-disable-interactive]').not('[data-ajax-select]').each(function(i, elem) {
        elem = $(elem);
        var ph = elem.find('option[value=""]').text();
        ph = ph != '' ? ph : '(Kein Eintrag)';
        elem.select2({
            language: 'de',
            width: 'resolve',
            placeholder: ph,
            allowClear: true
        });
    });

    $('select[data-ajax-select]').not('[data-disable-interactive]').each(function(i, elem) {
        elem = $(elem);
        var ph = elem.find('option[value=""]').text();
        ph = ph != '' ? ph : '(Kein Eintrag)';
        var entity = elem.data('ajax-entity');
        var url = elem.data('ajax-url');
        elem.select2({
            language: 'de',
            width: 'resolve',
            placeholder: ph,
            allowClear: true,
            ajax: {
                url: url,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term,
                        entity: entity
                    };
                },
                processResults: function (data, params) {
                    return {
                        results: data.items
                    };
                },
                cache: true
            },
            minimumInputLength: 2
        });
    });

});
