

(function ($) {

	$(document).ready(function () {

		//小鱼哥 给发布文章加自定从豆瓣抓取图书信息
		$("#bookautomsg a").click(function () {

			// loading
			//this.showLoading();
			var isbn = $("input[name='pods_meta_book_isbn']").attr('value');
			if (!isbn) {
				$("#bookautomsg span").text('请先输入ISBN');
			} else {
				// query
				$.ajax({
					url: ajaxurl,
					type: 'post',
					dataType: 'json',
					async: false,
					data: {
						action: 'automsglibrary',
						isbn: isbn
					},
					timeout: 15000,
					beforeSend: function () {
						$("#bookautomsg a").hide();
						$("#bookautomsg span").text('正在抓取，请稍等...');
					},
					success: function (json) {
						console.log(json);

						if (!json.title || json.title == null) {
							$("#bookautomsg a").show();
							$("#bookautomsg span").css('color', 'red').text('没有输出内容，请检查ISBN是否输出正确')
						}
						if (json.translator) {
							var translator = ' / ' + json.translator
						}
						if (json.publisher) {
							var publisher = ' / ' + json.publisher
						}
						if (json.published) {
							var published = ' / ' + json.published
						}


						// update vars
						$("input[name='pods_meta_book_dbid']").val(json.id);
						$("input[name='pods_meta_book_title']").val(json.title);
						$("input[name='pods_meta_book_price']").val(json.price);

						$("input[name='pods_meta_book_author']").val(json.author);
						$("input[name='pods_meta_book_translator']").val(json.translator);
						$("input[name='pods_meta_book_publisher']").val(json.publisher);
						$("input[name='pods_meta_book_published']").val(json.published);
						$("input[name='pods_meta_book_page']").val(json.page);
						$("input[name='pods_meta_book_designed']").val(json.designed);

						$("a#todoubanlink").attr('href', json.url).css('display', 'inline');
						$("a#todoubanlogo").attr('href', json.logo).css('display', 'inline');

						$("div.editor-post-excerpt textarea").val(json.author.name + translator + publisher + published);

					},
					complete: function () {
						//this.hideLoading();
						$("#bookautomsg a").show();
						$("#bookautomsg span").css('color', 'green').text('抓取完成a～');
					}
				});
			}

		});

		//小鱼哥 给发布文章加自定从豆瓣抓取影视信息
		$("#movieautomsg a").click(function () {

			// loading
			//this.showLoading();
			var dbid = $("input[name='pods_meta_book_dbid']").attr('value');
			if (!dbid) {
				$("#movieautomsg span").text('请先输入影视豆瓣ID');
			} else {
				// query
				$.ajax({
					url: ajaxurl,
					type: 'post',
					dataType: 'json',
					async: false,
					data: {
						action: 'automsgmovie',
						dbid: dbid
					},
					timeout: 15000,
					beforeSend: function () {
						$("#movieautomsg a").hide();
						$("#movieautomsg span").text('正在抓取，请稍等...');
					},
					success: function (json) {
						console.log(json);

						console.log(json.data_1);
						// error
						if (!json.title || json.title == null) {
							$("#movieautomsg a").show();
							$("#movieautomsg span").css('color', 'red').text('没有输出内容，请检查影视豆瓣ID是否输出正确')
						}
						if (json.type != '') {
							var type = ' / ' + json.type
						}
						if (json.time != '') {
							var time = ' / ' + json.time
						}
						if (json.opened != '') {
							var opened = ' / ' + json.opened
						}


						// update vars
						//$("input[name='pods_meta_book_dbid']").val(json.id);
						$("input[name='pods_meta_book_title']").val(json.title);
						$("input[name='pods_meta_book_area']").val(json.area);

						$("input[name='pods_meta_book_type']").val(json.type);
						$("input[name='pods_meta_book_alias']").val(json.alias);
						$("input[name='pods_meta_book_time']").val(json.time);
						$("input[name='pods_meta_book_opened']").val(json.opened);
						$("input[name='pods_meta_book_language']").val(json.language);
						$("input[name='pods_meta_book_director']").val(json.director);

						$("input[name='pods_meta_book_writer']").val(json.writer);
						$("input[name='pods_meta_book_star']").val(json.star);
						$("input[name='pods_meta_book_dbscore']").val(json.dbscore);
						$("input[name='pods_meta_book_imdb']").val(json.imdb);

						$("a#todoubanlink").attr('href', 'https://movie.douban.com/' + json.url).css('display', 'inline');
						$("a#todoubanlogo").attr('href', json.logo).css('display', 'inline');

						$("div.editor-post-excerpt textarea").val(json.area + type + time + opened);

						//this.$('.canvas-media').html( json.html );
					},
					complete: function () {
						//this.hideLoading();
						$("#movieautomsg a").show();
						$("#movieautomsg span").css('color', 'green').text('抓取完成le～');
					}
				});
			}

		});


		//小鱼哥 开始增加一键将内容插入到隐藏区


		// 	var panlink = $("input[name='pods_meta_book_panlink']").val();
		// var pancode = $("input[name='pods_meta_book_pancode']").val();
		// var panmarks = $("textarea[name='pods_meta_book_panmarks']").val();

		// var cnt = '<ul>\n  <li>资源盘：</li>\n  <li><a href="'+panlink+'">点击获取</a></li>\n</ul>\n';
		// 	cnt += '<ul>\n  <li>提取码：</li>\n  <li><a href="'+pancode+'">点击获取</a></li>\n</ul>\n';
		// 	cnt += '<ul>\n  <li>资源盘备注：</li>\n  <li>\n    <p>'+panmarks+'</p>\n  </li>\n</ul>\n';


		$("#panlinktohidearea a").click(function () {
			var panlink = $("input[name='pods_meta_book_panlink']").val() ? '<li><b>资源盘：</b></li><li><a href="' + $("input[name='pods_meta_book_panlink']").val() + '">' + $("input[name='pods_meta_book_panlink']").val() + '(点击复制)</a></li>' : '';
			var pancode = $("input[name='pods_meta_book_pancode']").val() ? '<li><b>提取码：</b></li><li><a href="' + $("input[name='pods_meta_book_pancode']").val() + '">' + $("input[name='pods_meta_book_pancode']").val() + '(点击复制)</a></li>' : '';
			// var book_panqr = $("input.pods-form-ui-field-name-pods-meta-book-panqr").val() ? '<img class="' + $("input.pods-form-ui-field-name-pods-meta-book-panqr").val() + '" src="'+ wp_get_attachment_url($("input.pods-form-ui-field-name-pods-meta-book-panqr").val()) +'" alt="' + $("input.pods-form-ui-field-name-pods-meta-book-fileopen-title").val() + '">' : '';
			var panmarks = $("textarea[name='pods_meta_book_panmarks']").val() ? '<li><b>资源盘备注：</b></li><li><p>' + $("textarea[name='pods_meta_book_panmarks']").val() + '</p></li>' : '';

			var panlinkcnt = '<div class="nolistyle"><ul>'+panlink + pancode + panmarks+'</ul></div>';

			var text = $("textarea[name='pods_meta_book_videoadscnt']").val() + panlinkcnt;
			$("textarea[name='pods_meta_book_videoadscnt']").val(text);

		});
		$("#panlink2tohidearea a").click(function () {
			var panlink = $("input[name='pods_meta_book_panlink2']").val() ? '<li><b>备用盘：</b></li><li><a href="' + $("input[name='pods_meta_book_panlink2']").val() + '">' + $("input[name='pods_meta_book_panlink2']").val() + '(点击复制)</a></li>' : '';
			var pancode = $("input[name='pods_meta_book_pancode2']").val() ? '<li><b>提取码：</b></li><li><a href="' + $("input[name='pods_meta_book_pancode2']").val() + '">' + $("input[name='pods_meta_book_pancode2']").val() + '(点击复制)</a></li>' : '';
			var panmarks = $("textarea[name='pods_meta_book_panmarks2']").val() ? '<li><b>备用盘备注：</b></li><li><p>' + $("textarea[name='pods_meta_book_panmarks2']").val() + '</p></li>' : '';

			var panlinkcnt = '<div class="nolistyle"><ul>'+panlink + pancode + panmarks+'</ul></div>';

			var text = $("textarea[name='pods_meta_book_videoadscnt']").val() + panlinkcnt;
			$("textarea[name='pods_meta_book_videoadscnt']").val(text);

		});
		$("#fileopentohidearea a").click(function () {
			// var fileopenid = $("input.pods-form-ui-field-name-pods-meta-book-fileopen").val(); 
			var fileopen = $("input.pods-form-ui-field-name-pods-meta-book-fileopen").val() ? '<fileopen class="' + $("input.pods-form-ui-field-name-pods-meta-book-fileopen").val() + '" name="' + $("input.pods-form-ui-field-name-pods-meta-book-fileopen-title").val() + 'ddd">查看文件</fileopen>' : '';

			var fileopencnt = fileopen;

			var text = $("textarea[name='pods_meta_book_videoadscnt']").val() + fileopencnt;
			$("textarea[name='pods_meta_book_videoadscnt']").val(text);

		});

		//小鱼哥 结束增加一键将内容插入到隐藏区

	});

})(jQuery);
