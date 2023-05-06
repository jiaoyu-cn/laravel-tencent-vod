function Vodupload(setting){

    if (typeof setting.dom == "undefined"){
        console.log("未指定上传DOM元素");
        return;
    }
    if (typeof setting.config == "undefined"){
        console.log("未指定配置名称");
        return;
    }

    // 获取对应文件信息
    this.dom = setting.dom;

    // 设置配置信息
    for (let param of Object.keys(this.config)) {
        if (typeof setting[param] == 'undefined'){
            continue;
        }
        this.config[param] = setting[param];
    }

    // 检测签名方法
    if (typeof this.config['signature'] == "undefined"){
        var tmpSign = '';
        this.config['signature'] = function(){
            $.getJSON('/jiaoyu/tencent/vod/sign/'+setting.config,function (data) {
                tmpSign = data.data.sign;
            })
            return tmpSign;
        };
    }

    // 设置回调事件
    if (typeof setting.callback == "function"){
        this.config.callback = setting.callback;
    }
    this.config.acceptedFiles = this.config.acceptedFiles.split(',');

    this.init();
}

Vodupload.prototype = {
    // 实例类
    tenvod:undefined,

    event:['media_upload', 'media_progress', 'cover_upload', 'cover_progress'],

    // 当前上传文件列表
    uploadFiles:{},

    //配置信息
    config: {
        signature:undefined,
        maxFilesize:10,
        acceptedFiles:'.mp4',
        maxFiles: 1,
        replace:true,
        cover: undefined,
        progressInterval:200,
        expireTime:7200,
        config:''
    },

    init: function (){

        // 实例化腾讯vod类
        this.tenvod = new TcVod.default({
            getSignature: this.config.signature
        });

        // 添加dom事件
        let that = this;
        $(this.dom).on('change', function (){
            let curFile = $(this).prop('files');

            if (curFile.length == 0){
                return;
            }

            // 对文件个数进行检测
            if (that.config.maxFiles == 1 && curFile.length == 1 && that.config.replace){
                // 只允许上传一个文件时，当设置为默认时，替换已上传视频文件
                for(let index in that.uploadFiles){
                    that.callback('files', 'remove', that.uploadFiles[index]);
                }
                that.uploadFiles = {};
            }

            // 对文件进行遍历
            for (let tmpFile of curFile) {

                // 文件个数判断
                if (Object.keys(that.uploadFiles).length >= that.config.maxFiles){
                    eolError("超过最大上传" + that.config.maxFiles+'个限制，文件<code>'+ tmpFile.name +'</code>无法上传');
                    return;
                }

                // 文件类型判断
                let fileType = tmpFile.name.slice(tmpFile.name.lastIndexOf('.'));
                if(that.config.acceptedFiles.indexOf(fileType) == -1) {
                    eolError("文件<code>" + tmpFile.name + '</code>的类型不支持');
                    continue;
                }

                // 文件大小判断
                if (tmpFile.size > that.config.maxFilesize * 1048576){
                    eolError("文件<code>" + tmpFile.name + '</code>的超过限制大小');
                    continue;
                }

                that.bind(tmpFile);
            }
        });
    },
    // 绑定上传事件
    bind:function(file){
        if (typeof file != "object") return;;

        let tmpUrl = URL.createObjectURL(file);
        let tmpHash = tmpUrl.substr(tmpUrl.lastIndexOf('/')+1)

        // 上传实例
        let tmpParam = {
            mediaFile: file,
            progressInterval:this.config.progressInterval,
            fileParallelLimit:1,
            chunkParallelLimit:1,
            chunkSize:8386,
        };

        // 封面图
        if (typeof this.config.cover != "undefined"){
            tmpParam.coverFile = this.config.cover
        }
        // this.uploadFiles[tmpHash] = file;
        // return;

        this.uploadFiles[tmpHash] = this.tenvod.upload(tmpParam);

        // 绑定回调事件
        let that = this;
        for (let tmpEvent of this.event) {
            this.uploadFiles[tmpHash].on(tmpEvent, function (info){
                info.key = tmpHash;
                info.file = that.uploadFiles[tmpHash];

                if (typeof tmpEvent == "string"){
                    tmpEvent = tmpEvent.split('_');
                }

                that.callback(tmpEvent[0], tmpEvent[1], info);
            });
        }

        // 绑定完成事件
        this.uploadFiles[tmpHash].done().then(function (info){
            that.uploadFiles[tmpHash]['doneResult'] = info;
            info.key = tmpHash;
            info.file = that.uploadFiles[tmpHash];

            // 设置视频的存放时间
            if (typeof that.config.expireTime != "undefined"){
                $.getJSON('/jiaoyu/tencent/vod/expire/'+that.config.config, {'ExpireTime': that.config.expireTime,'FileId': info.fileId}, function (data){
                    // console.log(data)
                });
            }

            that.callback('done', 'done', info);
        });

    },
    // 设置封面图
    setCover:function (file){
        this.config.cover = file;
    },
    removeCover:function (){
        this.setCover();
    },
    // 回调处理， type 处理类别  action 类型  data 数据
    callback:function (type, action, data) {

        // 回调业务接口
        if (typeof this.config.callback == "function") {
            this.config.callback(type, action, data);
        }
    },
    // 获取指定视频类
    getFile:function (index){
        return this.uploadFiles[index];
    },
    // 获取指定视频类
    removeFile:function (index){
        delete this.uploadFiles[index]
    },
    // 获取当前文件列表
    getFiles:function (){
        return this.uploadFiles;
    }

};
