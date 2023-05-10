class Vodupload {
    // 容器
    #dom;

    // 上传实例
    #tenvod = {};

    // 事件
    #event = ['media_upload', 'media_progress', 'cover_upload', 'cover_progress'];

    // 上传文件字典
    #uploadFiles = {};

    /**
     * 构造函数
     * @param setting
     */
    constructor(setting) {
        if (typeof setting.dom == "undefined"){
            console.log("未指定上传DOM元素");
            return;
        }
        if (typeof setting.config == "undefined"){
            console.log("未指定配置名称");
            return;
        }

        this.init(setting);
    }

    init(setting){
        this.#initConfig(setting);

        // 实例化腾讯vod类
        this.#tenvod = new TcVod.default({
            getSignature: this.config.signature
        });

        // 添加dom事件
        let that = this;
        $(this.#dom).on('change', function (){
            let curFile = $(this).prop('files');

            if (curFile.length == 0){
                return;
            }

            // 对文件个数进行检测
            if (that.config.maxFiles == 1 && curFile.length == 1 && that.config.replace){
                // 只允许上传一个文件时，当设置为默认时，替换已上传视频文件
                for(let index in that.#uploadFiles){
                    that.callback('files', 'remove', that.#uploadFiles[index]);
                }
                that.#uploadFiles = {};
            }

            // 对文件进行遍历
            for (let tmpFile of curFile) {

                let tmpUrl = URL.createObjectURL(tmpFile);
                let fileInfo = {
                    key:tmpUrl.substr(tmpUrl.lastIndexOf('/')+1),
                    file: tmpFile,
                    message:''
                };

                // 文件个数判断
                if (Object.keys(that.#uploadFiles).length >= that.config.maxFiles){
                    fileInfo.message = "超过最大上传" + that.config.maxFiles+'个限制，文件'+ tmpFile.name +'无法上传';
                    that.callback('files', 'error', fileInfo);
                    return;
                }

                // 文件类型判断
                let fileType = tmpFile.name.slice(tmpFile.name.lastIndexOf('.'));
                if(that.config.acceptedFiles.indexOf(fileType) == -1) {
                    fileInfo.message = "文件" + tmpFile.name + '的类型不支持';
                    that.callback('files', 'error',fileInfo);
                    continue;
                }

                // 文件大小判断
                if (tmpFile.size > that.config.maxFilesize * 1048576){
                    fileInfo.message = "文件" + tmpFile.name + '的超过限制大小';
                    that.callback('files', 'error', fileInfo);
                    continue;
                }

                that.#bind(fileInfo);
            }
        });

    }

    #initConfig(setting){
// 获取对应文件信息
        this.#dom = setting.dom;
        this.config = this.#getConfigDefault();

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
    }

    #bind(fileInfo){
        if (typeof fileInfo.file!= "object") return;

        // 上传实例
        let tmpParam = {
            mediaFile: fileInfo.file,
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

        this.#uploadFiles[fileInfo.key] = this.#tenvod.upload(tmpParam);

        // 绑定回调事件
        let that = this;
        for (let tmpEvent of this.#event) {
            this.#uploadFiles[fileInfo.key].on(tmpEvent, function (info){
                info.key = fileInfo.key;
                info.file = that.#uploadFiles[fileInfo.key];

                if (typeof tmpEvent == "string"){
                    tmpEvent = tmpEvent.split('_');
                }

                that.callback(tmpEvent[0], tmpEvent[1], info);
            });
        }

        // 绑定完成事件
        this.#uploadFiles[fileInfo.key].done().then(function (info){
            that.#uploadFiles[fileInfo.key]['doneResult'] = info;

            info.key = fileInfo.key;
            info.file = that.#uploadFiles[fileInfo.key];


            // 设置视频的存放时间
            if (typeof that.config.expireTime != "undefined"){
                $.getJSON('/jiaoyu/tencent/vod/modify/'+that.config.config, {'ExpireTime': that.config.expireTime,'FileId': info.fileId}, function (data){
                    // console.log(data)
                });
            }

            that.callback('done', 'done', info);
        });
    }

    setCover(file){
        this.config.cover = file;
    }
    removeCover(){
        this.setCover();
    }

    // 回调处理， type 处理类别  action 类型  data 数据
    callback(type, action, data) {

        // 回调业务接口
        if (typeof this.config.callback == "function") {
            this.config.callback(type, action, data);
        }
    }

    // 获取指定视频类
    getFile (index){
        return this.#uploadFiles[index];
    }

    // 获取指定视频类
    removeFile (index){
        delete this.#uploadFiles[index]
    }

    // 获取当前文件列表
    getFiles (){
        return this.#uploadFiles;
    }

    //取消上传
    cannelFile (key){
        let curFile = this.getFile(key);
        if(typeof curFile == "object"){
            curFile.cancel();
        }

        return true;
    }


    /**
     * 获取默认配置
     * @returns object
     */
    #getConfigDefault(){
        return {
            signature:undefined,
            maxFilesize:10,
            acceptedFiles:'.mp4',
            maxFiles: 1,
            replace:true,
            cover: undefined,
            progressInterval:200,
            expireTime:7200,
            config:''
        };
    }
}
