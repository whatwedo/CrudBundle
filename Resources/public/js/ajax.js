var whatwedo_ajax = {

    listen: null,
    noListen: null,
    callback: null,

    setListen: function (l) {
        this.listen = l;
    },
    setNoListen: function (n) {
        this.noListen = n;
    },
    setCallback: function (c) {
        this.callback = c;
    },

    formPrefix: '#form_',

    start: function () {
        var _ = this;
        $(this.listen).each(function ($i) {
            $(_.form($i)).on('change', function () {
                _.sendChange();
            });
        })
    },

    sendChange: function () {
        var _ = this;
        var vals = [];
        $(this.noListen).each(function ($i) {
            if ($(_.form($i)) ["0"].className.includes("ajax_date")) {
                vals[$i] = {
                    key: _.noListen[$i],
                    value: {
                        day: $(_.form($i)) ["0"].children["form[" + _.noListen[$i] + "][day]"].value,
                        month: $(_.form($i)) ["0"].children["form[" + _.noListen[$i] + "][month]"].value,
                        year: $(_.form($i)) ["0"].children["form[" + _.noListen[$i] + "][year]"].value,
                    }
                }
            } else if ($(_.form($i)).attr('type') === 'checkbox'
              || $(_.form($i)).attr('type') === 'radio') {
                vals[$i] = {
                    key: _.noListen[$i],
                    value: $(_.form($i)).is(':checked')
                };
            } else {
                vals[$i] = {
                    key: _.noListen[$i],
                    value: $(_.form($i)).val()
                };
            }
        });
        var data = {data: vals};
        _.loading(true);
        $.ajax({
            type: 'POST',
            url: _.callback,
            data: data,
            dataType: 'text',
            success: function (respond) {
                _.process($.parseJSON(respond));
            },
            complete: function () {
                _.loading(false);
            }
        });
    },

    process: function (respond) {
        var _ = this;
        $(this.noListen).each(function ($i) {
            if (respond.data[_.noListen[$i]] != undefined) {
                var c = respond.data[_.noListen[$i]];
                var formEle = $(_.form($i));

                if (formEle.is('textarea')) {
                    formEle.data("wysihtml5").editor.setValue(c.data)
                }
                if (c.values != null && formEle.is('select')) {
                    formEle.find('option').remove();
                    for (var key in c.values) {
                        if (c.values.hasOwnProperty(key)) {
                            var keyValue = key;
                            var s = parseInt(c.value) === parseInt(key) ? 'selected' : '';
                            if (key === '-' && c.value === null) {
                                s = 'selected';
                                keyValue = '';
                            }
                            if (key === '-') {
                                keyValue = '';
                            }
                            if (c.value === c.values[key]) {
                                s = 'selected';
                            }
                            formEle.append('<option '+ s +' value="' + keyValue + '">' + c.values[key] + '</option>')
                        }
                    }
                } if (formEle.attr('type') === 'checkbox'
                    || formEle.attr('type') === 'radio') {
                    formEle.prop('checked', c.value);
                } else {
                    formEle.val(c.value);

                    if (formEle.is('select') && formEle.data('select2-id')) {
                      formEle.trigger('change.select2');
                    }
                }
                if (formEle.is('[data-ajax-trigger]')) {
                    formEle.trigger('change');
                }
            }
        })
    },

    form: function ($i) {
        return this.formPrefix + this.noListen[$i];
    },

    loading: function (start) {
        var _ = this;
        if (start) {
            var loader = '<span data-source="wwd-ajax" class="wwd-loading wwd-loading-black form-control-feedback"></span>';
            $(this.noListen).each(function ($i) {
                element = $(_.form($i));
                if (!element.hasClass('wwd-loading-element')) {
                    element.addClass('wwd-loading-element');
                    if (element.is('select')) {
                        $(loader).addClass('wwd-loading-select').insertAfter(element);
                    } else {
                        $(loader).insertAfter(element);
                    }
                    element.closest('.form-group').addClass('has-feedback');
                }
            });
        } else {
            $(this.noListen).each(function ($i) {
                element = $(_.form($i));
                element.removeClass('wwd-loading-element');
                element.closest('.form-group').removeClass('has-feedback');
                $('span[data-source="wwd-ajax"]').remove();
            });
        }
    }
};
$(document).ready(function () {
    if (!(typeof whatwedo_ajax_listen === 'undefined' && typeof whatwedo_ajax_no_listen === 'undefined' && typeof whatwedo_ajax_callback === 'undefined')) {
        whatwedo_ajax.setListen(whatwedo_ajax_listen);
        whatwedo_ajax.setNoListen(whatwedo_ajax_no_listen);
        whatwedo_ajax.setCallback(whatwedo_ajax_callback);
        whatwedo_ajax.start();
        whatwedo_ajax.sendChange();
    }
});
