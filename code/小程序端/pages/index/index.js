const PCB = require('../../utils/common');
const API = require('../../utils/api');
const app = getApp()
let interstitialAd = null

Component({
  behaviors: [PCB],
  data: {
    isActive: true,
    isSearch: true,
    pageload: true,
  },
  properties: {
    // 接受页面参数
    posttype: String,
    gotourl: String
  },
  attached: function (options) {},
  pageLifetimes: {
    show: function () {},
    hide: function () {
      // 页面被隐藏
    },
    resize: function (size) {
      // 页面尺寸变化
    }
  },
  methods: {
    onLoad: function (options) {
      let that = this;
      // 对于分享进入的链接做内容类型缓存
      if (options.cnttype) {
        let cnttype = options.cnttype;
        app.globalData.cnttype = cnttype
      } else if (wx.getStorageSync('cnttype')) {
        let cnttype = wx.getStorageSync('cnttype');
        app.globalData.cnttype = cnttype
      } else {
        let cnttype = this.data.cnttype;
        app.globalData.cnttype = cnttype
      }
      if (options.gotourl) {
        this.autoGotourl(options);
      }
      

      this.setData({
        cnttype: app.globalData.cnttype
      })
      //保存到本地
      wx.setStorage({
        key: "cnttype",
        data: app.globalData.cnttype
      })
      console.log(app.globalData.cnttype)
      // 结束对于分享进入的链接做内容类型缓存
      
      this.getIndexnavList();
      //判断用户是否第一次使用小程序
      // var isFirst = wx.getStorageSync('isFirst');
      // if (!isFirst) {
      //   that.setData({
      //     isFirst: true
      //   });
      //   wx.setStorageSync('isFirst', 'no')
      // }
      

      this.setData({
        siteinfo: app.globalData.siteinfo
      })


      app.siteinfoCallBack = res => {
        this.setData({
          siteinfo: app.globalData.siteinfo
        })
        console.log(app.globalData.siteinfo)
      } 

    },

    autoGotourl: function (options) {
      let posturl = options.gotourl,
      postid = options.id,
      postisshare= options.isshare,
      postptype =options.posttype,
      postcnttype=options.cnttype,
      posttitle=options.title;
      let url = posturl +'?id='+postid +'&isshare='+postisshare +'&posttype='+postptype +'&cnttype='+postcnttype+'&title='+posttitle
      console.log('url',url)
      wx.navigateTo({
        url: url,
      })
    },

    chapingAds: function () {
      if (wx.createInterstitialAd) {
        interstitialAd = wx.createInterstitialAd({
          adUnitId: 'adunit-9dbd856ca2c68da3'
        })
        interstitialAd.onLoad(() => {})
        interstitialAd.onError((err) => {})
        interstitialAd.onClose(() => {})
      }
    },

    /**
     * 生命周期函数--监听页面初次渲染完成
     */
    onReady: function () {},

    /**
     * 生命周期函数--监听页面显示
     */
    onShow: function () {
      let user = app.globalData.user
      if (!user) {
        user = '';
      }
      this.setData({
        user: user,
      })
      if (user.role == 'administrator') {
        this.setData({
          isadmin: true
        })
      }
      // console.log('user', this.data.user)
    },

    /**
     * 生命周期函数--监听页面隐藏
     */
    // onHide: function () {

    // },

    /**
     * 生命周期函数--监听页面卸载
     */
    onUnload: function () {

    },

    /**
     * 页面相关事件处理函数--监听用户下拉动作
     */
    onPullDownRefresh: function () {

    },

    /**
     * 页面上拉触底事件的处理函数
     */
    onReachBottom: function () {

    },

    /**
     * 用户点击右上角分享
     */
    onShareAppMessage: function () {
      let that = this;
      if (this.data.user) {
        return {
          title: this.data.user.nickName + '分享了《' + this.data.siteinfo.name + '》小程序 - ' + this.data.cnttypetitle + '板块',
          path: '/pages/index/index?cnttype='+this.data.cnttype
        }
      } else {
      return {
        title: this.data.cnttypetitle + '共享 - ' + this.data.siteinfo.name,
        path: '/pages/index/index?cnttype='+this.data.cnttype
      }
    }
    },


    getIndexnavList() {
      API.getIndexnav(this.data.cnttype).then(res => {

        // 加载第一个分类的列表
        res[0].loaded = true;

        this.setData({
          indexnav: res
        });
        // this.getSiteInfo();
      });
    },

    handleIndexnavChange(e) {
      const {
        current,
        data
      } = e.detail;

      // console.log(e.detail)

      if (this.data.indexnav[current].posttype != 'mine') {
        this.setData({
          isActive: true,
          mineclick: false
        });
      } else {
        this.setData({
          mineclick: true
        });
        if (this.data.istrue_scroll) {
          this.setData({
            isActive: true
          });
        } else {
          this.setData({
            isActive: false
          });
        }
      }
      // data是组件返回的，当前选中选项的数据，也就是对应 categoryList[current] 那个

      // 让 tab 和 swiper同步
      this.setData({
        current
      })

      // 如果切换到了还没加载的分类，加载这个分类
      if (!this.data.indexnav[current].loaded) {
        this.setData({
          [`indexnav[${current}].loaded`]: true
        })
      }
    },

    pageloadState(e) {
      let that = this;
      that.setData({
        pageload: e.detail.pageload
      })
      setTimeout(function () {
        that.chapingAds();

        if (interstitialAd) {
          interstitialAd.show().catch((err) => {
            console.error(err)
          })
        }
      }, 1200);

      
    },



  },



})