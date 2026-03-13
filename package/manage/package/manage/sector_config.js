/**
 * @description 通用配置
 */
let url = ''
if (location.port == '15000') {
  //url = 'http://127.0.0.1/dever2/package/manage'
  url = 'http://127.0.0.1/api2/package/manage/api'
} else {
  url = location.origin + location.pathname.replace('sector.html', '') + 'api'
}
deverConfig = {
  // 一些基本配置，定义后台title
  setting: {
    title: '源主管理中台',
    tokenTableName: 'dever-yuandaibao-v1-sector',
    loginParam: { system: 'sector', number: 'default' },
  },
  // 网络配置
  network: {
    requestTimeout: 1000000,
    baseURL: url, // 配置服务器地址,
  },
  // 默认布局
  theme: {
    // 布局种类：横向布局horizontal、纵向布局vertical、分栏布局column、综合布局comprehensive、常规布局common、浮动布局float
    layout: 'column',
    // 主题名称：默认blue-black、blue-white、green-black、green-white、渐变ocean、red-white、red-black
    themeName: 'green-black',
    // 菜单背景 none、vab-background
    background: 'none',
    // 菜单宽度，仅支持px，建议大小：266px、277px、288px，其余尺寸会影响美观
    menuWidth: '266px',
    // 分栏风格(仅针对分栏布局column时生效)：横向风格horizontal、纵向风格vertical、卡片风格card、箭头风格arrow
    columnStyle: 'arrow',
    // 显示标签页时标签页样式：卡片风格card、灵动风格smart、圆滑风格smooth
    tabsBarStyle: 'smart',
  },
}
