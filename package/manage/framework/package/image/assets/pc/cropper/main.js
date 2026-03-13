window.onload = function () {
  'use strict';

  var Cropper = window.Cropper;
  var URL = window.URL || window.webkitURL;
  var container = document.querySelector('.img-container');
  var image = container.getElementsByTagName('img').item(0);
  var download = document.getElementById('download');
  var actions = document.getElementById('actions');
  var dataX = document.getElementById('dataX');
  var dataY = document.getElementById('dataY');
  var dataHeight = document.getElementById('dataHeight');
  var dataWidth = document.getElementById('dataWidth');
  var dataRotate = document.getElementById('dataRotate');
  var dataScaleX = document.getElementById('dataScaleX');
  var dataScaleY = document.getElementById('dataScaleY');

  var dataX_text = document.getElementById('dataX_text');
  var dataY_text = document.getElementById('dataY_text');
  var dataHeight_text = document.getElementById('dataHeight_text');
  var dataWidth_text = document.getElementById('dataWidth_text');
  var dataRotate_text = document.getElementById('dataRotate_text');
  var dataScaleX_text = document.getElementById('dataScaleX_text');
  var dataScaleY_text = document.getElementById('dataScaleY_text');

  var options = {
    aspectRatio: aspectRatioValue,
    preview: '.img-preview',
    ready: function (e) {
      //console.log(e.type);
      var param = $('#param').val();
      if (param) {
        param = param.split('_');
        param[0] = parseFloat(param[0]);
        param[1] = parseFloat(param[1]);
        param[2] = parseFloat(param[2]);
        param[3] = parseFloat(param[3]);
        param[4] = parseFloat(param[4]);
        param[5] = parseFloat(param[5]);
        param[6] = parseFloat(param[6]);
        cropper.setData({"x":param[0],"y":param[1],"width":param[3],"height":param[2],"rotate":param[4],"scaleX":param[5],"scaleY":param[6]});
      }
    },
    cropstart: function (e) {
      //console.log(e.type, e.detail.action);
    },
    cropmove: function (e) {
      //console.log(e.type, e.detail.action);
    },
    cropend: function (e) {
      //console.log(e.type, e.detail.action);
    },
    crop: function (e) {
      var data = e.detail;

      //console.log(e.type);
      dataX.value = Math.round(data.x);
      dataY.value = Math.round(data.y);
      dataHeight.value = Math.round(data.height);
      dataWidth.value = Math.round(data.width);
      dataRotate.value = typeof data.rotate !== 'undefined' ? data.rotate : '';
      dataScaleX.value = typeof data.scaleX !== 'undefined' ? data.scaleX : '';
      dataScaleY.value = typeof data.scaleY !== 'undefined' ? data.scaleY : '';

      dataX_text.innerHTML = dataX.value;
      dataY_text.innerHTML = dataY.value;
      dataHeight_text.innerHTML = dataHeight.value;
      dataWidth_text.innerHTML = dataWidth.value;
      dataRotate_text.innerHTML = dataRotate.value;
      dataScaleX_text.innerHTML = dataScaleX.value;
      dataScaleY_text.innerHTML = dataScaleY.value;
    },
    zoom: function (e) {
      //console.log(e.type, e.detail.ratio);
    }
  };

  var cropper = new Cropper(image, options);
  var originalImageURL = image.src;
  var uploadedImageType = 'image/jpeg';
  var uploadedImageName = 'cropped.jpg';
  var uploadedImageURL;

  // Tooltip
  $('[data-toggle="tooltip"]').tooltip();

  // Buttons
  if (!document.createElement('canvas').getContext) {
    $('button[data-method="getCroppedCanvas"]').prop('disabled', true);
  }

  if (typeof document.createElement('cropper').style.transition === 'undefined') {
    $('button[data-method="rotate"]').prop('disabled', true);
    $('button[data-method="scale"]').prop('disabled', true);
  }

  // Download
  if (typeof download.download === 'undefined') {
    download.className += ' disabled';
    download.title = 'Your browser does not support download';
  }

  function aspectRatio(value)
  {
      if (!cropper) {
        return;
      }
      options.aspectRatio = value;
      // Restart
      cropper.destroy();
      cropper = new Cropper(image, options);
  }

  // Methods
  actions.querySelector('.docs-buttons').onclick = function (event) {
    var e = event || window.event;
    var target = e.target || e.srcElement;
    var cropped;
    var result;
    var input;
    var data;

    if (!cropper) {
      return;
    }

    while (target !== this) {
      if (target.getAttribute('data-method')) {
        break;
      }

      target = target.parentNode;
    }

    if (target === this || target.disabled || target.className.indexOf('disabled') > -1) {
      return;
    }

    data = {
      method: target.getAttribute('data-method'),
      value: target.getAttribute('data-value'),
      target: target.getAttribute('data-target'),
      option: target.getAttribute('data-option') || undefined,
      secondOption: target.getAttribute('data-second-option') || undefined
    };

    cropped = cropper.cropped;

    if (data.method) {
      if (data.method == 'save') {
        var option = {};
        option.param_x = dataX.value;
        option.param_y = dataY.value;
        option.param_h = dataHeight.value;
        option.param_w = dataWidth.value;
        option.param_r = dataRotate.value;
        option.param_sx = dataScaleX.value;
        option.param_sy = dataScaleY.value;
        option.pic = $('.img-container img').attr('src');

        var cas = cropper.getCroppedCanvas({ maxWidth: 4096, maxHeight: 4096 })
        option.img = cas.toDataURL('image/jpeg');//生成base64图片的格式

        /*
        cas.toBlob(function (e) {
            console.log(e);  //生成Blob的图片格式
        })
        */
        option.id = $('#id').val();
        option.authorization = $('#authorization').val();
        option.group_id = $('#group_id').val();
        option.user_id = $('#user_id').val();
        option.project = $('#project').val();
        option.name = $('#name').val();
        option.type = $('#type').val();
        var url = config.host;
        $.post(url, option, function(t)
        {
            if (typeof t != 'object') {
              var t = eval('(' + t + ')');
            }
            t = t.data
            
            var pic = t.url;
            var info = '';
            if (t.width) {
              info = t.width + '_' + t.height + '_' +t.size + '_' + t.fid;
            }
            var state = $('#state').val();
            //window.parent.picSet(pic, state, info);
            window.parent.postMessage({action: 'picSet', pic:pic, name:option.name, type:option.type}, '*')
        })
        return;
      } else if (data.method == 'del') {
        var state = $('#state').val();
        window.parent.picDel(state);
        return;
      } else if (typeof data.target !== 'undefined') {
        input = document.querySelector(data.target);

        if (!target.hasAttribute('data-option') && data.target && input) {
          try {
            data.option = JSON.parse(input.value);
          } catch (e) {
            console.log(e.message);
          }
        }
      }

      switch (data.method) {
        case 'rotate':
          if (cropped && options.viewMode > 0) {
            cropper.clear();
          }

          break;

        case 'getCroppedCanvas':
          try {
            data.option = JSON.parse(data.option);
          } catch (e) {
            console.log(e.message);
          }

          if (uploadedImageType === 'image/jpeg') {
            if (!data.option) {
              data.option = {};
            }

            data.option.fillColor = '#fff';
          }

          break;
      }

      if (data.method != 'aspectRatio') {
        result = cropper[data.method](data.option, data.secondOption);
      }
      

      switch (data.method) {

        case 'aspectRatio':
          cropper.destroy();
          if (data.value.indexOf(':') == -1) {
            options.aspectRatio = data.value;
          } else {
            var tmp = data.value.split(':');
            options.aspectRatio = tmp[0]/tmp[1];
          }
          cropper = new Cropper(image, options);

          break;

        case 'rotate':
          if (cropped && options.viewMode > 0) {
            cropper.crop();
          }

          break;

        case 'scaleX':
        case 'scaleY':
          target.setAttribute('data-option', -data.option);
          break;

        case 'getCroppedCanvas':
          if (result) {
            // Bootstrap's Modal
            $('#getCroppedCanvasModal').modal().find('.modal-body').html(result);

            if (!download.disabled) {
              download.download = uploadedImageName;
              download.href = result.toDataURL(uploadedImageType);
            }
          }

          break;

        case 'destroy':
          cropper = null;

          if (uploadedImageURL) {
            URL.revokeObjectURL(uploadedImageURL);
            uploadedImageURL = '';
            image.src = originalImageURL;
          }

          break;
      }

      if (typeof result === 'object' && result !== cropper && input) {
        try {
          input.value = JSON.stringify(result);
        } catch (e) {
          console.log(e.message);
        }
      }
    }
  };

  document.body.onkeydown = function (event) {
    var e = event || window.event;

    if (e.target !== this || !cropper || this.scrollTop > 300) {
      return;
    }

    switch (e.keyCode) {
      case 37:
        e.preventDefault();
        cropper.move(-1, 0);
        break;

      case 38:
        e.preventDefault();
        cropper.move(0, -1);
        break;

      case 39:
        e.preventDefault();
        cropper.move(1, 0);
        break;

      case 40:
        e.preventDefault();
        cropper.move(0, 1);
        break;
    }
  };

  // Import image
  var inputImage = document.getElementById('inputImage');

  if (URL) {
    inputImage.onchange = function () {
      var files = this.files;
      var file;

      if (cropper && files && files.length) {
        file = files[0];

        if (/^image\/\w+/.test(file.type)) {
          uploadedImageType = file.type;
          uploadedImageName = file.name;

          if (uploadedImageURL) {
            URL.revokeObjectURL(uploadedImageURL);
          }

          image.src = uploadedImageURL = URL.createObjectURL(file);
          cropper.destroy();
          cropper = new Cropper(image, options);
          inputImage.value = null;
        } else {
          window.alert('Please choose an image file.');
        }
      }
    };
  } else {
    inputImage.disabled = true;
    inputImage.parentNode.className += ' disabled';
  }
};
