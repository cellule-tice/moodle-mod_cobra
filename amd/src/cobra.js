/* jshint ignore:start */
define(['jquery', 'core/log', 'core/templates', 'core/ajax', 'core/notification'], function($, log, templates, ajax, notification) {

    var jsonparams;
    var objparams;
    var glossaryentries;
    return {
        init: function(args) {
            // Adapt glossary height to text height.
            $("#glossary").css('height', $("#cobratext").css('height'));
            // Gather display and corpus params.
            jsonparams = args;
            objparams = JSON.parse(jsonparams);
            // Disable blocks toggle (hidden by default in Cobra context).
            $('small').hide();
            $('.hbl').hide();
            $('.sbl').hide();

            log.debug('CoBRA module init');
        },

        mod_form_triggers: function() {
            var scrolltop = 0;
            var scrollvalue = $(document.createElement('input'))
                .attr('type', 'hidden')
                .attr('name', 'scrolltop')
                .attr('value', 0);
            scrollvalue.appendTo('#id_language');

            if ($('input[name="scrolltop"]').length) {
                scrolltop = $('input[name="scrolltop"]').val();
            }
            $('html, body').animate({
                scrollTop: scrolltop,
            }, 100);

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
            if($(langbutton)) {
                $(langbutton).css('display', 'none');
            }
            if(collbutton) {
                collbutton.css('display', 'none');
            }

            langselect.on('change', function() {
                var lang = langselect.find('option:selected').text();
                if (lang == 'EN') {
                    corpusorder.val(objparams.en);
                } else if (lang == 'NL') {
                    corpusorder.val(objparams.nl);
                }
                scrollvalue.attr('value', $(window).scrollTop());
                langbutton.trigger('click');
            });

            collselect.on('change', function() {
                scrollvalue.attr('value', $(window).scrollTop());
                collbutton.trigger('click');
            });

            textselect.on('change', function() {
                if (textlocalname.val() == '') {
                    textlocalname.val($('#id_text :selected').text());
                }
            });

            defaultdisplaycheckbox.on('change', function() {
                scrollvalue.attr('value', $(window).scrollTop());
                defaultdisplaybutton.trigger('click');
            });

            defaultcorpuscheckbox.on('change', function() {
                scrollvalue.attr('value', $(window).scrollTop());
                defaultcorpusbutton.trigger('click');
            });

        },
        entry_on_click: function() {
            $('.lemma').on('click', function() {
                $('.clicked').removeClass('clicked');
                $('.emphasize').removeClass('emphasize');
                var conceptId = $(this).attr('name');
                $('.lemma[name=' + conceptId + ']').addClass('emphasize');
                $(this).removeClass('emphasize');
                $(this).addClass('clicked');
                displayDetails(conceptId, false);
                $('html, body').animate({
                    scrollTop: $('#details').offset().top,
                }, 1000);

            });
            $('.expression').on('click', function() {
                $('.clicked').removeClass('clicked');
                $('.emphasize').removeClass('emphasize');
                var conceptId = $(this).attr('name');

                $('.expression[name=' + conceptId + ']').addClass('emphasize');
                $(this).prevAll('span.expression').each(function() {
                    if ($(this).attr('name') == conceptId) {
                        $(this).removeClass('emphasize');
                        $(this).addClass('clicked');
                    } else {
                        return false;
                    }
                });
                $(this).nextAll('span.expression').each(function() {
                    if( $(this).attr('name') == conceptId) {
                        $(this).removeClass('emphasize');
                        $(this).addClass('clicked');
                    } else {
                        return false;
                    }
                });
                $(this).removeClass('emphasize');
                $(this).addClass('clicked');
                displayDetails(conceptId, true);
                $('html, body').animate({
                    scrollTop: $('#details').offset().top,
                }, 1000);
            });

        },
        concordance_on_click: function() {
            $('#details').on('click', '.cc_source', function() {
                displayFullConcordance($(this));
            });
        },
        text_glossary_actions: function() {
            // Load personal glossary entries.
            var promises = ajax.call([{
                methodname: 'mod_cobra_load_glossary',
                args: {
                    textid: objparams.text,
                    courseid: objparams.course,
                    userid: objparams.user
                }
            }]);
            promises[0]
                .done(function(response) {
                    glossaryentries = response;
                }).fail(notification.exception);

            $('#details').on('click', '.glossaryadd', function() {
                var lingEntity = $(this).prev().text();

                var promises = ajax.call([{
                    methodname: 'mod_cobra_add_to_glossary',
                    args: {
                        lingentity: lingEntity,
                        textid: objparams.text,
                        courseid: objparams.course,
                        userid: objparams.user
                    }
                }]);

                promises[0]
                    .done(function(response) {
                        // Add new entry to user personal glossary and sort glossary.
                        glossaryentries.push(response);
                        glossaryentries.sort(function(a, b) {
                            if (a.entry.toLowerCase() < b.entry.toLowerCase()) {
                                return -1;
                            }
                            if (a.entry.toLowerCase() > b.entry.toLowerCase()) {
                                return 1;
                            }
                            return 0;
                        });

                        // Change icon in digest row.
                        var datafortpl = new Array;
                        datafortpl['lingentity'] = lingEntity;
                        datafortpl['iconclass'] = 'inglossary';
                        datafortpl['add'] = false;

                        templates.render('mod_cobra/glossaryiconcell', datafortpl).done(function(html) {
                            $('#displayOnClic').find('tr:first th:first').replaceWith(html);
                        }).fail(notification.exception);

                        // Resend data to glossary template.
                        var datafortpl = new Array;
                        datafortpl['entries'] = glossaryentries;
                        datafortpl['cmid'] = objparams.cmid;
                        templates.render('mod_cobra/intextglossary', datafortpl).done(function(html) {
                            $('#glossary').replaceWith(html);
                        }).fail(notification.exception);
                    }).fail(notification.exception);
            });

            $('#myglossary').on('click', '.glossaryremove', function() {
                var lingEntity = $(this).prev().text();

                var promises = ajax.call([{
                    methodname: 'mod_cobra_remove_from_glossary',
                    args: {
                        lingentity: lingEntity,
                        courseid: objparams.course,
                        userid: objparams.user
                    }
                }]);

                promises[0]
                    .done(function(response) {
                        // Remove entry from displayed glossary and refresh view.
                        glossaryentries.forEach(function(result, index) {
                            if (parseInt(result.lingentity) === response.lingentity) {
                                glossaryentries.splice(index, 1);
                            }
                        });

                        // Change icon in digest row.
                        var datafortpl = new Array;
                        datafortpl['lingentity'] = lingEntity;
                        datafortpl['iconclass'] = 'glossaryadd';
                        datafortpl['add'] = true;

                        templates.render('mod_cobra/glossaryiconcell', datafortpl).done(function(html) {
                            $('#displayOnClic').find('tr:first th:first').replaceWith(html);
                        }).fail(notification.exception);

                        // Resend data to glossary template.
                        var datafortpl = new Array;
                        datafortpl['entries'] = glossaryentries;
                        datafortpl['cmid'] = objparams.cmid;
                        templates.render('mod_cobra/intextglossary', datafortpl).done(function(html) {
                            $('#glossary').replaceWith(html);
                        }).fail(notification.exception);
                    }).fail(notification.exception);
            });
        },
        global_glossary_actions : function() {
            $('#myglossary').on('click', '.glossaryremove', function () {
                var lingEntity = $(this).find('span:first').text();
                var currentElement = $(this);
                var promises = ajax.call([{
                    methodname: 'mod_cobra_remove_from_glossary',
                    args: {
                        lingentity: lingEntity,
                        courseid: objparams.course,
                        userid: objparams.user
                    }
                }]);
                promises[0]
                    .done(function(response) {
                        // Remove entry from displayed glossary and refresh view.
                        if (response.lingentity == lingEntity) {
                            if ($(currentElement).hasClass('inDisplay')) {
                                $(currentElement.parent().remove());
                            }
                        }
                    }).fail(notification.exception);
            });
        }
    };

    function displayDetails(conceptId, isExpression) {
        $('#full_concordance').hide();
        var detailsDiv = $('#details');

        var promises = ajax.call([{
            methodname: 'mod_cobra_display_entry',
            args: {
                concept_id: conceptId,
                is_expr: isExpression,
                params: jsonparams
            }
        }]);

        promises[0]
            .done(function(response) {
                var content = response.html.replace(/class="label"/g, 'class="cobralabel"')
                        .replace(/img\//g, 'pix\/')
                        .replace(/#FFFFCC/, '');
                detailsDiv.html(content);
                if(objparams.userglossary == 1) {
                    var datafortpl = new Array;
                    datafortpl['lingentity'] = response.lingentity;
                    if (true == response.inglossary) {
                        datafortpl['iconclass'] = 'inglossary';
                        datafortpl['add'] = false;
                    } else {
                        datafortpl['iconclass'] = 'glossaryadd';
                        datafortpl['add'] = true;
                    }
                    templates.render('mod_cobra/glossaryiconcell', datafortpl).done(function(html) {
                        $('#displayOnClic').find('tr:first')
                            .prepend(html)
                            .addClass('digestRow');
                    }).fail(notification.exception);
                } else {
                    $('#glossary').remove();
                }
            }).fail(notification.exception);
    }
    // Display full text of clicked concordance.
    function displayFullConcordance(quickindexitem)
    {
        var fullConcordanceDiv = $('#full_concordance');
        var idConcordance = quickindexitem.attr('name');
        var backgroundColor = quickindexitem.parent().parent().css('background-color');

        var promises = ajax.call([{
            methodname: 'mod_cobra_get_full_concordance',
            args: {
                id_cc: idConcordance,
                params: jsonparams
            }
        }]);

        promises[0]
            .done(function(response) {
                // Remove entry from displayed glossary and refresh view.
                fullConcordanceDiv.html(response.concordance);
                fullConcordanceDiv.css('background-color', backgroundColor);
                fullConcordanceDiv.show();
            }).fail(notification.exception);
    }

});
/* jshint ignore:end */
