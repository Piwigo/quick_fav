$(document).ready( () => {
    $("#thumbnails .card-thumbnail a").on("click", function (e) {
        // we need to know if the e.target has a parent .quickFav. If yes, then we preventDefault
        // TODO test printing e.target

        if (e.target.className.endsWith('Fav'))
        {
            e.preventDefault();
        }
    });

    $("#thumbnails .card .addFav").on("click", function () {
        $(this).hide().parent().children(".loadFav").show();
        let image_id = jQuery(this).parent().data('id');
        let $this = jQuery(this);

        jQuery.ajax({
          url: "ws.php?format=json&method=pwg.users.favorites.add",
          type: "POST",
          data: {
            image_id: image_id
          },
          success: function (raw_data) {
            data = jQuery.parseJSON(raw_data);
            if (data.stat === "ok") {
              $this.parent().children(".loadFav").hide().parent().children(".remFav").show();
            }
            else {
              // TODO
            }
          },
          error : function (err) {
            // TODO
          }
        })
    })

    $("#thumbnails .card .remFav").on("click", function () {
        $(this).hide().parent().children(".loadFav").show();
        let image_id = jQuery(this).parent().data('id');
        let $this = jQuery(this);

        jQuery.ajax({
          url: "ws.php?format=json&method=pwg.users.favorites.remove",
          type: "POST",
          data: {
            image_id: image_id
          },
          success: function (raw_data) {
            data = jQuery.parseJSON(raw_data);
            if (data.stat === "ok") {
              $this.parent().children(".loadFav").hide().parent().children(".addFav").show();

              // if we are on the favorites pages, the thumbnail must be removed completely
              if (jQuery("link[rel='canonical']").attr("href").endsWith('/favorites'))
              {
                $this.parents('.col-outer').hide();

                jQuery.ajax({
                  url: "ws.php?format=json&method=pwg.users.favorites.checkPartialAlbums",
                  type: "GET",
                  success: function (raw_data) {
                    data = jQuery.parseJSON(raw_data);
                    if (data.stat === "ok" && data.result) {
                      jQuery('.qfav-partial-albums').show();
                    }
                  },
                })

              }
            }
            else {
              // TODO
            }
          },
          error : function (err) {
            // TODO
          }
        })
    })

});