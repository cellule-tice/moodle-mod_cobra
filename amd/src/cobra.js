/* jshint ignore:start */
define(['jquery', 'core/log'], function($, log) {

    "use strict";

    return {
        init: function ($params) {
            log.debug($params);

        },
        mod_form_triggers: function (args) {
            // Init vars.
            var params = JSON.parse(args);

            var langbutton = $('#id_updatelanguage');
            var langselect = $('#id_language');
            var corpusorder = $('#id_corpusorder');
            var collbutton = $('#id_selectcollection');
            var collselect = $('#id_collection');
            var textselect = $('#id_text');
            var textlocalname = $('#id_name');
            var defaultdisplaybutton = $('#id_updatedefaultdisplayprefs');
            var defaultdisplaycheckbox = $('#id_isdefaultdisplayprefs');
            var defaultcorpusbutton = $('#id_updatedefaultcorpusorder');
            var defaultcorpuscheckbox = $('#id_isdefaultcorpusorder');

            // Hide trigger buttons.
            if ($(langbutton)) {
                $(langbutton).css('display', 'none');
            }
            if (collbutton) {
                collbutton.css('display', 'none');
            }

            //$(document).on('change', $('#id_language'), function() {
            langselect.on('change', function () {
                var lang = langselect.find('option:selected').text();
                if (lang == 'EN') {
                    corpusorder.val(params.en);
                } else if (lang == 'NL') {
                    corpusorder.val(params.nl);
                }
                langbutton.trigger('click');
            });

            collselect.on('change', function () {
                //$(body).get(0).scrollIntoView();
                collbutton.trigger('click');
            });

            textselect.on('change', function () {
                if (textlocalname.val() == '') {
                    textlocalname.val($('#id_text :selected').text());
                }
            });

            defaultdisplaycheckbox.on('change', function () {
                defaultdisplaybutton.trigger('click');
            });

            defaultcorpuscheckbox.on('change', function () {
                defaultcorpusbutton.trigger('click');
            });


        },
    };
});
/* jshint ignore:end */