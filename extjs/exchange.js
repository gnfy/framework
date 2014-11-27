/**
 * 外汇汇率
 *
 * Last modify: 2011-03-18 15:16:06
 * license: 非商业(GPL)
 */
Ext.onReady(function(){
    // create the Data Store
    var store = new Ext.data.JsonStore({
        root: 'data',
        remoteSort: false,
        fields: [
            {name: 'symbol'},
            {name: 'name'},
            {name: 'zjj', type:'float'},
            {name: 'cmj', type:'float'},
            {name: 'hmj', type:'float'},
            {name: 'chmj', type:'float'},
            {name: 'chg', type:'float'},
            {name: 'chg_pct', type:'float'},
            {name: 'hq_date'}
        ],
        // load using script tags for cross domain, if the data in on the same domain as
        // this page, an HttpProxy would be better
        proxy: new Ext.data.ScriptTagProxy({
            url: 'http://app.travel.ifeng.com/tools/index.php?apps=exchange&action=index'
        })
    });

    Ext.func = function() {
        return {
            check_exchange:function() {
                var from_val = Ext.getDom('from_num').value;
                if ( Ext.num(from_val) ) {
                    var sel_from_dom    = Ext.getDom('exchange_from');
                    var sel_to_dom      = Ext.getDom('exchange_to');
                    var sel_from_val    = sel_from_dom.options[sel_from_dom.selectedIndex].value;
                    var sel_to_val      = sel_to_dom.options[sel_to_dom.selectedIndex].value;
                    if (sel_from_val == sel_to_val) {
                        Ext.Msg.alert('提示消息', '币种相同，可以不必兑换！');
                    } else {
                        Ext.Ajax.request({
                            url: 'http://app.travel.ifeng.com/tools/index.php?apps=exchange&action=ajax',
                            params: {
                                from:   sel_from_val,
                                to:     sel_to_val,
                                val:    from_val
                            },
                            method: 'POST',
                            success: function(resp, opt) {
                                Ext.getDom('to_num').value = resp.responseText;
                            }
                        });
                    }
                } else {
                    Ext.Msg.alert('提示消息', '输入的数据不是数字，请重新输入！');
                }
            }
        }
    }
    // 监听回车事件 汇率输入
    Ext.get('from_num').on('keypress', function(e) {
        if (e.keyCode == 13) {
            Ext.func().check_exchange();
        }
    });
    // 监听回车事件 原货币
    Ext.get('exchange_from').on('keypress', function(e) {
        if (e.keyCode == 13) {
            Ext.func().check_exchange();
        }
    });
    // 监听回车事件 兑换货币
    Ext.get('exchange_to').on('keypress', function(e) {
        if (e.keyCode == 13) {
            Ext.func().check_exchange();
        }
    });
    // 显示日期
    function show_date(val) {
        d = new Date(val*1000);
        //年
        d_year  = d.getFullYear();
        // 月
        d_month = d.getMonth() + 1;
        d_month = d_month < 10 ? '0' + d_month : d_month;
        // 号
        d_day   = d.getDate();
        d_day   = d_day < 10 ? '0' + d_day : d_day;
        return d_year + '-' + d_month + '-' + d_day;
    }
    // 显示涨降幅
    function show_chg_pct(val) {
        return val > 0 ? String.format('<span style="color:#F00">{0}%</span>', val) : String.format('{0}%', val);
    }
    // 显示涨跌额
    function show_chg(val) {
        return val > 0 ? String.format('<span style="color:#F00">{0}%</span>', val) : String.format('{0}%', val);
    }

    store.setDefaultSort('chg_pct', 'DESC');

    //选择框
    var check_select = new Ext.grid.CheckboxSelectionModel();

    var grid = new Ext.grid.EditorGridPanel({
        autoWidth: true,
        autoHeight: true,
        store:  store,
        loadMask: true,
        title: '人民币汇率',
        sm:check_select,
        columns:[
            {
                id: 'top_symbol',
                header: '代码',
                dataIndex: 'symbol',
                width: 60,
                sortable: true
            },{
                header: '名称',
                dataIndex: 'name',
                width: 122,
                sortable: true
            },{
                header: '中间价',
                dataIndex: 'zjj',
                width: 122,
                sortable: true
            },{
                header: '钞买价',
                dataIndex: 'cmj',
                width: 122,
                sortable: true
            },{
                header: '汇买价',
                dataIndex: 'hmj',
                width: 122,
                sortable: true
            },{
                header: '钞/汇卖价',
                dataIndex: 'chmj',
                width: 122,
                sortable: true
            },{
                header: '涨跌额',
                dataIndex: 'chg',
                width: 122,
                renderer: show_chg,
                sortable: true
            },{
                header: '涨跌幅',
                dataIndex: 'chg_pct',
                width: 122,
                renderer: show_chg_pct,
                sortable: true
            },{
                header: '日期',
                dataIndex: 'hq_date',
                width: 84,
                renderer: show_date,
                sortable: true
            }
        ],
    });
    grid.render('exchange_data');
    store.load({params:{sort:'chg_pct', dir:'DESC'}});
});
