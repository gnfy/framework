/**
 * ajax 封装类
 *
 * Last modify: 2011-03-21 16:12:02
 *
 * example：
 * IF_ajax.request({
 *      url         请求URL
 *      method      请求方式(默认：GET)
 *      data        请求参数
 *      dataType    数据格式(默认：text)
 *      encode      请求的编码(默认：UTF-8)
 *      timeout     请求超时时间(默认：0, 不超时)
 *      success     请求成功后执行函数 参数：text、json、 xml数据
 *      failure     请求失败后执行函数 参数：msg, xmlhttp对象, exp
 *      cache       是否缓存(默认：false)
 *      async       是否异步(默认：true)
 * });
 */
var IF_ajax = (function(){
    
     /**
     * 创建XMLHTTPRequest
     */
    function create() {
        var xmlhttp = null;
        if (window.XMLHttpRequest) {
            xmlhttp = new XMLHttpRequest();
        } else {
            var ver_arr = ['Microsoft.XMLHTTP', 'MSXML6.XMLHTTP', 'MSXML5.XMLHTTP', 'MSXML4.XMLHTTP', 'MSXML3.XMLHTTP', 'MSXML2.XMLHTTP', 'MSXML.XMLHTTP'];
            for (var i in ver_arr) {
                try {
                    xmlhttp = new ActiveXObject(ver_arr[i]);
                    break;
                } catch(e) {
                    continue;
                }
            }
        }
        return xmlhttp;
    }
    
    /**
     * 发送请求
     */
    function request(obj) {
        function fn(){}
        obj = obj || {};
        var url     = obj.url       || window.location.toString(),
            method  = obj.method    || 'GET',
            data    = obj.data      || null,
            dataType= obj.dataType  || 'text',
            encode  = obj.encode    || 'UTF-8',
            timeout = obj.timeout   || 0,
            success = obj.success   || fn,
            failure = obj.failure   || fn,
            cache   = obj.cache     || false,
            async   = obj.async !== false;
            method  = method.toUpperCase();
            dataType= dataType.toLowerCase();
        if (data && typeof(data) == 'object') {
            data = _serialize(data);
        }
        xmlhttp = create();
        if (!xmlhttp) {
            alert('Not Support Ajax');
            return;
        }
        if (method == 'GET' && data) {
            url += (url.indexOf('?') == -1 ? '?' : '&') + data;
            data = null;
        }
        var isTimeout = false, timer;
        if (async && timeout > 0) {
            timer = setTimeout(function() {
                xmlhttp.abort();
                isTimeout = true;
            }, timeout);
        }
        xmlhttp.onreadystatechange = function() {
            if (xmlhttp.readyState == 4 && !isTimeout) {
                stateChange(xmlhttp, dataType, success, failure);
                clearTimeout(timer);
            }
        }
        xmlhttp.open(method, url, async);
        if ( method == 'POST' ) {
            xmlhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded;charset=' + encode);
        } else {
            if (!cache) {
                xmlhttp.setRequestHeader('If-Modified-Since', '0');
                xmlhttp.setRequestHeader('Cache-Control', 'no-cache');
            }
        }
        xmlhttp.send(data);
    }
    
    /** 
     * 转换参数
     */
    function _serialize(data) {
        var row = [];
        for (var k in data) {
            var val = data[k];
            if(val.constructor == Array) {
                for (var i = 0, max = val.length; i < max; i++) {
                    row.push(k + '=' + encodeURIComponent(val[i]));
                }
            } else {
                row.push(k + '=' + encodeURIComponent(val));
            }
        }
        return row.join('&');
    }

    /**
     * 状态变化
     */
    function stateChange(xmlhttp, dataType, success, failure) {
        var sts = xmlhttp.status, result;
        if (sts == 200) {
            switch (dataType) {
                case 'text':
                    result = xmlhttp.responseText;
                    break;
                case 'json':
                    result = function(str){
                        try {
                            return JSON.parse(str);
                        } catch(e) {
                            try {
                                return (new Function('return ' + str))();
                            } catch (e) {
                                try {
                                    return eval('(' + str + ')');
                                } catch(e) {
                                    failure('Parse json error', xmlhttp, e);
                                }
                            }
                        }
                    }(xmlhttp.responseText);
                    break;
                case 'xml':
                    result = xmlhttp.responseXML;
                    break;
            }
            typeof result !== 'undefined' && success(result);
        } else if (sts == 0) {
            failure('Request timeout', xmlhttp);
        } else {
            failure(sts, xmlhttp);
        }
        xmlhttp = null;
    }
    /**
     * 动态加载js
     * 
     * @param   string      url     链接地址
     */
    function loadScript(url) {
        // 加载失败
        var isload  = 1;
        var ahead   = document.head || document.getElementsByTagName( "head" )[0] || document.documentElement;
        var ascript = document.createElement('script');
        ascript.src = url;
        ascript.type= 'text/javascript';
        ahead.appendChild(ascript);
        ascript.onload = ascript.onreadystatechange = function() {
            if( !ascript.readyState || /loaded|complete/.test( ascript.readyState ) ) {
                isload = 2;
                ahead.removeChild(ascript);
            }
        }
        if (isload == 2) {
            alert('加载失败....');
        }
    }
    /**
     * 调用
     */
    return {request: request, loadScript: loadScript}
})();
