/* global Cookies, payaman_wishlist_object */
jQuery(function ($) {
  function showAlert(message) {
    showModalAlert(message || payaman_wishlist_object.i18n.generic_error);
  }
  function showToast(message) {
    var $toast = $("#payaman_wishlist-toast");
    if (!$toast.length) {
      $toast = $(
        '<div id="payaman_wishlist-toast" class="payaman_wishlist-toast"></div>',
      );
      $("body").append($toast);
    }
    $toast.text(message).addClass("is-visible");
    setTimeout(function () {
      $toast.removeClass("is-visible");
    }, 3000);
  }

  var $modal = $("#payaman_wishlist-modal");
  var collectionsData = payaman_wishlist_object.collections || [];
  var defaultCollectionId = payaman_wishlist_object.default_collection_id || "";
  var collectionLimit = parseInt(
    payaman_wishlist_object.collection_limit || 0,
    10,
  );
  var canManageCollections = !!payaman_wishlist_object.can_manage_collections;
  var pendingProductId = null;
  var pendingAction = null;
  var pendingCollectionId = defaultCollectionId;
  var pendingVariationId = 0;

  function parseWishlistCookie() {
    if (!Cookies.get("payaman_wishlist_product")) {
      return [];
    }
    try {
      var parsed = JSON.parse(Cookies.get("payaman_wishlist_product"));
      return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
      return [];
    }
  }

  function persistWishlistCookie(list) {
    try {
      Cookies.set("payaman_wishlist_product", JSON.stringify(list), {
        expires: 30,
        path: "/",
      });
    } catch (error) {
      // ignore cookie write issues
    }
  }

  function formatLabel(baseText, count) {
    if (
      payaman_wishlist_object.payaman_wishlist_count === "yes" &&
      count !== "" &&
      typeof count !== "undefined"
    ) {
      return baseText + " (" + count + ")";
    }
    return baseText;
  }

  var confirmCallback = null;

  function ensureModal() {
    if (!$modal.length) {
      $modal = $("#payaman_wishlist-modal");
    }
    return $modal;
  }

  function showModalView(view) {
    var modal = ensureModal();
    if (!modal.length) {
      return;
    }
    modal.attr("data-view", view);
    modal.addClass("is-visible");
  }

  function closeModal(keepPending) {
    if (!$modal.length) {
      return;
    }
    $modal.removeClass("is-visible");
    if (!keepPending) {
      pendingProductId = null;
      pendingAction = null;
    }
    confirmCallback = null;
  }

  function showModalAlert(message) {
    var modal = ensureModal();
    if (!modal.length) {
      showAlert(message);
      return;
    }
    modal
      .find(".payaman_wishlist-modal__view--message .payaman_wishlist-modal__message")
      .text(message || "");
    modal
      .find(".payaman_wishlist-modal__view--message .payaman_wishlist-modal__actions")
      .html(
        '<button type="button" class="button payaman_wishlist-modal__close" data-payaman_wishlist-close>' +
          payaman_wishlist_object.i18n.ok +
          "</button>",
      );
    modal.removeClass("is-manage is-confirm").addClass("is-message");
    showModalView("message");
  }

  function showModalConfirm(message, onConfirm) {
    var modal = ensureModal();
    if (!modal.length) {
      if (window.confirm(message) && onConfirm) onConfirm();
      return;
    }
    confirmCallback = onConfirm || null;
    modal
      .find(".payaman_wishlist-modal__view--confirm .payaman_wishlist-modal__message")
      .text(message || "");
    modal
      .find(".payaman_wishlist-modal__view--confirm .payaman_wishlist-modal__actions")
      .html(
        '<button type="button" class="button payaman_wishlist-modal__confirm-yes">' +
          payaman_wishlist_object.i18n.yes +
          '</button> ' +
          '<button type="button" class="button payaman_wishlist-modal__close" data-payaman_wishlist-close>' +
          payaman_wishlist_object.i18n.cancel +
          "</button>",
      );
    modal.removeClass("is-manage is-message").addClass("is-confirm");
    showModalView("confirm");
  }

  function openMessageModal(message) {
    var modal = ensureModal();
    if (!modal.length) {
      showAlert(message);
      return;
    }
    modal
      .find(".payaman_wishlist-modal__view--message .payaman_wishlist-modal__message")
      .text(message || "");
    modal
      .find(".payaman_wishlist-modal__view--message .payaman_wishlist-modal__actions")
      .html(
      '<a href="' +
        payaman_wishlist_object.wishlist_page_url +
        '" class="button payaman_wishlist-modal__view-link">' +
        payaman_wishlist_object.i18n.view_wishlist +
        '</a> ' +
        '<button type="button" class="button payaman_wishlist-modal__close" data-payaman_wishlist-close>' +
        payaman_wishlist_object.i18n.close +
        "</button>",
    );
    modal.removeClass("is-manage is-confirm").addClass("is-message");
    showModalView("message");
  }

  function renderCollectionOptions(selectedId) {
    var modal = ensureModal();
    var $select = modal.find(".payaman_wishlist-collection-select");
    if (!$select.length) {
      return;
    }
    var options = "";
    if (!collectionsData.length) {
      options +=
        '<option value="" disabled>' +
        (canManageCollections
          ? payaman_wishlist_object.i18n.no_collections_yet
          : "") +
        "</option>";
    } else {
      collectionsData.forEach(function (collection) {
        var selected = collection.id === selectedId ? " selected" : "";
        options +=
          '<option value="' +
          collection.id +
          '"' +
          selected +
          ">" +
          collection.name +
          " (" +
          collection.count +
          ")</option>";
      });
    }
    $select.html(options);
    if (selectedId) {
      $select.val(selectedId);
    }
  }

  function updateCollectionsData(newCollections) {
    if (!Array.isArray(newCollections)) {
      return;
    }
    collectionsData = newCollections;
    renderCollectionOptions(pendingCollectionId || defaultCollectionId);
    collectionsData.forEach(function (collection) {
      var $tabLink = $(
        '.payaman_wishlist-collection-link[data-collection-id="' +
          collection.id +
          '"]',
      );
      if ($tabLink.length) {
        $tabLink
          .find(".payaman_wishlist-tab-count")
          .text("(" + collection.count + ")");
      }
    });
  }

  function handleWishlistClick(productId, collectionId, action, variationId) {
    // Skip modal, use default collection
    var actionToUse = action || "insert";
    var collectionToUse = collectionId || defaultCollectionId;
    triggerWishlistAction(actionToUse, productId, collectionToUse, variationId);
  }

  function triggerWishlistAction(action, productId, collectionId, variationId) {
    updateButtonCollection(productId, collectionId);
    sendWishlistRequest(action, productId, collectionId, variationId);
  }

  function updateButtonCollection(productId, collectionId) {
    var selector = '.payaman_wishlist[data-product-id="' + productId + '"]';
    var $wrapper = $(selector);
    if (!$wrapper.length) {
      return;
    }
    $wrapper.attr("data-collection-id", collectionId || "");
    $wrapper
      .find(".payaman_wishlist-button")
      .attr("data-collection-id", collectionId || "");
  }

  function handleAjaxError(message, product_id) {
    $(".payaman_wishlist .payaman_wishlist-loading").removeClass("on");
    var selector = ".payaman_wishlist .payaman_wishlist-button";
    if (product_id) {
      selector =
        '.payaman_wishlist .payaman_wishlist-button[data-product-id="' +
        product_id +
        '"]';
    }
    $(selector).show();
    if (message) {
      showToast(message);
    }
  }

  function sendWishlistRequest(
    action,
    product_id,
    collection_id,
    variation_id,
  ) {
    var $wrapper = $('.payaman_wishlist[data-product-id="' + product_id + '"]');
    $wrapper.find(".payaman_wishlist-loading").addClass("on");
    $wrapper.find(".payaman_wishlist-button").hide();

    var dataPost = {
      action: "update_payaman_wishlist",
      fav_action: action,
      product_id: product_id,
      nonce: payaman_wishlist_object.nonce,
      collection_id: collection_id || "",
      variation_id: variation_id || 0,
    };

    $.ajax({
      url: payaman_wishlist_object.ajax_url,
      type: "POST",
      data: dataPost,
      success: function (response) {
        $wrapper.find(".payaman_wishlist-loading").removeClass("on");
        $wrapper.find(".payaman_wishlist-button").show();

        if (!response || !response.success) {
          var errorMessage =
            response && response.data && response.data.message
              ? response.data.message
              : payaman_wishlist_object.error_message;
          handleAjaxError(errorMessage, product_id);
          return;
        }

        var data = response.data || {};
        var count = typeof data.count !== "undefined" ? data.count : "";
        var buttonSelector =
          '.payaman_wishlist .payaman_wishlist-button[data-product-id="' +
          product_id +
          '"]';
        var wrapperSelector =
          '.payaman_wishlist[data-product-id="' + product_id + '"]';
        var $currentButton = $(buttonSelector);
        var $currentWrapper = $(wrapperSelector);

        var collectionState = data.collection_state || data.state;
        var collections = data.collections;

        if (collections) {
          updateCollectionsData(collections);
        }

        if (collectionState === "on") {
          if (payaman_wishlist_object.enable_add_success_message === "yes") {
            showToast(payaman_wishlist_object.add_success_message);
          }
          $currentButton.removeClass("off").addClass("on");
          $currentWrapper.removeClass("off").addClass("on");

          if (payaman_wishlist_object.button_type === "text") {
            $currentButton.text(formatLabel(payaman_wishlist_object.on_val, count));
          } else {
            $currentButton.attr("src", payaman_wishlist_object.on_val);
            $currentWrapper.find(".count").text(count);
          }
        } else {
          if (payaman_wishlist_object.enable_remove_success_message === "yes") {
            showToast(payaman_wishlist_object.remove_success_message);
          }
          $currentButton.removeClass("on").addClass("off");
          $currentWrapper.removeClass("on").addClass("off");

          if (payaman_wishlist_object.button_type === "text") {
            $currentButton.text(formatLabel(payaman_wishlist_object.off_val, count));
          } else {
            $currentButton.attr("src", payaman_wishlist_object.off_val);
            $currentWrapper.find(".count").text(count);
          }
          removeRowFromTable(product_id);
        }

        if (data.collection_id) {
          updateButtonCollection(product_id, data.collection_id);
        }
      },
      error: function () {
        $wrapper.find(".payaman_wishlist-loading").removeClass("on");
        $wrapper.find(".payaman_wishlist-button").show();
        handleAjaxError(payaman_wishlist_object.error_message, product_id);
      },
    });
  }

  function triggerWishlistAction(
    action,
    product_id,
    collection_id,
    variation_id,
  ) {
    collection_id = collection_id || defaultCollectionId;
    variation_id = parseInt(variation_id || 0, 10);
    var list = parseWishlistCookie();
    var position = list.indexOf(product_id);

    if (action === "insert") {
      if (position === -1) {
        list.push(product_id);
        persistWishlistCookie(list);
      }
    } else {
      if (position !== -1) {
        list.splice(position, 1);
        persistWishlistCookie(list);
      }
    }

    sendWishlistRequest(action, product_id, collection_id, variation_id);
  }

  function updateBulkControls($wrapper) {
    $wrapper =
      $wrapper && $wrapper.length
        ? $wrapper
        : $(".payaman_wishlist-table-wrapper");
    if (!$wrapper.length) {
      return;
    }
    var $checkboxes = $wrapper.find(".payaman_wishlist-bulk-checkbox");
    var checkedCount = $checkboxes.filter(":checked").length;
    $wrapper
      .find(".payaman_wishlist-bulk-remove")
      .prop("disabled", checkedCount === 0);
    var allChecked =
      $checkboxes.length > 0 && checkedCount === $checkboxes.length;
    $wrapper
      .find(".payaman_wishlist-bulk-select-all")
      .prop("checked", allChecked);
  }

  function removeRowFromTable(productId) {
    var $row = $(
      '.payaman_wishlist-table-wrapper tr[data-product-id="' + productId + '"]',
    );
    if (!$row.length) {
      return;
    }
    var $wrapper = $row.closest(".payaman_wishlist-table-wrapper");
    $row.remove();
    if ($wrapper.find("tbody tr").length === 0) {
      var emptyMessage =
        $wrapper.data("empty-message") || "No wishlist products found.";
      $wrapper.replaceWith(
        '<p class="payaman_wishlist-empty-message">' + emptyMessage + "</p>",
      );
    } else {
      updateBulkControls($wrapper);
    }
  }

  function bulkRemoveWishlist(productIds, $wrapper) {
    if (!productIds.length) {
      return;
    }

    var $button = $wrapper.find(".payaman_wishlist-bulk-remove");
    $button.prop("disabled", true).addClass("is-loading");

    $.ajax({
      url: payaman_wishlist_object.ajax_url,
      type: "POST",
      data: {
        action: "payaman_wishlist_bulk_remove",
        product_ids: productIds,
        nonce: payaman_wishlist_object.nonce_bulk,
      },
      success: function (response) {
        if (!response || !response.success) {
          var errorMessage =
            response && response.data && response.data.message
              ? response.data.message
              : payaman_wishlist_object.error_message;
          openMessageModal(errorMessage);
          return;
        }

        var removed = Array.isArray(response.data.removed)
          ? response.data.removed
          : productIds;
        removed = removed
          .map(function (id) {
            return parseInt(id, 10);
          })
          .filter(function (id) {
            return !isNaN(id);
          });

        var list = parseWishlistCookie().filter(function (id) {
          return removed.indexOf(id) === -1;
        });
        persistWishlistCookie(list);

        removed.forEach(function (id) {
          removeRowFromTable(id);
        });

        if (payaman_wishlist_object.enable_remove_success_message === "yes") {
          openMessageModal(payaman_wishlist_object.remove_success_message);
        }
      },
      error: function () {
        openMessageModal(payaman_wishlist_object.error_message);
      },
      complete: function () {
        $button.removeClass("is-loading");
        updateBulkControls($wrapper);
      },
    });
  }

  function handleCollectionCreate() {
    var modal = ensureModal();
    var name = modal.find(".payaman_wishlist-collection-name").val();
    var isPublic = modal
      .find(".payaman_wishlist-collection-public")
      .is(":checked");

    if (!name) {
      showAlert(payaman_wishlist_object.i18n.fill_collection_name);
      modal.find(".payaman_wishlist-collection-name").focus();
      return;
    }

    $.ajax({
      url: payaman_wishlist_object.ajax_url,
      type: "POST",
      data: {
        action: "payaman_wishlist_collection_create",
        nonce: payaman_wishlist_object.nonce_collection,
        name: name,
        is_public: isPublic,
      },
      success: function (response) {
        if (!response || !response.success) {
          var errorMessage =
            response && response.data && response.data.message
              ? response.data.message
              : payaman_wishlist_object.error_message;
          openMessageModal(errorMessage);
          return;
        }
        updateCollectionsData(response.data.collections);
        pendingCollectionId = response.data.collection.id;

        // Reset view
        modal.find(".payaman_wishlist-collection-name").val("");
        modal.find(".payaman_wishlist-collection-public").prop("checked", false);
        modal.find(".payaman_wishlist-collection-create").hide();
        modal.find(".payaman_wishlist-collection-select-wrapper").show();
        modal.find(".payaman_wishlist-modal__manage-actions").show();

        renderCollectionOptions(pendingCollectionId);
      },
      error: function () {
        openMessageModal(payaman_wishlist_object.error_message);
      },
    });
  }

  $("body").on("click", ".payaman_wishlist-button", function () {
    var requireLogin = payaman_wishlist_object.required_login === "yes";
    if (requireLogin && !payaman_wishlist_object.is_login) {
      showAlert(payaman_wishlist_object.required_login_message);
      return;
    }

    var $button = $(this);
    var productId = parseInt($button.data("product-id"), 10);
    var isActive = $button.hasClass("on");
    var collectionId =
      $button.data("collection-id") || defaultCollectionId || "";

    // Baca variation_id dari WooCommerce (diisi otomatis saat user pilih variasi)
    var $form = $button.closest("form.variations_form");
    var variationId = 0;
    if ($form.length) {
      variationId = parseInt(
        $form.find('input[name="variation_id"]').val() || 0,
        10,
      );
    } else {
      variationId = parseInt($button.data("variation-id") || 0, 10);
    }

    // Simpan ke data attribute agar konsisten
    $button.attr("data-variation-id", variationId || "");
    $button
      .closest(".payaman_wishlist")
      .attr("data-variation-id", variationId || "");

    if (!isActive) {
      handleWishlistClick(productId, collectionId, "insert", variationId);
    } else {
      triggerWishlistAction("delete", productId, collectionId, variationId);
    }
  });

  $(document).on("click", "[data-payaman_wishlist-close]", function (event) {
    event.preventDefault();
    closeModal();
  });

  $(document).on("click", ".payaman_wishlist-modal__confirm-yes", function () {
    if (typeof confirmCallback === "function") {
      confirmCallback();
    }
    closeModal();
  });

  $(document).on("click", ".payaman_wishlist-modal__backdrop", function () {
    closeModal();
  });

  $(document).on("keyup", function (event) {
    if (event.key === "Escape") {
      closeModal();
    }
  });

  $(document).on("change", ".payaman_wishlist-bulk-checkbox", function () {
    updateBulkControls($(this).closest(".payaman_wishlist-table-wrapper"));
  });

  $(document).on("change", ".payaman_wishlist-bulk-select-all", function () {
    var $wrapper = $(this).closest(".payaman_wishlist-table-wrapper");
    var state = $(this).is(":checked");
    $wrapper.find(".payaman_wishlist-bulk-checkbox").prop("checked", state);
    updateBulkControls($wrapper);
  });

  $(document).on("click", ".payaman_wishlist-bulk-remove", function (event) {
    event.preventDefault();
    var $wrapper = $(this).closest(".payaman_wishlist-table-wrapper");
    var $selected = $wrapper.find(".payaman_wishlist-bulk-checkbox:checked");
    if (!$selected.length) {
      return;
    }
    var productIds = $selected
      .map(function () {
        return parseInt($(this).val(), 10);
      })
      .get()
      .filter(function (id) {
        return !isNaN(id);
      });
    if (!productIds.length) {
      return;
    }
    bulkRemoveWishlist(productIds, $wrapper);
  });

  $(document).on("change", ".payaman_wishlist-bulk-move-target", function () {
    var $wrapper = $(this).closest(".payaman_wishlist-table-wrapper");
    var target = $(this).val();
    var hasSelection =
      $wrapper.find(".payaman_wishlist-bulk-checkbox:checked").length > 0;
    $wrapper
      .find(".payaman_wishlist-bulk-move-button")
      .prop("disabled", !target || !hasSelection);
  });

  $(document).on("click", ".payaman_wishlist-bulk-move-button", function (event) {
    event.preventDefault();
    var $wrapper = $(this).closest(".payaman_wishlist-table-wrapper");
    var target = $wrapper.find(".payaman_wishlist-bulk-move-target").val();
    if (!target) {
      return;
    }
    var $selected = $wrapper.find(".payaman_wishlist-bulk-checkbox:checked");
    if (!$selected.length) {
      return;
    }
    var productIds = $selected
      .map(function () {
        return parseInt($(this).val(), 10);
      })
      .get()
      .filter(function (id) {
        return !isNaN(id);
      });
    if (!productIds.length) {
      return;
    }

    $.ajax({
      url: payaman_wishlist_object.ajax_url,
      type: "POST",
      data: {
        action: "payaman_wishlist_collection_move_items",
        nonce: payaman_wishlist_object.nonce_collection,
        product_ids: productIds,
        target_collection_id: target,
        source_collection_id: $wrapper.data("collection-id") || "",
      },
      success: function (response) {
        if (!response || !response.success) {
          var errorMessage =
            response && response.data && response.data.message
              ? response.data.message
              : payaman_wishlist_object.error_message;
          openMessageModal(errorMessage);
          return;
        }

        updateCollectionsData(response.data.collections);
        $selected.prop("checked", false).closest("tr").fadeOut(300, function () {
          $(this).remove();
          if ($wrapper.find("tbody tr").length === 0) {
            var emptyMsg = $wrapper.data("empty-message");
            $wrapper.find(".payaman_wishlist-table-responsive").replaceWith(
              '<p class="payaman_wishlist-empty-message">' + emptyMsg + "</p>",
            );
          }
        });
        updateBulkControls($wrapper);
        $wrapper
          .find(".payaman_wishlist-bulk-move-button")
          .prop("disabled", true);
        showToast(payaman_wishlist_object.i18n.move_success);
      },
      error: function () {
        openMessageModal(payaman_wishlist_object.error_message);
      },
    });
  });

  $(document).on("click", ".payaman_wishlist-collection-apply", function (event) {
    event.preventDefault();
    var modal = ensureModal();
    var selected = modal.find(".payaman_wishlist-collection-select").val();
    if (!selected) {
      showAlert(payaman_wishlist_object.i18n.select_collection_first);
      return;
    }
    pendingCollectionId = selected;
    var productId = pendingProductId;
    var actionToUse = pendingAction || "insert";
    var variationId = pendingVariationId || 0;
    closeModal(true);
    if (productId) {
      triggerWishlistAction(actionToUse, productId, selected, variationId);
    }
  });

  $(document).on(
    "click",
    ".payaman_wishlist-collection-create-toggle",
    function (event) {
      event.preventDefault();
      var modal = ensureModal();
      modal.find(".payaman_wishlist-collection-select-wrapper").hide();
      modal.find(".payaman_wishlist-modal__manage-actions").hide();
      modal.find(".payaman_wishlist-collection-create").fadeIn(200);
    },
  );

  $(document).on(
    "click",
    ".payaman_wishlist-collection-create-cancel",
    function (event) {
      event.preventDefault();
      var modal = ensureModal();
      modal.find(".payaman_wishlist-collection-create").hide();
      modal.find(".payaman_wishlist-collection-select-wrapper").fadeIn(200);
      modal.find(".payaman_wishlist-modal__manage-actions").fadeIn(200);
    },
  );

  $(document).on(
    "click",
    ".payaman_wishlist-collection-create-submit",
    function (event) {
      event.preventDefault();
      if (collectionLimit && collectionsData.length >= collectionLimit) {
        openMessageModal(payaman_wishlist_object.i18n.collection_limit_reached);
        return;
      }
      handleCollectionCreate();
    },
  );

  $(document).on("click", ".payaman_wishlist-remove-item", function (event) {
    event.preventDefault();
    var $btn = $(this);
    var productId = parseInt($btn.data("product-id"), 10);
    var collectionId = $btn.data("collection-id") || "";
    if (!productId) return;
    $btn.prop("disabled", true);
    triggerWishlistAction("delete", productId, collectionId, 0);
    $btn.prop("disabled", false);
  });

  $(document).on("click", ".payaman_wishlist-add-all-cart", function (event) {
    event.preventDefault();
    var $btn = $(this);
    var $wrapper = $btn.closest(".payaman_wishlist-table-wrapper");
    var $rows = $wrapper.find(
      ".payaman_wishlist-table tbody tr[data-product-id]",
    );
    var productIds = [];
    $rows.each(function () {
      productIds.push($(this).data("product-id"));
    });
    if (!productIds.length) {
      showModalAlert(payaman_wishlist_object.i18n.no_products_in_wishlist);
      return;
    }
    $btn.prop("disabled", true).text(payaman_wishlist_object.i18n.adding_to_cart);
    $.ajax({
      url: payaman_wishlist_object.ajax_url,
      type: "POST",
      data: {
        action: "payaman_wishlist_add_all_to_cart",
        nonce: payaman_wishlist_object.nonce_bulk,
        product_ids: productIds,
      },
      success: function (response) {
        $btn.prop("disabled", false).text(payaman_wishlist_object.i18n.add_all_to_cart);
        if (!response || !response.success) {
          showModalAlert(
            response && response.data && response.data.message
              ? response.data.message
              : payaman_wishlist_object.error_message,
          );
          return;
        }
        var data = response.data;
        var noticeHtml =
          '<div class="payaman_wishlist-cart-notice payaman_wishlist-cart-notice--success">' +
          '<span class="payaman_wishlist-cart-notice__text">' +
          data.message +
          "</span>" +
          '<a href="' +
          payaman_wishlist_object.cart_url +
          '" class="payaman_wishlist-cart-notice__link">' +
          payaman_wishlist_object.i18n.view_cart +
          "</a>" +
          "</div>";
        $wrapper.find(".payaman_wishlist-cart-notice").html(noticeHtml).show();
        if (data.added && data.added.length) {
          var list = parseWishlistCookie();
          var changed = false;
          for (var i = 0; i < data.added.length; i++) {
            var pid = data.added[i];
            var pos = list.indexOf(pid);
            if (pos !== -1) {
              list.splice(pos, 1);
              changed = true;
            }
            $rows.filter('[data-product-id="' + pid + '"]').fadeOut(300, function () {
              $(this).remove();
            });
            $(
              '.payaman_wishlist-button[data-product-id="' +
                pid +
                '"], .payaman_wishlist[data-product-id="' +
                pid +
                '"]',
            ).each(function () {
              var $btn = $(this);
              $btn.removeClass("on").addClass("off");
              $btn.find("span").text(payaman_wishlist_object.off_val);
              if (payaman_wishlist_object.button_type === "image") {
                $btn.find("img").attr("src", payaman_wishlist_object.off_val);
              }
            });
          }
          if (changed) {
            persistWishlistCookie(list);
          }
        }
        setTimeout(function () {
          $wrapper.find(".payaman_wishlist-cart-notice").fadeOut(500);
        }, 5000);
      },
      error: function () {
        $btn.prop("disabled", false).text(payaman_wishlist_object.i18n.add_all_to_cart);
        showModalAlert(payaman_wishlist_object.error_message);
      },
    });
  });

  $(document).ready(function () {
    updateBulkControls($(".payaman_wishlist-table-wrapper"));
    renderCollectionOptions(defaultCollectionId);
  });

  // Sync variation_id saat WooCommerce memilih / mereset variasi
  $("body").on(
    "found_variation",
    "form.variations_form",
    function (event, variation) {
      var variationId =
        variation && variation.variation_id
          ? parseInt(variation.variation_id, 10)
          : 0;
      $(this)
        .find(".payaman_wishlist-button")
        .attr("data-variation-id", variationId || "");
      $(this)
        .find(".payaman_wishlist")
        .attr("data-variation-id", variationId || "");
    },
  );

  $("body").on("reset_data", "form.variations_form", function () {
    $(this).find(".payaman_wishlist-button").attr("data-variation-id", "");
    $(this).find(".payaman_wishlist").attr("data-variation-id", "");
  });

  $(document).on(
    "click",
    ".payaman_wishlist-collection-tabs a",
    function (event) {
      event.preventDefault();
      var url = $(this).attr("href");
      if (url) {
        window.location.href = url;
      }
    },
  );

  // Visibility Toggle
  $(document).on(
    "click",
    ".payaman_wishlist-collection-visibility",
    function (e) {
      e.preventDefault();
      e.stopPropagation();

      var $btn = $(this);
      var collectionId = $btn.data("collection-id");
      var isPublic = $btn.data("public") == "1";
      var newPublic = !isPublic;

      $btn.prop("disabled", true).css("opacity", "0.5");

      $.ajax({
        url: payaman_wishlist_object.ajax_url,
        type: "POST",
        data: {
          action: "payaman_wishlist_collection_update",
          nonce: payaman_wishlist_object.nonce_collection,
          collection_id: collectionId,
          is_public: newPublic ? "true" : "false",
        },
        success: function (response) {
          if (response.success) {
            window.location.reload();
          } else {
            showModalAlert(response.data.message || "Error updating collection.");
            $btn.prop("disabled", false).css("opacity", "1");
          }
        },
        error: function () {
          showModalAlert("Network error.");
          $btn.prop("disabled", false).css("opacity", "1");
        },
      });
    },
  );

  // Copy Share URL
  $(document).on("click", ".payaman_wishlist-copy-share-url", function (e) {
    e.preventDefault();
    var url = $(this).data("url");
    var $btn = $(this);

    var tempInput = $("<input>");
    $("body").append(tempInput);
    tempInput.val(url).select();
    document.execCommand("copy");
    tempInput.remove();

    showToast("Link copied to clipboard!");
  });

  // Wishlist Page Collection Management
  $(document).on("click", ".payaman_wishlist-collection-add-new", function (e) {
    e.preventDefault();
    var name = window.prompt(payaman_wishlist_object.i18n.fill_collection_name);
    if (!name) return;

    var $btn = $(this);
    $btn.prop("disabled", true).css("opacity", "0.5");

    $.ajax({
      url: payaman_wishlist_object.ajax_url,
      type: "POST",
      data: {
        action: "payaman_wishlist_collection_create",
        nonce: payaman_wishlist_object.nonce_collection,
        name: name,
        is_public: false,
      },
      success: function (response) {
        if (response.success) {
          window.location.reload();
        } else {
          showModalAlert(response.data.message || "Error creating collection.");
          $btn.prop("disabled", false).css("opacity", "1");
        }
      },
      error: function () {
        showModalAlert("Network error.");
        $btn.prop("disabled", false).css("opacity", "1");
      },
    });
  });

  $(document).on("click", ".payaman_wishlist-collection-rename", function (e) {
    e.preventDefault();
    var id = $(this).data("id");
    var currentName = $(this).data("name");
    var newName = window.prompt(payaman_wishlist_object.i18n.rename_collection, currentName);
    if (!newName || newName === currentName) return;

    var $btn = $(this);
    $btn.prop("disabled", true).css("opacity", "0.5");

    $.ajax({
      url: payaman_wishlist_object.ajax_url,
      type: "POST",
      data: {
        action: "payaman_wishlist_collection_update",
        nonce: payaman_wishlist_object.nonce_collection,
        collection_id: id,
        name: newName,
      },
      success: function (response) {
        if (response.success) {
          window.location.reload();
        } else {
          showModalAlert(response.data.message || "Error updating collection.");
          $btn.prop("disabled", false).css("opacity", "1");
        }
      },
      error: function () {
        showModalAlert("Network error.");
        $btn.prop("disabled", false).css("opacity", "1");
      },
    });
  });

  $(document).on("click", ".payaman_wishlist-collection-delete", function (e) {
    e.preventDefault();

    var $btn = $(this);
    var id = $btn.data("id");

    showModalConfirm(
      payaman_wishlist_object.i18n.confirm_delete_collection,
      function () {
        $btn.prop("disabled", true).css("opacity", "0.5");

        $.ajax({
          url: payaman_wishlist_object.ajax_url,
          type: "POST",
          data: {
            action: "payaman_wishlist_collection_delete",
            nonce: payaman_wishlist_object.nonce_collection,
            collection_id: id,
          },
          success: function (response) {
            if (response.success) {
              var url = new URL(window.location.href);
              url.searchParams.delete("collection");
              window.location.href = url.toString();
            } else {
              showModalAlert(response.data.message || "Error deleting collection.");
              $btn.prop("disabled", false).css("opacity", "1");
            }
          },
          error: function () {
            showModalAlert("Network error.");
            $btn.prop("disabled", false).css("opacity", "1");
          },
        });
      },
    );
  });

  // Handle WooCommerce Added to Cart
  $(document.body).on(
    "added_to_cart",
    function (event, fragments, cart_hash, $button) {
      var isOnWishlistPage = $button.closest(".payaman_wishlist-table-wrapper").length > 0;

      if (payaman_wishlist_object.remove_after_add_to_cart !== "yes" && !isOnWishlistPage) {
        return;
      }

      var productId = $button.data("product_id");
      if (!productId) {
        productId = $button.attr("value");
      }

      if (!productId) return;

      if (isOnWishlistPage) {
        var list = parseWishlistCookie();
        var pos = list.indexOf(productId);
        if (pos !== -1) {
          list.splice(pos, 1);
          persistWishlistCookie(list);
        }
        $.ajax({
          url: payaman_wishlist_object.ajax_url,
          type: "POST",
          data: {
            action: "payaman_wishlist_remove_from_table",
            product_id: productId,
            nonce: payaman_wishlist_object.nonce,
          },
        });
      }

      $(
        '.payaman_wishlist-button[data-product-id="' +
          productId +
          '"], .payaman_wishlist[data-product-id="' +
          productId +
          '"]',
      ).each(function () {
        var $btn = $(this);
        $btn.removeClass("on").addClass("off");
        $btn.find("span").text(payaman_wishlist_object.off_val);
        if (payaman_wishlist_object.button_type === "image") {
          $btn.find("img").attr("src", payaman_wishlist_object.off_val);
        }
      });

      var $wishlistRow = $(
        '.payaman_wishlist-table tr[data-product-id="' + productId + '"]',
      );
      if ($wishlistRow.length) {
        $wishlistRow.fadeOut(300, function () {
          $(this).remove();
          if ($(".payaman_wishlist-table tbody tr").length === 0) {
            var emptyMsg = $(".payaman_wishlist-table-wrapper").data(
              "empty-message",
            );
            $(".payaman_wishlist-table-wrapper").html(
              '<p class="payaman_wishlist-empty">' + emptyMsg + "</p>",
            );
          }
        });
      }
    },
  );
});
