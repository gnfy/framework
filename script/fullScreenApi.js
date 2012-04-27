/**
 * ********************************************
 * Description   : js全屏代码
 * Filename      : fullScreenApi.js
 * Create time   : 2012-02-13 12:05:06
 * Last modified : 2012-02-13 14:10:07
 * License       : MIT, GPL
 * ********************************************
 */

(function(){
    var fullScreenApi = {
        supportsFullScreen: false,
        isFullScreen: function() { return false; },
        requestFullScreen: function() {},
        cancelFullScreen: function() {},
        fullScreenEventName: '',
        prefix: ''
    },
    browserPrefixes = ['webkit', 'moz', 'o', 'ms', 'khtml'];
    // 检查当前设备是否支持全屏
    if (typeof document.cancelFullScreen != 'undefined') {
        fullScreenApi.supportsFullScreen = true;
    } else {
        for (var i = 0, il = browserPrefixes.length; i < il; i++) {
            fullScreenApi.prefix = browserPrefixes[i];
            if (typeof document[fullScreenApi.prefix + 'CancelFullScreen'] != 'undefined') {
                fullScreenApi.supportsFullScreen = true;
                break;
            }
        }
    }
    // 若支持全屏，则更新方法
    if (fullScreenApi.supportsFullScreen) {
        fullScreenApi.fullScreenEventName = fullScreenApi.prefix + 'fullscreenchange';
        fullScreenApi.isFullScreen = function() {
            switch (this.prefix) {
                case '':
                    return document.fullScreen;
                case 'webkit':
                    return document.webkitIsFullScreen;
                default:
                    return document[this.prefix + 'FullScreen'];
            }
        }
        fullScreenApi.requestFullScreen = function(el) {
            return (this.prefix === '') ? el.requestFullScreen() : el[this.prefix + 'RequestFullScreen']();
        }
        fullScreenApi.cancelFullScreen = function(el) {
            return (this.prefix === '') ? document.cancelFullScreen() : document[this.prefix + 'CancelFullScreen']();
        }
    }
    window.fullScreenApi = fullScreenApi;
})();
