(function() {
    tinymce.create('tinymce.plugins.bz9_tools', {
        init : function(ed, url) {
            ed.addCommand('bz9_tools', function() {
                ed.windowManager.open({
                    file : ajaxurl + '?action=bz9wt_shortpop',
                    width : 450 + parseInt(ed.getLang('example.delta_width', 0)),
                    height : 550 + parseInt(ed.getLang('example.delta_height', 0)),
                    inline : 1
                }, {
                    plugin_url : url
                });
            });

            ed.addButton('bz9_tools', {
                title : 'BZ9 Webmaster Tools',
                image : url+'/bz9tools.png',
                cmd : 'bz9_tools'
            });
        },
        createControl : function(n, cm) {
            return null;
        },
        getInfo : function() {
            return {
                longname : "BZ9 Tools",
                author : 'BZ9',
                authorurl : 'http://bz9.com',
                infourl : 'http://bz9.com',
                version : "1.8"
            };
        }
    });
    tinymce.PluginManager.add('bz9_tools', tinymce.plugins.bz9_tools);
})();