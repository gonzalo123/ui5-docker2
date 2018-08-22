sap.ui.define([
  "sap/ui/core/BusyIndicator"
], function (BusyIndicator) {
  "use strict";

  var doAjax, token, baseUrl;

  doAjax = function (method, uri, params) {
    params = params || {};
    BusyIndicator.show(1000);
    return new Promise(function (resolve) {
      jQuery.ajax({
        type: method,
        contentType: "application/json",
        data: method === 'GET' ? params : JSON.stringify(params),
        url: baseUrl + uri,
        cache: false,
        dataType: "json",
        async: true,
        beforeSend: function (xhr) {
          xhr.setRequestHeader('Authorization2', 'Bearer ' + token);
        }
      }).done(function (sResult) {
          resolve(sResult);
        }.bind(this)
      ).always(function () {
          BusyIndicator.hide();
        }
      );
    });
  };

  return {
    setBaseUrl: function (b) {
      baseUrl = b;
    },

    setToken: function (t) {
      token = t;
    },

    post: function (uri, params) {
      return doAjax('POST', uri, params);
    },

    get: function (uri, params) {
      return doAjax('GET', uri, params);
    }
  };
});
