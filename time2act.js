(function () {
    if ($('#time2act').length === 0) {
        return;
    }

    //create html
    $('#time2act').html('\
<div class="time2act-button">\n\
</div>\n\
<div class="time2act-panel">\n\
    <div class="time2act-panel_container">\n\
        <div class="time2act-panel_content"></div>\n\
        <div class="time2act-close"></div>\n\
    </div>\n\
</div>');

    var button = $('.time2act-button');
    var panel = $('.time2act-panel');

    var current_button_template = '';
    var current_template = '';
    var found_button_template = false;
    var found_template = false;

//    if (button.length > 0) {
//        var base_url = window.location.origin;
//    var pathArray = window.location.pathname.split('/');
//    var path = '';
//    if (pathArray.length > 0) {
//        path = '/' + pathArray[1];
//    }
//console.log(base_url + path + '/time2act/data/db.json');

//        var json_path = window.location.origin + '/time2act/data/db.json';
//        if (!UrlExists(json_path)) {
//            json_path = 'data/db.json';
//        }

        var myURL = retrieveURL('time2act');
//        alert(myURL);
//        $('head').append($('<link>').attr({href: myURL + 'css/time2act.css', rel: 'stylesheet'})); 
        var json_path = myURL + 'data/db.json';

        $.ajax({
            cache: false,
            url: json_path,
            dataType: "json",
            success: function (data) {
                //alert(data.name);
                button.css(data.settings.button.css);
                $('.time2act-panel_container').css(data.settings.container.css);
                //alert(data.settings.container.close.css.color);
                $('body').append('<style>.time2act-close::before, .time2act-close::after, .time2act-close:hover::before, .time2act-close:hover::after {background-color: ' +
                        data.settings.container.close.color + ';}</style>');

                panel.addClass("time2act-panel_from_" + data.settings.container.position);

                setPanelAnimation(data.settings.container.position, panel);

                // open panel when clicking on trigger btn
                button.on('click', function (event) {
                    event.preventDefault();

                    centerPanelContent();

                    if (panel.hasClass('time2act-panel-show')) {
                        panel.removeClass('time2act-panel-show');
                    } else {
                        panel.addClass('time2act-panel-show');
                    }
                });
                //close panel when clicking on 'x' or outside the panel
                panel.on('click', function (event) {
                    if ($(event.target).hasClass('time2act-close') || $(event.target).hasClass('time2act-panel')) {
                        event.preventDefault();
                        panel.removeClass('time2act-panel-show');
                    }
                });

                panel.css('display', 'initial');

                showContent(data);
            }
        });
//    }

    function showContent(data) {
        var now = new Date();
        // filtered content by time from db.json
        var get_contents_by_time = $.grep(data.contents, function (n, i) {
            var time_start = n.start.split(":");
            var time_end = n.end.split(":");
            var startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), parseInt(time_start[0]), parseInt(time_start[1]));
            var endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), parseInt(time_end[0]), parseInt(time_end[1]));

            if (startDate > endDate) {
                if (now.getHours() >= startDate.getHours()) {
                    endDate.setDate(endDate.getDate() + 1);
                } else {
                    startDate.setDate(startDate.getDate() - 1);
                }
            }

//            var startDate;
//            var endDate;
//            var savedStartDate = new Date(n.start);
//            var savedEndDate = new Date(n.end);
//
//            switch (n.type) {
//                case "once":
//                    startDate = savedStartDate;
//                    endDate = savedEndDate;
//
//                    break;
//                case "daily":
//                    //calculate selected date difference                    
//                    var diff = Math.floor((savedEndDate.getTime() - savedStartDate.getTime()) / (1000 * 60 * 60 * 24));
//                    startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), savedStartDate.getHours(), savedStartDate.getMinutes());
//                    endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() + diff, savedEndDate.getHours(), savedEndDate.getMinutes());
//
//                    break;
//                case "weekly":
//                    //debugger;
//                    var begDay, finDay, chkDay;
//                    begDay = savedStartDate.getDay();
//                    finDay = savedEndDate.getDay();
//                    chkDay = now.getDay();
//
//                    if (finDay < begDay) {
//                        finDay += 7;
//                        chkDay += 7;
//                    }
//
//                    if (chkDay >= begDay && chkDay <= finDay) {
//                        startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() - chkDay + begDay, savedStartDate.getHours(), savedStartDate.getMinutes());
//                        endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() - chkDay + finDay, savedEndDate.getHours(), savedEndDate.getMinutes());
//                    }
//                    //calculate selected date difference                    
////                    var diff = Math.floor((savedEndDate.getTime() - savedStartDate.getTime()) / (1000 * 60 * 60 * 24));
////                    startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), savedStartDate.getHours(), savedStartDate.getMinutes());
////                    endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() + diff, savedEndDate.getHours(), savedEndDate.getMinutes());
//
//                    break;
//                case "monthly":
//                    //calculate selected date difference                    
//                    var diff = Math.floor((savedEndDate.getTime() - savedStartDate.getTime()) / (1000 * 60 * 60 * 24));
//                    startDate = new Date(now.getFullYear(), now.getMonth(), savedStartDate.getDate(), savedStartDate.getHours(), savedStartDate.getMinutes());
//                    endDate = new Date(now.getFullYear(), now.getMonth(), savedEndDate.getDate() + diff, savedEndDate.getHours(), savedEndDate.getMinutes());
//
//                    break;
//                default:
//                    break;
//            }

            return n.active === 1 && now >= startDate && now < endDate;
        });

        found_button_template = false;
        found_template = false;

        $(get_contents_by_time).each(function (index, element) {
            found_button_template = true;
            found_template = true;

            setTimeout(function () {
                nextContent(element, data.settings.button.animation, data.settings.container.animation);
            }, index * data.settings.content_change_interval * 1000);
        });

        if (!found_button_template && current_button_template !== '') {
            button.fadeOut(function () {
                $(this).empty();
            });
            found_button_template = false;
        }
        if (!found_template && current_template !== '') {
            if (panel.hasClass('time2act-panel-show')) {
                panel.removeClass('time2act-panel-show');
                $(this).empty();
            } else {
                $(".time2act-panel_content").fadeOut(function () {
                    $(this).empty();
                });
            }
            found_template = false;
        }

        setTimeout(function () {
            showContent(data);
        }, get_contents_by_time.length * data.settings.content_change_interval * 1000);
    }

    function nextContent(element, button_animation, content_animation) {
        //console.log(current_button_template, element.button_template, current_template, element.template)
        if (current_button_template !== element.button_template) {
            current_button_template = element.button_template;

            switch (button_animation) {
                case "none":
                    button.hide().html(current_button_template).show();

                    break;
                case "fade":
                    button.fadeOut(function () {
                        $(this).html(current_button_template).fadeIn();
                    });

                    break;
                case "slide":
                    button.slideUp(function () {
                        $(this).html(current_button_template).slideDown();
                    });

                    break;
                default:

                    break;
            }
        }
        if (current_template !== element.template) {
            current_template = element.template;

            switch (content_animation) {
                case "none":
                    $(".time2act-panel_content").hide().html(current_template);
                    centerPanelContent();
                    $(".time2act-panel_content").show();

                    break;
                case "fade":
                    $(".time2act-panel_content").fadeOut(function () {
                        $(this).html(current_template);
                        centerPanelContent();
                        $(this).fadeIn();
                    });

                    break;
                case "slide":
                    $(".time2act-panel_content").slideUp(function () {
                        $(this).html(current_template);
                        centerPanelContent();
                        $(this).slideDown();
                    });

                    break;
                default:

                    break;
            }


        }
    }

    function centerPanelContent() {
        $(".time2act-panel_content").css("display", "inline-block");
//        console.log($(window).width() + " - " + $(".time2act-panel_content").width()); //1904 - 646 - 612
//        $(".time2act-panel_content").removeClass("content_big");

        if ($(".time2act-panel_content").width() > $(window).width() || $(".time2act-panel_content").height() > $(window).height()) {
            //$(".time2act-panel_content").addClass("content_big");
            $(".time2act-panel_content").css("display", "block");
        } else {
//            $(".time2act-panel_content").css("display", "inline-block");

            var leftPos = ($(".time2act-panel_container").width() / 2) - ($(".time2act-panel_content").width() / 2);
            var topPos = ($(".time2act-panel_container").height() / 2) - ($(".time2act-panel_content").height() / 2);

            if (leftPos < 0)
                leftPos = 0;
            if (topPos < 0)
                topPos = 0;

            $(".time2act-panel_content").css({"left": leftPos, "top": topPos});
        }
    }

    function setPanelAnimation(_pos, _panel) {
        var panel_position_left = parseInt($(window).width()) - parseInt(_panel.position().left);
        var panel_position_top = parseInt($(window).height()) - parseInt(_panel.position().top);

        switch (_pos) {
            case "right":
                $('.time2act-panel_from_right .time2act-panel_container').css({
                    '-webkit-transform': 'translate3d(' + panel_position_left + 'px, 0, 0)',
                    'transform': 'translate3d(' + panel_position_left + 'px, 0, 0)'
                });
                break;
            case "left":
                $('.time2act-panel_from_left .time2act-panel_container').css({
                    '-webkit-transform': 'translate3d(-' + panel_position_left + 'px, 0, 0)',
                    'transform': 'translate3d(-' + panel_position_left + 'px, 0, 0)'
                });
                break;
            case "bottom":
                $('.time2act-panel_from_bottom .time2act-panel_container').css({
                    '-webkit-transform': 'translate3d(0, ' + panel_position_top + 'px, 0)',
                    'transform': 'translate3d(0, ' + panel_position_top + 'px, 0)'
                });
                break;
            case "top":
                $('.time2act-panel_from_top .time2act-panel_container').css({
                    '-webkit-transform': 'translate3d(0, -' + panel_position_top + 'px, 0)',
                    'transform': 'translate3d(0, -' + panel_position_top + 'px, 0)'
                });
                break;
            case "center":
                $('.time2act-panel_from_center .time2act-panel_container').css({
                    '-webkit-transform': 'scale3d(0, 0, 0)',
                    'transform': 'scale3d(0, 0, 0)'
                });
                break;
            default:
                break;
        }
    }

//    function sleep(delay) {
//        var start = new Date().getTime();
//        while (new Date().getTime() < start + delay)
//            ;
//    }

    function UrlExists(url)
    {
        var http = new XMLHttpRequest();
        http.open('HEAD', url, false);
        http.send();
        return http.status !== 404;
    }

    function retrieveURL(filename) {
        var scripts = document.getElementsByTagName('script');
        if (scripts && scripts.length > 0) {
            for (var i in scripts) {
                if (scripts[i].src && scripts[i].src.match(new RegExp(filename + '\\.js$'))) {
                    return scripts[i].src.replace(new RegExp('(.*)' + filename + '\\.js$'), '$1');
                }
            }
        }
    }
})();