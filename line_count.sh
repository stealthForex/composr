#!/bin/bash

find . -name "*.css" -or -name "*.php" -or -name "*.tpl" -or -name "*.ini" -or -name "*.htm" -or -name "*.sh" -or -name "*.java" -or -name "*.js" -or -name "*.txt" -or -name "*.bat" -or -name "*.config" -or -name "*.htaccess" -or -name "*.hdf" | grep -v "\(\./data_custom/errorlog.php\|\./data_custom/execute_temp.php\|\./data_custom/jabber-logs\|\./data_custom/latest_activity.txt\|\./data_custom/permissioncheckslog.php\|\./docs/api\|\./docs/composr-api-template\|\./themes/default/templates_cached/EN\|\./data_custom/upload-crop\|\./uploads\|\./_config\.php\|\./sources_custom/browser_detect\.php\|\./sources_custom/facebook\|\./sources_custom/sabredav\|\./sources_custom/Cloudinary\|\./sources_custom/ILess\|\./sources_custom/composr_mobile_sdk/ios/ApnsPHP\|\./sources_custom/geshi\|\./sources_custom/photobucket\|\./_tests/simpletest\|\./data/editarea/edit_area_compressor\.php\|\./data_custom/upload-crop/upload_crop_v1\.2\.php\|\./sources/diff\.php\|\./sources/firephp\.php\|\./sources/jsmin\.php\|\./sources_custom/geshi\.php\|\./sources_custom/openid\.php\|\./sources_custom/vimeo\.php\|\./sources_custom/getid3\|\./sources_custom/GTranslate\.php\|\./sources_custom/openid\.php\|\./sources_custom/php-crossword\|\./sources_custom/programe\|\./sources_custom/spout\|\./sources/stemmer_EN\.php\|\./sources_custom/Swift-4\.1\.1\|\./sources_custom/twitter\.php\|\./tracker\|\./_old\|\./_tests/html_dump\|\./_tests/cmstest\|\./_tests/screens_tested\|\./_tests/simpletest\|\./caches\|\./exports\|\./imports\|\./data_custom/builds/debian\|\./data/ckeditor\|\./data_custom/ckeditor\|\./data/editarea\|\./nbproject\|\./_tests/codechecker/codechecker\.app\|\./_tests/codechecker/netbeans/nbproject\|\./_tests/codechecker/netbeans/build\|\./_tests/codechecker/netbeans/dist\|\./safe_mode_temp\|\./themes/default/javascript/xsl_mopup\.js\|\./themes/default/javascript/widget_color\.js\|\./themes/default/javascript/widget_date\.js\|\./themes/default/javascript/more\.js\|\./themes/default/javascript/jwplayer\.js\|\./themes/default/javascript/sound\.js\|\./themes/default/javascript_custom/xmpp_dom-all\.js\|\./themes/default/javascript/modernizr\.js\|\./themes/default/javascript_custom/xmpp_xmpp4js\.js\|\./themes/default/javascript/select2\.js\|\./themes/default/javascript/plupload\.js\|\./themes/default/javascript_custom/jquery\.js\|\./themes/default/javascript_custom/xmpp_prototype\.js\|\./themes/default/javascript_custom/jquery_effects_core\.js\|\./themes/default/javascript_custom/columns\.js\|\./themes/default/javascript_custom/xmpp_crypto\.js\|\./themes/default/javascript_custom/jquery_flip\.js\|\./themes/default/javascript_custom/tag_cloud\.js\|\./themes/default/javascript/jquery_ui_core\.js\|\./themes/default/javascript_custom/openid\.js\|\./themes/default/javascript_custom/base64\.js\|\./mobiquo/smartbanner\|\./mobiquo/lib\|\./data/modules/admin_stats/IP_Country\.txt\|.*/.htaccess\|.*/index\.html\|\./docs\).*" | xargs wc -l | grep "^\\s\+\\d\+ total$"
