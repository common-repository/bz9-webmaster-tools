<?php
/**
 * File : form.php
 **/

$saved_tools = '<option value="0">Select Saved Tool</option>';
$args = array(
    'post_type' => 'bz9_tools',
	'posts_per_page' => -1
);
$products = new WP_Query( $args );
if( $products->have_posts() ) {
    while( $products->have_posts() ) {
        $products->the_post();
        $saved_tools .= '<option value="'.get_the_ID().'">'.get_the_title().'</option>';
    }
}
else {
    $saved_tools = '<option value="0">'. __("No Saved Tools Found", "bz9-webmaster-tools") .'</option>';
}
?>
<!DOCTYPE html>
<head>
    <title><?php _e('BZ9 Webmaster Tools', 'bz9-webmaster-tools'); ?></title>
    <?php wp_enqueue_script("jquery"); ?>
    <?php wp_head(); ?>
    <script type="text/javascript" src="<?php echo get_option( 'siteurl' ) ?>/wp-includes/js/tinymce/tiny_mce_popup.js"></script>
    <script type="text/javascript">
        var $ = jQuery;
        var bz9_Tools = {
            e: '',
            init: function(e) {
                bz9_Tools.e = e;
                tinyMCEPopup.resizeToInnerSize();
            },
            insert: function createBZ9Shortcode(e) {
                var value = $('#bz9_code').val();
                var value2 = $('#bz9_code2').val();
                var value3 = $('#bz9_code3').val();
                var saved_tool = $('#bz9_saved_tools').val();
                var src = "";
                var src2 = "";
                var style = "";

                if ( value !== '' )
                {
                    value = vartest(value);
                    src = $(value).attr("src");
                    src2 = $(value).next().attr("src");
                    style = $(value).attr("style");
                    insertshort(src,src2,style);
                }
                if ( value2 !== '' )
                {
                    value2 = vartest(value2);
                    src = $(value2).attr("src");
                    src2 = $(value2).next().attr("src");
                    style = $(value2).attr("style");
                    insertshort(src,src2,style);
                }
                if ( value3 !== '' )
                {
                    value3 = vartest(value3);
                    src = $(value3).attr("src");
                    src2 = $(value3).next().attr("src");
                    style = $(value3).attr("style");
                    insertshort(src,src2,style);
                }
                if( saved_tool != 0)
                {
                    insertshort(saved_tool,'','saved');
                }
                tinyMCEPopup.close();
            }
        }
        tinyMCEPopup.onInit.add(bz9_Tools.init, bz9_Tools);

        function vartest(tvalue)
        {
            var myRe = new RegExp("var.+?;", "g");
            var pattern = /<script>.+?<\/script>/;
            var myArray;
            var params = "";
            while (myArray = myRe.exec(tvalue))
            {
                params = params+htmlEntities(myArray[0]);
            }
            if(params != ""){
                insertshort(params,'','saved_var');
                tvalue = tvalue.replace(pattern, "");
                return tvalue;
            }
            return tvalue;
        }

        function htmlEntities(str) {
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }


        function insertshort(src,src2,style)
        {
            var shortcode = 'bz9_webmaster_tools';
            var shortcode_close = '[/bz9_webmaster_tools]';
            var bz9tool;

            if (style === undefined) {
                if (src !== undefined) {
                    bz9tool = '[' + shortcode + ']' + src + shortcode_close;
                    tinyMCEPopup.execCommand('mceInsertContent', 0, bz9tool);
                }

                if (src2 !== undefined) {
                    bz9tool = '[' + shortcode + ']' + src2 + shortcode_close;
                    tinyMCEPopup.execCommand('mceInsertContent', 0, bz9tool);
                }

            } else if(style === 'saved'){
                bz9tool = '[' + shortcode + ' saved="' + src + '" /]';
                tinyMCEPopup.execCommand('mceInsertContent', 0, bz9tool);

            } else if(style === 'saved_var'){
                bz9tool = '[' + shortcode + ' type="vars"]' + src + shortcode_close;
                tinyMCEPopup.execCommand('mceInsertContent', 0, bz9tool);

            }else {
                bz9tool = '[' + shortcode + ' style="' + style + '"]' + src + shortcode_close;
                tinyMCEPopup.execCommand('mceInsertContent', 0, bz9tool);
            }
            return;
        }
    </script>
    <style>
        label {
            display: block;
        }
        textarea {
            margin-bottom: 10px;
        }
        #bz9_tools_txt {
            margin-bottom: 10px;
        }
        a {
            width:155px;
            display:block;
            margin-left:auto;
            margin-right:auto;
            padding: 2px 5px 2px 5px;
            text-decoration:none;
            font-family:arial;
            font-weight:bold;
            text-align:center;
            background-color: #fff9f8;
            color: white;
            font-size:9pt;
            border: 3px #454545 ridge;
        }
        a:hover {
            color: #5c79b7;
        }
    </style>
</head>
<body>
<div id="bz9_tools_txt"><?php _e('Enter your webmaster tools code into one of the boxes below. To save you time three boxes have been provided to make multiple shortcodes.', 'bz9-webmaster-tools'); ?></div>
<div id="bz9tools-form"><table id="bz9tools-table" class="form-table">
        <tr>
            <th><label for="bz9_code"><?php _e('Tools Code', 'bz9-webmaster-tools'); ?></label></th>
            <td><textarea id="bz9_code" name="columns" rows="5" cols="40"></textarea>
            </td>
        </tr>
        <tr>
            <th><label for="bz9_code2"><?php _e('Tools Code', 'bz9-webmaster-tools'); ?></label></th>
            <td><textarea id="bz9_code2" name="columns" rows="5" cols="40"></textarea>
            </td>
        </tr>
        <tr>
            <th><label for="bz9_code3"><?php _e('Tools Code', 'bz9-webmaster-tools'); ?></label></th>
            <td><textarea id="bz9_code3" name="columns" rows="5" cols="40"></textarea>
            </td>
        </tr>
        <tr>
            <th><label for="bz9_saved_tools"><?php _e('Saved Tools', 'bz9-webmaster-tools'); ?></label></th>
            <td><select name="bz9_saved_tools" id="bz9_saved_tools"><?php echo $saved_tools; ?></select></td>
        </tr>
    </table>
    <p class="submit">
        <a href="javascript:bz9_Tools.insert(bz9_Tools.e)"><?php _e('Create Shortcode', 'bz9-webmaster-tools'); ?></a>
    </p>
</div>
</body>
</html>