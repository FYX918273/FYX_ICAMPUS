// 页面加载完成后执行
window.onload = function () {
  // 简单的导航高亮：根据当前路径匹配链接（自己摸索写的）
  var path = window.location.pathname;
  var navLinks = document.querySelectorAll('nav .nav-link');
  for (var i = 0; i < navLinks.length; i++) {
    var href = navLinks[i].getAttribute('href');
    if (!href) continue;
    if (path === href || path.indexOf(href + '?') === 0) {
      navLinks[i].classList.add('active');
    }
  }

  // 顶部提示条自动淡出
  var flashEls = document.querySelectorAll('.flashMsgBox');
  if (flashEls && flashEls.length) {
    for (var f = 0; f < flashEls.length; f++) {
      (function (el, index) {
        // 稍微错峰一点消失，避免多条一起闪
        var delay = 2600 + index * 400;
        setTimeout(function () {
          el.classList.remove('flashMsgShow');
          el.classList.add('flashMsgHide');
          setTimeout(function () {
            if (el && el.parentNode) {
              el.parentNode.removeChild(el);
            }
          }, 350);
        }, delay);
      })(flashEls[f], f);
    }
  }

  // 导航条滚动效果（参考网上代码稍微改了下）
  var navbar = document.getElementById('mainNavbar');
  if (navbar && navbar.classList.contains('transparent')) {
    function updateNav() {
      if (window.scrollY > 30) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    }
    updateNav();
    window.addEventListener('scroll', updateNav);
  }

  // 简单图片预览：点击朋友圈图片，弹出大图 + 上一张/下一张
  var moments = document.querySelectorAll('.pengyouquanBlock a');
  if (!moments.length) {
    return;
  }

  // 收集所有图片地址
  var imgUrls = [];
  for (var j = 0; j < moments.length; j++) {
    var href2 = moments[j].getAttribute('href');
    if (href2) {
      imgUrls.push(href2);
    } else {
      var imgTag = moments[j].querySelector('img');
      if (imgTag) {
        imgUrls.push(imgTag.src);
      }
    }
  }

  // 创建一个很简单的预览层（参考博客示例自己改的）
  var viewer = document.createElement('div');
  viewer.id = 'imgViewer';
  viewer.className = 'imgViewBigBox';
  viewer.innerHTML =
    '<div class="imgViewBigBoxBg"></div>' +
    '<div class="imgViewBigBoxBody">' +
    '  <button type="button" id="imgViewerClose" class="imgViewBigClose">×</button>' +
    '  <button type="button" id="imgViewerPrev" class="imgViewBigArrow imgViewBigPrev">‹</button>' +
    '  <img id="imgViewerImg" class="imgViewBigImg" alt="预览图片">' +
    '  <button type="button" id="imgViewerNext" class="imgViewBigArrow imgViewBigNext">›</button>' +
    '</div>';
  document.body.appendChild(viewer);

  var viewerImg = document.getElementById('imgViewerImg');
  var btnPrev = document.getElementById('imgViewerPrev');
  var btnNext = document.getElementById('imgViewerNext');
  var btnClose = document.getElementById('imgViewerClose');
  var backdrop = viewer.querySelector('.imgViewBigBoxBg');

  var nowIndex = 0;

  function showViewer(index) {
    if (index < 0 || index >= imgUrls.length) return;
    nowIndex = index;
    viewerImg.src = imgUrls[nowIndex];
    viewer.classList.add('imgViewBigBoxOpen');
  }

  function hideViewer() {
    viewer.classList.remove('imgViewBigBoxOpen');
  }

  // 绑定每一张图片
  for (var k = 0; k < moments.length; k++) {
    (function (i) {
      moments[i].onclick = function (e) {
        e.preventDefault();
        showViewer(i);
      };
    })(k);
  }

  btnClose.onclick = hideViewer;
  backdrop.onclick = hideViewer;

  btnPrev.onclick = function () {
    var idx = nowIndex - 1;
    if (idx < 0) {
      idx = imgUrls.length - 1;
    }
    showViewer(idx);
  };

  btnNext.onclick = function () {
    var idx = nowIndex + 1;
    if (idx >= imgUrls.length) {
      idx = 0;
    }
    showViewer(idx);
  };
};

