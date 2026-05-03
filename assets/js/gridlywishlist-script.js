/* global Cookies, gridlywishlist_object */
jQuery(function ($) {
  function showAlert(message) {
    window.alert(message || gridlywishlist_object.i18n.generic_error);
  }
  var $modal = $("#gridlywishlist-modal");
  var collectionsData = gridlywishlist_object.collections || [];
  var defaultCollectionId = gridlywishlist_object.default_collection_id || "";
  var collectionLimit = parseInt(
    gridlywishlist_object.collection_limit || 0,
    10,
  );
  var canManageCollections = !!gridlywishlist_object.can_manage_collections;
  var pendingProductId = null;
  var pendingAction = null;
  var pendingCollectionId = defaultCollectionId;
  var pendingVariationId = 0;

  function parseWishlistCookie() {
    if (!Cookies.get("gridlywishlist_product")) {
      return [];
    }
    try {
      var parsed = JSON.parse(Cookies.get("gridlywishlist_product"));
      return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
      return [];
    }
  }

  function persistWishlistCookie(list) {
    try {
      Cookies.set("gridlywishlist_product", JSON.stringify(list), {
        expires: 30,
        path: "/",
      });
    } catch (error) {
      // ignore cookie write issues
    }
  }

  function formatLabel(baseText, count) {
    if (
      gridlywishlist_object.gridlywishlist_count === "yes" &&
      count !== "" &&
      typeof count !== "undefined"
    ) {
      return baseText + " (" + count + ")";
    }
    return baseText;
  }

  function ensureModal() {
    if (!$modal.length) {
      $modal = $("#gridlywishlist-modal");
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
  }

  function openMessageModal(message) {
    var modal = ensureModal();
    if (!modal.length) {
      showAlert(message);
      return;
    }
    modal
      .find(".gridlywishlist-modal__message")
      .text(message || "")
      .show();
    modal.removeClass("is-manage").addClass("is-message");
    showModalView("message");
  }

  function renderCollectionOptions(selectedId) {
    var modal = ensureModal();
    var $select = modal.find(".gridlywishlist-collection-select");
    if (!$select.length) {
      return;
    }
    var options = "";
    if (!collectionsData.length) {
      options +=
        '<option value="" disabled>' +
        (canManageCollections
          ? gridlywishlist_object.i18n.no_collections_yet
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
  }

  function openCollectionSelector(
    productId,
    collectionId,
    action,
    variationId,
  ) {
    if (!canManageCollections) {
      triggerWishlistAction(action, productId, collectionId, variationId);
      return;
    }
    pendingProductId = productId;
    pendingAction = action || "insert";
    pendingVariationId = variationId || 0;
    pendingCollectionId = collectionId || defaultCollectionId;
    renderCollectionOptions(pendingCollectionId);
    ensureModal().removeClass("is-message").addClass("is-manage");
    showModalView("manage");
  }

  function updateButtonCollection(productId, collectionId) {
    var selector = '.gridlywishlist[data-product-id="' + productId + '"]';
    var $wrapper = $(selector);
    if (!$wrapper.length) {
      return;
    }
    $wrapper.attr("data-collection-id", collectionId || "");
    $wrapper
      .find(".gridlywishlist-button")
      .attr("data-collection-id", collectionId || "");
  }

  function handleAjaxError(message, product_id) {
    $(".gridlywishlist .gridlywishlist-loading").removeClass("on");
    var selector = ".gridlywishlist .gridlywishlist-button";
    if (product_id) {
      selector =
        '.gridlywishlist .gridlywishlist-button[data-product-id="' +
        product_id +
        '"]';
    }
    $(selector).show();
    if (message) {
      showAlert(message);
    }
  }

  function sendWishlistRequest(
    action,
    product_id,
    collection_id,
    variation_id,
  ) {
    $(
      ".gridlywishlist .gridlywishlist-loading[data-product-id=" +
        product_id +
        "]",
    ).addClass("on");
    var dataPost = {
      action: "update_gridlywishlist",
      fav_action: action,
      product_id: product_id,
      nonce: gridlywishlist_object.nonce,
      collection_id: collection_id || "",
      variation_id: variation_id || 0,
    };
    $.ajax({
      url: gridlywishlist_object.ajax_url,
      type: "POST",
      data: dataPost,
      success: function (response) {
        $(".gridlywishlist .gridlywishlist-loading").removeClass("on");

        if (!response || !response.success) {
          var errorMessage =
            response && response.data && response.data.message
              ? response.data.message
              : gridlywishlist_object.error_message;
          handleAjaxError(errorMessage, product_id);
          return;
        }

        var data = response.data || {};
        var count = typeof data.count !== "undefined" ? data.count : "";
        var buttonSelector =
          '.gridlywishlist .gridlywishlist-button[data-product-id="' +
          product_id +
          '"]';
        var wrapperSelector =
          '.gridlywishlist[data-product-id="' + product_id + '"]';
        var $currentButton = $(buttonSelector);
        var $currentWrapper = $(wrapperSelector);
        var offText = formatLabel(gridlywishlist_object.off_val, count);
        var onText = formatLabel(gridlywishlist_object.on_val, count);
        var collectionState = data.collection_state || data.state;
        var collections = data.collections;

        if (collections) {
          updateCollectionsData(collections);
        }

        if (collectionState === "on") {
          if (gridlywishlist_object.enable_add_success_message === "yes") {
            openMessageModal(gridlywishlist_object.add_success_message);
          }
          $currentButton.removeClass("off").addClass("on");
          if (gridlywishlist_object.button_type === "text") {
            $currentButton.text(onText).show();
          } else {
            $currentButton.attr("src", gridlywishlist_object.on_val).show();
            $currentWrapper.find(".count").text(count);
          }
        } else {
          if (gridlywishlist_object.enable_remove_success_message === "yes") {
            openMessageModal(gridlywishlist_object.remove_success_message);
          }
          $currentButton.removeClass("on").addClass("off");
          if (gridlywishlist_object.button_type === "text") {
            $currentButton.text(offText).show();
          } else {
            $currentButton.attr("src", gridlywishlist_object.off_val).show();
            $currentWrapper.find(".count").text(count);
          }
          removeRowFromTable(product_id);
        }

        if (data.collection_id) {
          updateButtonCollection(product_id, data.collection_id);
        }
      },
      error: function () {
        handleAjaxError(gridlywishlist_object.error_message, product_id);
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
        : $(".gridlywishlist-table-wrapper");
    if (!$wrapper.length) {
      return;
    }
    var $checkboxes = $wrapper.find(".gridlywishlist-bulk-checkbox");
    var checkedCount = $checkboxes.filter(":checked").length;
    $wrapper
      .find(".gridlywishlist-bulk-remove")
      .prop("disabled", checkedCount === 0);
    var allChecked =
      $checkboxes.length > 0 && checkedCount === $checkboxes.length;
    $wrapper
      .find(".gridlywishlist-bulk-select-all")
      .prop("checked", allChecked);
  }

  function removeRowFromTable(productId) {
    var $row = $(
      '.gridlywishlist-table-wrapper tr[data-product-id="' + productId + '"]',
    );
    if (!$row.length) {
      return;
    }
    var $wrapper = $row.closest(".gridlywishlist-table-wrapper");
    $row.remove();
    if ($wrapper.find("tbody tr").length === 0) {
      var emptyMessage =
        $wrapper.data("empty-message") || "No wishlist products found.";
      $wrapper.replaceWith(
        '<p class="gridlywishlist-empty-message">' + emptyMessage + "</p>",
      );
    } else {
      updateBulkControls($wrapper);
    }
  }

  function bulkRemoveWishlist(productIds, $wrapper) {
    if (!productIds.length) {
      return;
    }

    var $button = $wrapper.find(".gridlywishlist-bulk-remove");
    $button.prop("disabled", true).addClass("is-loading");

    $.ajax({
      url: gridlywishlist_object.ajax_url,
      type: "POST",
      data: {
        action: "gridlywishlist_bulk_remove",
        product_ids: productIds,
        nonce: gridlywishlist_object.nonce,
      },
      success: function (response) {
        if (!response || !response.success) {
          var errorMessage =
            response && response.data && response.data.message
              ? response.data.message
              : gridlywishlist_object.error_message;
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

        if (gridlywishlist_object.enable_remove_success_message === "yes") {
          openMessageModal(gridlywishlist_object.remove_success_message);
        }
      },
      error: function () {
        openMessageModal(gridlywishlist_object.error_message);
      },
      complete: function () {
        $button.removeClass("is-loading");
        updateBulkControls($wrapper);
      },
    });
  }

  function handleCollectionCreate() {
    var modal = ensureModal();
    var name = modal.find(".gridlywishlist-collection-name").val();
    var isPublic = modal
      .find(".gridlywishlist-collection-public")
      .is(":checked");

    if (!name) {
      showAlert(gridlywishlist_object.i18n.fill_collection_name);
      modal.find(".gridlywishlist-collection-name").focus();
      return;
    }

    $.ajax({
      url: gridlywishlist_object.ajax_url,
      type: "POST",
      data: {
        action: "gridlywishlist_collection_create",
        nonce: gridlywishlist_object.nonce,
        name: name,
        is_public: isPublic,
      },
      success: function (response) {
        if (!response || !response.success) {
          var errorMessage =
            response && response.data && response.data.message
              ? response.data.message
              : gridlywishlist_object.error_message;
          openMessageModal(errorMessage);
          return;
        }
        updateCollectionsData(response.data.collections);
        pendingCollectionId = response.data.collection.id;
        renderCollectionOptions(pendingCollectionId);
        modal.find(".gridlywishlist-collection-name").val("");
        modal.find(".gridlywishlist-collection-public").prop("checked", false);
        modal
          .find(".gridlywishlist-collection-create")
          .attr("aria-hidden", "true");
      },
      error: function () {
        openMessageModal(gridlywishlist_object.error_message);
      },
    });
  }

  $("body").on("click", ".gridlywishlist-button", function () {
    var requireLogin = gridlywishlist_object.required_login === "yes";
    if (requireLogin && !gridlywishlist_object.is_login) {
      showAlert(gridlywishlist_object.required_login_message);
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
      .closest(".gridlywishlist")
      .attr("data-variation-id", variationId || "");

    if (!isActive) {
      if (canManageCollections) {
        openCollectionSelector(productId, collectionId, "insert", variationId);
        return;
      }
      triggerWishlistAction("insert", productId, collectionId, variationId);
    } else {
      triggerWishlistAction("delete", productId, collectionId, variationId);
    }
  });

  $(document).on("click", "[data-gridlywishlist-close]", function (event) {
    event.preventDefault();
    closeModal();
  });

  $(document).on("keyup", function (event) {
    if (event.key === "Escape") {
      closeModal();
    }
  });

  $(document).on("change", ".gridlywishlist-bulk-checkbox", function () {
    updateBulkControls($(this).closest(".gridlywishlist-table-wrapper"));
  });

  $(document).on("change", ".gridlywishlist-bulk-select-all", function () {
    var $wrapper = $(this).closest(".gridlywishlist-table-wrapper");
    var state = $(this).is(":checked");
    $wrapper.find(".gridlywishlist-bulk-checkbox").prop("checked", state);
    updateBulkControls($wrapper);
  });

  $(document).on("click", ".gridlywishlist-bulk-remove", function (event) {
    event.preventDefault();
    var $wrapper = $(this).closest(".gridlywishlist-table-wrapper");
    var $selected = $wrapper.find(".gridlywishlist-bulk-checkbox:checked");
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

  $(document).on("change", ".gridlywishlist-bulk-move-target", function () {
    var $wrapper = $(this).closest(".gridlywishlist-table-wrapper");
    var target = $(this).val();
    var hasSelection =
      $wrapper.find(".gridlywishlist-bulk-checkbox:checked").length > 0;
    $wrapper
      .find(".gridlywishlist-bulk-move-button")
      .prop("disabled", !target || !hasSelection);
  });

  $(document).on("click", ".gridlywishlist-bulk-move-button", function (event) {
    event.preventDefault();
    var $wrapper = $(this).closest(".gridlywishlist-table-wrapper");
    var target = $wrapper.find(".gridlywishlist-bulk-move-target").val();
    if (!target) {
      return;
    }
    var $selected = $wrapper.find(".gridlywishlist-bulk-checkbox:checked");
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
      url: gridlywishlist_object.ajax_url,
      type: "POST",
      data: {
        action: "gridlywishlist_collection_move_items",
        nonce: gridlywishlist_object.nonce,
        product_ids: productIds,
        target_collection_id: target,
        source_collection_id: $wrapper.data("collection-id") || "",
      },
      success: function (response) {
        if (!response || !response.success) {
          var errorMessage =
            response && response.data && response.data.message
              ? response.data.message
              : gridlywishlist_object.error_message;
          openMessageModal(errorMessage);
          return;
        }

        updateCollectionsData(response.data.collections);
        $selected
          .prop("checked", false)
          .closest("tr")
          .each(function () {
            var targetName = "";
            for (var i = 0; i < collectionsData.length; i += 1) {
              if (collectionsData[i].id === target) {
                targetName = collectionsData[i].name;
                break;
              }
            }
            $(this)
              .attr("data-collection-id", target)
              .find(".gridlywishlist-table__collection")
              .text(targetName);
          });
        updateBulkControls($wrapper);
        $wrapper
          .find(".gridlywishlist-bulk-move-button")
          .prop("disabled", true);
        if (gridlywishlist_object.enable_add_success_message === "yes") {
          openMessageModal(gridlywishlist_object.add_success_message);
        }
      },
      error: function () {
        openMessageModal(gridlywishlist_object.error_message);
      },
    });
  });

  $(document).on("click", ".gridlywishlist-collection-apply", function (event) {
    event.preventDefault();
    var modal = ensureModal();
    var selected = modal.find(".gridlywishlist-collection-select").val();
    if (!selected) {
      showAlert(gridlywishlist_object.i18n.select_collection_first);
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
    ".gridlywishlist-collection-create-toggle",
    function (event) {
      event.preventDefault();
      var modal = ensureModal();
      var $create = modal.find(".gridlywishlist-collection-create");
      var expanded = $create.attr("aria-hidden") === "false";
      $create.attr("aria-hidden", expanded ? "true" : "false");
    },
  );

  $(document).on(
    "click",
    ".gridlywishlist-collection-create-submit",
    function (event) {
      event.preventDefault();
      if (collectionLimit && collectionsData.length >= collectionLimit) {
        openMessageModal(gridlywishlist_object.i18n.collection_limit_reached);
        return;
      }
      handleCollectionCreate();
    },
  );

  $(document).ready(function () {
    updateBulkControls($(".gridlywishlist-table-wrapper"));
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
        .find(".gridlywishlist-button")
        .attr("data-variation-id", variationId || "");
      $(this)
        .find(".gridlywishlist")
        .attr("data-variation-id", variationId || "");
    },
  );

  $("body").on("reset_data", "form.variations_form", function () {
    $(this).find(".gridlywishlist-button").attr("data-variation-id", "");
    $(this).find(".gridlywishlist").attr("data-variation-id", "");
  });

  $(document).on(
    "click",
    ".gridlywishlist-collection-tabs a",
    function (event) {
      event.preventDefault();
      var url = $(this).attr("href");
      if (url) {
        window.location.href = url;
      }
    },
  );
});
