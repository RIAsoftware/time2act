<?php
session_name( 'time2act' );
session_start();

$json_path = 'data/db.json';
$json_backup_path = 'data/db.json.bak';

$db = json_decode(file_get_contents($json_path), false);
if (json_last_error() === JSON_ERROR_NONE) {
    // backup valid json
    copy($json_path, $json_backup_path);
} else {
    // JSON is NOT valid. restore from last good
    copy($json_backup_path, $json_path);
    $db = json_decode(file_get_contents($json_path), false);
}

$settings = $db->settings;
$timers = $db->contents;

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['logout']) && $_POST['logout'] === "logout") {
        session_destroy();
    }
    if (!empty($_POST['template'])) {
        $all_timers = [];

        for ($i = 0; $i < count($_POST['name']); $i++) {
            $all_timers[] = array(
                'name' => $_POST['name'][$i],
                'active' => isset($_POST['active'][$i]) ? 1 : 0,
                'start' => $_POST['start'][$i],
                'end' => $_POST['end'][$i],
//                'type' => $_POST['type'][$i],
                'button_template' => $_POST['button_template'][$i],
                'template' => $_POST['template'][$i]
            );
        }

        if (!empty($_POST['settings_password'])) {
            $_SESSION['password'] = md5($_POST['settings_password']);
        }

        //print_r($timers);
        $response = array(
            'settings' => array('button' => ['css' => ['width' => $_POST['button_width'], 'height' => $_POST['button_height'], 'margin' => $_POST['button_margin'], 'border' => $_POST['button_border'], 'border-radius' => $_POST['button_border_radius'],
                        'left' => $_POST['button_left'], 'right' => $_POST['button_right'], 'top' => $_POST['button_top'], 'bottom' => $_POST['button_bottom'], 'padding' => $_POST['button_padding'], 'background' => $_POST['button_background']],
                'animation' => $_POST['button_animation']],
                'container' => ['position' => $_POST['content_position'], 'css' => ['background' => $_POST['content_background'], 'left' => $_POST['content_left'], 'right' => $_POST['content_right'], 'top' => $_POST['content_top'], 'bottom' => $_POST['content_bottom'],
                        'width' => $_POST['content_width'], 'height' => $_POST['content_height'], 'margin' => $_POST['content_margin'], 'padding' => $_POST['content_padding'], 'border' => $_POST['content_border'], 'border-radius' => $_POST['content_border_radius']], 'close' => ['color' => $_POST['content_close_color']],
                    'animation' => $_POST['content_animation']],
                'content_change_interval' => intval($_POST['content_change_interval']),
                'password' => $_SESSION['password'],
                'preview_changes' => $_POST['preview_changes']),
            'contents' => $all_timers
        );

        $fp = fopen($json_path, 'w');
        fwrite($fp, json_encode($response, JSON_PRETTY_PRINT));
        fclose($fp);
    }
    if (!empty($_POST['password'])) {
        $_SESSION['password'] = md5($_POST['password']);
    }

    //prevent resubmit of refreshed page
    unset($_POST);
    header('Location:' . $_SERVER['PHP_SELF']);
}
?>

<?php if (isset($_SESSION['password']) && $_SESSION['password'] === $settings->password): ?>
    <!DOCTYPE html>
    <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>time2act</title>
            <link href="plugins/jquery-ui/jquery-ui.min.css" rel="stylesheet" type="text/css"/>
            <link href="plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>
            <link href="plugins/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css"/>
            <link href="plugins/spectrum/spectrum.css" rel="stylesheet" type="text/css"/>
            <link href="plugins/jQuery-Timepicker-Addon/jquery-ui-timepicker-addon.css" rel="stylesheet" type="text/css"/>

            <link href="time2act.css" rel="stylesheet" type="text/css"/>
            <style>
                /* custom checkbox */
                .checkbox {
                    margin: 0;
                }
                .checkbox label:after {
                    content: '';
                    display: table;
                    clear: both;
                }
                .checkbox .cr {
                    position: relative;
                    display: inline-block;
                    border: 1px solid #ccc;
                    border-radius: 4px;
                    width: 34px;
                    height: 34px;
                    float: left;
                    margin-right: .5em;
                    cursor: pointer;
                }
                .checkbox .cr:focus {
                    border-color: #66afe9;
                    outline: 0;
                    -webkit-box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 8px rgba(102, 175, 233, 0.6);
                    box-shadow: inset 0 1px 1px rgba(0,0,0,.075), 0 0 8px rgba(102, 175, 233, 0.6);
                }
                .checkbox .cr .cr-icon {
                    position: absolute;
                    font-size: 20px;
                    line-height: 0;
                    top: 50%;
                    left: 15%;
                }
                .checkbox label input[type="checkbox"] {
                    display: none;
                }
                .checkbox label input[type="checkbox"]+.cr>.cr-icon {
                    opacity: 0;
                }
                .checkbox label input[type="checkbox"]:checked+.cr>.cr-icon {
                    opacity: 1;
                }
                .checkbox label input[type="checkbox"]:disabled+.cr {
                    opacity: .5;
                }
                .checkbox label, .radio label {
                    padding-left: 0;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <br />
                <h1 class="text-center">time2act template & time selection page</h1>
                <br />
                <form id="form-settings" name="form-settings" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="card">
                        <div class="card-header">
                            <div class="pull-right">
                                <input id="logout" name="logout" type="hidden" value=""/>
                                <button id="logout-button" class="btn btn-warning" type="submit"><i class="fa fa-sign-out"></i> Logout</button>
                            </div>
                            <h3><i class="fa fa-gears"></i> Settings</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-3 col-sm-6">
                                    <div class="form-group">
                                        <label class="control-label">Login password:</label>
                                        <input name="settings_password" class="form-control" type="password" value="" />
                                    </div>
                                </div>                                
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-lg-12">
                                    <label class="control-label"><i class="fa fa-square"></i> Button</label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-2 col-sm-6 col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label">Width:</label>
                                        <input name="button_width" class="form-control" value="<?php echo $settings->button->css->width; ?>"  onkeyup="liveUpdate(this, 'button', 'width');"/>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-6 col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label">Height:</label>
                                        <input name="button_height" class="form-control" value="<?php echo $settings->button->css->height; ?>"  onkeyup="liveUpdate(this, 'button', 'height');"/>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-3 col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label">Left:</label>
                                        <input name="button_left" class="form-control" value="<?php echo $settings->button->css->left; ?>" onkeyup="liveUpdate(this, 'button', 'left');" />
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-3 col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label">Right:</label>
                                        <input name="button_right" class="form-control" value="<?php echo $settings->button->css->right; ?>" onkeyup="liveUpdate(this, 'button', 'right');" />
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-3 col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label">Top:</label>
                                        <input name="button_top" class="form-control" value="<?php echo $settings->button->css->top; ?>" onkeyup="liveUpdate(this, 'button', 'top');" />
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-3 col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label">Bottom:</label>
                                        <input name="button_bottom" class="form-control" value="<?php echo $settings->button->css->bottom; ?>" onkeyup="liveUpdate(this, 'button', 'bottom');" />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-2 col-sm-6 col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label">Margin:</label>
                                        <input name="button_margin" class="form-control" value="<?php echo $settings->button->css->margin; ?>"  onkeyup="liveUpdate(this, 'button', 'margin');"/>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-6 col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label">Padding:</label>
                                        <input name="button_padding" class="form-control" value="<?php echo $settings->button->css->padding; ?>" onkeyup="liveUpdate(this, 'button', 'padding');" />
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-6 col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label">Border:</label>
                                        <input name="button_border" class="form-control" value="<?php echo $settings->button->css->border; ?>"  onkeyup="liveUpdate(this, 'button', 'border');"/>
                                    </div>
                                </div>
                                <div class="col-lg-2  col-sm-6 col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label">Border radius:</label>
                                        <input name="button_border_radius" class="form-control" value="<?php echo $settings->button->css->{'border-radius'}; ?>"  onkeyup="liveUpdate(this, 'button', 'border-radius');"/>
                                    </div>
                                </div>                                
                                <div class="col-lg-2 col-sm-6 col-xs-6">
                                    <div class="form-group">
                                        <label>Background: </label>
                                        <input name="button_background" class="form-control spectrum" value="<?php echo $settings->button->css->background; ?>" />
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-6 col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label">Animation: *</label><br>
                                        <select id="button_animation" name="button_animation" class="form-control" onchange="//liveUpdate(this, 'button', '');">
                                            <option value="none" <?php echo $settings->button->animation == 'none' ? 'selected ="selected"' : ''; ?>>None</option>
                                            <option value="fade" <?php echo $settings->button->animation == 'fade' ? 'selected ="selected"' : ''; ?>>Fade</option>
                                            <option value="slide" <?php echo $settings->button->animation == 'slide' ? 'selected ="selected"' : ''; ?>>Slide</option>
                                        </select>                                
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-lg-12">
                                    <label class="control-label"><i class="fa fa-list-alt"></i> Content</label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-2 col-sm-6 col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label">Width:</label>
                                        <input name="content_width" class="form-control" value="<?php echo $settings->container->css->width; ?>"  onkeyup="liveUpdate(this, 'panel_container', 'width');"/>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-6 col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label">Height:</label>
                                        <input name="content_height" class="form-control" value="<?php echo $settings->container->css->height; ?>"  onkeyup="liveUpdate(this, 'panel_container', 'height');"/>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-3 col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label">Left:</label>
                                        <input name="content_left" class="form-control" value="<?php echo $settings->container->css->left; ?>" onkeyup="liveUpdate(this, 'panel_container', 'left');" />
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-3 col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label">Right:</label>
                                        <input name="content_right" class="form-control" value="<?php echo $settings->container->css->right; ?>" onkeyup="liveUpdate(this, 'panel_container', 'right');" />
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-3 col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label">Top:</label>
                                        <input name="content_top" class="form-control" value="<?php echo $settings->container->css->top; ?>" onkeyup="liveUpdate(this, 'panel_container', 'top');" />
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-3 col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label">Bottom:</label>
                                        <input name="content_bottom" class="form-control" value="<?php echo $settings->container->css->bottom; ?>" onkeyup="liveUpdate(this, 'panel_container', 'bottom');" />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-2 col-sm-6 col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label">Margin:</label>
                                        <input name="content_margin" class="form-control" value="<?php echo $settings->container->css->margin; ?>"  onkeyup="liveUpdate(this, 'panel_container', 'margin');"/>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-6 col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label">Padding:</label>
                                        <input name="content_padding" class="form-control" value="<?php echo $settings->container->css->padding; ?>" onkeyup="liveUpdate(this, 'panel_container', 'padding');" />
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-6 col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label">Border:</label>
                                        <input name="content_border" class="form-control" value="<?php echo $settings->container->css->border; ?>"  onkeyup="liveUpdate(this, 'panel_container', 'border');"/>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-6 col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label">Border Radius:</label>
                                        <input name="content_border_radius" class="form-control" value="<?php echo $settings->container->css->{'border-radius'}; ?>"  onkeyup="liveUpdate(this, 'panel_container', 'border-radius');"/>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-6 col-xs-6">
                                    <div class="form-group">
                                        <label>Background: </label>
                                        <input name="content_background" class="form-control spectrum" value="<?php echo $settings->container->css->background; ?>" />
                                    </div>
                                </div>
                                <div class="col-lg-2 col-sm-6 col-xs-6">
                                    <div class="form-group">
                                        <label class="control-label">Animation: *</label><br>
                                        <select id="content_animation" name="content_animation" class="form-control" onchange="//liveUpdate(this, 'panel_container', '');">
                                            <option value="none" <?php echo $settings->container->animation == 'none' ? 'selected ="selected"' : ''; ?>>None</option>
                                            <option value="fade" <?php echo $settings->container->animation == 'fade' ? 'selected ="selected"' : ''; ?>>Fade</option>
                                            <option value="slide" <?php echo $settings->container->animation == 'slide' ? 'selected ="selected"' : ''; ?>>Slide</option>
                                        </select>                                
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-4 col-sm-6">
                                    <div class="form-group">
                                        <label class="control-label">Animate from:</label><br>
                                        <select id="content_position" name="content_position" class="form-control" onchange="updatePositionAnimate(this);">
                                            <option value="left" <?php echo $settings->container->position == 'left' ? 'selected ="selected"' : ''; ?>>Left</option>
                                            <option value="right" <?php echo $settings->container->position == 'right' ? 'selected ="selected"' : ''; ?>>Right</option>
                                            <option value="top" <?php echo $settings->container->position == 'top' ? 'selected ="selected"' : ''; ?>>Top</option>
                                            <option value="bottom" <?php echo $settings->container->position == 'bottom' ? 'selected ="selected"' : ''; ?>>Bottom</option>
                                            <option value="center" <?php echo $settings->container->position == 'center' ? 'selected ="selected"' : ''; ?>>Center</option>
                                        </select>                                
                                    </div>
                                </div>
                                <div class="col-lg-4 col-sm-6">
                                    <div class="form-group">
                                        <label class="control-label">Change interval in sec:</label>
                                        <input name="content_change_interval" class="form-control" type="number" min="1" step="1" maxlength="4" value="<?php echo $settings->content_change_interval; ?>" onkeypress="return isNumber(event);" />
                                    </div>
                                </div>                                
                                <div class="col-lg-4 col-sm-6">
                                    <div class="form-group">
                                        <label>Close button color: </label>
                                        <input name="content_close_color" class="form-control spectrum" value="<?php echo $settings->container->close->color; ?>" />
                                    </div>
                                </div>                                
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <label>* changes not available in live preview</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br />

                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fa fa-clock-o"></i> Timers</h3>
                        </div>
                        <div class="card-body">
                            <?php foreach ($timers as $i => $timer): ?>
                                <div class="timer-holder">
                                    <a class="btn btn-danger btn-sm pull-right" href="javascript:void(0);" onclick="removeTimer(this);"><i class="fa fa-remove"></i></a>
                                    <div class="row">
                                        <div class="col-lg-3 col-sm-6">
                                            <div class="form-group"> 
                                                <label>Name:</label>
                                                <input name="name[]" class="form-control" type="text" value="<?php echo $timer->name; ?>" required>
                                            </div>                                        
                                        </div>
                                        <div class="col-lg-3 col-sm-3">
                                            <div class="form-group">
                                                <label>Active:</label>
                                                <div class="checkbox">
                                                    <label>
                                                        <input name="active[<?php echo $i; ?>]" class="form-control" type="checkbox" <?php echo $timer->active == 1 ? "checked" : ""; ?>>
                                                        <span class="cr"><i class="cr-icon fa fa-check"></i></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>                                      
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-3 col-sm-6">                            
                                            <div class="form-group"> 
                                                <label>Start:</label>
                                                <input name="start[]" class="form-control timepicker timer-start" type="text" value="<?php echo $timer->start; ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-sm-6">
                                            <div class="form-group"> 
                                                <label>End:</label>
                                                <input name="end[]" class="form-control timepicker timer-end" type="text" value="<?php echo $timer->end; ?>" required>
                                            </div>
                                        </div>
                                        <!--                                        <div class="col-lg-3 col-sm-6">
                                                                                    <div class="form-group">
                                                                                        <label class="control-label">Show:</label><br>
                                                                                        <select name="type[]" class="form-control" onchange="changePickerOptions(this);">
                                                                                            <option value="once" <?php //echo $timer->type == 'once' ? 'selected ="selected"' : '';        ?>>Once</option>
                                                                                            <option value="daily" <?php //echo $timer->type == 'daily' ? 'selected ="selected"' : '';        ?>>Daily</option>
                                                                                            <option value="weekly" <?php //echo $timer->type == 'weekly' ? 'selected ="selected"' : '';        ?>>Weekly</option>
                                                                                            <option value="monthly" <?php //echo $timer->type == 'monthly' ? 'selected ="selected"' : '';        ?>>Monthly</option>
                                                                                        </select>                                
                                                                                    </div>
                                                                                </div>-->
                                    </div>
                                    <div class="row">                                    
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label>Button template:</label>
                                                <textarea name="button_template[]" class="form-control tinymce" rows="5"><?php echo $timer->button_template; ?></textarea>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label>Content template:</label>
                                                <textarea name="template[]" class="form-control tinymce" rows="5"><?php echo $timer->template; ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <hr />
                                </div>
                            <?php endforeach; ?>
                            <div class="form-group">
                                <a class="btn btn-success" href="javascript:void(0);" onclick="addTimer();"><i class="fa fa-plus"></i> Add new</a>
                            </div>
                        </div>
                    </div>
                    <br />

                    <div class="form-group">
                        <button name="submit" class="btn btn-primary" type="submit"><i class="fa fa-save"></i> Save</button>
                        <a id="preview_changes-btn" class="btn btn-info float-right" href="javascript:void(0);" onclick="previewChanges(true);"><i class="fa fa-eye"></i> Preview</a>
                        <input id="preview_changes" name="preview_changes" type="hidden" value="<?php echo $settings->preview_changes; ?>">
                    </div>
                </form>
            </div>

            <div id="time2act"></div>

            <script src="plugins/jquery/jquery-3.3.1.min.js" type="text/javascript"></script>
            <script src="plugins/jquery-ui/jquery-ui.min.js" type="text/javascript"></script>
            <script src="plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
            <script src="plugins/spectrum/spectrum.js" type="text/javascript"></script>
            <script src="plugins/jQuery-Timepicker-Addon/jquery-ui-timepicker-addon.js" type="text/javascript"></script>
            <script src="plugins/tinymce/tinymce.min.js" type="text/javascript"></script>
            <script src="time2act.js" type="text/javascript"></script>
            <script>
                            if (window.history.replaceState) {
                                window.history.replaceState(null, null, window.location.href);
                            }

                            //var language = window.navigator.userLanguage || window.navigator.language;
                            //alert(language);
                            //                                    $(document).ready(function () {
                            //                                        $.ajaxSetup({cache: false});
                            //                                    });

                            $('#form-settings').on('keyup keypress', function (e) {
                                var keyCode = e.keyCode || e.which;
                                if (keyCode === 13) {
                                    e.preventDefault();
                                    return false;
                                }
                            });

                            var timer = $('div.timer-holder').first().clone();
                            timer.find('input, textarea').val('');
                            timer.find('input[type=checkbox]').removeAttr('checked');

                            $(".spectrum").spectrum({
                                preferredFormat: "rgb",
                                showAlpha: true,
                                change: function (color) {
                                    if ($(this).attr('name') === 'button_background') {
                                        liveUpdate(this, 'button', 'background');
                                    } else if ($(this).attr('name') === 'content_background') {
                                        liveUpdate(this, 'panel_container', 'background');
                                    } else if ($(this).attr('name') === 'content_close_color') {
                                        //liveUpdate(this, 'close', 'color');
                                        $('body').append('<style>.time2act-close::before, .time2act-close::after, .time2act-close:hover::before, .time2act-close:hover::after {background-color: ' +
                                                color + ';}</style>');
                                    }
                                }
                            });

                            $('.timepicker').each(function () {
                                $(this).timepicker({
                                    //                                    dateFormat: "dd.mm.yy",
                                    timeFormat: "HH:mm"
                                }); //.regional[language];
                            });

                            tinyMCEinit('.tinymce');

                            function tinyMCEinit(elm) {
                                tinymce.init({
                                    selector: elm,
                                    branding: false,
                                    plugins: "code fontawesome noneditable textcolor image link",
                                    toolbar1: 'formatselect | bold italic strikethrough forecolor backcolor | link | alignleft aligncenter alignright alignjustify  | numlist bullist outdent indent  | removeformat | image | fontawesome',
                                    content_css: 'https://netdna.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css',
                                    noneditable_noneditable_class: 'fa',
                                    extended_valid_elements: 'span[*]',
                                    // enable title field in the Image dialog
                                    image_title: false,
                                    forced_root_block: "",
                                    // enable automatic uploads of images represented by blob or data URIs
                                    automatic_uploads: true,
                                    apply_source_formatting : false,
                                    // URL of our upload handler (for more details check: https://www.tinymce.com/docs/configure/file-image-upload/#images_upload_url)
                                    // images_upload_url: 'postAcceptor.php',
                                    // here we add custom filepicker only to Image dialog
                                    file_picker_types: 'image',
                                    relative_urls: false,
                                    remove_script_host: false,
                                    convert_urls: true,
                                    // and here's our custom image picker
                                    file_picker_callback: function (cb, value, meta) {
                                        var input = document.createElement('input');
                                        input.setAttribute('type', 'file');
                                        input.setAttribute('accept', 'image/*');

                                        // Note: In modern browsers input[type="file"] is functional without 
                                        // even adding it to the DOM, but that might not be the case in some older
                                        // or quirky browsers like IE, so you might want to add it to the DOM
                                        // just in case, and visually hide it. And do not forget do remove it
                                        // once you do not need it anymore.

                                        input.onchange = function () {
                                            var file = this.files[0];

                                            var reader = new FileReader();
                                            reader.onload = function () {
                                                // Note: Now we need to register the blob in TinyMCEs image blob
                                                // registry. In the next release this part hopefully won't be
                                                // necessary, as we are looking to handle it internally.
                                                var id = 'blobid' + (new Date()).getTime();
                                                var blobCache = tinymce.activeEditor.editorUpload.blobCache;
                                                var base64 = reader.result.split(',')[1];
                                                var blobInfo = blobCache.create(id, file, base64);
                                                blobCache.add(blobInfo);

                                                // call the callback and populate the Title field with the file name
                                                cb(blobInfo.blobUri(), {title: file.name});
                                            };
                                            reader.readAsDataURL(file);
                                        };

                                        input.click();
                                    },
                                    images_upload_url: 'uploader.php',
                                    //images_upload_base_path: base_url + path + '/time2act/images/',
                                    images_upload_credentials: true
                                });
                            }

                            function addTimer() {
                                var new_timer = timer.clone();
                                new_timer.slideUp(function () {
                                    new_timer.insertAfter($('div.timer-holder').last());
                                    tinyMCEinit('div.timer-holder:last .tinymce');
                                    new_timer.find('.timepicker').timepicker({timeFormat: "HH:mm"});

                                    new_timer.slideDown(function () {
                                        $('html, body').animate({
                                            scrollTop: new_timer.offset().top
                                        }, 600);
                                    });
                                });
                            }

                            function removeTimer(me) {
                                if ($('div.timer-holder').length > 1) {
                                    $(me).closest('div.timer-holder').slideUp(function () {
                                        $(this).remove();
                                    });
                                }
                            }

                            function isNumber(evt) {
                                evt = (evt) ? evt : window.event;
                                var charCode = (evt.which) ? evt.which : evt.keyCode;
                                if ((charCode > 31 && charCode < 48) || charCode > 57) {
                                    return false;
                                }
                                return true;
                            }

                            $('#logout-button').click(function () {
                                $('#logout').val('logout');
                            });

                            previewChanges(false);
                            function previewChanges(change) {
                                //console.log($('#preview_changes').val());
                                if (change) {
                                    if (parseInt($('#preview_changes').val()) === 1) {
                                        $('#preview_changes').val(0);
                                    } else {
                                        $('#preview_changes').val(1);
                                    }
                                }

                                if (parseInt($('#preview_changes').val()) === 0) {
                                    $('.time2act-button').removeClass('d-block').addClass('d-none'); //.css('display', 'none'); //
                                    $('#preview_changes-btn i').switchClass('fa-eye', 'fa-eye-slash');
                                } else {
                                    $('.time2act-button').removeClass('d-none').addClass('d-block'); //.css('display', 'initial'); //
                                    $('#preview_changes-btn i').switchClass('fa-eye-slash', 'fa-eye');
                                }
                            }

                            function liveUpdate(me, element, css) {
                                $('.time2act-' + element).css(css, $(me).val());
                            }

                            function updatePositionAnimate(me) {
                                $('.time2act-panel').removeClass(function (index, css) {
                                    return (css.match(/\btime2act-panel_from_\S+/g) || []).join(' '); // removes anything that starts with "page-"
                                });
                                //$('.time2act-panel').removeClass('[class^="time2act-panel_from_"]');
                                $('.time2act-panel').addClass("time2act-panel_from_" + $('#content_position option:selected').val());

                                setPanelAnimation($(me).val(), $('.time2act-panel'));
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

                            //                            function changePickerOptions(me) {
                            //                                var show = $(me).val();
                            //                                //alert(show);
                            //
                            //
                            //                                var timer_start = $(me).closest('.row').find('.timer-start');
                            //                                var timer_end = $(me).closest('.row').find('.timer-end');
                            //
                            //                                var timer_start_date = new Date(timer_start.val());
                            //                                var timer_end_date = new Date(timer_end.val());
                            //
                            //                                switch (show) {
                            //                                    case "once":
                            //                                        //debugger;
                            //                                        if (timer_start_date instanceof Date && !isNaN(timer_start_date)) {
                            //                                            var now = new Date();
                            //                                            timer_start_date = new Date(now.getFullYear(), now.getMonth(), now.getDate(), timer_start_date.getHours(), timer_start_date.getMinutes());
                            //                                        }
                            //                                        if (timer_end instanceof Date && !isNaN(timer_end)) {
                            //                                            var now = new Date();
                            //                                            timer_end = new Date(now.getFullYear(), now.getMonth(), now.getDate(), timer_end_date.getHours(), timer_end_date.getMinutes());
                            //                                        }
                            //                                        
                            //                                        timer_start.timepicker("destroy").datetimepicker("destroy").datetimepicker();
                            //                                        timer_end.timepicker("destroy").datetimepicker("destroy").datetimepicker();
                            //
                            //    //                                        timer_start.datetimepicker('setDate', (new Date()) );
                            //                                        timer_start.val(timer_start_date);
                            //                                        timer_end.val(timer_end_date);
                            //
                            //                                        break;
                            //                                    case "daily":
                            //                                        
                            //
                            //                                        timer_start.timepicker("destroy").datetimepicker("destroy").timepicker();
                            //                                        timer_end.timepicker("destroy").datetimepicker("destroy").timepicker();
                            //
                            //                                        timer_start.val(timer_start_date.getHours() + ':' + timer_start_date.getMinutes());
                            //                                        timer_end.val(timer_end_date.getHours() + ':' + ('0' + timer_end_date.getMinutes()).slice(-2));
                            //
                            //                                        break;
                            //                                    default:
                            //                                        break;
                            //                                }
                            //                                //alert($(me).closest('.row').find('.timer-start').val());
                            //
                            //    //
                            //    //                                $(me).closest('.row').find('.timer-start').timepicker("destroy").datetimepicker();
                            //                            }
            </script>
        </body>
    </html>
<?php else: ?>
    <!DOCTYPE html>
    <html lang="en">
        <head>
            <meta charset="utf-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>time2act</title>
            <link href="plugins/jquery-ui/jquery-ui.min.css" rel="stylesheet" type="text/css"/>
            <link href="plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css"/>            
        </head>
        <body>
            <div class="container">
                <row>
                    <div class="col-sm-4 offset-sm-4">
                        <div class="login-form">
                            <div class="panel">
                                <h2>time2act backend login</h2>
                                <p>Please enter your password</p>
                            </div>
                            <form name="form-login" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                <div class="form-group">
                                    <input id="password" name="password" class="form-control" type="password" placeholder="Password" required>
                                </div>
                                <button class="btn btn-primary" type="submit">Login</button>
                            </form>
                        </div>
                    </div>
                </row>
            </div>

            <script src="plugins/jquery/jquery-3.3.1.min.js" type="text/javascript"></script>
            <script src="plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
            <script type="text/javascript">
                            $(document).ready(function () {
                                $('#password').focus();
                            });
            </script>
        </body>
    </html>
<?php endif; ?>