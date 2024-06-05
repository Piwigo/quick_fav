$(document).ready( () => {
    $(".card-thumbnail a").not(".card[class^='path-ext-']").on("click", function (e) {
        // we need to know if the e.target has a parent .quickFav. If yes, then we preventDefault
        // TODO test printing e.target

        if (e.target.className.endsWith('Fav'))
        {
            console.log('hasClass *Fav');
            e.preventDefault();
        }
    });

    $(".card .addFav").not(".card[class^='path-ext-']").on("click", function () {
        $(this).hide().parent().children(".loadFav").show();
        let $this = jQuery(this);
        let cat_id = jQuery(this).parent().data('id');

        jQuery.ajax({
          url: "ws.php?format=json&method=pwg.users.favorites.addAlbum",
          type: "POST",
          data: {
            category_id: cat_id
          },
          success: function (raw_data) {
            data = jQuery.parseJSON(raw_data);
            if (data.stat === "ok") {
              $this.parent().children(".loadFav").hide().parent().children(".fav-status-full").show();
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

    $(".card .remFav").not(".card[class^='path-ext-']").on("click", function () {
        $(this).hide().parent().children(".loadFav").show();
        let cat_id = jQuery(this).parent().data('id');
        let $this = jQuery(this);

        jQuery.ajax({
          url: "ws.php?format=json&method=pwg.users.favorites.removeAlbum",
          type: "POST",
          data: {
            category_id: cat_id
          },
          success: function (raw_data) {
            data = jQuery.parseJSON(raw_data);
            if (data.stat === "ok") {
              $this.parent().children(".loadFav").hide().parent().children(".fav-status-not").show();
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